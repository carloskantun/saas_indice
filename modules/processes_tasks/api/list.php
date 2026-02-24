<?php
require __DIR__.'/../../../bootstrap.php';
require __DIR__.'/../../../core/auth.php';
require __DIR__.'/../../../core/permissions.php';
require __DIR__.'/../../../core/scope.php';
require __DIR__.'/../includes/enum_normalizer.php';
header('Content-Type: application/json');

requireLogin();
$ucId = currentUserCompany();
$userId = (int)currentUserId();
if (!hasPermission($ucId, 'processes_tasks', 'view')) { 
    http_response_code(403); 
    echo json_encode(['ok'=>false,'error'=>'no_permission']); 
    exit; 
}

try {
    $pdo = db();

    $delegadoIdKindSelect = (defined('APP_DEBUG') && APP_DEBUG)
        ? ", CASE WHEN ud.id IS NULL THEN 'unknown' ELSE 'users' END AS delegado_id_kind"
        : '';
    
    // Determinar rol de compañía para enforcement (superadmin vs admin vs usuario)
    $stmtRole = $pdo->prepare("SELECT role FROM user_companies WHERE user_id = ? AND company_id = ? AND status = 'active' LIMIT 1");
    $stmtRole->execute([$userId, $ucId]);
    $companyRole = (string)($stmtRole->fetchColumn() ?: '');
    $isSuperadmin = in_array($companyRole, ['root', 'superadmin'], true);
    $isAdmin = ($companyRole === 'admin');

    // Construir query con filtros para tabla TASKS (unificada)
    $where = ['t.company_id = ?']; 
    $params = [$ucId];

    // Scope unit/business: superadmin lo ignora; cualquier otro rol (incl admin) lo aplica
    if (!$isSuperadmin) {
        $scope = getUserScope($userId, $ucId);
        if (($scope['type'] ?? '') === 'limited') {
            if (!empty($scope['units'])) {
                $placeholders = implode(',', array_fill(0, count($scope['units']), '?'));
                $where[] = "t.unit_id IN ($placeholders)";
                $params = array_merge($params, $scope['units']);
            }
            if (!empty($scope['businesses'])) {
                $placeholders = implode(',', array_fill(0, count($scope['businesses']), '?'));
                $where[] = "t.business_id IN ($placeholders)";
                $params = array_merge($params, $scope['businesses']);
            }
        }
    }

    // Organigrama: solo para no-admin/superadmin
    if (!$isSuperadmin && !$isAdmin) {
        $stmtAllowed = $pdo->prepare('SELECT target_user_id FROM processes_tasks_org_access WHERE company_id = ? AND owner_user_id = ?');
        $stmtAllowed->execute([$ucId, $userId]);
        $allowedUserIds = array_map('intval', $stmtAllowed->fetchAll(PDO::FETCH_COLUMN) ?: []);

        // Compatibilidad: algunas instancias usan hr_employees.id en usuario_delegado
        $allowedAssigneeIds = $allowedUserIds;
        if (!empty($allowedUserIds)) {
            $ph = implode(',', array_fill(0, count($allowedUserIds), '?'));
            $paramsEmp = array_merge([$ucId], $allowedUserIds);
            $stmtEmp = $pdo->prepare("SELECT id FROM hr_employees WHERE company_id = ? AND user_id IN ($ph)");
            $stmtEmp->execute($paramsEmp);
            $empIds = array_map('intval', $stmtEmp->fetchAll(PDO::FETCH_COLUMN) ?: []);
            $allowedAssigneeIds = array_values(array_unique(array_merge($allowedAssigneeIds, $empIds)));
        }

        if (empty($allowedAssigneeIds)) {
            // Anti-fricción: si no hay organigrama, al menos sus tareas creadas
            $where[] = 't.usuario_creador = ?';
            $params[] = $userId;
        } else {
            $ph = implode(',', array_fill(0, count($allowedAssigneeIds), '?'));
            $where[] = "(t.usuario_creador = ? OR t.usuario_delegado IN ($ph))";
            $params[] = $userId;
            $params = array_merge($params, $allowedAssigneeIds);
        }
    }
    
    // Filtro por status (normalizado)
    if (!empty($_GET['status'])) { 
        $normalizedStatus = normalizeStatus($_GET['status']);
        $where[] = 't.status = ?'; 
        $params[] = $normalizedStatus; 
    }
    
    // Filtro por usuario delegado (assignee en frontend = usuario_delegado en DB)
    if (!empty($_GET['assignee']) || !empty($_GET['usuario_delegado'])) { 
        $assigneeId = $_GET['assignee'] ?? $_GET['usuario_delegado'];
        $where[] = 't.usuario_delegado = ?'; 
        $params[] = (int)$assigneeId; 
    }
    
    // Filtro por tipo
    if (!empty($_GET['tipo']) || !empty($_GET['type'])) {
        $type = $_GET['tipo'] ?? $_GET['type'];
        $normalizedType = normalizeTaskType($type);
        $where[] = 't.tipo = ?';
        $params[] = $normalizedType;
    }
    
    // Filtro por unidad de negocio
    if (!empty($_GET['unit_id'])) { 
        $where[] = 't.unit_id = ?'; 
        $params[] = (int)$_GET['unit_id']; 
    }
    
    // Filtro por negocio
    if (!empty($_GET['business_id'])) { 
        $where[] = 't.business_id = ?'; 
        $params[] = (int)$_GET['business_id']; 
    }
    
    // Búsqueda por texto
    if (!empty($_GET['q']) || !empty($_GET['search'])) {
        $search = $_GET['q'] ?? $_GET['search'];
        $where[] = '(t.titulo LIKE ? OR t.descripcion LIKE ? OR t.folio LIKE ?)';
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $sql = '
        SELECT 
            t.*,
            b.name AS business_nombre,
            u.name AS unit_nombre,
            uc.email AS creador_email,
            ec.full_name AS creador_nombre,
            ud.email AS delegado_email,
            ed.full_name AS delegado_nombre
            ' . $delegadoIdKindSelect . '
        FROM tasks t
        LEFT JOIN businesses b ON b.id = t.business_id
        LEFT JOIN units u ON u.id = t.unit_id
        LEFT JOIN users uc ON uc.id = t.usuario_creador
        LEFT JOIN hr_employees ec ON ec.user_id = t.usuario_creador AND ec.company_id = t.company_id
        LEFT JOIN users ud ON ud.id = t.usuario_delegado
        LEFT JOIN hr_employees ed ON ed.user_id = t.usuario_delegado AND ed.company_id = t.company_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY 
            CASE t.status
                WHEN "Vencida" THEN 0
                WHEN "En tiempo" THEN 1
                WHEN "En proceso" THEN 2
                WHEN "Terminada" THEN 3
                WHEN "Auditada" THEN 4
                WHEN "Pausada" THEN 5
                ELSE 6
            END,
            t.fecha_entrega IS NULL,
            t.fecha_entrega ASC,
            t.created_at DESC
        LIMIT 1000
    ';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    // Normalizar campos para compatibilidad con frontend
    foreach ($items as &$item) {
        // Asegurar que tenga campos que espera el frontend
        $item['title'] = $item['titulo'] ?? $item['title'] ?? '';
        $item['description'] = $item['descripcion'] ?? $item['description'] ?? '';
        $item['start_date'] = $item['fecha_inicio'] ?? $item['start_date'] ?? '';
        $item['due_date'] = $item['fecha_entrega'] ?? $item['due_date'] ?? '';
        $item['has_attachments'] = false; // Por ahora
    }
    
    echo json_encode([
        'ok' => true,
        'items' => $items,
        'data' => $items, // Alias para compatibilidad
        'count' => count($items),
        'debug' => [
            'company_id' => $ucId,
            'filters' => $_GET,
            'query_executed' => true
        ]
    ]);
} catch (Throwable $e) {
    error_log('[processes_tasks:list] Error: ' . $e->getMessage());
    echo json_encode([
        'ok' => false,
        'items' => [],
        'error' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}


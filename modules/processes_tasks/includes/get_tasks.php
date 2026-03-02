<?php
/**
 * /modules/processes_tasks/includes/get_tasks.php
 * Endpoint para obtener tareas con paginación, búsqueda, filtros y ordenamiento
 * 
 * Parámetros GET:
 * - page: número de página (default 1)
 * - pageSize: registros por página (default 25)
 * - search: búsqueda en folio, titulo, descripcion
 * - tipo: filtro por tipo
 * - unidad: filtro por unidad
 * - status: filtro por status
 * - sortBy: campo para ordenar (default 'id')
 * - sortDir: dirección (asc/desc, default 'desc')
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../../core/auth.php';
require_once __DIR__ . '/../../../core/permissions.php';
require_once __DIR__ . '/../../../core/scope.php';

if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
}

requireLogin();
$ucId = (int)currentUserCompany();
$userId = (int)currentUserId();
if (!$ucId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No company context', 'data' => [], 'total' => 0]);
    exit;
}

if (!hasPermission($ucId, 'processes_tasks', 'view')) {
    $logLine = json_encode([
        'ts' => date('c'),
        'endpoint' => 'includes/get_tasks.php',
        'reason' => 'no_permission:view',
        'user_id' => $_SESSION['user_id'] ?? null,
        'company_id' => $ucId,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ], JSON_UNESCAPED_SLASHES);
    @file_put_contents(__DIR__ . '/../logs/legacy_access_denied.log', $logLine . "\n", FILE_APPEND | LOCK_EX);
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sin permisos', 'data' => [], 'total' => 0]);
    exit;
}

$pdo = db();

// Rol de compañía (superadmin ignora scope; admin NO lo ignora)
$stmtRole = $pdo->prepare("SELECT role FROM user_companies WHERE user_id = ? AND company_id = ? AND status = 'active' LIMIT 1");
$stmtRole->execute([$userId, $ucId]);
$companyRole = (string)($stmtRole->fetchColumn() ?: '');
$isSuperadmin = in_array($companyRole, ['root', 'superadmin'], true);
$isAdmin = ($companyRole === 'admin');

try {
    // Parámetros de paginación
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $pageSize = isset($_GET['pageSize']) ? min(100, max(1, (int)$_GET['pageSize'])) : 25;
    $offset = ($page - 1) * $pageSize;
    
    // Parámetros de búsqueda y filtros
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
    $unidad = isset($_GET['unidad']) ? trim($_GET['unidad']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    
    // Parámetros de ordenamiento
    $allowedSortFields = [
        'id', 'folio', 'titulo', 'unidad', 'negocio', 'fecha_inicio', 
        'fecha_fin', 'nivel', 'tipo', 'proyecto', 'ponderacion', 'status'
    ];
    $sortBy = isset($_GET['sortBy']) && in_array($_GET['sortBy'], $allowedSortFields) 
        ? $_GET['sortBy'] 
        : 'id';
    $sortDir = isset($_GET['sortDir']) && strtolower($_GET['sortDir']) === 'asc' 
        ? 'ASC' 
        : 'DESC';
    
    // Construir WHERE clause (SIEMPRE con company_id)
    $whereClauses = ["company_id = ?"];
    $params = [$ucId];

    // Scope unit/business: superadmin lo ignora; cualquier otro rol (incl admin) lo aplica
    if (!$isSuperadmin) {
        $scope = getUserScope($userId, $ucId);
        if (($scope['type'] ?? '') === 'limited') {
            if (!empty($scope['units'])) {
                $ph = implode(',', array_fill(0, count($scope['units']), '?'));
                $whereClauses[] = "unit_id IN ($ph)";
                $params = array_merge($params, $scope['units']);
            }
            if (!empty($scope['businesses'])) {
                $ph = implode(',', array_fill(0, count($scope['businesses']), '?'));
                $whereClauses[] = "business_id IN ($ph)";
                $params = array_merge($params, $scope['businesses']);
            }
        }
    }

    // Organigrama: solo para no-admin/superadmin
    if (!$isSuperadmin && !$isAdmin) {
        $stmtAllowed = $pdo->prepare('SELECT target_user_id FROM processes_tasks_org_access WHERE company_id = ? AND owner_user_id = ?');
        $stmtAllowed->execute([$ucId, $userId]);
        $allowedUserIds = array_map('intval', $stmtAllowed->fetchAll(PDO::FETCH_COLUMN) ?: []);

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
            $whereClauses[] = 'usuario_creador = ?';
            $params[] = $userId;
        } else {
            $ph = implode(',', array_fill(0, count($allowedAssigneeIds), '?'));
            $whereClauses[] = "(usuario_creador = ? OR usuario_delegado IN ($ph))";
            $params[] = $userId;
            $params = array_merge($params, $allowedAssigneeIds);
        }
    }
    
    if (!empty($search)) {
        $whereClauses[] = "(
            folio LIKE ? OR 
            titulo LIKE ? OR 
            descripcion LIKE ?
        )";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($tipo)) {
        $whereClauses[] = "tipo = ?";
        $params[] = $tipo;
    }
    
    if (!empty($unidad)) {
        $whereClauses[] = "unidad = ?";
        $params[] = $unidad;
    }
    
    if (!empty($status)) {
        $whereClauses[] = "status = ?";
        $params[] = $status;
    }
    
    $whereSQL = !empty($whereClauses) 
        ? "WHERE " . implode(" AND ", $whereClauses) 
        : "";
    
    // Contar total de registros
    $countSQL = "SELECT COUNT(*) as total FROM tasks $whereSQL";
    $countStmt = $pdo->prepare($countSQL);
    $countStmt->execute($params);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Obtener registros paginados
    $dataSQL = "
        SELECT 
            id,
            folio,
            unidad,
            negocio,
            titulo,
            descripcion,
            fecha_inicio,
            fecha_fin,
            archivos_count,
            creador,
            delegado,
            nivel,
            tipo,
            proyecto,
            ponderacion,
            status,
            created_at,
            updated_at
        FROM tasks 
        $whereSQL 
        ORDER BY $sortBy $sortDir 
        LIMIT ? OFFSET ?
    ";
    
    $dataStmt = $pdo->prepare($dataSQL);
    $dataStmt->execute([...$params, $pageSize, $offset]);
    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Respuesta
    echo json_encode([
        'success' => true,
        'data' => $rows,
        'total' => (int)$total,
        'page' => $page,
        'pageSize' => $pageSize,
        'totalPages' => (int)ceil($total / $pageSize)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage(),
        'data' => [],
        'total' => 0
    ]);
}

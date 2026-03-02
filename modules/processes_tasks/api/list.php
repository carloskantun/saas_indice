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

    $colExists = static function (PDO $pdo, string $table, string $column): bool {
        try {
            $st = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
            $st->execute([$column]);
            return (bool)$st->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return false;
        }
    };

    $hasEmpUserCompanyId = $colExists($pdo, 'hr_employees', 'user_company_id');
    $hasUsersName = $colExists($pdo, 'users', 'name');

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
    
    // Filtro por status (normalizado). Soporta string o array (status[]=...)
    if (isset($_GET['status']) && $_GET['status'] !== '' && $_GET['status'] !== null) {
        $rawStatuses = is_array($_GET['status']) ? $_GET['status'] : [$_GET['status']];
        $normalizedStatuses = [];
        foreach ($rawStatuses as $s) {
            $s = trim((string)$s);
            if ($s === '') continue;
            $normalizedStatuses[] = normalizeStatus($s);
        }
        $normalizedStatuses = array_values(array_unique(array_filter($normalizedStatuses, fn($v) => $v !== null && $v !== '')));

        if (count($normalizedStatuses) === 1) {
            $where[] = 't.status = ?';
            $params[] = $normalizedStatuses[0];
        } elseif (count($normalizedStatuses) > 1) {
            $ph = implode(',', array_fill(0, count($normalizedStatuses), '?'));
            $where[] = "t.status IN ($ph)";
            $params = array_merge($params, $normalizedStatuses);
        }
    }
    
    // Filtro por usuario delegado (assignee en frontend = usuario_delegado en DB). Soporta string o array (assignee[]=...)
    $rawAssignees = [];
    if (isset($_GET['assignee']) && $_GET['assignee'] !== '' && $_GET['assignee'] !== null) {
        $rawAssignees = array_merge($rawAssignees, is_array($_GET['assignee']) ? $_GET['assignee'] : [$_GET['assignee']]);
    }
    if (isset($_GET['usuario_delegado']) && $_GET['usuario_delegado'] !== '' && $_GET['usuario_delegado'] !== null) {
        $rawAssignees = array_merge($rawAssignees, is_array($_GET['usuario_delegado']) ? $_GET['usuario_delegado'] : [$_GET['usuario_delegado']]);
    }
    $assigneeIds = array_values(array_unique(array_filter(array_map('intval', $rawAssignees), fn($v) => $v > 0)));

    // Compatibilidad: usuario_delegado puede almacenar users.id o hr_employees.id.
    // Expandimos el set consultando hr_employees para incluir ambos (id y user_id) asociados.
    if (!empty($assigneeIds)) {
        try {
            $ph = implode(',', array_fill(0, count($assigneeIds), '?'));
            $stmtMap = $pdo->prepare("SELECT id, user_id FROM hr_employees WHERE company_id = ? AND (id IN ($ph) OR user_id IN ($ph))");
            $stmtMap->execute(array_merge([$ucId], $assigneeIds, $assigneeIds));
            $rows = $stmtMap->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $expanded = $assigneeIds;
            foreach ($rows as $r) {
                if (isset($r['id'])) $expanded[] = (int)$r['id'];
                if (isset($r['user_id'])) $expanded[] = (int)$r['user_id'];
            }
            $assigneeIds = array_values(array_unique(array_filter(array_map('intval', $expanded), fn($v) => $v > 0)));
        } catch (Throwable $e) {
            // ignorar: si no hay tabla o falla el mapeo, aplicamos solo los IDs recibidos
        }
    }
    if (count($assigneeIds) === 1) {
        $where[] = 't.usuario_delegado = ?';
        $params[] = $assigneeIds[0];
    } elseif (count($assigneeIds) > 1) {
        $ph = implode(',', array_fill(0, count($assigneeIds), '?'));
        $where[] = "t.usuario_delegado IN ($ph)";
        $params = array_merge($params, $assigneeIds);
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

    // Modo Agenda (por defecto): actividades del día + pendientes
    // Se activa solo si el cliente envía agenda_default=1.
    if (!empty($_GET['agenda_default'])) {
        $agendaDate = (string)($_GET['agenda_date'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $agendaDate)) {
            $agendaDate = date('Y-m-d');
        }

        // "Pendientes" = cualquier status distinto de Terminada/Auditada (incluye NULL).
        // "Del día" = fecha_inicio o fecha_entrega cae en la fecha enviada.
        $where[] = '(
            (DATE(t.fecha_inicio) = ? OR DATE(t.fecha_entrega) = ?)
            OR (t.status IS NULL OR t.status NOT IN ("Terminada", "Auditada"))
        )';
        $params[] = $agendaDate;
        $params[] = $agendaDate;
    }

    // Modo Agenda (por periodo): rango de fechas y/o pendientes vencidas
    // Solo aplica si NO está activo agenda_default.
    if (empty($_GET['agenda_default'])) {
        $dateFrom = (string)($_GET['date_from'] ?? '');
        $dateTo = (string)($_GET['date_to'] ?? '');

        $validFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) ? $dateFrom : '';
        $validTo = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) ? $dateTo : '';

        if ($validFrom && !$validTo) {
            $validTo = $validFrom;
        } elseif ($validTo && !$validFrom) {
            $validFrom = $validTo;
        }

        // Normalizar orden del rango
        if ($validFrom && $validTo && strcmp($validFrom, $validTo) > 0) {
            [$validFrom, $validTo] = [$validTo, $validFrom];
        }

        if ($validFrom && $validTo) {
            // Actividades del periodo: fecha_inicio o fecha_entrega cae dentro del rango
            $where[] = '(
                (t.fecha_inicio IS NOT NULL AND DATE(t.fecha_inicio) BETWEEN ? AND ?)
                OR (t.fecha_entrega IS NOT NULL AND DATE(t.fecha_entrega) BETWEEN ? AND ?)
            )';
            $params[] = $validFrom;
            $params[] = $validTo;
            $params[] = $validFrom;
            $params[] = $validTo;
        }

        if (!empty($_GET['agenda_pending_overdue'])) {
            $today = (string)($_GET['agenda_today'] ?? '');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $today)) {
                $today = date('Y-m-d');
            }

            // "Pendientes" (según UX): no concluidas y vencidas
            $where[] = '(
                (t.status IS NULL OR t.status NOT IN ("Terminada", "Auditada"))
                AND (
                    t.status = "Vencida"
                    OR (t.fecha_entrega IS NOT NULL AND DATE(t.fecha_entrega) < ?)
                )
            )';
            $params[] = $today;
        }
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
    
    $whereSql = implode(' AND ', $where);
    $joinEmpUc = '';
    $creatorEmpUcExpr = '';
    $delegateEmpUcExpr = '';
    if ($hasEmpUserCompanyId) {
        $joinEmpUc = "
        LEFT JOIN user_companies ucc ON ucc.user_id = uc.id AND ucc.company_id = t.company_id
        LEFT JOIN hr_employees ec_uc ON ec_uc.company_id = t.company_id AND ec_uc.user_company_id = ucc.id
        LEFT JOIN user_companies ucd ON ucd.user_id = ud.id AND ucd.company_id = t.company_id
        LEFT JOIN hr_employees ed_uc ON ed_uc.company_id = t.company_id AND ed_uc.user_company_id = ucd.id
        ";
        $creatorEmpUcExpr = "NULLIF(ec_uc.full_name, ''),";
        $delegateEmpUcExpr = "NULLIF(ed_uc.full_name, ''),";
    }

    $creatorUsersNameExpr = $hasUsersName ? "NULLIF(uc.name, '')," : '';
    $delegateUsersNameExpr = $hasUsersName ? "NULLIF(ud.name, '')," : '';

    $sql = "
        SELECT 
            t.*,
            b.name AS business_nombre,
            u.name AS unit_nombre,
            uc.email AS creador_email,
            COALESCE(
                NULLIF(ec.full_name, ''),
                {$creatorEmpUcExpr}
                NULLIF(ec_email.full_name, ''),
                NULLIF(uc.full_name, ''),
                {$creatorUsersNameExpr}
                CONCAT('Usuario #', uc.id)
            ) AS creador_nombre,
            COALESCE(ud.email, ed_legacy.email) AS delegado_email,
            CASE
                WHEN ud.id IS NOT NULL THEN COALESCE(
                    NULLIF(ed.full_name, ''),
                    {$delegateEmpUcExpr}
                    NULLIF(ed_email.full_name, ''),
                    NULLIF(ud.full_name, ''),
                    {$delegateUsersNameExpr}
                    CONCAT('Usuario #', ud.id)
                )
                WHEN ed_legacy.id IS NOT NULL THEN COALESCE(
                    NULLIF(ed_legacy.full_name, ''),
                    CONCAT('Empleado #', ed_legacy.id)
                )
                ELSE NULL
            END AS delegado_nombre
            {$delegadoIdKindSelect}
        FROM tasks t
        LEFT JOIN businesses b ON b.id = t.business_id
        LEFT JOIN units u ON u.id = t.unit_id
        LEFT JOIN users uc ON uc.id = t.usuario_creador
        LEFT JOIN hr_employees ec ON ec.user_id = t.usuario_creador AND ec.company_id = t.company_id
        LEFT JOIN hr_employees ec_email ON LOWER(TRIM(ec_email.email)) = LOWER(TRIM(uc.email)) AND ec_email.company_id = t.company_id
        LEFT JOIN users ud ON ud.id = t.usuario_delegado
        LEFT JOIN hr_employees ed ON ed.user_id = t.usuario_delegado AND ed.company_id = t.company_id
        LEFT JOIN hr_employees ed_email ON LOWER(TRIM(ed_email.email)) = LOWER(TRIM(ud.email)) AND ed_email.company_id = t.company_id
        LEFT JOIN hr_employees ed_legacy ON ed_legacy.id = t.usuario_delegado AND ed_legacy.company_id = t.company_id
        {$joinEmpUc}
        WHERE {$whereSql}
        ORDER BY 
            CASE t.status
                WHEN 'Vencida' THEN 0
                WHEN 'En tiempo' THEN 1
                WHEN 'En proceso' THEN 2
                WHEN 'Terminada' THEN 3
                WHEN 'Auditada' THEN 4
                WHEN 'Pausada' THEN 5
                ELSE 6
            END,
            t.fecha_entrega IS NULL,
            t.fecha_entrega ASC,
            t.created_at DESC
        LIMIT 1000
    ";
    
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


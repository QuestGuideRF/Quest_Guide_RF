<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
function logAudit($entity_type, $entity_id, $action, $old_data = null, $new_data = null, $changes = null) {
    if (!isAdminLoggedIn()) {
        return false;
    }
    $admin = getCurrentAdmin();
    if (!$admin) {
        return false;
    }
    $pdo = getDB()->getConnection();
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'audit_log'");
        if ($stmt->rowCount() == 0) {
            return false;
        }
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $stmt = $pdo->prepare("
            INSERT INTO audit_log (user_id, entity_type, entity_id, action, old_data, new_data, changes, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $admin['id'],
            $entity_type,
            $entity_id,
            $action,
            $old_data ? json_encode($old_data, JSON_UNESCAPED_UNICODE) : null,
            $new_data ? json_encode($new_data, JSON_UNESCAPED_UNICODE) : null,
            $changes,
            $ip_address,
            $user_agent
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
        return false;
    }
}
function getAuditLog($entity_type = null, $entity_id = null, $limit = 100) {
    $pdo = getDB()->getConnection();
    try {
        $where = [];
        $params = [];
        if ($entity_type) {
            $where[] = "entity_type = ?";
            $params[] = $entity_type;
        }
        if ($entity_id) {
            $where[] = "entity_id = ?";
            $params[] = $entity_id;
        }
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = $pdo->prepare("
            SELECT al.*, u.first_name, u.username
            FROM audit_log al
            LEFT JOIN users u ON al.user_id = u.id
            $whereClause
            ORDER BY al.created_at DESC
            LIMIT ?
        ");
        $params[] = $limit;
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Get audit log error: " . $e->getMessage());
        return [];
    }
}
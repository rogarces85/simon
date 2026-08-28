<?php
require_once __DIR__ . '/../includes/db.php';

class Notification
{
    // Crear notificación
    public static function create($userId, $message, $type = 'info')
    {
        $db = Database::getInstance();
        $sql = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$userId, $message, $type]);
    }

    // Obtener notificaciones no leídas de un usuario
    public static function getUnread($userId)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Marcar como leída. $userId acota el UPDATE al dueño de la notificación:
    // sin ese filtro cualquier usuario autenticado podía marcar las ajenas.
    public static function markAsRead($id, $userId)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    // Marcar todas como leídas
    public static function markAllAsRead($userId)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }

    // Obtener todas las notificaciones (leídas y no leídas) de un usuario, limitadas
    public static function getAll($userId, $limit = 50)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY is_read ASC, created_at DESC LIMIT ?");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

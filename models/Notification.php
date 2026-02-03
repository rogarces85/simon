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

    // Marcar como leída
    public static function markAsRead($id)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Marcar todas como leídas
    public static function markAllAsRead($userId)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
}

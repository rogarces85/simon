<?php
require_once __DIR__ . '/db.php';

class Auth
{
    public static function init()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login($username, $password)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            return true;
        }
        return false;
    }

    public static function logout()
    {
        self::init();
        session_destroy();
    }

    public static function check()
    {
        self::init();
        return isset($_SESSION['user_id']);
    }

    public static function user()
    {
        self::init();
        if (self::check()) {
            return [
                'id' => $_SESSION['user_id'],
                'role' => $_SESSION['role'],
                'name' => $_SESSION['name']
            ];
        }
        return null;
    }

    public static function requireRole($role)
    {
        if (!self::check() || $_SESSION['role'] !== $role) {
            header('Location: login.php');
            exit;
        }
    }

    public static function requireRoleLike($roles)
    {
        if (!self::check() || !in_array($_SESSION['role'], $roles)) {
            header('Location: login.php');
            exit;
        }
    }
}

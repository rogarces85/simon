<?php
require_once __DIR__ . '/db.php';

class Auth
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_LOCKOUT_MINUTES = 15;

    public static function init()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function isLoginLocked($username)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM login_attempts WHERE username = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)"
        );
        $stmt->execute([$username, self::LOGIN_LOCKOUT_MINUTES]);
        return $stmt->fetchColumn() >= self::MAX_LOGIN_ATTEMPTS;
    }

    public static function login($username, $password)
    {
        if (self::isLoginLocked($username)) {
            return false;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            self::clearFailedLoginAttempts($username);
            return true;
        }

        self::recordFailedLoginAttempt($username);
        return false;
    }

    private static function recordFailedLoginAttempt($username)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("INSERT INTO login_attempts (username, ip_address) VALUES (?, ?)");
        $stmt->execute([$username, $_SERVER['REMOTE_ADDR'] ?? null]);
    }

    private static function clearFailedLoginAttempts($username)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM login_attempts WHERE username = ?");
        $stmt->execute([$username]);
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

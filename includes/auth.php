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
            self::fillSession($user);
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

    /**
     * Vuelca en la sesion los datos del usuario que el resto del sistema
     * necesita. coach_id y team_id son imprescindibles: sin ellos el atleta no
     * ve el branding de su team y el coach no recibe aviso cuando se completa
     * un entrenamiento.
     */
    private static function fillSession(array $user)
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['coach_id'] = $user['coach_id'] ?? null;
        $_SESSION['team_id'] = $user['team_id'] ?? null;
        $_SESSION['avatar_url'] = $user['avatar_url'] ?? null;
    }

    public static function user()
    {
        self::init();
        if (!self::check()) {
            return null;
        }

        // Sesiones abiertas antes de que se guardaran estos campos: se
        // rehidratan una sola vez desde la base de datos.
        if (!array_key_exists('coach_id', $_SESSION)) {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT id, role, name, coach_id, team_id, avatar_url FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $fresh = $stmt->fetch();
            if ($fresh) {
                self::fillSession($fresh);
            } else {
                $_SESSION['coach_id'] = null;
                $_SESSION['team_id'] = null;
                $_SESSION['avatar_url'] = $_SESSION['avatar_url'] ?? null;
            }
        }

        return [
            'id' => $_SESSION['user_id'],
            'role' => $_SESSION['role'],
            'name' => $_SESSION['name'],
            'coach_id' => $_SESSION['coach_id'],
            'team_id' => $_SESSION['team_id'],
            'avatar_url' => $_SESSION['avatar_url'] ?? null,
        ];
    }

    /**
     * Refresca en sesion los datos que el usuario acaba de cambiar (nombre,
     * avatar). Se llama desde perfil.php tras guardar.
     */
    public static function refreshSession()
    {
        self::init();
        if (!self::check()) {
            return;
        }
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, role, name, coach_id, team_id, avatar_url FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $fresh = $stmt->fetch();
        if ($fresh) {
            self::fillSession($fresh);
        }
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

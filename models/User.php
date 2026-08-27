<?php
require_once __DIR__ . '/../includes/db.php';

class User
{
    public static function create($data)
    {
        $db = Database::getInstance();
        $sql = "INSERT INTO users (username, password, role, name, coach_id, team_id, goal_date, goal_pace, level, available_days, preferred_long_run_day, max_time_per_session, observations) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['username'],
            $data['password'],
            $data['role'] ?? 'athlete',
            $data['name'],
            $data['coach_id'] ?? null,
            $data['team_id'] ?? null,
            $data['goal_date'] ?? null,
            $data['goal_pace'] ?? null,
            $data['level'] ?? null,
            self::encodeAvailableDays($data['available_days'] ?? null),
            $data['preferred_long_run_day'] ?? null,
            $data['max_time_per_session'] ?? null,
            $data['observations'] ?? null
        ]);

        return $db->lastInsertId();
    }

    public static function getById($id)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if ($user && $user['available_days']) {
            $user['available_days'] = json_decode($user['available_days'], true);
        }
        return $user;
    }

    public static function getAthletesByCoach($coachId)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, username, name, level, goal_date FROM users WHERE coach_id = ? AND role = 'athlete'");
        $stmt->execute([$coachId]);
        return $stmt->fetchAll();
    }

    /**
     * Serializa available_days una sola vez. Antes atletas.php hacia json_encode
     * y este modelo lo repetia, guardando la cadena doblemente codificada.
     */
    private static function encodeAvailableDays($value)
    {
        if ($value === null) {
            return null;
        }
        return is_array($value) ? json_encode(array_values($value)) : $value;
    }

    /** Verdadero solo si $athleteId es un atleta de $coachId. */
    public static function belongsToCoach($athleteId, $coachId)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE id = ? AND coach_id = ? AND role = 'athlete'");
        $stmt->execute([$athleteId, $coachId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * $coachId acota la operacion a los atletas de ese coach. Las llamadas que
     * no lo pasan (perfil propio, admin) mantienen el comportamiento anterior.
     */
    public static function update($id, $data, $coachId = null)
    {
        if ($coachId !== null && !self::belongsToCoach($id, $coachId)) {
            return false;
        }

        $db = Database::getInstance();
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = ($key === 'available_days') ? self::encodeAvailableDays($value) : $value;
        }

        if (empty($fields))
            return false;

        $values[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute($values);
    }

    public static function delete($id, $coachId = null)
    {
        if ($coachId !== null && !self::belongsToCoach($id, $coachId)) {
            return false;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function getByCoachId($coachId)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE coach_id = ? AND role = 'athlete' ORDER BY name");
        $stmt->execute([$coachId]);
        return $stmt->fetchAll();
    }

    public static function getByRole($role)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE role = ? ORDER BY name");
        $stmt->execute([$role]);
        return $stmt->fetchAll();
    }
}

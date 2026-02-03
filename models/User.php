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
            isset($data['available_days']) ? json_encode($data['available_days']) : null,
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

    public static function update($id, $data)
    {
        $db = Database::getInstance();
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            if ($key === 'available_days') {
                $fields[] = "$key = ?";
                $values[] = json_encode($value);
            } else {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }

        if (empty($fields))
            return false;

        $values[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute($values);
    }

    public static function delete($id)
    {
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

<?php
require_once __DIR__ . '/../includes/db.php';

class Workout
{
    public static function create($data)
    {
        $db = Database::getInstance();
        $sql = "INSERT INTO workouts (athlete_id, date, type, description, status, planned_distance, planned_time, structure, delivery_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['athlete_id'],
            $data['date'],
            $data['type'],
            $data['description'] ?? null,
            $data['status'] ?? 'pending',
            $data['planned_distance'] ?? null,
            $data['planned_time'] ?? null,
            isset($data['structure']) ? json_encode($data['structure']) : null,
            $data['delivery_status'] ?? 'pending'
        ]);

        return $db->lastInsertId();
    }

    public static function getByAthlete($athleteId, $from = null, $to = null)
    {
        $db = Database::getInstance();
        $sql = "SELECT * FROM workouts WHERE athlete_id = ?";
        $params = [$athleteId];

        if ($from) {
            $sql .= " AND date >= ?";
            $params[] = $from;
        }
        if ($to) {
            $sql .= " AND date <= ?";
            $params[] = $to;
        }

        $sql .= " ORDER BY date ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $workouts = $stmt->fetchAll();

        foreach ($workouts as &$w) {
            if ($w['structure'])
                $w['structure'] = json_decode($w['structure'], true);
        }

        return $workouts;
    }

    public static function update($id, $data)
    {
        $db = Database::getInstance();
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            if ($key === 'structure') {
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
        $sql = "UPDATE workouts SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute($values);
    }

    public static function getById($id)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM workouts WHERE id = ?");
        $stmt->execute([$id]);
        $workout = $stmt->fetch();

        if ($workout && $workout['structure']) {
            $workout['structure'] = json_decode($workout['structure'], true);
        }

        return $workout;
    }

    public static function getByCoach($coachId, $from = null, $to = null)
    {
        $db = Database::getInstance();
        $sql = "SELECT w.*, u.name as athlete_name, u.username as athlete_email 
                FROM workouts w 
                INNER JOIN users u ON w.athlete_id = u.id 
                WHERE u.coach_id = ?";
        $params = [$coachId];

        if ($from) {
            $sql .= " AND w.date >= ?";
            $params[] = $from;
        }
        if ($to) {
            $sql .= " AND w.date <= ?";
            $params[] = $to;
        }

        $sql .= " ORDER BY w.date DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $workouts = $stmt->fetchAll();

        foreach ($workouts as &$w) {
            if ($w['structure'])
                $w['structure'] = json_decode($w['structure'], true);
        }

        return $workouts;
    }

    public static function markAsReceived($athleteId)
    {
        $db = Database::getInstance();
        $sql = "UPDATE workouts SET delivery_status = 'received', viewed_at = NOW() 
                WHERE athlete_id = ? AND delivery_status = 'sent' AND viewed_at IS NULL";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$athleteId]);
    }

    public static function addCoachFeedback($workoutId, $feedback)
    {
        $db = Database::getInstance();
        $sql = "UPDATE workouts SET coach_feedback = ?, coach_feedback_at = NOW() WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$feedback, $workoutId]);
    }

    public static function getCompletedByCoach($coachId)
    {
        $db = Database::getInstance();
        $sql = "SELECT w.*, u.name as athlete_name, u.username as athlete_email 
                FROM workouts w 
                INNER JOIN users u ON w.athlete_id = u.id 
                WHERE u.coach_id = ? AND w.status = 'completed'
                ORDER BY w.completed_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$coachId]);
        $workouts = $stmt->fetchAll();

        foreach ($workouts as &$w) {
            if ($w['structure'])
                $w['structure'] = json_decode($w['structure'], true);
        }

        return $workouts;
    }

    public static function getPlanStatsByCoach($coachId)
    {
        $db = Database::getInstance();
        $sql = "SELECT 
                    w.delivery_status,
                    COUNT(*) as count
                FROM workouts w 
                INNER JOIN users u ON w.athlete_id = u.id 
                WHERE u.coach_id = ? AND w.date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY w.delivery_status";
        $stmt = $db->prepare($sql);
        $stmt->execute([$coachId]);
        return $stmt->fetchAll();
    }
}

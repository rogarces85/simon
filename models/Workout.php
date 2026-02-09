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

    public static function getCompletedByCoach($coachId, $athleteId = null)
    {
        $db = Database::getInstance();
        $sql = "SELECT w.*, u.name as athlete_name, u.username as athlete_email 
                FROM workouts w 
                INNER JOIN users u ON w.athlete_id = u.id 
                WHERE u.coach_id = ? AND w.status = 'completed'";
        $params = [$coachId];

        if ($athleteId) {
            $sql .= " AND w.athlete_id = ?";
            $params[] = $athleteId;
        }

        $sql .= " ORDER BY w.completed_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
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

    // Get detailed metrics for an athlete
    public static function getAthleteMetrics($athleteId)
    {
        $db = Database::getInstance();

        // Total stats
        $sql = "SELECT 
                    COUNT(*) as total_workouts,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_workouts,
                    AVG(actual_distance) as avg_distance,
                    AVG(actual_time) as avg_time,
                    AVG(rpe) as avg_rpe,
                    SUM(actual_distance) as total_distance,
                    SUM(actual_time) as total_time
                FROM workouts 
                WHERE athlete_id = ? AND status = 'completed'";
        $stmt = $db->prepare($sql);
        $stmt->execute([$athleteId]);
        $metrics = $stmt->fetch();

        // Calculate average pace (min/km)
        if ($metrics['total_distance'] > 0 && $metrics['total_time'] > 0) {
            $metrics['avg_pace'] = round($metrics['total_time'] / $metrics['total_distance'], 2);
        } else {
            $metrics['avg_pace'] = null;
        }

        return $metrics;
    }

    // Get weekly progression data for charts
    public static function getProgressionData($athleteId, $weeks = 8)
    {
        $db = Database::getInstance();
        $sql = "SELECT 
                    YEARWEEK(date, 1) as year_week,
                    MIN(date) as week_start,
                    SUM(actual_distance) as total_distance,
                    SUM(actual_time) as total_time,
                    AVG(rpe) as avg_rpe,
                    COUNT(*) as workout_count
                FROM workouts 
                WHERE athlete_id = ? 
                    AND status = 'completed' 
                    AND date >= DATE_SUB(CURDATE(), INTERVAL ? WEEK)
                GROUP BY YEARWEEK(date, 1)
                ORDER BY year_week ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$athleteId, $weeks]);
        $data = $stmt->fetchAll();

        // Calculate pace per week
        foreach ($data as &$week) {
            if ($week['total_distance'] > 0 && $week['total_time'] > 0) {
                $week['avg_pace'] = round($week['total_time'] / $week['total_distance'], 2);
            } else {
                $week['avg_pace'] = null;
            }
        }

        return $data;
    }

    // Get metrics for all coach's athletes (for comparison)
    public static function getCoachAthletesMetrics($coachId)
    {
        $db = Database::getInstance();
        $sql = "SELECT 
                    u.id as athlete_id,
                    u.name as athlete_name,
                    COUNT(w.id) as total_workouts,
                    SUM(CASE WHEN w.status = 'completed' THEN 1 ELSE 0 END) as completed_workouts,
                    AVG(w.actual_distance) as avg_distance,
                    AVG(w.actual_time) as avg_time,
                    AVG(w.rpe) as avg_rpe,
                    SUM(w.actual_distance) as total_distance,
                    SUM(w.actual_time) as total_time
                FROM users u
                LEFT JOIN workouts w ON u.id = w.athlete_id
                WHERE u.coach_id = ? AND u.role = 'athlete'
                GROUP BY u.id, u.name
                ORDER BY total_distance DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$coachId]);
        $athletes = $stmt->fetchAll();

        foreach ($athletes as &$athlete) {
            if ($athlete['total_distance'] > 0 && $athlete['total_time'] > 0) {
                $athlete['avg_pace'] = round($athlete['total_time'] / $athlete['total_distance'], 2);
            } else {
                $athlete['avg_pace'] = null;
            }
            $athlete['compliance_rate'] = $athlete['total_workouts'] > 0
                ? round(($athlete['completed_workouts'] / $athlete['total_workouts']) * 100)
                : 0;
        }

        return $athletes;
    }

    // Get all workouts by coach (for viewing all generated plans)
    public static function getAllByCoach($coachId, $athleteId = null, $status = null)
    {
        $db = Database::getInstance();
        $sql = "SELECT w.*, u.name as athlete_name, u.username as athlete_email 
                FROM workouts w 
                INNER JOIN users u ON w.athlete_id = u.id 
                WHERE u.coach_id = ?";
        $params = [$coachId];

        if ($athleteId) {
            $sql .= " AND w.athlete_id = ?";
            $params[] = $athleteId;
        }

        if ($status) {
            $sql .= " AND w.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY w.date DESC, u.name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $workouts = $stmt->fetchAll();

        foreach ($workouts as &$w) {
            if ($w['structure'])
                $w['structure'] = json_decode($w['structure'], true);
        }

        return $workouts;
    }

    // Get stats summary for plans
    public static function getPlansSummaryByCoach($coachId)
    {
        $db = Database::getInstance();
        $sql = "SELECT 
                    COUNT(*) as total_plans,
                    SUM(CASE WHEN w.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN w.status = 'completed' AND w.feedback IS NOT NULL THEN 1 ELSE 0 END) as with_feedback_count,
                    SUM(CASE WHEN w.status = 'completed' AND w.feedback IS NULL THEN 1 ELSE 0 END) as completed_no_feedback,
                    SUM(CASE WHEN w.status = 'completed' AND w.coach_feedback IS NOT NULL THEN 1 ELSE 0 END) as responded_count
                FROM workouts w 
                INNER JOIN users u ON w.athlete_id = u.id 
                WHERE u.coach_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$coachId]);
        return $stmt->fetch();
    }
}

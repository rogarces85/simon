<?php
require_once __DIR__ . '/../includes/db.php';

class Workout
{
    public static function create($data)
    {
        $db = Database::getInstance();
        $sql = "INSERT INTO workouts (athlete_id, date, type, description, status, planned_distance, planned_time, structure) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['athlete_id'],
            $data['date'],
            $data['type'],
            $data['description'] ?? null,
            $data['status'] ?? 'pending',
            $data['planned_distance'] ?? null,
            $data['planned_time'] ?? null,
            isset($data['structure']) ? json_encode($data['structure']) : null
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
}

<?php
require_once __DIR__ . '/../includes/db.php';

class Team
{
    // Crear un nuevo Team
    public static function create($data)
    {
        $db = Database::getInstance();
        $sql = "INSERT INTO teams (coach_id, name, logo_url, primary_color) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['coach_id'],
            $data['name'],
            $data['logo_url'] ?? null,
            $data['primary_color'] ?? '#3b82f6'
        ]);
        return $db->lastInsertId();
    }

    // Obtener Team por ID
    public static function find($id)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM teams WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Obtener Team por Coach ID
    public static function findByCoach($coachId)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM teams WHERE coach_id = ?");
        $stmt->execute([$coachId]);
        return $stmt->fetch();
    }

    // Actualizar Team
    public static function update($id, $data)
    {
        $db = Database::getInstance();
        $sql = "UPDATE teams SET name = ?, logo_url = ?, primary_color = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['logo_url'],
            $data['primary_color'],
            $id
        ]);
    }
}

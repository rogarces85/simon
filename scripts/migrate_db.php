<?php
// Script to initialize MySQL tables for TrainPro on Hostinger
require_once __DIR__ . '/../includes/db.php';

$db = Database::getInstance();

$queries = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) NOT NULL DEFAULT 'athlete',
        name VARCHAR(255) NOT NULL,
        coach_id INT NULL,
        goal_date DATETIME NULL,
        goal_pace VARCHAR(50) NULL,
        level VARCHAR(50) NULL,
        available_days JSON NULL,
        preferred_long_run_day VARCHAR(50) NULL,
        max_time_per_session INT NULL,
        observations TEXT NULL,
        INDEX (coach_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        coach_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        type VARCHAR(100) NOT NULL,
        block_type VARCHAR(100) NULL,
        structure JSON NOT NULL,
        INDEX (coach_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS workouts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        athlete_id INT NOT NULL,
        date DATETIME NOT NULL,
        type VARCHAR(100) NOT NULL,
        description TEXT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        planned_distance INT NULL,
        planned_time INT NULL,
        actual_distance INT NULL,
        actual_time INT NULL,
        rpe INT NULL,
        feedback TEXT NULL,
        coach_feedback TEXT NULL,
        completed_at DATETIME NULL,
        structure JSON NULL,
        INDEX (athlete_id),
        INDEX (date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($queries as $sql) {
    try {
        $db->exec($sql);
        echo "Successfully executed query.\n";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

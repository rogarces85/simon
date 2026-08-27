<?php
/**
 * Esquema base de RUNCOACH v2.0.
 *
 * Recuperado de scripts/setup.php (commit 291234f), que fue borrado del working
 * tree. A partir de aqui el esquema vive versionado en db/migrations/ y se
 * aplica con `php db/migrate.php`.
 *
 * Las columnas heredadas users.email, workouts.viewed_at, workouts.evidence_url
 * y workouts.planned_* se conservan tal cual para no romper instalaciones
 * existentes; algunas empiezan a usarse a partir de la migracion 001.
 */

return [
    'name' => 'Esquema base (users, teams, templates, workouts, notifications, login_attempts)',
    'up' => function (PDO $db): array {
        $log = [];

        $log[] = Schema::createTable($db, 'users', "
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'coach', 'athlete') NOT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            avatar_url VARCHAR(255) DEFAULT NULL,
            coach_id INT DEFAULT NULL,
            team_id INT DEFAULT NULL,
            goal_date DATE DEFAULT NULL,
            goal_pace VARCHAR(20) DEFAULT NULL,
            level VARCHAR(50) DEFAULT 'Principiante',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            available_days TEXT NULL,
            preferred_long_run_day VARCHAR(50) NULL,
            max_time_per_session INT NULL,
            observations TEXT NULL,
            FOREIGN KEY (coach_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX (coach_id),
            INDEX (team_id)
        ");

        $log[] = Schema::createTable($db, 'teams', "
            id INT AUTO_INCREMENT PRIMARY KEY,
            coach_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            logo_url VARCHAR(255) NULL,
            primary_color VARCHAR(50) DEFAULT '#3b82f6',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (coach_id)
        ");

        $log[] = Schema::createTable($db, 'notifications', "
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            type VARCHAR(50) DEFAULT 'info',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id)
        ");

        $log[] = Schema::createTable($db, 'templates', "
            id INT AUTO_INCREMENT PRIMARY KEY,
            coach_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(100) NOT NULL,
            block_type VARCHAR(100) NULL,
            structure TEXT NOT NULL,
            INDEX (coach_id)
        ");

        $log[] = Schema::createTable($db, 'login_attempts', "
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            ip_address VARCHAR(45) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (username, created_at)
        ");

        $log[] = Schema::createTable($db, 'workouts', "
            id INT AUTO_INCREMENT PRIMARY KEY,
            athlete_id INT NOT NULL,
            date DATETIME NOT NULL,
            type VARCHAR(100) NOT NULL,
            description TEXT NULL,
            status VARCHAR(50) DEFAULT 'pending',
            planned_distance INT NULL,
            planned_time INT NULL,
            actual_distance DECIMAL(6,2) NULL,
            actual_time DECIMAL(6,2) NULL,
            rpe INT NULL,
            feedback TEXT NULL,
            evidence_path VARCHAR(255) NULL,
            coach_feedback TEXT NULL,
            coach_feedback_at DATETIME NULL,
            delivery_status VARCHAR(20) DEFAULT 'pending',
            viewed_at DATETIME NULL,
            completed_at DATETIME NULL,
            structure TEXT NULL,
            INDEX (athlete_id),
            INDEX (date)
        ");

        // Instalaciones muy antiguas pueden no tener estas columnas.
        $log[] = Schema::addColumn($db, 'users', 'team_id', 'INT NULL');
        $log[] = Schema::addColumn($db, 'workouts', 'structure', 'TEXT NULL');
        $log[] = Schema::addColumn($db, 'workouts', 'evidence_path', 'VARCHAR(255) NULL');

        return $log;
    },
];

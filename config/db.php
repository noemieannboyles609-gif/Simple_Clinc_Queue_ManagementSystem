<?php
// config/db.php - Database connection & auto-schema initializer
header('Content-Type: text/html; charset=utf-8');

// Check for custom / InfinityFree database config if present
$customConfig = __DIR__ . '/db_config.php';
if (file_exists($customConfig)) {
    require_once $customConfig;
}

$host   = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: 'localhost');
$user   = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'root');
$pass   = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
$dbname = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'clinic_queue_db');
$port   = defined('DB_PORT') ? DB_PORT : (getenv('DB_PORT') ?: '3306');

$connected = false;

// Attempt 1: Connect directly to the specific database (standard for InfinityFree / shared hosting)
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    initTables($pdo);
    $connected = true;
} catch (PDOException $directEx) {
    // Attempt 2: If database does not exist yet (typical on first local XAMPP run), try creating it
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");
        initTables($pdo);
        $connected = true;
    } catch (PDOException $createEx) {
        $connected = false;
    }
}

// Attempt 3: Fallback to SQLite if MySQL is unavailable
if (!$connected) {
    try {
        $sqlitePath = __DIR__ . '/clinic_queue.sqlite';
        $pdo = new PDO("sqlite:$sqlitePath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        initTablesSQLite($pdo);
    } catch (Exception $ex) {
        die("Database Connection Error: " . htmlspecialchars($ex->getMessage()));
    }
}

function initTables($pdo) {
    // 1. Services table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `services` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `code` VARCHAR(10) NOT NULL UNIQUE,
        `name` VARCHAR(100) NOT NULL,
        `prefix` VARCHAR(5) NOT NULL,
        `estimated_mins` INT DEFAULT 15,
        `color` VARCHAR(20) DEFAULT '#0ea5e9',
        `icon` VARCHAR(50) DEFAULT 'stethoscope',
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Counters table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `counters` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `counter_number` VARCHAR(20) NOT NULL UNIQUE,
        `name` VARCHAR(100) NOT NULL,
        `staff_name` VARCHAR(100) NOT NULL,
        `status` ENUM('Active', 'Inactive', 'Break') DEFAULT 'Active',
        `current_ticket_id` INT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 3. Queue Tickets table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `queue_tickets` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `ticket_number` VARCHAR(20) NOT NULL,
        `patient_name` VARCHAR(100) NOT NULL,
        `patient_phone` VARCHAR(30) NULL,
        `patient_type` ENUM('Regular', 'Senior', 'PWD', 'Pregnant', 'Child', 'Emergency') DEFAULT 'Regular',
        `service_id` INT NOT NULL,
        `counter_id` INT NULL,
        `status` ENUM('Waiting', 'Serving', 'Completed', 'Skipped', 'Cancelled') DEFAULT 'Waiting',
        `skip_reason` VARCHAR(255) NULL,
        `notes` TEXT NULL,
        `priority_score` INT DEFAULT 0,
        `check_in_time` DATETIME NOT NULL,
        `called_time` DATETIME NULL,
        `completed_time` DATETIME NULL,
        `wait_duration_secs` INT DEFAULT 0,
        `service_duration_secs` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_status (`status`),
        INDEX idx_ticket (`ticket_number`),
        INDEX idx_checkin (`check_in_time`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 4. Users table (Administrator, Clinic Staff, Patient)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(100) NOT NULL,
        `role` ENUM('Administrator', 'Clinic Staff', 'Patient') NOT NULL DEFAULT 'Patient',
        `counter_id` INT NULL,
        `email` VARCHAR(100) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Check if services table is empty, if so, seed defaults
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM `services`");
    if ($stmt->fetch()['cnt'] == 0) {
        seedInitialServices($pdo);
    }

    // Check if counters table is empty, if so, seed defaults
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM `counters`");
    if ($stmt->fetch()['cnt'] == 0) {
        seedInitialCounters($pdo);
    }

    // Check if queue_tickets is empty, if so, seed sample fictional queue
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM `queue_tickets`");
    if ($stmt->fetch()['cnt'] == 0) {
        seedInitialTickets($pdo);
    }

    // Check if users table is empty, if so, seed demo accounts
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM `users`");
    if ($stmt->fetch()['cnt'] == 0) {
        seedInitialUsers($pdo);
    }
}

function initTablesSQLite($pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS services (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code TEXT NOT NULL UNIQUE,
        name TEXT NOT NULL,
        prefix TEXT NOT NULL,
        estimated_mins INTEGER DEFAULT 15,
        color TEXT DEFAULT '#0ea5e9',
        icon TEXT DEFAULT 'stethoscope',
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS counters (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        counter_number TEXT NOT NULL UNIQUE,
        name TEXT NOT NULL,
        staff_name TEXT NOT NULL,
        status TEXT DEFAULT 'Active',
        current_ticket_id INTEGER NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS queue_tickets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ticket_number TEXT NOT NULL,
        patient_name TEXT NOT NULL,
        patient_phone TEXT NULL,
        patient_type TEXT DEFAULT 'Regular',
        service_id INTEGER NOT NULL,
        counter_id INTEGER NULL,
        status TEXT DEFAULT 'Waiting',
        skip_reason TEXT NULL,
        notes TEXT NULL,
        priority_score INTEGER DEFAULT 0,
        check_in_time DATETIME NOT NULL,
        called_time DATETIME NULL,
        completed_time DATETIME NULL,
        wait_duration_secs INTEGER DEFAULT 0,
        service_duration_secs INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM services");
    if ($stmt->fetch()['cnt'] == 0) {
        seedInitialServices($pdo);
        seedInitialCounters($pdo);
        seedInitialTickets($pdo);
    }
}

function seedInitialServices($pdo) {
    $services = [
        ['GEN', 'General Medicine', 'A', 15, '#0ea5e9', 'stethoscope'],
        ['PED', 'Pediatrics & Child Care', 'P', 20, '#ec4899', 'baby'],
        ['DEN', 'Dental Consultation', 'D', 25, '#8b5cf6', 'tooth'],
        ['LAB', 'Laboratory & Blood Test', 'L', 10, '#10b981', 'flask'],
        ['VAC', 'Vaccination & Immunization', 'V', 10, '#06b6d4', 'syringe'],
        ['PRI', 'Priority Care (Senior / PWD)', 'S', 12, '#f59e0b', 'wheelchair']
    ];

    $stmt = $pdo->prepare("INSERT INTO services (code, name, prefix, estimated_mins, color, icon) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($services as $s) {
        $stmt->execute($s);
    }
}

function seedInitialCounters($pdo) {
    $counters = [
        ['Counter 1', 'General Medicine - Room 101', 'Dr. Sarah Jenkins', 'Active'],
        ['Counter 2', 'Pediatrics - Room 102', 'Dr. Emily Clark', 'Active'],
        ['Counter 3', 'Dental Care - Room 103', 'Dr. Mark Mendoza', 'Active'],
        ['Counter 4', 'Triage & Vital Signs', 'Nurse Michael Tan', 'Active'],
        ['Counter 5', 'Express / Senior Priority', 'Nurse Elena Ramos', 'Active']
    ];

    $stmt = $pdo->prepare("INSERT INTO counters (counter_number, name, staff_name, status) VALUES (?, ?, ?, ?)");
    foreach ($counters as $c) {
        $stmt->execute($c);
    }
}

function seedInitialTickets($pdo) {
    $now = date('Y-m-d H:i:s');
    $time1 = date('Y-m-d H:i:s', strtotime('-45 minutes'));
    $time2 = date('Y-m-d H:i:s', strtotime('-30 minutes'));
    $time3 = date('Y-m-d H:i:s', strtotime('-20 minutes'));
    $time4 = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    $time5 = date('Y-m-d H:i:s', strtotime('-10 minutes'));
    $time6 = date('Y-m-d H:i:s', strtotime('-5 minutes'));
    $time7 = date('Y-m-d H:i:s', strtotime('-2 minutes'));

    // Fictional sample patients
    $tickets = [
        // Completed
        ['A001', 'Maria Clara Dela Cruz', '+63 912 345 6789', 'Regular', 1, 1, 'Completed', null, 'Routine check-up completed. Normal BP.', 0, $time1, date('Y-m-d H:i:s', strtotime('-35 minutes')), date('Y-m-d H:i:s', strtotime('-20 minutes')), 600, 900],
        ['P001', 'Noah Gabriel Reyes', '+63 920 456 7890', 'Child', 2, 2, 'Completed', null, 'Mild fever, prescribed paracetamol.', 1, $time1, date('Y-m-d H:i:s', strtotime('-30 minutes')), date('Y-m-d H:i:s', strtotime('-15 minutes')), 900, 900],
        ['S001', 'Arturo Hernandez (Sr.)', '+63 917 111 2233', 'Senior', 6, 5, 'Completed', null, 'Maintenance medication refilled.', 2, $time2, date('Y-m-d H:i:s', strtotime('-20 minutes')), date('Y-m-d H:i:s', strtotime('-5 minutes')), 600, 900],
        
        // Currently Serving
        ['A002', 'Liam Mateo Santos', '+63 918 222 3344', 'Regular', 1, 1, 'Serving', null, 'Consulting for cough and cold.', 0, $time3, date('Y-m-d H:i:s', strtotime('-8 minutes')), null, 720, 0],
        ['P002', 'Sophia Nicole Castro', '+63 919 333 4455', 'Child', 2, 2, 'Serving', null, 'Pediatric wellness exam.', 1, $time4, date('Y-m-d H:i:s', strtotime('-6 minutes')), null, 540, 0],
        ['D001', 'Chloe Anne Tan', '+63 921 444 5566', 'Regular', 3, 3, 'Serving', null, 'Dental prophylaxis cleaning.', 0, $time4, date('Y-m-d H:i:s', strtotime('-4 minutes')), null, 660, 0],
        
        // Waiting
        ['S002', 'Lourdes Bautista', '+63 922 555 6677', 'Senior', 6, null, 'Waiting', null, null, 2, $time5, null, null, 0, 0],
        ['A003', 'Benjamin David Cruz', '+63 923 666 7788', 'Regular', 1, null, 'Waiting', null, null, 0, $time5, null, null, 0, 0],
        ['L001', 'Isabella Marie Gomez', '+63 924 777 8899', 'Pregnant', 4, null, 'Waiting', null, null, 2, $time6, null, null, 0, 0],
        ['V001', 'Ethan James Villanueva', '+63 925 888 9900', 'Regular', 5, null, 'Waiting', null, null, 0, $time6, null, null, 0, 0],
        ['A004', 'Hannah Grace Morales', '+63 926 999 0011', 'Regular', 1, null, 'Waiting', null, null, 0, $time7, null, null, 0, 0],

        // Skipped
        ['A005', 'Daniel Joseph Flores', '+63 927 000 1122', 'Regular', 1, 1, 'Skipped', 'Patient was momentarily not in waiting area', 'Called twice with no response.', 0, $time4, date('Y-m-d H:i:s', strtotime('-12 minutes')), null, 180, 0]
    ];

    $stmt = $pdo->prepare("INSERT INTO queue_tickets 
        (ticket_number, patient_name, patient_phone, patient_type, service_id, counter_id, status, skip_reason, notes, priority_score, check_in_time, called_time, completed_time, wait_duration_secs, service_duration_secs) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($tickets as $t) {
        $stmt->execute($t);
        $insertId = $pdo->lastInsertId();
        // If serving, link counter
        if ($t[6] === 'Serving' && !empty($t[5])) {
            $pdo->prepare("UPDATE counters SET current_ticket_id = ? WHERE id = ?")->execute([$insertId, $t[5]]);
        }
    }
}

function seedInitialUsers($pdo) {
    $users = [
        [
            'admin',
            password_hash('admin123', PASSWORD_DEFAULT),
            'System Administrator',
            'Administrator',
            null,
            'admin@carequeue.local'
        ],
        [
            'doctor',
            password_hash('doctor123', PASSWORD_DEFAULT),
            'Dr. Sarah Jenkins',
            'Clinic Staff',
            1,
            'dr.jenkins@carequeue.local'
        ],
        [
            'nurse',
            password_hash('nurse123', PASSWORD_DEFAULT),
            'Nurse Michael Tan',
            'Clinic Staff',
            4,
            'nurse.tan@carequeue.local'
        ],
        [
            'patient',
            password_hash('patient123', PASSWORD_DEFAULT),
            'Maria Clara Santos',
            'Patient',
            null,
            'maria.santos@example.com'
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, counter_id, email) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($users as $u) {
        $stmt->execute($u);
    }
}


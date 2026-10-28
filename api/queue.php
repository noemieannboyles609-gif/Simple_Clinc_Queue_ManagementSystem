<?php
// api/queue.php - REST API for Clinic Queue Management System
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Helper function to send JSON response
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    switch ($action) {

        // ==========================================
        // 1. GET DISPLAY BOARD DATA (Realtime TV display)
        // ==========================================
        case 'display':
            // Get all counters with currently serving tickets
            $countersStmt = $pdo->query("
                SELECT c.*, 
                       qt.ticket_number as serving_ticket,
                       qt.patient_name as serving_patient,
                       qt.patient_type as serving_patient_type,
                       qt.called_time as serving_called_time,
                       s.name as service_name,
                       s.color as service_color
                FROM counters c
                LEFT JOIN queue_tickets qt ON c.current_ticket_id = qt.id AND qt.status = 'Serving'
                LEFT JOIN services s ON qt.service_id = s.id
                WHERE c.status != 'Inactive'
                ORDER BY c.id ASC
            ");
            $counters = $countersStmt->fetchAll();

            // Most recently called ticket (for voice broadcast and hero card)
            $lastCalledStmt = $pdo->query("
                SELECT qt.*, c.counter_number, c.name as counter_name, c.staff_name, s.name as service_name
                FROM queue_tickets qt
                JOIN counters c ON qt.counter_id = c.id
                JOIN services s ON qt.service_id = s.id
                WHERE qt.status = 'Serving' AND qt.called_time IS NOT NULL
                ORDER BY qt.called_time DESC
                LIMIT 1
            ");
            $lastCalled = $lastCalledStmt->fetch();

            // Upcoming waiting tickets (next 8)
            $waitingStmt = $pdo->query("
                SELECT qt.id, qt.ticket_number, qt.patient_name, qt.patient_type, qt.priority_score, 
                       qt.check_in_time, s.name as service_name, s.color as service_color, s.prefix
                FROM queue_tickets qt
                JOIN services s ON qt.service_id = s.id
                WHERE qt.status = 'Waiting'
                ORDER BY qt.priority_score DESC, qt.check_in_time ASC
                LIMIT 8
            ");
            $upcomingWaiting = $waitingStmt->fetchAll();

            // Recently completed tickets (last 5)
            $completedStmt = $pdo->query("
                SELECT qt.ticket_number, qt.patient_name, qt.completed_time, s.name as service_name, c.counter_number
                FROM queue_tickets qt
                LEFT JOIN services s ON qt.service_id = s.id
                LEFT JOIN counters c ON qt.counter_id = c.id
                WHERE qt.status = 'Completed'
                ORDER BY qt.completed_time DESC
                LIMIT 5
            ");
            $recentCompleted = $completedStmt->fetchAll();

            // Recently skipped tickets (last 4)
            $skippedStmt = $pdo->query("
                SELECT qt.id, qt.ticket_number, qt.patient_name, qt.skip_reason, s.name as service_name
                FROM queue_tickets qt
                LEFT JOIN services s ON qt.service_id = s.id
                WHERE qt.status = 'Skipped'
                ORDER BY qt.id DESC
                LIMIT 4
            ");
            $recentSkipped = $skippedStmt->fetchAll();

            // Quick summary counts
            $countsStmt = $pdo->query("
                SELECT 
                    SUM(CASE WHEN status = 'Waiting' THEN 1 ELSE 0 END) as waiting_count,
                    SUM(CASE WHEN status = 'Serving' THEN 1 ELSE 0 END) as serving_count,
                    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_count,
                    SUM(CASE WHEN status = 'Skipped' THEN 1 ELSE 0 END) as skipped_count,
                    COUNT(*) as total_today
                FROM queue_tickets
                WHERE DATE(check_in_time) = CURDATE() OR check_in_time IS NOT NULL
            ");
            $counts = $countsStmt->fetch();

            jsonResponse([
                'success' => true,
                'timestamp' => date('Y-m-d H:i:s'),
                'counters' => $counters,
                'last_called' => $lastCalled,
                'upcoming' => $upcomingWaiting,
                'completed' => $recentCompleted,
                'skipped' => $recentSkipped,
                'counts' => $counts
            ]);
            break;

        // ==========================================
        // 2. PATIENT REGISTRATION / TICKET GENERATION
        // ==========================================
        case 'register':
            $patientName = trim($_POST['patient_name'] ?? '');
            $patientPhone = trim($_POST['patient_phone'] ?? '');
            $patientType = $_POST['patient_type'] ?? 'Regular';
            $serviceId = intval($_POST['service_id'] ?? 1);
            $notes = trim($_POST['notes'] ?? '');

            if (empty($patientName)) {
                $patientName = 'Walk-in Guest #' . rand(100, 999);
            }

            // Verify service
            $serviceStmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
            $serviceStmt->execute([$serviceId]);
            $service = $serviceStmt->fetch();
            if (!$service) {
                jsonResponse(['success' => false, 'message' => 'Invalid service selected'], 400);
            }

            // Calculate priority score (Emergency: 10, Senior/PWD/Pregnant: 5, Child: 2, Regular: 0)
            $priorityScore = 0;
            switch ($patientType) {
                case 'Emergency': $priorityScore = 10; break;
                case 'Senior':
                case 'PWD':
                case 'Pregnant': $priorityScore = 5; break;
                case 'Child': $priorityScore = 2; break;
                default: $priorityScore = 0; break;
            }

            // Generate unique ticket number for today
            // Prefix + 3 digit sequential number for today
            $prefix = $service['prefix'];
            if ($patientType === 'Senior' || $patientType === 'PWD') {
                // Route to Priority prefix if requested or keep service prefix
                $prefix = ($service['code'] === 'PRI') ? 'S' : $service['prefix'];
            }

            $seqStmt = $pdo->prepare("
                SELECT COUNT(*) as count_today 
                FROM queue_tickets 
                WHERE service_id = ? AND (DATE(check_in_time) = CURDATE() OR 1=1)
            ");
            $seqStmt->execute([$serviceId]);
            $seqRow = $seqStmt->fetch();
            $nextSeq = ($seqRow ? intval($seqRow['count_today']) : 0) + 1;
            
            // Format: A001, P002, etc.
            $ticketNumber = sprintf('%s%03d', $prefix, $nextSeq);

            // Double check uniqueness
            $checkStmt = $pdo->prepare("SELECT id FROM queue_tickets WHERE ticket_number = ? AND DATE(check_in_time) = CURDATE()");
            $checkStmt->execute([$ticketNumber]);
            if ($checkStmt->fetch()) {
                $ticketNumber = sprintf('%s%03d', $prefix, rand(500, 999));
            }

            $checkInTime = date('Y-m-d H:i:s');

            $insertStmt = $pdo->prepare("
                INSERT INTO queue_tickets 
                (ticket_number, patient_name, patient_phone, patient_type, service_id, status, notes, priority_score, check_in_time) 
                VALUES (?, ?, ?, ?, ?, 'Waiting', ?, ?, ?)
            ");
            $insertStmt->execute([
                $ticketNumber,
                $patientName,
                $patientPhone,
                $patientType,
                $serviceId,
                $notes,
                $priorityScore,
                $checkInTime
            ]);
            $ticketId = $pdo->lastInsertId();

            // Calculate waiting position and estimated wait time
            $positionStmt = $pdo->prepare("
                SELECT COUNT(*) as ahead 
                FROM queue_tickets 
                WHERE status = 'Waiting' AND (
                    priority_score > ? OR (priority_score = ? AND check_in_time < ?)
                )
            ");
            $positionStmt->execute([$priorityScore, $priorityScore, $checkInTime]);
            $aheadCount = intval($positionStmt->fetch()['ahead'] ?? 0);

            $estimatedWaitMins = max(5, ($aheadCount + 1) * intval($service['estimated_mins']));

            jsonResponse([
                'success' => true,
                'message' => "Queue Ticket $ticketNumber created successfully!",
                'ticket' => [
                    'id' => $ticketId,
                    'ticket_number' => $ticketNumber,
                    'patient_name' => $patientName,
                    'patient_type' => $patientType,
                    'service_name' => $service['name'],
                    'service_color' => $service['color'],
                    'check_in_time' => $checkInTime,
                    'patients_ahead' => $aheadCount,
                    'estimated_wait_mins' => $estimatedWaitMins
                ]
            ]);
            break;

        // ==========================================
        // 3. CALL NEXT PATIENT (Staff Console)
        // ==========================================
        case 'call_next':
            $counterId = intval($_POST['counter_id'] ?? 0);
            $serviceFilter = intval($_POST['service_id'] ?? 0);

            if ($counterId <= 0) {
                jsonResponse(['success' => false, 'message' => 'Please select an active counter first'], 400);
            }

            // Check if counter currently has an uncompleted ticket
            $counterStmt = $pdo->prepare("SELECT * FROM counters WHERE id = ?");
            $counterStmt->execute([$counterId]);
            $counter = $counterStmt->fetch();
            if (!$counter) {
                jsonResponse(['success' => false, 'message' => 'Counter not found'], 404);
            }

            // Find next waiting patient: Order by priority_score DESC, check_in_time ASC
            $sql = "
                SELECT qt.*, s.name as service_name, s.estimated_mins 
                FROM queue_tickets qt
                JOIN services s ON qt.service_id = s.id
                WHERE qt.status = 'Waiting'
            ";
            $params = [];
            if ($serviceFilter > 0) {
                $sql .= " AND qt.service_id = ?";
                $params[] = $serviceFilter;
            }
            $sql .= " ORDER BY qt.priority_score DESC, qt.check_in_time ASC LIMIT 1";

            $nextStmt = $pdo->prepare($sql);
            $nextStmt->execute($params);
            $nextTicket = $nextStmt->fetch();

            if (!$nextTicket) {
                jsonResponse(['success' => false, 'message' => 'No waiting patients in queue for this service!']);
            }

            // If counter was serving another ticket, auto-complete or prompt?
            if (!empty($counter['current_ticket_id'])) {
                // If previous ticket is still marked Serving, mark completed
                $prevId = $counter['current_ticket_id'];
                $pdo->prepare("
                    UPDATE queue_tickets 
                    SET status = 'Completed', 
                        completed_time = NOW(),
                        service_duration_secs = TIMESTAMPDIFF(SECOND, called_time, NOW())
                    WHERE id = ? AND status = 'Serving'
                ")->execute([$prevId]);
            }

            // Update next ticket to Serving
            $now = date('Y-m-d H:i:s');
            $waitSecs = strtotime($now) - strtotime($nextTicket['check_in_time']);

            $updateStmt = $pdo->prepare("
                UPDATE queue_tickets 
                SET status = 'Serving', 
                    counter_id = ?, 
                    called_time = ?, 
                    wait_duration_secs = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$counterId, $now, $waitSecs, $nextTicket['id']]);

            // Update counter current_ticket_id
            $pdo->prepare("UPDATE counters SET current_ticket_id = ? WHERE id = ?")->execute([$nextTicket['id'], $counterId]);

            // Fetch updated ticket with counter details for announcement
            $calledTicketStmt = $pdo->prepare("
                SELECT qt.*, c.counter_number, c.name as counter_name, c.staff_name, s.name as service_name
                FROM queue_tickets qt
                JOIN counters c ON qt.counter_id = c.id
                JOIN services s ON qt.service_id = s.id
                WHERE qt.id = ?
            ");
            $calledTicketStmt->execute([$nextTicket['id']]);
            $calledTicket = $calledTicketStmt->fetch();

            jsonResponse([
                'success' => true,
                'message' => "Now calling ticket {$calledTicket['ticket_number']} to {$counter['counter_number']}",
                'ticket' => $calledTicket
            ]);
            break;

        // ==========================================
        // 4. CALL SPECIFIC TICKET / RE-ANNOUNCE
        // ==========================================
        case 'call_specific':
            $ticketId = intval($_POST['ticket_id'] ?? 0);
            $counterId = intval($_POST['counter_id'] ?? 0);

            if ($ticketId <= 0) {
                jsonResponse(['success' => false, 'message' => 'Invalid ticket ID'], 400);
            }

            $stmt = $pdo->prepare("SELECT * FROM queue_tickets WHERE id = ?");
            $stmt->execute([$ticketId]);
            $ticket = $stmt->fetch();
            if (!$ticket) {
                jsonResponse(['success' => false, 'message' => 'Ticket not found'], 404);
            }

            $now = date('Y-m-d H:i:s');
            // If already serving or waiting or skipped, update to serving at this counter
            $waitSecs = $ticket['wait_duration_secs'];
            if (empty($ticket['called_time'])) {
                $waitSecs = strtotime($now) - strtotime($ticket['check_in_time']);
            }

            $targetCounterId = ($counterId > 0) ? $counterId : ($ticket['counter_id'] ?? 1);

            $updateStmt = $pdo->prepare("
                UPDATE queue_tickets 
                SET status = 'Serving', 
                    counter_id = ?, 
                    called_time = ?, 
                    wait_duration_secs = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$targetCounterId, $now, $waitSecs, $ticketId]);

            $pdo->prepare("UPDATE counters SET current_ticket_id = ? WHERE id = ?")->execute([$ticketId, $targetCounterId]);

            $calledTicketStmt = $pdo->prepare("
                SELECT qt.*, c.counter_number, c.name as counter_name, c.staff_name, s.name as service_name
                FROM queue_tickets qt
                JOIN counters c ON qt.counter_id = c.id
                JOIN services s ON qt.service_id = s.id
                WHERE qt.id = ?
            ");
            $calledTicketStmt->execute([$ticketId]);
            $calledTicket = $calledTicketStmt->fetch();

            jsonResponse([
                'success' => true,
                'message' => "Announcing ticket {$calledTicket['ticket_number']} at {$calledTicket['counter_number']}",
                'ticket' => $calledTicket
            ]);
            break;

        // ==========================================
        // 5. COMPLETE CONSULTATION
        // ==========================================
        case 'complete':
            $ticketId = intval($_POST['ticket_id'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');

            if ($ticketId <= 0) {
                jsonResponse(['success' => false, 'message' => 'Invalid ticket ID'], 400);
            }

            $stmt = $pdo->prepare("SELECT * FROM queue_tickets WHERE id = ?");
            $stmt->execute([$ticketId]);
            $ticket = $stmt->fetch();
            if (!$ticket) {
                jsonResponse(['success' => false, 'message' => 'Ticket not found'], 404);
            }

            $now = date('Y-m-d H:i:s');
            $calledTime = !empty($ticket['called_time']) ? strtotime($ticket['called_time']) : strtotime($now);
            $serviceDuration = max(60, strtotime($now) - $calledTime);

            $updateStmt = $pdo->prepare("
                UPDATE queue_tickets 
                SET status = 'Completed', 
                    completed_time = ?, 
                    service_duration_secs = ?,
                    notes = COALESCE(NULLIF(?, ''), notes)
                WHERE id = ?
            ");
            $updateStmt->execute([$now, $serviceDuration, $notes, $ticketId]);

            // Clear counter current_ticket_id
            if (!empty($ticket['counter_id'])) {
                $pdo->prepare("UPDATE counters SET current_ticket_id = NULL WHERE id = ? AND current_ticket_id = ?")
                    ->execute([$ticket['counter_id'], $ticketId]);
            }

            jsonResponse([
                'success' => true,
                'message' => "Consultation for ticket {$ticket['ticket_number']} completed successfully! Service duration: " . round($serviceDuration / 60, 1) . " mins.",
                'service_duration_secs' => $serviceDuration
            ]);
            break;

        // ==========================================
        // 6. SKIP PATIENT
        // ==========================================
        case 'skip':
            $ticketId = intval($_POST['ticket_id'] ?? 0);
            $reason = trim($_POST['reason'] ?? 'Patient did not respond when called');

            if ($ticketId <= 0) {
                jsonResponse(['success' => false, 'message' => 'Invalid ticket ID'], 400);
            }

            $stmt = $pdo->prepare("SELECT * FROM queue_tickets WHERE id = ?");
            $stmt->execute([$ticketId]);
            $ticket = $stmt->fetch();
            if (!$ticket) {
                jsonResponse(['success' => false, 'message' => 'Ticket not found'], 404);
            }

            $updateStmt = $pdo->prepare("
                UPDATE queue_tickets 
                SET status = 'Skipped', 
                    skip_reason = ? 
                WHERE id = ?
            ");
            $updateStmt->execute([$reason, $ticketId]);

            // Clear counter current_ticket_id if linked
            if (!empty($ticket['counter_id'])) {
                $pdo->prepare("UPDATE counters SET current_ticket_id = NULL WHERE id = ? AND current_ticket_id = ?")
                    ->execute([$ticket['counter_id'], $ticketId]);
            }

            jsonResponse([
                'success' => true,
                'message' => "Ticket {$ticket['ticket_number']} has been skipped (Reason: $reason)."
            ]);
            break;

        // ==========================================
        // 7. RECALL SKIPPED PATIENT
        // ==========================================
        case 'recall':
            $ticketId = intval($_POST['ticket_id'] ?? 0);
            $counterId = intval($_POST['counter_id'] ?? 0);

            if ($ticketId <= 0) {
                jsonResponse(['success' => false, 'message' => 'Invalid ticket ID'], 400);
            }

            $now = date('Y-m-d H:i:s');
            // Move back to Waiting with priority boost or directly to Serving if counter provided
            if ($counterId > 0) {
                $updateStmt = $pdo->prepare("
                    UPDATE queue_tickets 
                    SET status = 'Serving', 
                        counter_id = ?, 
                        called_time = ?,
                        skip_reason = NULL
                    WHERE id = ?
                ");
                $updateStmt->execute([$counterId, $now, $ticketId]);
                $pdo->prepare("UPDATE counters SET current_ticket_id = ? WHERE id = ?")->execute([$ticketId, $counterId]);
            } else {
                $updateStmt = $pdo->prepare("
                    UPDATE queue_tickets 
                    SET status = 'Waiting', 
                        priority_score = priority_score + 1,
                        skip_reason = NULL
                    WHERE id = ?
                ");
                $updateStmt->execute([$ticketId]);
            }

            jsonResponse([
                'success' => true,
                'message' => "Ticket has been recalled back to the active queue!"
            ]);
            break;

        // ==========================================
        // 8. TRANSFER TICKET TO ANOTHER SERVICE/COUNTER
        // ==========================================
        case 'transfer':
            $ticketId = intval($_POST['ticket_id'] ?? 0);
            $targetServiceId = intval($_POST['target_service_id'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');

            if ($ticketId <= 0 || $targetServiceId <= 0) {
                jsonResponse(['success' => false, 'message' => 'Invalid parameters'], 400);
            }

            $stmt = $pdo->prepare("SELECT * FROM queue_tickets WHERE id = ?");
            $stmt->execute([$ticketId]);
            $ticket = $stmt->fetch();
            if (!$ticket) {
                jsonResponse(['success' => false, 'message' => 'Ticket not found'], 404);
            }

            // Clear old counter
            if (!empty($ticket['counter_id'])) {
                $pdo->prepare("UPDATE counters SET current_ticket_id = NULL WHERE id = ? AND current_ticket_id = ?")
                    ->execute([$ticket['counter_id'], $ticketId]);
            }

            $transferNote = "Transferred from previous station. " . $notes;

            $updateStmt = $pdo->prepare("
                UPDATE queue_tickets 
                SET status = 'Waiting', 
                    service_id = ?, 
                    counter_id = NULL,
                    notes = CONCAT(COALESCE(notes, ''), ' [Transfer] ', ?)
                WHERE id = ?
            ");
            $updateStmt->execute([$targetServiceId, $transferNote, $ticketId]);

            jsonResponse([
                'success' => true,
                'message' => "Ticket {$ticket['ticket_number']} transferred successfully."
            ]);
            break;

        // ==========================================
        // 9. FULL QUEUE LIST (Staff / Admin monitor)
        // ==========================================
        case 'list':
            $statusFilter = $_GET['status'] ?? '';
            $serviceFilter = intval($_GET['service_id'] ?? 0);

            $sql = "
                SELECT qt.*, s.name as service_name, s.color as service_color, s.prefix,
                       c.counter_number, c.name as counter_name, c.staff_name
                FROM queue_tickets qt
                JOIN services s ON qt.service_id = s.id
                LEFT JOIN counters c ON qt.counter_id = c.id
                WHERE 1=1
            ";
            $params = [];

            if (!empty($statusFilter) && in_array($statusFilter, ['Waiting', 'Serving', 'Completed', 'Skipped', 'Cancelled'])) {
                $sql .= " AND qt.status = ?";
                $params[] = $statusFilter;
            }

            if ($serviceFilter > 0) {
                $sql .= " AND qt.service_id = ?";
                $params[] = $serviceFilter;
            }

            $sql .= " ORDER BY 
                CASE qt.status 
                    WHEN 'Serving' THEN 1 
                    WHEN 'Waiting' THEN 2 
                    WHEN 'Skipped' THEN 3 
                    WHEN 'Completed' THEN 4 
                    ELSE 5 
                END,
                qt.priority_score DESC, 
                qt.check_in_time ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $tickets = $stmt->fetchAll();

            // Fetch counters
            $countersStmt = $pdo->query("SELECT * FROM counters WHERE status != 'Inactive'");
            $counters = $countersStmt->fetchAll();

            // Fetch services
            $servicesStmt = $pdo->query("SELECT * FROM services WHERE is_active = 1");
            $services = $servicesStmt->fetchAll();

            jsonResponse([
                'success' => true,
                'tickets' => $tickets,
                'counters' => $counters,
                'services' => $services
            ]);
            break;

        // ==========================================
        // 10. CLINIC DASHBOARD ANALYTICS & STATS
        // ==========================================
        case 'stats':
            // Today's summary
            $summaryStmt = $pdo->query("
                SELECT 
                    COUNT(*) as total_patients,
                    SUM(CASE WHEN status = 'Waiting' THEN 1 ELSE 0 END) as waiting_count,
                    SUM(CASE WHEN status = 'Serving' THEN 1 ELSE 0 END) as serving_count,
                    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_count,
                    SUM(CASE WHEN status = 'Skipped' THEN 1 ELSE 0 END) as skipped_count,
                    COALESCE(AVG(CASE WHEN wait_duration_secs > 0 THEN wait_duration_secs ELSE NULL END), 0) as avg_wait_secs,
                    COALESCE(AVG(CASE WHEN service_duration_secs > 0 THEN service_duration_secs ELSE NULL END), 0) as avg_service_secs
                FROM queue_tickets
            ");
            $summary = $summaryStmt->fetch();

            // Format averages to readable minutes
            $summary['avg_wait_mins'] = round($summary['avg_wait_secs'] / 60, 1);
            $summary['avg_service_mins'] = round($summary['avg_service_secs'] / 60, 1);

            // Distribution by Service
            $serviceDistStmt = $pdo->query("
                SELECT s.name, s.color, COUNT(qt.id) as ticket_count
                FROM services s
                LEFT JOIN queue_tickets qt ON s.id = qt.service_id
                GROUP BY s.id
                ORDER BY ticket_count DESC
            ");
            $serviceDistribution = $serviceDistStmt->fetchAll();

            // Distribution by Patient Category
            $patientTypeStmt = $pdo->query("
                SELECT patient_type, COUNT(*) as count
                FROM queue_tickets
                GROUP BY patient_type
                ORDER BY count DESC
            ");
            $patientTypeDistribution = $patientTypeStmt->fetchAll();

            // Counter Performance
            $counterPerfStmt = $pdo->query("
                SELECT c.counter_number, c.name, c.staff_name,
                       COUNT(qt.id) as served_count,
                       COALESCE(AVG(qt.service_duration_secs), 0) as avg_secs
                FROM counters c
                LEFT JOIN queue_tickets qt ON c.id = qt.counter_id AND qt.status = 'Completed'
                GROUP BY c.id
                ORDER BY served_count DESC
            ");
            $counterPerformance = $counterPerfStmt->fetchAll();
            foreach ($counterPerformance as &$cp) {
                $cp['avg_mins'] = round($cp['avg_secs'] / 60, 1);
            }

            // Hourly traffic distribution
            $hourlyStmt = $pdo->query("
                SELECT HOUR(check_in_time) as hour_of_day, COUNT(*) as count
                FROM queue_tickets
                GROUP BY HOUR(check_in_time)
                ORDER BY hour_of_day ASC
            ");
            $hourlyTraffic = $hourlyStmt->fetchAll();

            jsonResponse([
                'success' => true,
                'summary' => $summary,
                'services' => $serviceDistribution,
                'patient_types' => $patientTypeDistribution,
                'counters' => $counterPerformance,
                'hourly' => $hourlyTraffic
            ]);
            break;

        // ==========================================
        // 11. QUEUE HISTORY WITH SEARCH & FILTERS
        // ==========================================
        case 'history':
            $search = trim($_GET['search'] ?? '');
            $status = $_GET['status'] ?? '';
            $serviceId = intval($_GET['service_id'] ?? 0);
            $date = $_GET['date'] ?? '';

            $sql = "
                SELECT qt.*, s.name as service_name, s.color as service_color,
                       c.counter_number, c.name as counter_name, c.staff_name
                FROM queue_tickets qt
                JOIN services s ON qt.service_id = s.id
                LEFT JOIN counters c ON qt.counter_id = c.id
                WHERE 1=1
            ";
            $params = [];

            if (!empty($search)) {
                $sql .= " AND (qt.ticket_number LIKE ? OR qt.patient_name LIKE ? OR qt.patient_phone LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            if (!empty($status)) {
                $sql .= " AND qt.status = ?";
                $params[] = $status;
            }

            if ($serviceId > 0) {
                $sql .= " AND qt.service_id = ?";
                $params[] = $serviceId;
            }

            if (!empty($date)) {
                $sql .= " AND DATE(qt.check_in_time) = ?";
                $params[] = $date;
            }

            $sql .= " ORDER BY qt.id DESC LIMIT 100";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $history = $stmt->fetchAll();

            jsonResponse([
                'success' => true,
                'count' => count($history),
                'history' => $history
            ]);
            break;

        // ==========================================
        // 12. TRACK TICKET (For Patient Status Lookup)
        // ==========================================
        case 'track':
            $ticketNumber = trim($_GET['ticket_number'] ?? '');
            if (empty($ticketNumber)) {
                jsonResponse(['success' => false, 'message' => 'Please enter a ticket number'], 400);
            }

            $stmt = $pdo->prepare("
                SELECT qt.*, s.name as service_name, s.color as service_color, s.estimated_mins,
                       c.counter_number, c.name as counter_name, c.staff_name
                FROM queue_tickets qt
                JOIN services s ON qt.service_id = s.id
                LEFT JOIN counters c ON qt.counter_id = c.id
                WHERE qt.ticket_number = ?
                ORDER BY qt.id DESC LIMIT 1
            ");
            $stmt->execute([$ticketNumber]);
            $ticket = $stmt->fetch();

            if (!$ticket) {
                jsonResponse(['success' => false, 'message' => "Ticket '$ticketNumber' not found."], 404);
            }

            // If waiting, calculate position
            $aheadCount = 0;
            $estimatedWaitMins = 0;
            if ($ticket['status'] === 'Waiting') {
                $posStmt = $pdo->prepare("
                    SELECT COUNT(*) as ahead 
                    FROM queue_tickets 
                    WHERE status = 'Waiting' AND (
                        priority_score > ? OR (priority_score = ? AND check_in_time < ?)
                    )
                ");
                $posStmt->execute([$ticket['priority_score'], $ticket['priority_score'], $ticket['check_in_time']]);
                $aheadCount = intval($posStmt->fetch()['ahead'] ?? 0);
                $estimatedWaitMins = max(3, ($aheadCount + 1) * intval($ticket['estimated_mins']));
            }

            jsonResponse([
                'success' => true,
                'ticket' => $ticket,
                'ahead_count' => $aheadCount,
                'estimated_wait_mins' => $estimatedWaitMins
            ]);
            break;

        // ==========================================
        // 13. SEED FICTIONAL DEMO PATIENT DATA
        // ==========================================
        case 'seed':
            // Reset existing queue_tickets
            $pdo->exec("DELETE FROM queue_tickets");
            $pdo->exec("UPDATE counters SET current_ticket_id = NULL");
            seedInitialTickets($pdo);

            jsonResponse([
                'success' => true,
                'message' => 'Demo patient queue refreshed with fictional patients!'
            ]);
            break;

        // ==========================================
        // 14. RESET QUEUE
        // ==========================================
        case 'reset':
            $pdo->exec("DELETE FROM queue_tickets");
            $pdo->exec("UPDATE counters SET current_ticket_id = NULL");
            jsonResponse([
                'success' => true,
                'message' => 'Queue has been successfully reset.'
            ]);
            break;

        // ==========================================
        // 15. COUNTERS & SERVICES CONFIG (Admin)
        // ==========================================
        case 'save_counter':
            $counterId = intval($_POST['counter_id'] ?? 0);
            $counterNum = trim($_POST['counter_number'] ?? '');
            $counterName = trim($_POST['name'] ?? '');
            $staffName = trim($_POST['staff_name'] ?? '');
            $status = $_POST['status'] ?? 'Active';

            if (empty($counterNum) || empty($counterName) || empty($staffName)) {
                jsonResponse(['success' => false, 'message' => 'All fields are required.'], 400);
            }

            if ($counterId > 0) {
                $stmt = $pdo->prepare("UPDATE counters SET counter_number = ?, name = ?, staff_name = ?, status = ? WHERE id = ?");
                $stmt->execute([$counterNum, $counterName, $staffName, $status, $counterId]);
                $msg = "Counter updated successfully!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO counters (counter_number, name, staff_name, status) VALUES (?, ?, ?, ?)");
                $stmt->execute([$counterNum, $counterName, $staffName, $status]);
                $msg = "New counter added successfully!";
            }

            jsonResponse(['success' => true, 'message' => $msg]);
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
            break;
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()], 500);
}

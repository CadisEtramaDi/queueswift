<?php
require_once 'config.php';
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$token = $_GET['token'] ?? '';

function generateToken($length = 8) {
    return strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, $length));
}

function getQueuePosition($db, $business_id, $queue_number) {
    $stmt = $db->prepare("SELECT COUNT(*) as pos FROM queues WHERE business_id = ? AND status = 'waiting' AND queue_number < ? AND DATE(joined_at) = CURDATE()");
    $stmt->bind_param("ii", $business_id, $queue_number);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['pos'] + 1;
}

if ($method === 'POST' && $action === 'join') {
    $data = json_decode(file_get_contents('php://input'), true);
    $business_id = (int)($data['business_id'] ?? 0);
    $name = trim($data['customer_name'] ?? '');
    $phone = trim($data['customer_phone'] ?? '');
    $email = trim($data['customer_email'] ?? '');
    $service = trim($data['service_type'] ?? '');
    $notes = trim($data['notes'] ?? '');

    if (!$business_id || !$name) {
        echo json_encode(['error' => 'Business and name are required']); exit;
    }

    // Check business exists and is open
    $biz = $db->query("SELECT * FROM businesses WHERE id = $business_id AND is_active = 1")->fetch_assoc();
    if (!$biz) { echo json_encode(['error' => 'Business not found']); exit; }

    // Get next queue number for today
    $stmt = $db->prepare("SELECT MAX(queue_number) as max_q FROM queues WHERE business_id = ? AND DATE(joined_at) = CURDATE()");
    $stmt->bind_param("i", $business_id);
    $stmt->execute();
    $maxQ = $stmt->get_result()->fetch_assoc()['max_q'] ?? 0;
    $next_q = $maxQ + 1;

    // Count waiting
    $waitingStmt = $db->prepare("SELECT COUNT(*) as cnt FROM queues WHERE business_id = ? AND status = 'waiting' AND DATE(joined_at) = CURDATE()");
    $waitingStmt->bind_param("i", $business_id);
    $waitingStmt->execute();
    $waiting = $waitingStmt->get_result()->fetch_assoc()['cnt'];

    if ($waiting >= $biz['max_queue']) {
        echo json_encode(['error' => 'Queue is full for today. Please try again tomorrow.']); exit;
    }

    $est_wait = $waiting * $biz['avg_service_minutes'];
    $token_val = generateToken();
    // ensure unique
    while ($db->query("SELECT id FROM queues WHERE token = '$token_val'")->num_rows > 0) {
        $token_val = generateToken();
    }

    $stmt = $db->prepare("INSERT INTO queues (business_id, customer_name, customer_phone, customer_email, service_type, queue_number, token, estimated_wait, notes) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("issssisss", $business_id, $name, $phone, $email, $service, $next_q, $token_val, $est_wait, $notes);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'token' => $token_val,
        'queue_number' => $next_q,
        'position' => $waiting + 1,
        'estimated_wait' => $est_wait,
        'business_name' => $biz['name']
    ]);

} elseif ($method === 'GET' && $action === 'status' && $token) {
    $stmt = $db->prepare("SELECT q.*, b.name as business_name, b.avg_service_minutes FROM queues q JOIN businesses b ON q.business_id = b.id WHERE q.token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $q = $stmt->get_result()->fetch_assoc();
    if (!$q) { echo json_encode(['error' => 'Token not found']); exit; }

    $position = getQueuePosition($db, $q['business_id'], $q['queue_number']);
    // get currently serving
    $serving = $db->query("SELECT queue_number, customer_name FROM queues WHERE business_id = {$q['business_id']} AND status = 'serving' LIMIT 1")->fetch_assoc();

    $q['position'] = $q['status'] === 'waiting' ? $position : 0;
    $q['estimated_wait'] = max(0, ($position - 1) * $q['avg_service_minutes']);
    $q['currently_serving'] = $serving;
    echo json_encode($q);

} elseif ($method === 'POST' && $action === 'cancel') {
    $data = json_decode(file_get_contents('php://input'), true);
    $token_val = $data['token'] ?? '';
    $stmt = $db->prepare("UPDATE queues SET status = 'cancelled' WHERE token = ? AND status = 'waiting'");
    $stmt->bind_param("s", $token_val);
    $stmt->execute();
    echo json_encode(['success' => $stmt->affected_rows > 0]);

} elseif ($method === 'GET' && $action === 'live') {
    // Live queue board for a business
    $business_id = (int)($_GET['business_id'] ?? 0);
    $serving = $db->query("SELECT queue_number, customer_name, service_type FROM queues WHERE business_id = $business_id AND status = 'serving' LIMIT 1")->fetch_assoc();
    $waiting_stmt = $db->prepare("SELECT queue_number, customer_name, service_type, joined_at FROM queues WHERE business_id = ? AND status = 'waiting' AND DATE(joined_at) = CURDATE() ORDER BY queue_number LIMIT 10");
    $waiting_stmt->bind_param("i", $business_id);
    $waiting_stmt->execute();
    $waiting = $waiting_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $total = $db->query("SELECT COUNT(*) as c FROM queues WHERE business_id = $business_id AND status = 'waiting' AND DATE(joined_at) = CURDATE()")->fetch_assoc()['c'];
    echo json_encode(['serving' => $serving, 'waiting' => $waiting, 'total_waiting' => $total]);

} elseif ($method === 'POST' && $action === 'admin_next') {
    // Admin: serve next
    $data = json_decode(file_get_contents('php://input'), true);
    $business_id = (int)($data['business_id'] ?? 0);
    // Mark current serving as done
    $db->query("UPDATE queues SET status = 'done', done_at = NOW() WHERE business_id = $business_id AND status = 'serving'");
    // Get next waiting
    $next = $db->query("SELECT id FROM queues WHERE business_id = $business_id AND status = 'waiting' AND DATE(joined_at) = CURDATE() ORDER BY queue_number LIMIT 1")->fetch_assoc();
    if ($next) {
        $db->query("UPDATE queues SET status = 'serving', served_at = NOW() WHERE id = {$next['id']}");
        echo json_encode(['success' => true, 'message' => 'Next customer called']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No more customers in queue']);
    }

} elseif ($method === 'GET' && $action === 'admin_queue') {
    $business_id = (int)($_GET['business_id'] ?? 0);
    $date = $_GET['date'] ?? date('Y-m-d');
    $result = $db->query("SELECT * FROM queues WHERE business_id = $business_id AND DATE(joined_at) = '$date' ORDER BY queue_number");
    $rows = [];
    while ($r = $result->fetch_assoc()) $rows[] = $r;
    echo json_encode($rows);
}

$db->close();

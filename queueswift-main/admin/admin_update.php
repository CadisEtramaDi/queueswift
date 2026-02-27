<?php
require_once '../api/config.php';
$db = getDB();
$data = json_decode(file_get_contents('php://input'), true);
$id = (int)($data['id'] ?? 0);
$status = $data['status'] ?? '';

$allowed = ['waiting', 'serving', 'done', 'cancelled', 'no_show'];
if (!$id || !in_array($status, $allowed)) {
    echo json_encode(['error' => 'Invalid input']); exit;
}

$done_at = in_array($status, ['done','cancelled','no_show']) ? ', done_at = NOW()' : '';
$served_at = $status === 'serving' ? ', served_at = NOW()' : '';

$stmt = $db->prepare("UPDATE queues SET status = ? $done_at $served_at WHERE id = ?");
$stmt->bind_param("si", $status, $id);
$stmt->execute();

echo json_encode(['success' => $stmt->affected_rows > 0]);
$db->close();

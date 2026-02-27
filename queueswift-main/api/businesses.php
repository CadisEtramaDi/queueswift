<?php
require_once 'config.php';
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;
$category = $_GET['category'] ?? null;

if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare("SELECT b.*, GROUP_CONCAT(bs.service_name ORDER BY bs.id SEPARATOR '||') as services, GROUP_CONCAT(bs.duration_minutes ORDER BY bs.id SEPARATOR '||') as durations FROM businesses b LEFT JOIN business_services bs ON b.id = bs.business_id WHERE b.id = ? AND b.is_active = 1 GROUP BY b.id");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if ($result) {
            $result['services_list'] = $result['services'] ? array_map(null, explode('||', $result['services']), explode('||', $result['durations'])) : [];
        }
        echo json_encode($result);
    } else {
        $where = $category ? "WHERE b.category = ? AND b.is_active = 1" : "WHERE b.is_active = 1";
        $sql = "SELECT b.*, (SELECT COUNT(*) FROM queues q WHERE q.business_id = b.id AND q.status = 'waiting' AND DATE(q.joined_at) = CURDATE()) as current_queue FROM businesses b $where ORDER BY b.name";
        if ($category) {
            $stmt = $db->prepare($sql);
            $stmt->bind_param("s", $category);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $db->query($sql);
        }
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        echo json_encode($rows);
    }
}
$db->close();

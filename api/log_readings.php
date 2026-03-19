<?php
// Receives a JSON batch of sensor readings from main.js and saves them to light_logs.
// Called automatically by the frontend once per minute.

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);

if (empty($body['entries']) || !is_array($body['entries'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No entries provided']);
    exit;
}

// Insert all entries in a single prepared statement loop
$stmt = $pdo->prepare(
    "INSERT INTO light_logs (building_id, lux, pollution_level, online) VALUES (?, ?, ?, ?)"
);

$pdo->beginTransaction();
try {
    foreach ($body['entries'] as $entry) {
        $stmt->execute([
            (int)$entry['building_id'],
            (float)$entry['lux'],
            $entry['pollution_level'],
            (int)$entry['online'],
        ]);
    }
    $pdo->commit();
    echo json_encode(['success' => true, 'saved' => count($body['entries'])]);
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

<?php
/**
 * API endpoint para obtener tips por IDs.
 * GET /api/get_tips.php?ids=1,2,3
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$db = Database::getInstance();

$idsParam = $_GET['ids'] ?? '';
if (!$idsParam) {
    echo json_encode(['tips' => []]);
    exit;
}

$ids = array_map('intval', explode(',', $idsParam));
$ids = array_filter($ids, fn($id) => $id > 0);

if (empty($ids)) {
    echo json_encode(['tips' => []]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sql = "SELECT id, title, content, category FROM tips WHERE id IN ($placeholders) AND is_active = 1";
$stmt = $db->prepare($sql);
$stmt->execute($ids);
$tips = $stmt->fetchAll();

echo json_encode(['tips' => $tips]);
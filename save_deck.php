<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$user_id = $_SESSION['user_id'];
$deck_id = $data['deck_id'] ?? null;
$deck_name = $data['deck_name'] ?? 'Untitled Deck';
$cover_image = $data['cover_image'] ?? '';
$card_list = json_encode($data['card_list'] ?? []);

try {
    if ($deck_id) {
        $stmt = $pdo->prepare("UPDATE decks SET deck_name = ?, cover_image = ?, card_list = ? WHERE id = ? AND user_id = ?");
        $success = $stmt->execute([$deck_name, $cover_image, $card_list, $deck_id, $user_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO decks (user_id, deck_name, cover_image, card_list) VALUES (?, ?, ?, ?)");
        $success = $stmt->execute([$user_id, $deck_name, $cover_image, $card_list]);
    }

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
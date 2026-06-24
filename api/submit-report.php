<?php
require_once "./config.php";

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$user_id = $_SESSION['user_id'];
$category = trim($data['category'] ?? '');
$subtheme = trim($data['subtheme'] ?? '');
$description = trim($data['description'] ?? '');
$address = trim($data['address'] ?? '');
$contact = trim($data['contact'] ?? '');
$photo_path = trim($data['photo_path'] ?? '');

if (empty($category)) {
    echo json_encode(['success' => false, 'error' => 'Категория обязательна']);
    exit;
}

if (empty($description)) {
    echo json_encode(['success' => false, 'error' => 'Описание обязательно']);
    exit;
}

if (empty($address)) {
    echo json_encode(['success' => false, 'error' => 'Адрес обязателен']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO reports (user_id, category, subtheme, description, address, contact, photo_path, status) 
        VALUES (:user_id, :category, :subtheme, :description, :address, :contact, :photo_path, 'checking')
    ");

    $stmt->execute([
        ':user_id' => $user_id,
        ':category' => $category,
        ':subtheme' => $subtheme,
        ':description' => $description,
        ':address' => $address,
        ':contact' => $contact,
        ':photo_path' => $photo_path
    ]);

    $report_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Заявка успешно отправлена!',
        'report_id' => $report_id,
        'photo_path' => $photo_path
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка при сохранении заявки: ' . $e->getMessage()
    ]);
}
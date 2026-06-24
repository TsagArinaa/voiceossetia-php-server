<?php
require_once "./config.php";

session_start();

// Проверяем авторизацию
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

// Проверяем роль администратора
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Доступ запрещён']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$report_id = $data['report_id'] ?? null;
$new_status = $data['status'] ?? null;

if (!$report_id || !$new_status) {
    echo json_encode(['success' => false, 'error' => 'Недостаточно данных']);
    exit;
}

// Валидация статуса
$allowed_statuses = ['checking', 'in-progress', 'resolved', 'rejected'];
if (!in_array($new_status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'error' => 'Некорректный статус']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE reports SET status = :status WHERE id = :report_id");
    $stmt->execute([
        ':status' => $new_status,
        ':report_id' => $report_id
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Статус обновлён'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка обновления статуса: ' . $e->getMessage()
    ]);
}
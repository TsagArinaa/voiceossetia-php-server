<?php
require_once "./config.php";

session_start();

// Проверяем авторизацию
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

// Возвращаем данные пользователя из сессии
echo json_encode([
    'success' => true,
    'user' => [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email']
    ]
]);
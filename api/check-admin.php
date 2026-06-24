<?php
require_once "./config.php";

session_start();

// Проверяем авторизацию
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

try {
    // Получаем роль пользователя из базы данных
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Пользователь не найден']);
        exit;
    }
    
    $isAdmin = ($user['role'] === 'admin');
    
    echo json_encode([
        'success' => true,
        'isAdmin' => $isAdmin,
        'role' => $user['role']
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка проверки роли: ' . $e->getMessage()
    ]);
}
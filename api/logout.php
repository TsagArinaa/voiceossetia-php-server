<?php
require_once "./config.php";

// Запускаем сессию
session_start();

// Очищаем все переменные сессии
$_SESSION = array();

// Удаляем cookie сессии если она есть
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Уничтожаем сессию
session_destroy();

echo json_encode(['success' => true, 'message' => 'Выход выполнен успешно']);
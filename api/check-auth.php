<?php
require_once "./config.php";

// Запускаем сессию
session_start();

// Проверяем, авторизован ли пользователь
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    echo json_encode([
        'authenticated' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email']
        ]
    ]);
} else {
    echo json_encode([
        'authenticated' => false,
        'user' => null
    ]);
}
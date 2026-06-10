<?php
// Подключаем конфигурацию с настройками БД
require_once "./config.php";

// Проверяем, что запрос выполнен методом POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Получаем данные из тела запроса (JSON)
$data = json_decode(file_get_contents('php://input'), true);

// Извлекаем email и пароль из полученных данных
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

// Валидация: проверяем что поля не пустые
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Все поля обязательны']);
    exit;
}

// Валидация email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Некорректный email']);
    exit;
}

try {
    // Ищем пользователя в БД по email
    $stmt = $pdo->prepare("SELECT id, username, email, password FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Проверяем, найден ли пользователь
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'Неверный email или пароль']);
        exit;
    }
    
    // Проверяем пароль (используем password_verify для сравнения с хешем)
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'error' => 'Неверный email или пароль']);
        exit;
    }
    
    // Удаляем пароль из данных пользователя перед отправкой
    unset($user['password']);
    
    // Запускаем сессию для сохранения состояния авторизации
    session_start();
    
    // Сохраняем данные пользователя в сессии
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['logged_in'] = true;
    
    // Возвращаем успешный ответ с данными пользователя
    echo json_encode([
        'success' => true,
        'message' => 'Вход выполнен успешно',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
        ]
    ]);
    
} catch (PDOException $e) {
    // Обработка ошибок базы данных
    echo json_encode(['success' => false, 'error' => 'Ошибка при входе: ' . $e->getMessage()]);
}
<?php
require_once "./config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'Method not allowed']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$username = trim($data['username'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

// Валидация
if (empty($username) || empty($email) || empty($password)) {
  echo json_encode(['success' => false, 'error' => 'Все поля обязательны']);
  exit;
}

if (strlen($username) < 3) {
  echo json_encode(['success' => false, 'error' => 'Имя пользователя должно быть минимум 3 символа']);
  exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo json_encode(['success' => false, 'error' => 'Некорректный email']);
  exit;
}

if (strlen($password) < 6) {
  echo json_encode(['success' => false, 'error' => 'Пароль должен быть минимум 6 символов']);
  exit;
}

// Хешируем пароль
$password = password_hash($password, PASSWORD_DEFAULT);

try {
  $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
  $stmt->execute([
    ':username' => $username,
    ':email' => $email,
    ':password' => $password
  ]);

  echo json_encode(['success' => true, 'message' => 'Регистрация успешна!']);
} catch (PDOException $e) {
  if ($e->errorInfo[1] == 1062) {
    echo json_encode(['success' => false, 'error' => 'Пользователь с таким именем или email уже существует']);
  } else {
    echo json_encode(['success' => false, 'error' => 'Ошибка: ' . $e->getMessage()]);
  }
}

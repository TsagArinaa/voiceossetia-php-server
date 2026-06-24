<?php
header('Content-Type: application/json');
require_once "./config.php";

session_start();

// Проверяем авторизацию
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

// Проверяем, что файл загружен
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    $error_message = 'Ошибка загрузки файла';
    if (isset($_FILES['photo']['error'])) {
        switch ($_FILES['photo']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = 'Файл слишком большой (макс. 5MB)';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = 'Файл загружен частично';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = 'Файл не выбран';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message = 'Временная папка отсутствует';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message = 'Ошибка записи файла';
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message = 'Расширение файла запрещено';
                break;
        }
    }
    echo json_encode(['success' => false, 'error' => $error_message]);
    exit;
}

$file = $_FILES['photo'];

// Проверяем тип файла
$allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'error' => 'Неподдерживаемый формат. Разрешены: JPG, PNG, WEBP, GIF']);
    exit;
}

// Проверяем размер (макс 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'Файл слишком большой (макс. 5MB)']);
    exit;
}

// Генерируем имя файла
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = date('Ymd_His') . '_' . uniqid() . '.' . $ext;
$upload_dir = __DIR__ . '/../uploads/reports/';

// Создаём папку если её нет
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$filepath = $upload_dir . $filename;

// Перемещаем файл
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode([
        'success' => true,
        'message' => 'Фото загружено',
        'photo_path' => '/uploads/reports/' . $filename
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка сохранения файла'
    ]);
}
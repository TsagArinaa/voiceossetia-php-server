<?php
require_once "./config.php";

session_start();

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

try {
    $stmt = $pdo->prepare("
        SELECT 
            r.id, 
            r.category, 
            r.subtheme, 
            r.description, 
            r.address, 
            r.contact, 
            r.photo_path,
            r.status, 
            r.created_at, 
            r.updated_at,
            u.username,
            u.email
        FROM reports r
        LEFT JOIN users u ON r.user_id = u.id
        ORDER BY r.created_at DESC
    ");
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $base_url = 'http://voiceossetia.local';

    foreach ($reports as &$report) {
        $report['status_text'] = getStatusText($report['status']);
        $report['status_class'] = $report['status'];
        $report['date'] = date('d.m.Y', strtotime($report['created_at']));
        $report['user_name'] = $report['username'] ?? 'Пользователь';
        
        // ✅ Формируем URL для фото
        if (!empty($report['photo_path'])) {
            if (strpos($report['photo_path'], 'http') === 0) {
                $report['photo_url'] = $report['photo_path'];
            } else {
                $report['photo_url'] = $base_url . $report['photo_path'];
            }
        } else {
            $report['photo_url'] = null;
        }
    }

    echo json_encode([
        'success' => true,
        'reports' => $reports
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка получения заявок: ' . $e->getMessage()
    ]);
}

function getStatusText($status) {
    $map = [
        'checking' => 'На проверке',
        'in-progress' => 'В работе',
        'resolved' => 'Решено',
        'rejected' => 'Отклонено'
    ];
    return $map[$status] ?? $status;
}
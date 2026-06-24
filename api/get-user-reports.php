<?php
require_once "./config.php";

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT id, category, subtheme, description, address, contact, photo_path, photo_base64, status, created_at, updated_at
        FROM reports 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC
    ");
    $stmt->execute([':user_id' => $user_id]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ БАЗОВЫЙ URL ДЛЯ ФОТО
    $base_url = 'http://voiceossetia.local';

    foreach ($reports as &$report) {
        $report['status_text'] = getStatusText($report['status']);
        $report['status_class'] = $report['status'];
        $report['date'] = date('d.m.Y', strtotime($report['created_at']));
        
        // ✅ ФОРМИРУЕМ ПРАВИЛЬНЫЙ URL ДЛЯ ФОТО
        if (!empty($report['photo_path'])) {
            // Если путь уже содержит полный URL
            if (strpos($report['photo_path'], 'http') === 0) {
                $report['photo_url'] = $report['photo_path'];
            } else {
                // Добавляем базовый URL
                $report['photo_url'] = $base_url . $report['photo_path'];
            }
        } elseif (!empty($report['photo_base64'])) {
            // Если фото сохранено как base64
            $report['photo_url'] = $report['photo_base64'];
        } else {
            $report['photo_url'] = null;
        }
        
        // ✅ ЛОГИРУЕМ ДЛЯ ОТЛАДКИ
        error_log("Report ID: {$report['id']}, Photo URL: " . ($report['photo_url'] ?? 'null'));
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
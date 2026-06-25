<?php
require_once "./config.php";

session_start();

try {
    // Получаем параметры пагинации
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
    $offset = ($page - 1) * $limit;
    
    // ✅ Получаем заявки со статусами 'in-progress' и 'resolved'
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
        WHERE r.status IN ('in-progress', 'resolved')
        ORDER BY r.created_at DESC
        LIMIT :offset, :limit
    ");
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ Получаем общее количество
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM reports 
        WHERE status IN ('in-progress', 'resolved')
    ");
    $countStmt->execute();
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

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
        'reports' => $reports,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => (int)$total,
            'total_pages' => ceil($total / $limit)
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка получения результатов: ' . $e->getMessage()
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
<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_login = $_SESSION['user_login'];

// Получаем заявки пользователя
$stmt = $pdo->prepare("
    SELECT b.*, r.name as room_name, r.type as room_type,
           (SELECT COUNT(*) FROM reviews WHERE booking_id = b.id) as has_review
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Конференции.РФ - Личный кабинет</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏛️ Конференции.РФ</h1>
            <p>Добро пожаловать, <?= htmlspecialchars($user_login) ?>!</p>
        </div>
        <div class="content">
            <!-- СЛАЙДЕР -->
            <div id="conference-slider" style="margin-bottom: 30px;"></div>
            
            <h2>📋 Мои заявки на бронирование</h2>
            
            <?php if(empty($bookings)): ?>
                <p style="text-align:center; color:#999;">У вас пока нет заявок. Создайте первую!</p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr><th>Помещение</th><th>Тип</th><th>Дата</th><th>Оплата</th><th>Статус</th><th>Отзыв</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($bookings as $booking): ?>
                            <tr>
                                <td><?= htmlspecialchars($booking['room_name']) ?></td>
                                <td><?= htmlspecialchars($booking['room_type']) ?></td>
                                <td><?= date('d.m.Y', strtotime($booking['event_date'])) ?></td>
                                <td><?= htmlspecialchars($booking['payment_method']) ?></td>
                                <td><?= $booking['status'] ?></td>
                                <td>
                                    <?php if($booking['status'] === 'Мероприятие завершено' && $booking['has_review'] == 0): ?>
                                        <a href="add_review.php?booking_id=<?= $booking['id'] ?>" class="action-link">✍️ Оставить отзыв</a>
                                    <?php elseif($booking['has_review'] > 0): ?>
                                        <span style="color:green;">✓ Отзыв оставлен</span>
                                    <?php else: ?>
                                        <span style="color:#999;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="nav-links" style="margin-top: 20px;">
                <a href="create_booking.php" class="btn" style="display:inline-block; width:auto;">➕ Новая заявка</a>
                <a href="logout.php" class="btn btn-secondary" style="display:inline-block; width:auto;">🚪 Выйти</a>
            </div>
        </div>
    </div>
    
    <script src="assets/js/slider.js"></script>
</body>
</html>
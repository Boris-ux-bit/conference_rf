<?php
session_start();
require_once 'config.php';

// Проверка авторизации
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Обработка смены статуса
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];
    
    if ($action == 'assign') {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'Мероприятие назначено' WHERE id = ?");
        $stmt->execute([$id]);
        $msg = "Заявка #$id назначена";
    } elseif ($action == 'complete') {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'Мероприятие завершено' WHERE id = ?");
        $stmt->execute([$id]);
        $msg = "Заявка #$id завершена";
    }
}

// Получаем все заявки
$stmt = $pdo->query("
    SELECT b.*, u.login, u.full_name, r.name as room_name 
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN rooms r ON b.room_id = r.id
    ORDER BY b.created_at DESC
");
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #1a3a5c;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 20px;
        }
        h1 { color: #1a3a5c; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th { background: #f8f9fa; }
        .btn {
            display: inline-block;
            padding: 5px 10px;
            margin: 2px;
            text-decoration: none;
            border-radius: 5px;
            color: white;
            font-size: 12px;
        }
        .btn-assign { background: #17a2b8; }
        .btn-complete { background: #28a745; }
        .btn-logout {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .msg {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-new { background: #ffc107; }
        .status-assigned { background: #17a2b8; color: white; }
        .status-completed { background: #28a745; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>👑 Панель администратора</h1>
        <p>Управление заявками на бронирование</p>
        
        <?php if(isset($msg)): ?>
            <div class="msg">✅ <?= $msg ?></div>
        <?php endif; ?>
        
        <table>
            <thead>
                <tr><th>ID</th><th>Пользователь</th><th>Помещение</th><th>Дата</th><th>Статус</th><th>Действия</th></tr>
            </thead>
            <tbody>
                <?php foreach($bookings as $b): ?>
                    <?php
                        $status_class = '';
                        if ($b['status'] == 'Новая') $status_class = 'status-new';
                        elseif ($b['status'] == 'Мероприятие назначено') $status_class = 'status-assigned';
                        elseif ($b['status'] == 'Мероприятие завершено') $status_class = 'status-completed';
                    ?>
                    <tr>
                        <td><?= $b['id'] ?></td>
                        <td><?= htmlspecialchars($b['login']) ?><br><small><?= htmlspecialchars($b['full_name']) ?></small></td>
                        <td><?= htmlspecialchars($b['room_name']) ?></td>
                        <td><?= date('d.m.Y', strtotime($b['event_date'])) ?></td>
                        <td><span class="status <?= $status_class ?>"><?= $b['status'] ?></span></td>
                        <td>
                            <?php if($b['status'] == 'Новая'): ?>
                                <a href="?action=assign&id=<?= $b['id'] ?>" class="btn btn-assign" onclick="return confirm('Назначить мероприятие?')">📅 Назначить</a>
                            <?php endif; ?>
                            <?php if($b['status'] == 'Мероприятие назначено'): ?>
                                <a href="?action=complete&id=<?= $b['id'] ?>" class="btn btn-complete" onclick="return confirm('Завершить мероприятие?')">✅ Завершить</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <a href="logout.php" class="btn-logout">🚪 Выйти</a>
    </div>
</body>
</html>

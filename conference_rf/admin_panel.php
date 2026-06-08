<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$success_message = '';

// Обработка смены статуса
if (isset($_GET['change_status']) && isset($_GET['booking_id'])) {
    $new_status = $_GET['change_status'];
    $booking_id = (int)$_GET['booking_id'];
    $allowed = ['Новая', 'Мероприятие назначено', 'Мероприятие завершено'];
    
    if (in_array($new_status, $allowed)) {
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $booking_id]);
        $success_message = "Статус заявки #$booking_id изменён на «$new_status»";
    }
}

// Фильтрация
$status_filter = $_GET['status_filter'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "
    SELECT b.*, u.login, u.full_name, r.name as room_name, r.type as room_type
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN rooms r ON b.room_id = r.id
    WHERE 1=1
";
$params = [];

if ($status_filter && $status_filter !== 'all') {
    $sql .= " AND b.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $sql .= " AND (u.login LIKE ? OR u.full_name LIKE ? OR r.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Пагинация
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$count_sql = str_replace("SELECT b.*, u.login, u.full_name, r.name as room_name, r.type as room_type", "SELECT COUNT(*)", $sql);
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $limit);

$sql .= " ORDER BY b.created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Конференции.РФ - Админ-панель</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .filter-bar {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: flex-end;
        }
        .filter-group { flex: 1; min-width: 150px; }
        .filter-group label { font-size: 12px; margin-bottom: 5px; display: block; color: #666; }
        .filter-group input, .filter-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .filter-btn {
            background: #1e3c72;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            height: 38px;
        }
        .reset-btn { background: #6c757d; }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-new { background: #ffc107; color: #000; }
        .status-assigned { background: #17a2b8; color: #fff; }
        .status-completed { background: #28a745; color: #fff; }
        .admin-btn {
            display: inline-block;
            padding: 5px 10px;
            background: #1e3c72;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 11px;
            margin: 2px;
        }
        .admin-btn.assign { background: #17a2b8; }
        .admin-btn.complete { background: #28a745; }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
        }
        .pagination a, .pagination span {
            padding: 8px 12px;
            background: #f8f9fa;
            color: #1e3c72;
            text-decoration: none;
            border-radius: 8px;
        }
        .pagination .active { background: #1e3c72; color: white; }
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            z-index: 1000;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👑 Панель администратора</h1>
            <p>Управление заявками на бронирование</p>
        </div>
        <div class="content">
            <form method="GET" class="filter-bar">
                <div class="filter-group">
                    <label>🔍 Поиск</label>
                    <input type="text" name="search" placeholder="Логин, ФИО, помещение" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="filter-group">
                    <label>📊 Фильтр по статусу</label>
                    <select name="status_filter">
                        <option value="all">Все заявки</option>
                        <option value="Новая" <?= $status_filter === 'Новая' ? 'selected' : '' ?>>🟡 Новая</option>
                        <option value="Мероприятие назначено" <?= $status_filter === 'Мероприятие назначено' ? 'selected' : '' ?>>🔵 Мероприятие назначено</option>
                        <option value="Мероприятие завершено" <?= $status_filter === 'Мероприятие завершено' ? 'selected' : '' ?>>🟢 Мероприятие завершено</option>
                    </select>
                </div>
                <button type="submit" class="filter-btn">Применить</button>
                <a href="admin_panel.php" class="filter-btn reset-btn">Сбросить</a>
            </form>
            
            <?php if(count($bookings) > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr><th>ID</th><th>Пользователь</th><th>Помещение</th><th>Дата</th><th>Оплата</th><th>Статус</th><th>Действия</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($bookings as $b): ?>
                                <?php
                                    $status_class = match($b['status']) {
                                        'Новая' => 'status-new',
                                        'Мероприятие назначено' => 'status-assigned',
                                        'Мероприятие завершено' => 'status-completed',
                                        default => ''
                                    };
                                ?>
                                <tr>
                                    <td><?= $b['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($b['login']) ?></strong><br><small><?= htmlspecialchars($b['full_name']) ?></small></td>
                                    <td><?= htmlspecialchars($b['room_name']) ?><br><small><?= $b['room_type'] ?></small></td>
                                    <td><?= date('d.m.Y', strtotime($b['event_date'])) ?></td>
                                    <td><?= htmlspecialchars($b['payment_method']) ?></td>
                                    <td><span class="status-badge <?= $status_class ?>"><?= $b['status'] ?></span></td>
                                    <td>
                                        <?php if($b['status'] !== 'Мероприятие завершено'): ?>
                                            <a href="?change_status=Мероприятие назначено&booking_id=<?= $b['id'] ?>&page=<?= $page ?>" 
                                               class="admin-btn assign"
                                               onclick="return confirm('Назначить мероприятие для заявки №<?= $b['id'] ?>?')">
                                                📅 Назначить
                                            </a>
                                            <a href="?change_status=Мероприятие завершено&booking_id=<?= $b['id'] ?>&page=<?= $page ?>" 
                                               class="admin-btn complete"
                                               onclick="return confirm('Завершить мероприятие для заявки №<?= $b['id'] ?>?')">
                                                ✅ Завершить
                                            </a>
                                        <?php else: ?>
                                            <span style="color:#999;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if($total_pages > 1): ?>
                    <div class="pagination">
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if($i == $page): ?>
                                <span class="active"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?page=<?= $i ?>&status_filter=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p style="text-align:center; color:#999;">Нет заявок</p>
            <?php endif; ?>
            
            <div class="nav-links" style="margin-top: 20px;">
                <a href="logout.php" class="btn" style="display:inline-block; width:auto;">🚪 Выйти</a>
            </div>
        </div>
    </div>
    
    <?php if(isset($success_message) && $success_message): ?>
    <script>
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.innerHTML = '✅ <?= addslashes($success_message) ?>';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    </script>
    <?php endif; ?>
</body>
</html>
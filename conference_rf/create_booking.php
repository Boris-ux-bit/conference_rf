<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = false;

$stmt = $pdo->query("SELECT * FROM rooms ORDER BY type, name");
$rooms = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = $_POST['room_id'] ?? '';
    $event_date = trim($_POST['event_date'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';
    
    if (empty($room_id)) {
        $error = 'Выберите помещение';
    } elseif (empty($event_date)) {
        $error = 'Укажите дату проведения';
    } elseif (empty($payment_method)) {
        $error = 'Выберите способ оплаты';
    } else {
        if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $event_date)) {
            $parts = explode('.', $event_date);
            $event_date_db = "$parts[2]-$parts[1]-$parts[0]";
            
            $today = date('Y-m-d');
            if ($event_date_db < $today) {
                $error = 'Дата не может быть в прошлом';
            } else {
                $sql = "INSERT INTO bookings (user_id, room_id, event_date, payment_method, status) 
                        VALUES (?, ?, ?, ?, 'Новая')";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$user_id, $room_id, $event_date_db, $payment_method])) {
                    $success = true;
                } else {
                    $error = 'Ошибка при создании заявки';
                }
            }
        } else {
            $error = 'Неверный формат даты. Используйте ДД.ММ.ГГГГ';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Конференции.РФ - Новая заявка</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏛️ Конференции.РФ</h1>
            <p>Бронирование помещений для конференций</p>
        </div>
        <div class="content">
            <?php if($success): ?>
                <div class="success">
                    <h3>✅ Заявка успешно создана!</h3>
                    <p>Ваша заявка отправлена на согласование администратору.</p>
                    <a href="profile.php" class="btn">В личный кабинет</a>
                </div>
            <?php else: ?>
                <h2>📝 Новая заявка на бронирование</h2>
                
                <?php if($error): ?>
                    <div class="error">❌ <?= $error ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>🏛️ Выберите помещение</label>
                        <select name="room_id" required>
                            <option value="">-- Выберите помещение --</option>
                            <?php foreach($rooms as $room): ?>
                                <option value="<?= $room['id'] ?>">
                                    <?= htmlspecialchars($room['name']) ?> 
                                    (<?= $room['type'] ?>, 
                                     вместимость: <?= $room['capacity'] ?> чел., 
                                     <?= number_format($room['price_per_hour'], 0, '', ' ') ?> ₽/час)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>📅 Дата проведения</label>
                        <input type="text" name="event_date" id="event_date" placeholder="ДД.ММ.ГГГГ" required>
                        <small>Формат: 25.12.2024</small>
                    </div>
                    
                    <div class="form-group">
                        <label>💳 Способ оплаты</label>
                        <select name="payment_method" required>
                            <option value="">-- Выберите способ --</option>
                            <option value="Наличные">💰 Наличные</option>
                            <option value="Карта">💳 Банковская карта</option>
                            <option value="Безналичный расчёт">🏦 Безналичный расчёт</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn">🎉 Отправить заявку</button>
                    <a href="profile.php" class="btn btn-secondary" style="text-align:center;">← Назад</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Маска для ввода даты
        const dateInput = document.getElementById('event_date');
        if (dateInput) {
            dateInput.addEventListener('input', function(e) {
                let value = this.value.replace(/[^\d]/g, '');
                if (value.length >= 2 && value.length < 5) {
                    value = value.slice(0, 2) + '.' + value.slice(2);
                } else if (value.length >= 5 && value.length < 9) {
                    value = value.slice(0, 2) + '.' + value.slice(2, 4) + '.' + value.slice(4, 8);
                } else if (value.length >= 9) {
                    value = value.slice(0, 2) + '.' + value.slice(2, 4) + '.' + value.slice(4, 8);
                }
                this.value = value;
            });
        }
    </script>
</body>
</html>

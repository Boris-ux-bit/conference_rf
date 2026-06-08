<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$booking_id = (int)$_GET['booking_id'];
$user_id = $_SESSION['user_id'];

// Проверяем, что заявка принадлежит пользователю и имеет статус "Мероприятие завершено"
$stmt = $pdo->prepare("
    SELECT b.*, r.name as room_name, r.type as room_type
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE b.id = ? AND b.user_id = ? AND b.status = 'Мероприятие завершено'
");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch();

if (!$booking) {
    die("<div class='container'><div class='header'><h1>🏛️ Конференции.РФ</h1></div><div class='content'><div class='error'><h2>❌ Ошибка</h2><p>Отзыв можно оставить только после завершения мероприятия.</p><a href='profile.php' class='btn' style='display:inline-block; width:auto; margin-top:20px;'>← Вернуться</a></div></div></div>");
}

// Проверяем, нет ли уже отзыва
$stmt = $pdo->prepare("SELECT id FROM reviews WHERE booking_id = ?");
$stmt->execute([$booking_id]);
if ($stmt->fetch()) {
    die("<div class='container'><div class='header'><h1>🏛️ Конференции.РФ</h1></div><div class='content'><div class='success'><h2>ℹ️ Информация</h2><p>Вы уже оставляли отзыв для этой заявки.</p><a href='profile.php' class='btn' style='display:inline-block; width:auto; margin-top:20px;'>← Вернуться</a></div></div></div>");
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    
    if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
        $stmt = $pdo->prepare("
            INSERT INTO reviews (booking_id, user_id, rating, comment) 
            VALUES (?, ?, ?, ?)
        ");
        if ($stmt->execute([$booking_id, $user_id, $rating, $comment])) {
            $success = true;
        } else {
            $error = 'Ошибка при сохранении отзыва';
        }
    } else {
        $error = 'Пожалуйста, заполните все поля';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Конференции.РФ - Отзыв</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .rating-stars {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            justify-content: center;
        }
        .star {
            font-size: 40px;
            cursor: pointer;
            color: #ddd;
            transition: all 0.2s ease;
        }
        .star:hover, .star.active {
            color: #ffc107;
            transform: scale(1.1);
        }
        .booking-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        .booking-info p {
            margin: 5px 0;
        }
        .rating-label {
            text-align: center;
            margin-bottom: 10px;
            font-weight: bold;
            color: #333;
        }
        textarea {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }
        .review-form {
            max-width: 400px;
            margin: 0 auto;
        }
        .star-rating-text {
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏛️ Конференции.РФ</h1>
            <p>Оставить отзыв о мероприятии</p>
        </div>
        <div class="content">
            <?php if($success): ?>
                <div class="success">
                    <h3>✅ Спасибо за отзыв!</h3>
                    <p>Ваше мнение очень важно для нас.</p>
                    <br>
                    <a href="profile.php" class="btn" style="display: inline-block; width: auto; padding: 10px 30px;">📋 Вернуться в личный кабинет</a>
                </div>
            <?php else: ?>
                <div class="booking-info">
                    <p><strong>🏛️ Мероприятие:</strong> <?= htmlspecialchars($booking['room_name']) ?></p>
                    <p><strong>📅 Дата проведения:</strong> <?= date('d.m.Y', strtotime($booking['event_date'])) ?></p>
                    <p><strong>📊 Статус:</strong> <?= $booking['status'] ?></p>
                </div>
                
                <?php if($error): ?>
                    <div class="error">❌ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="POST" class="review-form" id="reviewForm">
                    <div class="form-group">
                        <div class="rating-label">⭐ Ваша оценка</div>
                        <div class="rating-stars" id="ratingStars">
                            <span class="star" data-value="1">★</span>
                            <span class="star" data-value="2">★</span>
                            <span class="star" data-value="3">★</span>
                            <span class="star" data-value="4">★</span>
                            <span class="star" data-value="5">★</span>
                        </div>
                        <input type="hidden" name="rating" id="ratingValue" required>
                        <div class="star-rating-text" id="ratingText">Нажмите на звезду, чтобы оценить</div>
                    </div>
                    
                    <div class="form-group">
                        <label>💬 Ваш отзыв</label>
                        <textarea name="comment" rows="5" placeholder="Расскажите о своём опыте: как прошло мероприятие, понравилось ли помещение, обслуживание, атмосфера..." required></textarea>
                    </div>
                    
                    <button type="submit" class="btn" id="submitBtn">✍️ Отправить отзыв</button>
                    <a href="profile.php" class="btn btn-secondary" style="text-align: center; display: block; margin-top: 10px;">← Отмена</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Интерактивные звёзды для рейтинга
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('ratingValue');
        const ratingText = document.getElementById('ratingText');
        
        const ratingMessages = {
            1: '😞 Очень плохо',
            2: '😐 Плохо',
            3: '🙂 Нормально',
            4: '😊 Хорошо',
            5: '🤩 Отлично!'
        };
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const value = this.dataset.value;
                ratingInput.value = value;
                
                // Обновляем текст
                if (ratingText) {
                    ratingText.innerHTML = ratingMessages[value] || 'Вы выбрали ' + value + ' звезд';
                }
                
                // Обновляем звёзды
                stars.forEach((s, index) => {
                    if (index < value) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
            
            star.addEventListener('mouseenter', function() {
                const value = this.dataset.value;
                stars.forEach((s, index) => {
                    if (index < value) {
                        s.style.color = '#ffc107';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
            
            star.addEventListener('mouseleave', function() {
                const currentValue = ratingInput.value;
                stars.forEach((s, index) => {
                    if (currentValue && index < currentValue) {
                        s.style.color = '#ffc107';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });
        
        // Валидация перед отправкой
        const form = document.getElementById('reviewForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                const rating = ratingInput.value;
                const comment = document.querySelector('textarea[name="comment"]').value.trim();
                
                if (!rating) {
                    e.preventDefault();
                    alert('Пожалуйста, поставьте оценку (выберите количество звёзд)');
                    return false;
                }
                
                if (!comment) {
                    e.preventDefault();
                    alert('Пожалуйста, напишите отзыв');
                    return false;
                }
                
                // Анимация кнопки
                const btn = document.getElementById('submitBtn');
                btn.innerHTML = '⏳ Отправка...';
                btn.classList.add('btn-loading');
                btn.disabled = true;
            });
        }
    </script>
</body>
</html>
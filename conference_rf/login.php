<?php
session_start();

// ОЧИЩАЕМ СЕССИЮ ПРИ ЗАГРУЗКЕ СТРАНИЦЫ ВХОДА
// Это гарантирует, что никакие старые данные не останутся
session_unset();
session_destroy();
session_start();

require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($login) || empty($password)) {
        $error = 'Заполните все поля';
    } else {
        // Ищем пользователя в БД
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Проверяем пароль
            if (password_verify($password, $user['password'])) {
                // Очищаем сессию ещё раз перед записью новых данных
                session_unset();
                
                // Записываем данные ТОЛЬКО этого пользователя
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_login'] = $user['login'];
                $_SESSION['user_role'] = $user['role'];
                
                // Перенаправляем в зависимости от роли
                if ($user['role'] === 'admin') {
                    header('Location: admin_panel.php');
                } else {
                    header('Location: profile.php');
                }
                exit;
            } else {
                $error = 'Неверный пароль';
            }
        } else {
            $error = 'Пользователь не найден';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Конференции.РФ - Вход</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .admin-hint {
            background: #e8f0fe;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            margin-top: 20px;
        }
        .error-box {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏛️ Конференции.РФ</h1>
            <p>Бронирование помещений для конференций</p>
        </div>
        <div class="content">
            <h2 style="text-align: center;">🔑 Вход в систему</h2>
            
            <?php if($error): ?>
                <div class="error-box">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>👤 Логин</label>
                    <input type="text" name="login" required>
                </div>
                
                <div class="form-group">
                    <label>🔒 Пароль</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit" class="btn">Войти</button>
                
                <div class="nav-links">
                    <a href="register.php">📝 Ещё не зарегистрированы? Регистрация</a>
                </div>
            </form>
            
            <div class="admin-hint">
                👑 <strong>Администратор:</strong> Логин: <strong>Admin26</strong> / Пароль: <strong>Demo20</strong>
            </div>
        </div>
    </div>
</body>
</html>
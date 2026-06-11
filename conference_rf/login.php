<?php
session_start();
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // ПРЯМАЯ ПРОВЕРКА ДЛЯ АДМИНИСТРАТОРА
    if ($login === 'Admin26' && $password === 'Demo20') {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_login'] = 'Admin26';
        $_SESSION['user_role'] = 'admin';
        header('Location: admin2.php');  // <--- ИЗМЕНЕНО!
        exit;
    }
    
    // Обычная проверка для остальных пользователей
    if (empty($login) || empty($password)) {
        $error = 'Заполните все поля';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();
        
        if ($user) {
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_login'] = $user['login'];
                $_SESSION['user_role'] = $user['role'];
                
                if ($user['role'] === 'admin') {
                    header('Location: admin2.php');  // <--- ИЗМЕНЕНО!
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
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-top: 25px;
        }
        .error-box {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }
        .login-form {
            max-width: 400px;
            margin: 0 auto;
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
            <div class="login-form">
                <h2 style="text-align: center;">🔑 Вход в систему</h2>
                
                <?php if($error): ?>
                    <div class="error-box">❌ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>👤 Логин</label>
                        <input type="text" name="login" placeholder="Введите логин" required>
                    </div>
                    
                    <div class="form-group">
                        <label>🔒 Пароль</label>
                        <input type="password" name="password" placeholder="Введите пароль" required>
                    </div>
                    
                    <button type="submit" class="btn">🚪 Войти</button>
                    
                    <div class="nav-links" style="text-align: center; margin-top: 15px;">
                        <a href="register.php">📝 Ещё не зарегистрированы? Регистрация</a>
                    </div>
                </form>
                
                <div class="admin-hint">
                    👑 <strong>Тестовые данные:</strong><br>
                    Администратор: <strong>Admin26</strong> / <strong>Demo20</strong><br>
                    Обычный пользователь: зарегистрируйтесь сами
                </div>
            </div>
        </div>
    </div>
</body>
</html>

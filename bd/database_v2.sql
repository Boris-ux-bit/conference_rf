-- =====================================================
-- БАЗА ДАННЫХ ДЛЯ ПОРТАЛА «Конференции.РФ»
-- Вариант №2
-- =====================================================

-- Создание базы данных
CREATE DATABASE IF NOT EXISTS conference_rf;
USE conference_rf;

-- =====================================================
-- 1. ТАБЛИЦА users (пользователи)
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    login VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 2. ТАБЛИЦА rooms (помещения для конференций)
-- =====================================================
CREATE TABLE IF NOT EXISTS rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    capacity INT,
    price_per_hour DECIMAL(10, 2)
);

-- =====================================================
-- 3. ТАБЛИЦА bookings (заявки на бронирование)
-- =====================================================
CREATE TABLE IF NOT EXISTS bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    event_date DATE NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    status ENUM('Новая', 'Мероприятие назначено', 'Мероприятие завершено') DEFAULT 'Новая',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

-- =====================================================
-- 4. ТАБЛИЦА reviews (отзывы)
-- =====================================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- =====================================================
-- ТЕСТОВЫЕ ДАННЫЕ
-- =====================================================

-- Добавление помещений для конференций
INSERT IGNORE INTO rooms (id, name, type, capacity, price_per_hour) VALUES
(1, 'Аудитория 101', 'аудитория', 50, 5000),
(2, 'Коворкинг Центр', 'коворкинг', 30, 8000),
(3, 'Кинозал Медиа', 'кинозал', 100, 15000),
(4, 'Аудитория 202', 'аудитория', 40, 4000),
(5, 'Коворкинг Про', 'коворкинг', 60, 12000),
(6, 'Кинозал VIP', 'кинозал', 50, 20000),
(7, 'Конференц-зал Пленарный', 'аудитория', 200, 30000),
(8, 'Переговорная', 'коворкинг', 10, 2000);

-- Добавление администратора (пароль: Demo20)
-- Хеш пароля Demo20: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT IGNORE INTO users (id, login, password, full_name, phone, email, role) 
VALUES (1, 'Admin26', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
        'Администратор', '+7 (000) 000-00-00', 'admin@conference.ru', 'admin');

-- =====================================================
-- КОНЕЦ ФАЙЛА
-- =====================================================
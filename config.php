<?php
// config.php - Конфигурация подключения к базе данных
$host = 'localhost';
$dbname = 'u82461';
$username = 'u82461';
$password = '3874492';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Секретный ключ для JWT (используется в login.php и edit.php)
define('SECRET_KEY', 'your-secret-key-here-change-it-2026');
function authenticateAdmin() {
    if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
        header('WWW-Authenticate: Basic realm="Admin Area"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Требуется авторизация';
        exit;
    }
    
    global $pdo;
    $login = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];
    
    $stmt = $pdo->prepare("SELECT password_hash FROM admins WHERE login = :login");
    $stmt->execute([':login' => $login]);
    $admin = $stmt->fetch();
    
    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        header('WWW-Authenticate: Basic realm="Admin Area"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Неверный логин или пароль';
        exit;
    }
    
    return $login;
}
?>
<?php
// login.php - Страница входа
session_start();
require_once 'config.php';

// Если уже авторизован — перенаправляем на форму
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// Обработка POST-запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($login) || empty($password)) {
        $error = 'Введите логин и пароль.';
    } else {
        // Ищем пользователя по логину
        $stmt = $pdo->prepare("SELECT id, full_name, password_hash FROM applications WHERE login = :login");
        $stmt->execute([':login' => $login]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Успешный вход — создаём сессию
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            
            // Перенаправляем на форму
            header('Location: index.php');
            exit;
        } else {
            $error = 'Неверный логин или пароль.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — Лабораторная работа №5</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 24px;
            box-shadow: 0 8px 20px rgba(240, 98, 146, 0.1);
        }
        .login-container h2 {
            color: #d81b60;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .login-error {
            background: #ffebee;
            color: #c62828;
            padding: 0.75rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .login-btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #f06292, #d81b60);
            color: white;
            border: none;
            border-radius: 40px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .login-btn:hover {
            transform: translateY(-2px);
        }
        .register-link {
            text-align: center;
            margin-top: 1rem;
        }
        .register-link a {
            color: #d81b60;
            text-decoration: none;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
           
            <h2>Задание 5. Вход в систему</h2>
        </div>
    </header>

    <main class="container">
        <div class="login-container">
            <h2>🔐 Вход</h2>
            
            <?php if ($error): ?>
                <div class="login-error">❌ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="login">Логин</label>
                    <input type="text" id="login" name="login" required placeholder="Введите логин">
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required placeholder="Введите пароль">
                </div>
                
                <button type="submit" class="login-btn">🔑 Войти</button>
            </form>
            
            <div class="register-link">
                <p>Нет аккаунта? <a href="index.php">Заполните форму</a> — логин и пароль сгенерируются автоматически.</p>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>Лабораторная работа №5 — Авторизация с сессиями | Май 2026</p>
        </div>
    </footer>
</body>
</html>
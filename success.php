<?php

$id = $_GET['id'] ?? '';

// Очищаем Cookies ошибок (они больше не нужны)
foreach (['full_name', 'phone', 'email', 'birth_date', 'gender', 'languages', 'biography', 'contract_accepted'] as $field) {
    setcookie("error_$field", "", time() - 3600, '/');
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Успешное сохранение — Лабораторная работа №4</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .success-message {
            background-color: #e8f5e9;
            border-left: 5px solid #4caf50;
            padding: 1.5rem;
            border-radius: 20px;
            text-align: center;
        }
        .success-message .id {
            font-size: 1.2rem;
            color: #d81b60;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
           
           
            <h2>Задание 4. Данные успешно сохранены</h2>
        </div>
    </header>
    <main class="container">
        <div class="success-message">
            <h2>✅ Данные успешно сохранены!</h2>
            <?php if ($id): ?>
                <p>ID вашей записи: <span class="id"><?php echo htmlspecialchars($id); ?></span></p>
            <?php endif; ?>
            <p>Данные сохранены в Cookies на 1 год.</p>
        </div>
        <div class="action-buttons">
            <a href="index.php" class="action-btn">📝 Заполнить новую анкету</a>
            <a href="list.php" class="action-btn">📋 Посмотреть все анкеты</a>
        </div>
    </main>
    <footer>
        <div class="container">
            <p>Лабораторная работа №4 — Cookies | Май 2026</p>
        </div>
    </footer>
</body>
</html>
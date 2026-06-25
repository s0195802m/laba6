<?php
// admin_edit.php - Редактирование анкеты администратором
require_once 'config.php';

// Проверка HTTP-авторизации
$adminLogin = authenticateAdmin();

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: admin.php');
    exit;
}

// Получаем данные анкеты
$stmt = $pdo->prepare("SELECT * FROM applications WHERE id = :id");
$stmt->execute([':id' => $id]);
$application = $stmt->fetch();

if (!$application) {
    header('Location: admin.php');
    exit;
}

// Получаем языки анкеты
$langStmt = $pdo->prepare("
    SELECT pl.name FROM application_languages al
    JOIN programming_languages pl ON al.language_id = pl.id
    WHERE al.application_id = :id
");
$langStmt->execute([':id' => $id]);
$userLanguages = $langStmt->fetchAll(PDO::FETCH_COLUMN);

$allowed_languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
$errors = [];

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $languages = $_POST['languages'] ?? [];
    $biography = trim($_POST['biography'] ?? '');
    $contract_accepted = isset($_POST['contract_accepted']) ? 1 : 0;
    
    // Валидация
    if (empty($full_name)) {
        $errors['full_name'] = "ФИО обязательно";
    } elseif (strlen($full_name) > 150) {
        $errors['full_name'] = "ФИО не длиннее 150 символов";
    } elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u', $full_name)) {
        $errors['full_name'] = "Только буквы, пробелы и дефис";
    }
    
    $phone_clean = preg_replace('/[^0-9+]/', '', $phone);
    if (empty($phone_clean)) {
        $errors['phone'] = "Телефон обязателен";
    } elseif (!preg_match('/^(\+7|8)[0-9]{10}$/', $phone_clean)) {
        $errors['phone'] = "Формат +7XXXXXXXXXX или 8XXXXXXXXXX";
    }
    
    if (empty($email)) {
        $errors['email'] = "Email обязателен";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Некорректный email";
    }
    
    if (empty($birth_date)) {
        $errors['birth_date'] = "Дата обязательна";
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $birth_date);
        if (!$date || $date->format('Y-m-d') !== $birth_date) {
            $errors['birth_date'] = "Формат ГГГГ-ММ-ДД";
        } elseif ($date > new DateTime()) {
            $errors['birth_date'] = "Дата не может быть в будущем";
        }
    }
    
    if (!in_array($gender, ['male', 'female'])) {
        $errors['gender'] = "Выберите пол";
    }
    
    if (empty($languages)) {
        $errors['languages'] = "Выберите хотя бы один язык";
    }
    
    if (!$contract_accepted) {
        $errors['contract_accepted'] = "Подтвердите согласие с контрактом";
    }
    
    // Если нет ошибок — сохраняем
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Обновление анкеты
            $sql = "UPDATE applications SET 
                    full_name = :full_name,
                    phone = :phone,
                    email = :email,
                    birth_date = :birth_date,
                    gender = :gender,
                    biography = :biography,
                    contract_accepted = :contract_accepted
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':full_name' => $full_name,
                ':phone' => $phone,
                ':email' => $email,
                ':birth_date' => $birth_date,
                ':gender' => $gender,
                ':biography' => $biography,
                ':contract_accepted' => $contract_accepted,
                ':id' => $id
            ]);
            
            // Удаляем старые языки
            $pdo->prepare("DELETE FROM application_languages WHERE application_id = :id")->execute([':id' => $id]);
            
            // Вставляем новые языки
            $langStmt = $pdo->prepare("SELECT id FROM programming_languages WHERE name = :name");
            $linkStmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (:app, :lang)");
            foreach ($languages as $lang) {
                $langStmt->execute([':name' => $lang]);
                $row = $langStmt->fetch();
                if ($row) {
                    $linkStmt->execute([':app' => $id, ':lang' => $row['id']]);
                }
            }
            
            $pdo->commit();
            
            header('Location: admin.php?updated=1');
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['general'] = "Ошибка БД: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование — Админ-панель</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .error-border { border: 2px solid #f44336 !important; background: #ffebee !important; }
        .field-error { color: #f44336; font-size: 0.8rem; display: block; margin-top: 0.25rem; }
        .admin-edit-container { max-width: 800px; margin: 0 auto; }
        .btn-back { background: #b0bec5; color: white; padding: 0.75rem 1.5rem; border-radius: 40px; text-decoration: none; display: inline-block; }
        .btn-back:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>📡 Программно-аппаратные средства Web</h1>
            <p class="student-info">Администратор: <?php echo htmlspecialchars($adminLogin); ?></p>
        </div>
    </header>

    <main class="container admin-edit-container">
        <a href="admin.php" class="btn-back">← Назад к списку</a>
        
        <?php if (!empty($errors)): ?>
            <div class="error-summary" style="background:#ffebee; border-left:5px solid #f44336; padding:1rem; border-radius:12px; margin:1rem 0;">
                <strong>❌ Ошибки:</strong>
                <ul><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="application-form" style="margin-top:1rem;">
            <div class="form-group">
                <label>ФИО <span class="required">*</span></label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($application['full_name']); ?>"
                       class="<?php echo isset($errors['full_name']) ? 'error-border' : ''; ?>">
                <?php if (isset($errors['full_name'])): ?>
                    <span class="field-error">⚠️ <?php echo $errors['full_name']; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Телефон <span class="required">*</span></label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($application['phone']); ?>"
                       class="<?php echo isset($errors['phone']) ? 'error-border' : ''; ?>">
                <?php if (isset($errors['phone'])): ?>
                    <span class="field-error">⚠️ <?php echo $errors['phone']; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Email <span class="required">*</span></label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($application['email']); ?>"
                       class="<?php echo isset($errors['email']) ? 'error-border' : ''; ?>">
                <?php if (isset($errors['email'])): ?>
                    <span class="field-error">⚠️ <?php echo $errors['email']; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Дата рождения <span class="required">*</span></label>
                <input type="date" name="birth_date" value="<?php echo htmlspecialchars($application['birth_date']); ?>"
                       class="<?php echo isset($errors['birth_date']) ? 'error-border' : ''; ?>">
                <?php if (isset($errors['birth_date'])): ?>
                    <span class="field-error">⚠️ <?php echo $errors['birth_date']; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Пол <span class="required">*</span></label>
                <div class="radio-group">
                    <label><input type="radio" name="gender" value="male" <?php echo $application['gender'] == 'male' ? 'checked' : ''; ?>> Мужской</label>
                    <label><input type="radio" name="gender" value="female" <?php echo $application['gender'] == 'female' ? 'checked' : ''; ?>> Женский</label>
                </div>
                <?php if (isset($errors['gender'])): ?>
                    <span class="field-error">⚠️ <?php echo $errors['gender']; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Языки <span class="required">*</span></label>
                <select name="languages[]" multiple size="6" class="<?php echo isset($errors['languages']) ? 'error-border' : ''; ?>">
                    <?php foreach ($allowed_languages as $lang): ?>
                        <option value="<?php echo $lang; ?>" <?php echo in_array($lang, $userLanguages) ? 'selected' : ''; ?>>
                            <?php echo $lang; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['languages'])): ?>
                    <span class="field-error">⚠️ <?php echo $errors['languages']; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Биография</label>
                <textarea name="biography" rows="5"><?php echo htmlspecialchars($application['biography'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="contract_accepted" value="1" <?php echo $application['contract_accepted'] ? 'checked' : ''; ?>>
                    С контрактом ознакомлен(а) <span class="required">*</span>
                </label>
                <?php if (isset($errors['contract_accepted'])): ?>
                    <span class="field-error">⚠️ <?php echo $errors['contract_accepted']; ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="submit-btn">💾 Сохранить изменения</button>
        </form>
    </main>

    <footer>
        <div class="container">
            <p>Лабораторная работа №6 — Панель администратора | Май 2026</p>
        </div>
    </footer>
</body>
</html>

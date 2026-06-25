<?php
// index.php - Форма с валидацией, Cookies и авторизацией
session_start();
require_once 'config.php';

// Функция для получения значения из Cookies или GET
function getValue($fieldName, $default = '') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET[$fieldName]) && $_GET[$fieldName] !== '') {
        return htmlspecialchars(trim($_GET[$fieldName]));
    }
    if (isset($_COOKIE['form_' . $fieldName])) {
        return htmlspecialchars($_COOKIE['form_' . $fieldName]);
    }
    return $default;
}

// Функция для получения ошибки из Cookies
function getError($fieldName) {
    if (isset($_COOKIE['error_' . $fieldName])) {
        return $_COOKIE['error_' . $fieldName];
    }
    return '';
}

// Функция для проверки наличия ошибки
function hasError($fieldName) {
    return isset($_COOKIE['error_' . $fieldName]);
}

// Если пользователь авторизован — загружаем его данные из БД
$userData = null;
$userLanguages = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $userData = $stmt->fetch();
    
    if ($userData) {
        // Загружаем языки пользователя
        $langStmt = $pdo->prepare("
            SELECT pl.name 
            FROM application_languages al
            JOIN programming_languages pl ON al.language_id = pl.id
            WHERE al.application_id = :id
        ");
        $langStmt->execute([':id' => $_SESSION['user_id']]);
        $userLanguages = $langStmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Получаем значения полей (приоритет: GET > Cookie > БД)
$full_name = getValue('full_name', $userData['full_name'] ?? '');
$phone = getValue('phone', $userData['phone'] ?? '');
$email = getValue('email', $userData['email'] ?? '');
$birth_date = getValue('birth_date', $userData['birth_date'] ?? '');
$gender = getValue('gender', $userData['gender'] ?? '');

// Языки: из Cookie или БД
$languages = [];
if (isset($_COOKIE['form_languages']) && $_COOKIE['form_languages'] !== '') {
    $languages = explode(',', $_COOKIE['form_languages']);
} elseif (!empty($userLanguages)) {
    $languages = $userLanguages;
}
if (isset($_GET['languages']) && is_array($_GET['languages'])) {
    $languages = $_GET['languages'];
}

$biography = getValue('biography', $userData['biography'] ?? '');
$contract_accepted = (isset($_COOKIE['form_contract_accepted']) && $_COOKIE['form_contract_accepted'] == '1') ||
                     (isset($_GET['contract_accepted']) && $_GET['contract_accepted'] == '1') ||
                     ($userData && $userData['contract_accepted'] == 1);

$allowed_languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Лабораторная работа №5 — Форма с авторизацией</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .error-border {
            border: 2px solid #f44336 !important;
            background-color: #ffebee !important;
        }
        .field-error {
            color: #f44336;
            font-size: 0.8rem;
            margin-top: 0.25rem;
            display: block;
        }
        .error-summary {
            background-color: #ffebee;
            border-left: 5px solid #f44336;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 12px;
        }
        .user-info {
            background-color: #e8f5e9;
            border-left: 5px solid #4caf50;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .user-info .logout-btn {
            background: #f44336;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .user-info .logout-btn:hover {
            background: #c62828;
        }
        .credentials-box {
            background: #fff3e0;
            border: 2px dashed #ff9800;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .credentials-box .login-cred {
            font-weight: bold;
            color: #e65100;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    

    <main class="container">
        <section class="intro">
            <p>Заполните форму ниже. При первой отправке генерируются логин и пароль. <br>
            <?php if (isset($_SESSION['user_id'])): ?>
                Вы авторизованы как <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
            <?php else: ?>
                <a href="login.php">Войдите</a>, чтобы редактировать свои данные.
            <?php endif; ?>
            </p>
        </section>

        <!-- Информация о пользователе -->
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="user-info">
            <span>👤 Вы вошли как <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
            <a href="logout.php" class="logout-btn">🚪 Выйти</a>
        </div>
        <?php endif; ?>

        <!-- Показываем логин/пароль, если они ещё не были показаны -->
        <?php if (isset($_GET['new_login']) && isset($_GET['new_password']) && isset($_GET['credentials_shown']) && $_GET['credentials_shown'] == '0'): ?>
        <div class="credentials-box" id="credentialsBox">
            <p><strong>✅ Ваши данные для входа сохранены!</strong></p>
            <p>Запишите их — они понадобятся для редактирования анкеты:</p>
            <p class="login-cred">🔑 Логин: <strong><?php echo htmlspecialchars($_GET['new_login']); ?></strong></p>
            <p class="login-cred">🔒 Пароль: <strong><?php echo htmlspecialchars($_GET['new_password']); ?></strong></p>
            <p style="font-size: 0.8rem; color: #666;">* Пароль отображается только один раз. Сохраните его!</p>
        </div>
        <?php endif; ?>

        <!-- Сообщения об ошибках из Cookies -->
        <?php
        $error_fields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'languages', 'contract_accepted'];
        $error_messages = [];
        foreach ($error_fields as $field) {
            $err = getError($field);
            if (!empty($err)) $error_messages[] = $err;
        }
        if (!empty($error_messages)): ?>
        <div class="error-summary">
            <strong>❌ Исправьте следующие ошибки:</strong>
            <ul>
                <?php foreach ($error_messages as $msg): ?>
                    <li><?php echo htmlspecialchars($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Форма -->
        <form action="process.php" method="GET" class="application-form">
            <input type="hidden" name="edit_id" value="<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>">

            <!-- 1. ФИО -->
            <div class="form-group">
                <label for="full_name">ФИО <span class="required">*</span></label>
                <input type="text" id="full_name" name="full_name" 
                       class="<?php echo hasError('full_name') ? 'error-border' : ''; ?>"
                       value="<?php echo $full_name; ?>">
                <small>Только буквы, пробелы и дефис, не более 150 символов</small>
                <?php if (hasError('full_name')): ?>
                    <span class="field-error">⚠️ <?php echo htmlspecialchars(getError('full_name')); ?></span>
                <?php endif; ?>
            </div>

            <!-- 2. Телефон -->
            <div class="form-group">
                <label for="phone">Телефон <span class="required">*</span></label>
                <input type="tel" id="phone" name="phone"
                       class="<?php echo hasError('phone') ? 'error-border' : ''; ?>"
                       value="<?php echo $phone; ?>">
                <small>Формат: +7XXXXXXXXXX или 8XXXXXXXXXX (только цифры и +)</small>
                <?php if (hasError('phone')): ?>
                    <span class="field-error">⚠️ <?php echo htmlspecialchars(getError('phone')); ?></span>
                <?php endif; ?>
            </div>

            <!-- 3. Email -->
            <div class="form-group">
                <label for="email">E-mail <span class="required">*</span></label>
                <input type="email" id="email" name="email"
                       class="<?php echo hasError('email') ? 'error-border' : ''; ?>"
                       value="<?php echo $email; ?>">
                <small>example@domain.ru</small>
                <?php if (hasError('email')): ?>
                    <span class="field-error">⚠️ <?php echo htmlspecialchars(getError('email')); ?></span>
                <?php endif; ?>
            </div>

            <!-- 4. Дата рождения -->
            <div class="form-group">
                <label for="birth_date">Дата рождения <span class="required">*</span></label>
                <input type="date" id="birth_date" name="birth_date"
                       class="<?php echo hasError('birth_date') ? 'error-border' : ''; ?>"
                       value="<?php echo $birth_date; ?>">
                <small>Формат: ГГГГ-ММ-ДД, возраст не более 120 лет</small>
                <?php if (hasError('birth_date')): ?>
                    <span class="field-error">⚠️ <?php echo htmlspecialchars(getError('birth_date')); ?></span>
                <?php endif; ?>
            </div>

            <!-- 5. Пол -->
            <div class="form-group">
                <label>Пол <span class="required">*</span></label>
                <div class="radio-group">
                    <label><input type="radio" name="gender" value="male" 
                        <?php echo ($gender == 'male') ? 'checked' : ''; ?>> Мужской</label>
                    <label><input type="radio" name="gender" value="female" 
                        <?php echo ($gender == 'female') ? 'checked' : ''; ?>> Женский</label>
                </div>
                <?php if (hasError('gender')): ?>
                    <span class="field-error">⚠️ <?php echo htmlspecialchars(getError('gender')); ?></span>
                <?php endif; ?>
            </div>

            <!-- 6. Языки -->
            <div class="form-group">
                <label for="languages">Любимый язык программирования <span class="required">*</span></label>
                <select name="languages[]" id="languages" multiple size="6" 
                        class="<?php echo hasError('languages') ? 'error-border' : ''; ?>">
                    <?php foreach ($allowed_languages as $lang): ?>
                        <option value="<?php echo $lang; ?>" 
                            <?php echo (in_array($lang, $languages)) ? 'selected' : ''; ?>>
                            <?php echo $lang; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>Зажмите Ctrl (или Cmd на Mac) для выбора нескольких языков</small>
                <?php if (hasError('languages')): ?>
                    <span class="field-error">⚠️ <?php echo htmlspecialchars(getError('languages')); ?></span>
                <?php endif; ?>
            </div>

            <!-- 7. Биография -->
            <div class="form-group">
                <label for="biography">Биография</label>
                <textarea id="biography" name="biography" rows="5"><?php echo $biography; ?></textarea>
                <small>Не более 5000 символов</small>
                <?php if (hasError('biography')): ?>
                    <span class="field-error">⚠️ <?php echo htmlspecialchars(getError('biography')); ?></span>
                <?php endif; ?>
            </div>

            <!-- 8. Чекбокс -->
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="contract_accepted" value="1"
                        <?php echo $contract_accepted ? 'checked' : ''; ?>
                        class="<?php echo hasError('contract_accepted') ? 'error-border' : ''; ?>">
                    С контрактом ознакомлен(а) <span class="required">*</span>
                </label>
                <?php if (hasError('contract_accepted')): ?>
                    <span class="field-error">⚠️ <?php echo htmlspecialchars(getError('contract_accepted')); ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="submit-btn">✅ Сохранить</button>
        </form>

        <div class="action-buttons">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="login.php" class="action-btn">🔐 Войти</a>
            <?php endif; ?>
            <a href="list.php" class="action-btn">📋 Анкеты</a>
           
    <a href="admin.php" class="action-btn secondary">👑 Админ-панель</a>
   


        </div>
    </main>

    <footer>
        <div class="container">
            <p>Лабораторная работа №5 — Авторизация с сессиями | Май 2026</p>
        </div>
    </footer>
</body>
</html>
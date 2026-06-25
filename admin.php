<?php
// admin.php - Панель администратора
require_once 'config.php';

// Проверка HTTP-авторизации
$adminLogin = authenticateAdmin();

// Получаем все анкеты
$stmt = $pdo->prepare("
    SELECT a.*, 
           GROUP_CONCAT(pl.name ORDER BY pl.name SEPARATOR ', ') as languages
    FROM applications a
    LEFT JOIN application_languages al ON a.id = al.application_id
    LEFT JOIN programming_languages pl ON al.language_id = pl.id
    GROUP BY a.id
    ORDER BY a.created_at DESC
");
$stmt->execute();
$applications = $stmt->fetchAll();

// Получаем статистику по языкам
$statsStmt = $pdo->prepare("
    SELECT pl.name, COUNT(al.application_id) as count
    FROM programming_languages pl
    LEFT JOIN application_languages al ON pl.id = al.language_id
    GROUP BY pl.id
    ORDER BY count DESC
");
$statsStmt->execute();
$languageStats = $statsStmt->fetchAll();

// Общее количество анкет
$totalApplications = count($applications);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора — Лабораторная работа №6</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            background: white;
            padding: 1rem 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .admin-header .admin-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .admin-header .badge-admin {
            background: #4caf50;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1rem;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }
        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: #d81b60;
        }
        .stat-card .label {
            color: #9b4b6e;
            font-size: 0.9rem;
        }
        .stat-card .lang-bar {
            margin-top: 0.5rem;
            height: 6px;
            background: #ffccd9;
            border-radius: 3px;
            overflow: hidden;
        }
        .stat-card .lang-bar .fill {
            height: 100%;
            background: linear-gradient(90deg, #f06292, #d81b60);
            border-radius: 3px;
            transition: width 0.5s;
        }
        .table-wrapper {
            overflow-x: auto;
            background: white;
            border-radius: 20px;
            padding: 1rem;
            box-shadow: 0 8px 20px rgba(240,98,146,0.1);
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }
        .admin-table th {
            background: linear-gradient(135deg, #f8b0c0, #f48fb1);
            color: white;
            padding: 0.75rem 1rem;
            text-align: left;
            position: sticky;
            top: 0;
        }
        .admin-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #ffccd9;
            vertical-align: middle;
        }
        .admin-table tr:hover {
            background: #fff5f7;
        }
        .badge {
            background: #f06292;
            color: white;
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            display: inline-block;
            margin: 0.1rem;
        }
        .btn-admin-edit {
            background: #2196f3;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.75rem;
            display: inline-block;
        }
        .btn-admin-edit:hover {
            background: #1976d2;
        }
        .btn-admin-delete {
            background: #f44336;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.75rem;
            display: inline-block;
            border: none;
            cursor: pointer;
        }
        .btn-admin-delete:hover {
            background: #c62828;
        }
        .btn-back {
            background: linear-gradient(135deg, #b0bec5, #90a4ae);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 40px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
        }
        .btn-back:hover {
            transform: translateY(-2px);
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #9b4b6e;
        }
        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
    </style>
</head>
<body>
   

    <main class="admin-container">
        <!-- Информация об администраторе -->
        <div class="admin-header">
            <div class="admin-info">
                <span style="font-size:1.5rem;">👑</span>
                <span><strong>Администратор:</strong> <?php echo htmlspecialchars($adminLogin); ?></span>
                <span class="badge-admin">✅ Авторизован</span>
            </div>
            <div>
                <a href="index.php" class="btn-back" style="margin-right: 0.5rem;">📝 Форма</a>
                <a href="list.php" class="btn-back">📋 Анкеты</a>
            </div>
        </div>

        <!-- Статистика -->
        <h3 style="color: #d81b60; margin-bottom: 1rem;">📊 Статистика</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="number"><?php echo $totalApplications; ?></div>
                <div class="label">Всего анкет</div>
            </div>
            <?php foreach ($languageStats as $stat): ?>
            <div class="stat-card">
                <div class="number" style="font-size:1.5rem;"><?php echo $stat['count']; ?></div>
                <div class="label"><?php echo htmlspecialchars($stat['name']); ?></div>
                <div class="lang-bar">
                    <div class="fill" style="width: <?php echo $totalApplications > 0 ? ($stat['count'] / $totalApplications * 100) : 0; ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Таблица анкет -->
        <h3 style="color: #d81b60; margin-bottom: 1rem;">📋 Все анкеты</h3>
        <div class="table-wrapper">
            <?php if (empty($applications)): ?>
                <div class="empty-state">
                    <p>😕 Нет ни одной анкеты в базе данных.</p>
                </div>
            <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th>Дата рождения</th>
                        <th>Пол</th>
                        <th>Языки</th>
                        <th>Биография</th>
                        <th>Дата создания</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                    <tr>
                        <td><?php echo $app['id']; ?></td>
                        <td><?php echo htmlspecialchars($app['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($app['phone']); ?></td>
                        <td><?php echo htmlspecialchars($app['email']); ?></td>
                        <td><?php echo date('d.m.Y', strtotime($app['birth_date'])); ?></td>
                        <td><?php echo $app['gender'] == 'male' ? '♂ Мужской' : '♀ Женский'; ?></td>
                        <td>
                            <?php 
                            $langs = explode(', ', $app['languages'] ?? '');
                            foreach ($langs as $lang):
                                if (trim($lang)):
                            ?>
                                <span class="badge"><?php echo htmlspecialchars(trim($lang)); ?></span>
                            <?php endif; endforeach; ?>
                        </td>
                        <td style="max-width:150px; word-break:break-word;">
                            <?php 
                            $bio = htmlspecialchars($app['biography'] ?? '');
                            echo empty($bio) ? '<em style="color:#9b4b6e;">—</em>' : 
                                (strlen($bio) > 50 ? substr($bio, 0, 50) . '…' : $bio);
                            ?>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($app['created_at'])); ?></td>
                        <td style="white-space:nowrap;">
                            <a href="admin_edit.php?id=<?php echo $app['id']; ?>" class="btn-admin-edit">✏️</a>
                            <a href="admin_delete.php?id=<?php echo $app['id']; ?>" 
                               class="btn-admin-delete" 
                               onclick="return confirm('Удалить анкету №<?php echo $app['id']; ?>? Это действие нельзя отменить.');">🗑️</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>Лабораторная работа №6 — Панель администратора | Май 2026</p>
        </div>
    </footer>
</body>
</html>
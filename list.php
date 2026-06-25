<?php
// list.php - Страница со списком всех сохранённых анкет
require_once 'config.php';

// Получаем все анкеты с их языками программирования
$sql = "SELECT a.*, 
        GROUP_CONCAT(pl.name ORDER BY pl.name SEPARATOR ', ') as languages
        FROM applications a
        LEFT JOIN application_languages al ON a.id = al.application_id
        LEFT JOIN programming_languages pl ON al.language_id = pl.id
        GROUP BY a.id
        ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$applications = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Список сохранённых анкет — Лабораторная работа №3</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Дополнительные стили для таблицы */
        .container {
            max-width: 1400px;  /* Увеличено с 800px до 1400px */
            width: 95%;
        }
        
        .applications-table {
            width: 100%;
            min-width: 1200px;  /* Минимальная ширина таблицы */
            border-collapse: collapse;
            background-color: #fff;
            border-radius: 20px;
            overflow-x: auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .applications-table th,
        .applications-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid #ffccd9;
            vertical-align: top;
        }
        
        .applications-table th {
            background: linear-gradient(135deg, #f8b0c0, #f48fb1);
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
            white-space: nowrap;
        }
        
        .applications-table tr:hover {
            background-color: #fff5f7;
        }
        
        .applications-table tr:last-child td {
            border-bottom: none;
        }
        
        .badge {
            display: inline-block;
            background-color: #f06292;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            margin: 0.1rem;
            white-space: nowrap;
        }
        
        .languages-cell {
            min-width: 180px;
            max-width: 250px;
        }
        
        .biography-cell {
            max-width: 250px;
            word-break: break-word;
            white-space: normal;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #9b4b6e;
            background-color: #fff0f3;
            border-radius: 20px;
        }
        
        .stats {
            background-color: #fff0f3;
            padding: 1rem 1.5rem;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: nowrap;
            white-space: nowrap;
        }
        
        .btn-view {
            background-color: #f06292;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.75rem;
            transition: background-color 0.2s;
            white-space: nowrap;
        }
        
        .btn-view:hover {
            background-color: #d81b60;
        }
        
        .btn-delete {
            background-color: #ffebee;
            color: #f44336;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.75rem;
            border: 1px solid #f44336;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .btn-delete:hover {
            background-color: #f44336;
            color: white;
        }
        
        .table-wrapper {
            overflow-x: auto;
            border-radius: 20px;
            margin: 0 -0.5rem;
            padding: 0 0.5rem;
        }
        
        .action-buttons {
            margin-top: 1.5rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .action-btn {
            background: linear-gradient(135deg, #f06292, #d81b60);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 40px;
            text-decoration: none;
            font-weight: bold;
            transition: transform 0.2s;
            display: inline-block;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
        }
        
        .action-btn.secondary {
            background: linear-gradient(135deg, #b0bec5, #90a4ae);
        }
        
        /* Модальное окно для просмотра */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 24px;
            max-width: 500px;
            width: 90%;
            padding: 2rem;
            position: relative;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-close {
            position: absolute;
            right: 1.5rem;
            top: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #9b4b6e;
        }
        
        .modal-close:hover {
            color: #d81b60;
        }
        
        .modal h3 {
            color: #d81b60;
            margin-bottom: 1rem;
        }
        
        .modal-field {
            margin-bottom: 0.75rem;
        }
        
        .modal-field strong {
            color: #d81b60;
            display: inline-block;
            width: 120px;
        }
        
        @media (max-width: 768px) {
            .container {
                width: 100%;
                padding: 0 10px;
            }
            .applications-table th,
            .applications-table td {
                padding: 0.5rem;
                font-size: 0.8rem;
            }
            .badge {
                font-size: 0.6rem;
                padding: 0.15rem 0.4rem;
            }
        }
        
        /* Цветовая индикация пола */
        .gender-male {
            color: #2196f3;
            font-weight: bold;
        }
        .gender-female {
            color: #e91e63;
            font-weight: bold;
        }
        
        /* Улучшенная читаемость */
        .applications-table td {
            word-break: break-word;
        }
        
        .applications-table .email-cell {
            word-break: break-all;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
           
            <h2>Задание 3. Список сохранённых анкет</h2>
           
        </div>
    </header>

    <main class="container">
        <div class="stats">
             Всего анкет в базе данных: <strong><?php echo count($applications); ?></strong>
        </div>

        <?php if (empty($applications)): ?>
            <div class="empty-state">
                <p>😕 Пока нет ни одной сохранённой анкеты.</p>
                <a href="index.php" class="action-btn" style="margin-top: 1rem; display: inline-block;"> Заполнить первую анкету</a>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="applications-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ФИО</th>
                            <th>Телефон</th>
                            <th>Email</th>
                            <th>Дата рождения</th>
                            <th>Пол</th>
                            <th>Любимые ЯП</th>
                            <th>Биография</th>
                            <th>Дата создания</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($app['id']); ?></td>
                                <td><?php echo htmlspecialchars($app['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($app['phone']); ?></td>
                                <td class="email-cell"><?php echo htmlspecialchars($app['email']); ?></td>
                                <td><?php echo date('d.m.Y', strtotime($app['birth_date'])); ?></td>
                                <td class="<?php echo $app['gender'] == 'male' ? 'gender-male' : 'gender-female'; ?>">
                                    <?php 
                                    $gender_text = '';
                                    if ($app['gender'] == 'male') $gender_text = '♂ Мужской';
                                    if ($app['gender'] == 'female') $gender_text = '♀ Женский';
                                    echo $gender_text;
                                    ?>
                                </td>
                                <td class="languages-cell">
                                    <?php 
                                    $languages = explode(', ', $app['languages'] ?? '');
                                    foreach ($languages as $lang):
                                        if (trim($lang)):
                                    ?>
                                        <span class="badge"><?php echo htmlspecialchars(trim($lang)); ?></span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    if (empty($app['languages'])):
                                        echo '<em style="color: #9b4b6e;">— не выбрано —</em>';
                                    endif;
                                    ?>
                                </td>
                                <td class="biography-cell">
                                    <?php 
                                    $bio = htmlspecialchars($app['biography'] ?? '');
                                    if (empty($bio)):
                                        echo '<em style="color: #9b4b6e;">— не указано —</em>';
                                    else:
                                        echo strlen($bio) > 100 ? substr($bio, 0, 100) . '…' : $bio;
                                    endif;
                                    ?>
                                </td>
                                <td><?php echo date('d.m.Y H:i:s', strtotime($app['created_at'])); ?></td>
                                
                             </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <div class="action-buttons">
            <a href="index.php" class="action-btn"> Добавить новую анкету</a>
        </div>
    </main>

   

    <!-- Модальное окно для просмотра деталей -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal()">&times;</span>
            <h3> Детали анкеты</h3>
            <div id="modalBody"></div>
        </div>
    </div>

    
</body>
</html>
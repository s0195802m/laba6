<?php
// admin_delete.php - Удаление анкеты администратором
require_once 'config.php';

// Проверка HTTP-авторизации
$adminLogin = authenticateAdmin();

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: admin.php');
    exit;
}

// Удаляем анкету (связанные языки удалятся каскадно)
$stmt = $pdo->prepare("DELETE FROM applications WHERE id = :id");
$stmt->execute([':id' => $id]);

header('Location: admin.php?deleted=1');
exit;
?>
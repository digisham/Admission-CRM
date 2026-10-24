<?php
require_once __DIR__ . '/../app/autoload.php';
App\Config\App::init();
$db = App\Config\Database::getConnection();
$roles = $db->query('SELECT * FROM roles')->fetchAll(PDO::FETCH_ASSOC);
echo "=== ROLES ===\n";
print_r($roles);
$users = $db->query('SELECT u.id, u.name, u.email, u.role_id, r.slug, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id')->fetchAll(PDO::FETCH_ASSOC);
echo "=== USERS ===\n";
print_r($users);

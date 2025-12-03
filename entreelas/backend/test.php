<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

if ($conn) {
    echo "✅ Conexão com banco OK!";
} else {
    echo "❌ Erro na conexão com banco";
}
?>
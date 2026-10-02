<?php

$host = 'localhost';
$port = '3306';
$dbname = 'industria_db';
$usuario = 'root';
$senha = 'mysql';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;port=$port;charset=utf8mb4", $usuario, $senha);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}



function verificarLogin() {
    session_start();
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit();
    }
}
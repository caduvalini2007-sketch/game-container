<?php
/**
 * Configurações do Banco de Dados
 */
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'game_container'); 
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Inicialização da Sessão e Funções Utilitárias
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Escapa strings para saída HTML segura
 */
function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Conexão PDO
 */
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    $err = htmlspecialchars($e->getMessage());
    die("<h2>Erro ao conectar ao MySQL</h2><p>Verifique as configurações em <code>includes/config.php</code>.</p><pre>$err</pre>");
}
?>

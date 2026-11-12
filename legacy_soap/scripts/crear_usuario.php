<?php
/**
 * Crea (o actualiza la clave de) un usuario del servicio SOAP.
 * Uso:  php scripts/crear_usuario.php <usuario> <password>
 * Solo por línea de comandos: no debe poder ejecutarse desde el navegador.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script solo se ejecuta desde la línea de comandos.\n");
}

require_once __DIR__ . '/../config/Database.php';

if ($argc !== 3) {
    exit("Uso: php scripts/crear_usuario.php <usuario> <password>\n");
}

[$usuario, $password] = [trim($argv[1]), $argv[2]];

if ($usuario === '' || strlen($password) < 8) {
    exit("Error: usuario vacío o contraseña menor a 8 caracteres.\n");
}

$db   = Database::getInstance()->getConnection();
$hash = password_hash($password, PASSWORD_DEFAULT); // bcrypt con sal incluida

$stmt = $db->prepare(
    "INSERT INTO usuarios (usuario, password_hash, estado)
     VALUES (:usuario, :hash, 'Activo')
     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)"
);
$stmt->execute([':usuario' => $usuario, ':hash' => $hash]);

echo "Usuario '$usuario' listo.\n";
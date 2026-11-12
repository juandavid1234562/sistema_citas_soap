<?php
/**
 * Script de prueba de validación de tokens generados desde la API REST.
 *
 * Ejecución en terminal:
 *   php probar_validacion_rest.php
 */
require_once __DIR__ . '/ValidadorTokenSecreto.php';
require_once __DIR__ . '/ConfiguracionSecreto.php';

echo "====================================================================\n";
echo "  SISTEMA SOAP: PRUEBA DE VALIDACION DE TOKENS PROCEDENTES DE REST  \n";
echo "       (ENFOQUE 2: CRIPTOGRAFIA SIMETRICA CON CLAVE SECRETA)        \n";
echo "====================================================================\n\n";

$validador = new ValidadorTokenSecreto();
$clave = ConfiguracionSecreto::obtenerClave();

echo "[*] Clave secreta compartida configurada: " . substr($clave, 0, 10) . "..." . substr($clave, -5) . "\n\n";

// Helper para generar token simulado desde REST
function generarTokenRest(string $usuario, int $minutosVigencia, string $clave): string {
    $payload = [
        'usuario' => $usuario,
        'iat'     => time(),
        'exp'     => time() + ($minutosVigencia * 60),
        'origen'  => 'API-REST-Laravel',
        'jti'     => bin2hex(random_bytes(16))
    ];
    $json = json_encode($payload);
    $b64 = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    $firma = hash_hmac('sha256', $b64, $clave);
    return $b64 . '.' . $firma;
}

// CASO 1: Token Válido generado por REST con clave secreta compartida
echo "--- CASO 1: TOKEN VALIDO GENERADO POR REST CON CLAVE SECRETA ---\n";
$tokenValido = generarTokenRest('doctor_garcia@consultorio.com', 30, $clave);
echo "Token recibido: " . substr($tokenValido, 0, 35) . "...\n";
$resultado1 = $validador->validar($tokenValido);
echo "Resultado: " . ($resultado1['valido'] ? "EXITO [VALIDO]" : "FALLO [INVALIDO]") . "\n";
echo "Codigo: " . $resultado1['codigo'] . "\n";
echo "Mensaje: " . $resultado1['mensaje'] . "\n";
echo "Usuario identificado: " . $resultado1['usuario'] . "\n";
echo "Fecha expiracion: " . $resultado1['expiracion'] . "\n\n";

// CASO 2: Token con payload manipulado (intento de suplantación)
echo "--- CASO 2: TOKEN CON PAYLOAD ALTERADO (FIRMA NO COINCIDE) ---\n";
$partes = explode('.', $tokenValido);
$payloadAlterado = rtrim(strtr(base64_encode(json_encode(['usuario' => 'hacker_infiltrado', 'exp' => time() + 3600])), '+/', '-_'), '=');
$tokenAlterado = $payloadAlterado . '.' . $partes[1];
echo "Token alterado: " . substr($tokenAlterado, 0, 35) . "...\n";
$resultado2 = $validador->validar($tokenAlterado);
echo "Resultado: " . ($resultado2['valido'] ? "EXITO [VALIDO]" : "RECHAZADO [INVALIDO]") . "\n";
echo "Codigo: " . $resultado2['codigo'] . "\n";
echo "Mensaje: " . $resultado2['mensaje'] . "\n\n";

// CASO 3: Token generado con una clave secreta incorrecta
echo "--- CASO 3: TOKEN GENERADO CON CLAVE SECRETA INCORRECTA ---\n";
$tokenClaveFalsa = generarTokenRest('usuario_externo', 30, 'clave_falsa_e_invalida_999');
echo "Token recibido: " . substr($tokenClaveFalsa, 0, 35) . "...\n";
$resultado3 = $validador->validar($tokenClaveFalsa);
echo "Resultado: " . ($resultado3['valido'] ? "EXITO [VALIDO]" : "RECHAZADO [INVALIDO]") . "\n";
echo "Codigo: " . $resultado3['codigo'] . "\n";
echo "Mensaje: " . $resultado3['mensaje'] . "\n\n";

// CASO 4: Token expirado (vigencia negativa)
echo "--- CASO 4: TOKEN EXPIRADO ---\n";
$tokenExpirado = generarTokenRest('doctor_garcia@consultorio.com', -10, $clave);
$resultado4 = $validador->validar($tokenExpirado);
echo "Resultado: " . ($resultado4['valido'] ? "EXITO [VALIDO]" : "RECHAZADO [INVALIDO/EXPIRADO]") . "\n";
echo "Codigo: " . $resultado4['codigo'] . "\n";
echo "Mensaje: " . $resultado4['mensaje'] . "\n";
echo "Fecha expiracion: " . $resultado4['expiracion'] . "\n\n";

echo "====================================================================\n";
echo "  TODAS LAS PRUEBAS DE VALIDACION CON CLAVE SECRETA COMPLETADAS     \n";
echo "====================================================================\n";

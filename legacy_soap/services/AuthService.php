<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Token.php';
require_once __DIR__ . '/AutenticacionException.php';
require_once __DIR__ . '/../../Tokenst-conexion-REST/ValidadorTokenSecreto.php';

const TOKEN_MINUTOS = 30;

// Hash bcrypt válido de una clave que nadie conoce. Se usa cuando el usuario NO existe,
// para que la respuesta tarde lo mismo que con un usuario real (evita adivinar usuarios por tiempo).
const HASH_SENUELO = '$2y$10$BSbn.bwmVgdPnkXcjMLyoeQ9TPNmYwcekTuSn/NFh9D2ImISh1xzW';

/**
 * LOGIN: valida usuario y contraseña (vienen del <wsse:UsernameToken>) y emite un token (Enfoque 1).
 */
function svcAutenticar($usuario, $password) {
    if (trim((string)$usuario) === '' || (string)$password === '') {
        throw new AutenticacionException('Se requiere un wsse:UsernameToken con Username y Password en el header.');
    }

    $fila = (new UsuarioModel())->obtenerPorUsuario(trim($usuario));

    // password_verify SIEMPRE se ejecuta, exista o no el usuario
    $hash  = $fila ? $fila['password_hash'] : HASH_SENUELO;
    $claveOk = password_verify((string)$password, $hash);

    // Mensaje único y genérico: no revela si falló el usuario, la clave o el estado
    if (!$fila || !$claveOk || $fila['estado'] !== 'Activo') {
        throw new AutenticacionException('Credenciales inválidas.');
    }

    $tokenModel = new TokenModel();
    $tokenModel->eliminarExpirados();

    $token = bin2hex(random_bytes(32));        // 32 bytes aleatorios seguros -> 64 caracteres hex
    $tokenModel->crear($fila['id'], hash('sha256', $token), TOKEN_MINUTOS);

    return ['token' => $token, 'expiraEnMinutos' => TOKEN_MINUTOS];
}

/**
 * Valida un token generado mediante clave secreta compartida (Enfoque 2).
 * Devuelve un array con valido (bool), codigo, mensaje, usuario y expiracion.
 */
function svcValidarTokenSecreto($token) {
    $validador = new ValidadorTokenSecreto();
    return $validador->validar($token);
}

/**
 * Valida el token que llega en el header de las operaciones protegidas.
 * Soporta de manera transparente ambos enfoques:
 * 1. Tokens de Base de Datos (generados con svcAutenticar)
 * 2. Tokens con Clave Secreta Compartida (generados desde la API REST)
 */
function svcValidarToken($token) {
    if (trim((string)$token) === '') {
        throw new AutenticacionException('Se requiere un AuthToken en el header SOAP.');
    }

    $tokenTrim = trim((string)$token);

    // Enfoque 2: Si el token tiene formato de clave secreta (payload.firma)
    if (ValidadorTokenSecreto::esTokenSecreto($tokenTrim)) {
        $validador = new ValidadorTokenSecreto();
        $resultado = $validador->validar($tokenTrim);
        if (!$resultado['valido']) {
            throw new AutenticacionException($resultado['mensaje']);
        }
        return [
            'id' => 0,
            'usuario_id' => 0,
            'usuario' => $resultado['usuario'],
            'tipo_token' => 'clave_secreta_hmac',
        ];
    }

    // Enfoque 1: Token opaco de base de datos
    $fila = (new TokenModel())->obtenerVigente(hash('sha256', $tokenTrim));
    if (!$fila) {
        throw new AutenticacionException('Token inválido o vencido.');
    }
    return $fila;
}

<?php
require_once __DIR__ . '/ConfiguracionSecreto.php';

/**
 * Validador de Tokens Criptograficos basados en Clave Secreta Compartida (HMAC-SHA256).
 *
 * Implementacion del Enfoque 2 (Token sin estado / firmado simetricamente):
 * El cliente REST genera el token firmando el contenido con la clave secreta.
 * El servidor SOAP recibe el token, recalcula la firma con la misma clave secreta
 * y certifica matematicamente la integridad, procedencia y vigencia sin necesidad
 * de consultar bases de datos.
 */
class ValidadorTokenSecreto {
    private string $claveSecreta;

    public function __construct(?string $claveSecreta = null) {
        $this->claveSecreta = $claveSecreta ?? ConfiguracionSecreto::obtenerClave();
    }

    /**
     * Determina si una cadena tiene el formato de un token firmado con clave secreta (payload.firma).
     */
    public static function esTokenSecreto(?string $token): bool {
        if (!is_string($token) || trim($token) === '') {
            return false;
        }
        $partes = explode('.', trim($token));
        return count($partes) === 2 && strlen($partes[0]) > 0 && strlen($partes[1]) === 64;
    }

    /**
     * Valida el token recibido y retorna un array con el estado de la validacion.
     *
     * @param string $token
     * @return array [
     *   'valido'     => bool,
     *   'codigo'     => int,
     *   'mensaje'    => string,
     *   'usuario'    => string,
     *   'expiracion' => string,
     *   'detalles'   => array
     * ]
     */
    public function validar(?string $token): array {
        if (!is_string($token) || trim($token) === '') {
            return [
                'valido'     => false,
                'codigo'     => 400,
                'mensaje'    => 'El token proporcionado esta vacio o no es valido.',
                'usuario'    => '',
                'expiracion' => '',
                'detalles'   => [],
            ];
        }

        $token = trim($token);
        $partes = explode('.', $token);

        if (count($partes) !== 2) {
            return [
                'valido'     => false,
                'codigo'     => 401,
                'mensaje'    => 'Formato de token no reconocido. Debe estructurarse como [payload].[firma_hmac].',
                'usuario'    => '',
                'expiracion' => '',
                'detalles'   => [],
            ];
        }

        [$payloadB64, $firmaRecibida] = $partes;

        // 1. Recalcular la firma criptografica con la clave secreta
        $firmaEsperada = hash_hmac('sha256', $payloadB64, $this->claveSecreta);

        // 2. Comparacion segura contra ataques de temporizacion (timing attacks)
        if (!hash_equals($firmaEsperada, $firmaRecibida)) {
            return [
                'valido'     => false,
                'codigo'     => 401,
                'mensaje'    => 'Token INVALIDO: La firma criptografica no coincide con la clave secreta (token alterado o clave incorrecta).',
                'usuario'    => '',
                'expiracion' => '',
                'detalles'   => [],
            ];
        }

        // 3. Decodificar el payload JSON
        $json = base64_decode(strtr($payloadB64, '-_', '+/'));
        $datos = json_decode($json, true);

        if (!is_array($datos) || !isset($datos['exp'])) {
            return [
                'valido'     => false,
                'codigo'     => 400,
                'mensaje'    => 'Token corrupto: El cuerpo del token no contiene un payload JSON valido.',
                'usuario'    => '',
                'expiracion' => '',
                'detalles'   => [],
            ];
        }

        $usuario = $datos['usuario'] ?? ($datos['iss'] ?? 'Anonimo');
        $exp     = (int) $datos['exp'];
        $fechaExp = date('Y-m-d H:i:s', $exp);

        // 4. Verificar expiracion
        if (time() > $exp) {
            return [
                'valido'     => false,
                'codigo'     => 410,
                'mensaje'    => "Token EXPIRADO: La sesion caduco el {$fechaExp}.",
                'usuario'    => (string)$usuario,
                'expiracion' => $fechaExp,
                'detalles'   => $datos,
            ];
        }

        // 5. Token 100% valido y autentico
        return [
            'valido'     => true,
            'codigo'     => 200,
            'mensaje'    => 'Token VALIDO: Autenticado y verificado exitosamente mediante la clave secreta.',
            'usuario'    => (string)$usuario,
            'expiracion' => $fechaExp,
            'detalles'   => $datos,
        ];
    }
}

<?php
/**
 * Configuracion de la Clave Secreta Compartida (Shared Secret Key)
 * Utilizada para la generacion en REST y validacion en SOAP.
 */
class ConfiguracionSecreto {
    // Clave secreta simetrica compartida entre REST y SOAP
    const CLAVE_SECRETA = 'Consultorio_Secret_Key_Corte2_3_2026!#';

    // Duracion estandar de vigencia del token (en minutos)
    const MINUTOS_VIGENCIA = 30;

    public static function obtenerClave(): string {
        $envKey = getenv('SOAP_SECRET_KEY');
        if ($envKey !== false && trim($envKey) !== '') {
            return trim($envKey);
        }
        return self::CLAVE_SECRETA;
    }
}

<?php
require_once __DIR__ . '/ServicioException.php';

/**
 * Error de autenticación: credenciales o token ausentes, inválidos o vencidos.
 * Se traduce a un soap_fault con faultcode 'Client.Authentication'.
 */
class AutenticacionException extends ServicioException {
    public function __construct($mensaje) {
        parent::__construct($mensaje, 'Client.Authentication');
    }
}
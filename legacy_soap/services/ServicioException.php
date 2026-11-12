<?php
/**
 * Excepción controlada de la capa de servicio.
 * - 'Client': el cliente envió algo inválido (campos vacíos, no existe, duplicado...).
 * - 'Server': falla interna nuestra.
 * En la Fase 2 la especializaremos (ValidationException, NotFoundException, etc.).
 */
class ServicioException extends Exception {
    private $faultCode;

    public function __construct($mensaje, $faultCode = 'Client') {
        parent::__construct($mensaje);
        $this->faultCode = $faultCode;
    }

    public function getFaultCode() {
        return $this->faultCode;
    }
}
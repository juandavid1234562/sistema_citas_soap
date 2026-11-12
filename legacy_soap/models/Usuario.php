<?php
require_once __DIR__ . '/../config/Database.php';

class UsuarioModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function obtenerPorUsuario($usuario) {
        $stmt = $this->db->prepare(
            "SELECT id, usuario, password_hash, estado FROM usuarios WHERE usuario = :usuario"
        );
        $stmt->execute([':usuario' => $usuario]);
        return $stmt->fetch();
    }
}
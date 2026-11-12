<?php
require_once __DIR__ . '/../config/Database.php';

class PacienteModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function registrar($documento, $nombre, $telefono) {
        $stmt = $this->db->prepare("INSERT INTO pacientes (documento, nombre, telefono) VALUES (:documento, :nombre, :telefono)");
        return $stmt->execute([
            ':documento' => $documento,
            ':nombre'    => $nombre,
            ':telefono'  => $telefono
        ]);
    }

    public function obtenerPorDocumento($documento) {
        $stmt = $this->db->prepare("SELECT * FROM pacientes WHERE documento = :documento");
        $stmt->execute([':documento' => $documento]);
        return $stmt->fetch();
    }

    public function obtenerPorId($id) {
        $stmt = $this->db->prepare("SELECT * FROM pacientes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function listarTodos() {
        $stmt = $this->db->query("SELECT * FROM pacientes");
        return $stmt->fetchAll();
    }
}
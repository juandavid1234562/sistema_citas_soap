<?php
require_once __DIR__ . '/../config/Database.php';

class ProfesionalModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function registrar($documento, $nombre, $especialidad, $estado = 'Activo') {
        $stmt = $this->db->prepare("INSERT INTO profesionales (documento, nombre, especialidad, estado) VALUES (:documento, :nombre, :especialidad, :estado)");
        return $stmt->execute([
            ':documento'    => $documento,
            ':nombre'       => $nombre,
            ':especialidad' => $especialidad,
            ':estado'       => $estado
        ]);
    }

    public function obtenerPorDocumento($documento) {
        $stmt = $this->db->prepare("SELECT * FROM profesionales WHERE documento = :documento");
        $stmt->execute([':documento' => $documento]);
        return $stmt->fetch();
    }

    public function obtenerPorId($id) {
        $stmt = $this->db->prepare("SELECT * FROM profesionales WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function listarTodos() {
        $stmt = $this->db->query("SELECT * FROM profesionales");
        return $stmt->fetchAll();
    }

    public function actualizar($id, $nombre, $especialidad, $estado) {
        $stmt = $this->db->prepare("UPDATE profesionales SET nombre = :nombre, especialidad = :especialidad, estado = :estado WHERE id = :id");
        return $stmt->execute([
            ':id'           => $id,
            ':nombre'       => $nombre,
            ':especialidad' => $especialidad,
            ':estado'       => $estado
        ]);
    }
}
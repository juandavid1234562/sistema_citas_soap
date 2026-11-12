<?php
require_once __DIR__ . '/../config/Database.php';

class CitaModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function existeSolapamiento($profesional_id, $fecha, $hora) {
        $stmt = $this->db->prepare("SELECT id FROM citas WHERE profesional_id = :profesional_id AND fecha = :fecha AND hora = :hora AND estado != 'Cancelada'");
        $stmt->execute([
            ':profesional_id' => $profesional_id,
            ':fecha'          => $fecha,
            ':hora'           => $hora
        ]);
        return $stmt->fetch() !== false;
    }

    public function registrar($profesional_id, $paciente_id, $fecha, $hora, $estado = 'Programada') {
        $stmt = $this->db->prepare("INSERT INTO citas (profesional_id, paciente_id, fecha, hora, estado) VALUES (:profesional_id, :paciente_id, :fecha, :hora, :estado)");
        return $stmt->execute([
            ':profesional_id' => $profesional_id,
            ':paciente_id'    => $paciente_id,
            ':fecha'          => $fecha,
            ':hora'           => $hora,
            ':estado'         => $estado
        ]);
    }

    public function consultarPorFiltro($profesional_id = null, $fecha = null) {
        $sql = "SELECT c.id, c.profesional_id, pr.nombre AS profesional, c.paciente_id, pa.nombre AS paciente, c.fecha, c.hora, c.estado 
                FROM citas c
                INNER JOIN profesionales pr ON c.profesional_id = pr.id
                INNER JOIN pacientes pa ON c.paciente_id = pa.id
                WHERE 1=1";
        
        $params = [];

        if (!empty($profesional_id)) {
            $sql .= " AND c.profesional_id = :profesional_id";
            $params[':profesional_id'] = $profesional_id;
        }

        if (!empty($fecha)) {
            $sql .= " AND c.fecha = :fecha";
            $params[':fecha'] = $fecha;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
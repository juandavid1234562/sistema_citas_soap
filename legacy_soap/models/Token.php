<?php
require_once __DIR__ . '/../config/Database.php';

class TokenModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function crear($usuario_id, $tokenHash, $minutos) {
        $stmt = $this->db->prepare(
            "INSERT INTO tokens (usuario_id, token_hash, expira_en)
             VALUES (:usuario_id, :token_hash, DATE_ADD(NOW(), INTERVAL :minutos MINUTE))"
        );
        $stmt->bindValue(':usuario_id', (int)$usuario_id, PDO::PARAM_INT);
        $stmt->bindValue(':token_hash', $tokenHash);
        $stmt->bindValue(':minutos', (int)$minutos, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function obtenerVigente($tokenHash) {
        $stmt = $this->db->prepare(
            "SELECT t.id, t.usuario_id, u.usuario
             FROM tokens t
             INNER JOIN usuarios u ON u.id = t.usuario_id
             WHERE t.token_hash = :token_hash
               AND t.expira_en > NOW()
               AND u.estado = 'Activo'"
        );
        $stmt->execute([':token_hash' => $tokenHash]);
        return $stmt->fetch();
    }

    public function eliminarExpirados() {
        return $this->db->exec("DELETE FROM tokens WHERE expira_en <= NOW()");
    }
}
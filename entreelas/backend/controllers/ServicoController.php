<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/response.php';

class ServicoController {
    private $conn;
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Lista todos os serviços ativos
     */
    public function listarServicos() {
        try {
            $query = "SELECT 
                        s.id,
                        s.titulo,
                        s.descricao,
                        s.categoria,
                        s.preco_estimado,
                        s.localizacao,
                        u.nome as prestadora_nome,
                        u.id as prestadora_id,
                        u.telefone as prestadora_telefone,
                        s.data_cadastro
                      FROM servicos s
                      INNER JOIN usuarios u ON s.usuario_id = u.id
                      WHERE s.ativo = 1
                      ORDER BY s.data_cadastro DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $servicos = $stmt->fetchAll();
            
            sendSuccess($servicos, 'Serviços listados com sucesso');

        } catch (PDOException $e) {
            error_log("Erro ao listar serviços: " . $e->getMessage());
            sendError('Erro ao listar serviços', 500);
        }
    }
}
?>
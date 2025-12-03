<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/AuthController.php';

class SolicitacaoController {
    private $conn;
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Solicita um serviço
     */
    public function solicitarServico($data) {
        try {
            // Verifica autenticação
            $authController = new AuthController();
            $usuarioId = $authController->verificarAutenticacao();

            // Valida campos
            if (!isset($data['servico_id'])) {
                sendError('ID do serviço é obrigatório', 400);
            }

            $servicoId = intval($data['servico_id']);
            $mensagem = isset($data['mensagem']) ? sanitizeString($data['mensagem']) : null;

            // Verifica se o serviço existe
            $query = "SELECT id, usuario_id FROM servicos WHERE id = :servico_id AND ativo = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':servico_id', $servicoId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                sendError('Serviço não encontrado', 404);
            }

            $servico = $stmt->fetch();

            // Verifica se não está solicitando o próprio serviço
            if ($servico['usuario_id'] == $usuarioId) {
                sendError('Você não pode solicitar seu próprio serviço', 400);
            }

            // Verifica se já existe solicitação pendente
            $query = "SELECT id FROM solicitacoes 
                      WHERE servico_id = :servico_id 
                      AND cliente_id = :cliente_id 
                      AND status = 'pendente'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':servico_id', $servicoId, PDO::PARAM_INT);
            $stmt->bindParam(':cliente_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                sendError('Você já possui uma solicitação pendente para este serviço', 400);
            }

            // Insere solicitação
            $query = "INSERT INTO solicitacoes (servico_id, cliente_id, mensagem) 
                      VALUES (:servico_id, :cliente_id, :mensagem)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':servico_id', $servicoId, PDO::PARAM_INT);
            $stmt->bindParam(':cliente_id', $usuarioId, PDO::PARAM_INT);
            $stmt->bindParam(':mensagem', $mensagem);

            if ($stmt->execute()) {
                sendSuccess([
                    'solicitacao_id' => $this->conn->lastInsertId()
                ], 'Solicitação enviada com sucesso!', 201);
            } else {
                sendError('Erro ao criar solicitação', 500);
            }

        } catch (PDOException $e) {
            error_log("Erro ao solicitar serviço: " . $e->getMessage());
            sendError('Erro ao processar solicitação', 500);
        }
    }
}
?>
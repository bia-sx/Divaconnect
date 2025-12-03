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

    /**
     * Lista solicitações enviadas pela usuária
     */
    public function minhasSolicitacoes() {
        try {
            $authController = new AuthController();
            $usuarioId = $authController->verificarAutenticacao();

            $query = "SELECT 
                        sol.id,
                        sol.mensagem,
                        sol.status,
                        sol.data_solicitacao,
                        s.titulo as servico_titulo,
                        s.descricao as servico_descricao,
                        s.preco_estimado,
                        s.localizacao,
                        u.nome as prestadora_nome,
                        u.telefone as prestadora_telefone
                      FROM solicitacoes sol
                      INNER JOIN servicos s ON sol.servico_id = s.id
                      INNER JOIN usuarios u ON s.usuario_id = u.id
                      WHERE sol.cliente_id = :usuario_id
                      ORDER BY sol.data_solicitacao DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();
            
            $solicitacoes = $stmt->fetchAll();
            
            sendSuccess($solicitacoes, 'Solicitações listadas com sucesso');

        } catch (PDOException $e) {
            error_log("Erro ao listar solicitações: " . $e->getMessage());
            sendError('Erro ao listar solicitações', 500);
        }
    }

    /**
     * Lista solicitações recebidas pela prestadora
     */
    public function solicitacoesRecebidas() {
        try {
            $authController = new AuthController();
            $usuarioId = $authController->verificarAutenticacao();

            $query = "SELECT 
                        sol.id,
                        sol.mensagem,
                        sol.status,
                        sol.data_solicitacao,
                        s.titulo as servico_titulo,
                        s.descricao as servico_descricao,
                        s.preco_estimado,
                        s.localizacao,
                        u.nome as cliente_nome,
                        u.telefone as cliente_telefone
                      FROM solicitacoes sol
                      INNER JOIN servicos s ON sol.servico_id = s.id
                      INNER JOIN usuarios u ON sol.cliente_id = u.id
                      WHERE s.usuario_id = :usuario_id
                      ORDER BY sol.data_solicitacao DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();
            
            $solicitacoes = $stmt->fetchAll();
            
            sendSuccess($solicitacoes, 'Solicitações listadas com sucesso');

        } catch (PDOException $e) {
            error_log("Erro ao listar solicitações: " . $e->getMessage());
            sendError('Erro ao listar solicitações', 500);
        }
    }

    /**
     * Responde uma solicitação (aceitar/recusar)
     */
    public function responderSolicitacao($data) {
        try {
            $authController = new AuthController();
            $usuarioId = $authController->verificarAutenticacao();

            if (!isset($data['solicitacao_id']) || !isset($data['status'])) {
                sendError('Dados incompletos', 400);
            }

            $solicitacaoId = intval($data['solicitacao_id']);
            $status = $data['status'];

            if (!in_array($status, ['aceita', 'recusada'])) {
                sendError('Status inválido', 400);
            }

            // Verifica se a solicitação pertence a um serviço da usuária
            $query = "SELECT sol.id 
                      FROM solicitacoes sol
                      INNER JOIN servicos s ON sol.servico_id = s.id
                      WHERE sol.id = :solicitacao_id 
                      AND s.usuario_id = :usuario_id
                      AND sol.status = 'pendente'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':solicitacao_id', $solicitacaoId, PDO::PARAM_INT);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                sendError('Solicitação não encontrada ou já respondida', 404);
            }

            // Atualiza status
            $query = "UPDATE solicitacoes 
                      SET status = :status, data_resposta = NOW()
                      WHERE id = :solicitacao_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':solicitacao_id', $solicitacaoId, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $message = $status === 'aceita' ? 'Solicitação aceita!' : 'Solicitação recusada';
                sendSuccess([], $message);
            } else {
                sendError('Erro ao responder solicitação', 500);
            }

        } catch (PDOException $e) {
            error_log("Erro ao responder solicitação: " . $e->getMessage());
            sendError('Erro ao processar resposta', 500);
        }
    }
}
?>
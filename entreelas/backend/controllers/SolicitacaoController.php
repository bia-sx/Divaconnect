<?php
// Inclui arquivos de dependência
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/AuthController.php';

class SolicitacaoController {
    private $conn;
    private $db;
    private $authController;

    public function __construct() {
        // Inicializa a conexão com o banco de dados
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        
        // Instancia o AuthController uma única vez
        $this->authController = new AuthController();
    }

    /**
     * Solicita um serviço.
     * @param array $data Dados da solicitação (servico_id, mensagem).
     */
    public function solicitarServico($data) {
        try {
            // Verifica autenticação e obtém o ID do usuário (cliente)
            $usuarioId = $this->authController->verificarAutenticacao();

            // Valida campos
            if (!isset($data['servico_id'])) {
                sendError('ID do serviço é obrigatório', 400);
            }

            $servicoId = intval($data['servico_id']);
            // Assume-se que 'sanitizeString' é uma função de utilidade definida em algum lugar
            $mensagem = isset($data['mensagem']) ? sanitizeString($data['mensagem']) : null;

            // 1. Verifica se o serviço existe e obtém o ID do prestador
            $query = "SELECT id, usuario_id FROM servicos WHERE id = :servico_id AND ativo = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':servico_id', $servicoId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                sendError('Serviço não encontrado', 404);
            }

            $servico = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. Verifica se o cliente não está solicitando o próprio serviço
            if ($servico['usuario_id'] == $usuarioId) {
                sendError('Você não pode solicitar seu próprio serviço', 400);
            }

            // 3. Verifica se já existe solicitação PENDENTE do mesmo cliente para o mesmo serviço
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

            // 4. Insere a nova solicitação
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
     * Lista solicitações enviadas pelo usuário autenticado (Cliente).
     */
    public function minhasSolicitacoes() {
        try {
            $usuarioId = $this->authController->verificarAutenticacao();

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
                      INNER JOIN usuarios u ON s.usuario_id = u.id -- Prestadora do serviço
                      WHERE sol.cliente_id = :usuario_id
                      ORDER BY sol.data_solicitacao DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();
            
            $solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendSuccess($solicitacoes, 'Solicitações listadas com sucesso');

        } catch (PDOException $e) {
            error_log("Erro ao listar solicitações enviadas: " . $e->getMessage());
            sendError('Erro ao listar solicitações', 500);
        }
    }

    /**
     * Lista solicitações recebidas pelo usuário autenticado (Prestadora).
     */
    public function solicitacoesRecebidas() {
        try {
            $usuarioId = $this->authController->verificarAutenticacao();

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
                      INNER JOIN usuarios u ON sol.cliente_id = u.id -- Cliente que solicitou
                      WHERE s.usuario_id = :usuario_id -- Serviços pertencentes ao usuário autenticado (prestadora)
                      ORDER BY sol.data_solicitacao DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();
            
            $solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendSuccess($solicitacoes, 'Solicitações recebidas listadas com sucesso');

        } catch (PDOException $e) {
            error_log("Erro ao listar solicitações recebidas: " . $e->getMessage());
            sendError('Erro ao listar solicitações', 500);
        }
    }

    /**
     * Busca detalhes de uma solicitação específica, verificando se o usuário 
     * é o cliente ou a prestadora.
     * @param int $solicitacaoId O ID da solicitação a ser buscada.
     */
    public function detalhesSolicitacao($solicitacaoId) {
        try {
            // Verifica autenticação
            $usuarioId = $this->authController->verificarAutenticacao();
            $solicitacaoId = intval($solicitacaoId);

            // Consulta para buscar detalhes e verificar propriedade/cliente
            $query = "SELECT 
                        sol.id,
                        sol.mensagem,
                        sol.status,
                        sol.data_solicitacao,
                        sol.data_resposta,
                        s.id as servico_id,
                        s.titulo as servico_titulo,
                        s.descricao as servico_descricao,
                        s.preco_estimado,
                        s.localizacao,
                        s.usuario_id as prestadora_id,
                        CASE 
                            WHEN s.usuario_id = :usuario_id THEN 'prestadora'
                            WHEN sol.cliente_id = :usuario_id THEN 'cliente'
                            ELSE 'nao_autorizado'
                        END as papel_usuario,
                        up.nome as prestadora_nome,
                        up.telefone as prestadora_telefone,
                        uc.nome as cliente_nome,
                        uc.telefone as cliente_telefone
                      FROM solicitacoes sol
                      INNER JOIN servicos s ON sol.servico_id = s.id
                      INNER JOIN usuarios up ON s.usuario_id = up.id -- Dados da Prestadora
                      INNER JOIN usuarios uc ON sol.cliente_id = uc.id -- Dados do Cliente
                      WHERE sol.id = :solicitacao_id
                      AND (s.usuario_id = :usuario_id OR sol.cliente_id = :usuario_id)"; // Apenas a prestadora ou o cliente podem ver
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':solicitacao_id', $solicitacaoId, PDO::PARAM_INT);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();
            
            $solicitacao = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$solicitacao || $solicitacao['papel_usuario'] === 'nao_autorizado') {
                sendError('Solicitação não encontrada ou acesso não autorizado', 404);
            }

            // Remove o campo de controle interno antes de enviar a resposta
            unset($solicitacao['papel_usuario']);
            
            sendSuccess($solicitacao, 'Detalhes da solicitação');

        } catch (PDOException $e) {
            error_log("Erro ao buscar detalhes da solicitação: " . $e->getMessage());
            sendError('Erro ao buscar detalhes da solicitação', 500);
        }
    }

    /**
     * Responde uma solicitação (aceitar/recusar).
     * @param array $data Dados da resposta (solicitacao_id, status).
     */
    public function responderSolicitacao($data) {
        try {
            $usuarioId = $this->authController->verificarAutenticacao();

            if (!isset($data['solicitacao_id']) || !isset($data['status'])) {
                sendError('Dados incompletos', 400);
            }

            $solicitacaoId = intval($data['solicitacao_id']);
            $status = $data['status'];

            if (!in_array($status, ['aceita', 'recusada'])) {
                sendError('Status inválido. Deve ser "aceita" ou "recusada"', 400);
            }

            // 1. Verifica se a solicitação pertence a um serviço da usuária (prestadora) E se está PENDENTE
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
                sendError('Solicitação não encontrada, não pertence a você ou já foi respondida', 404);
            }

            // 2. Atualiza status
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
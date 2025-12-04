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

    public function listarServicos() {
        try {
            $usuarioId = null;
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (isset($_SESSION['usuario_id'])) {
                $usuarioId = $_SESSION['usuario_id'];
            }

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
                      WHERE s.ativo = 1";
            
            if ($usuarioId) {
                $query .= " AND s.usuario_id != :usuario_id";
            }
            
            $query .= " ORDER BY s.data_cadastro DESC";
            
            $stmt = $this->conn->prepare($query);
            
            if ($usuarioId) {
                $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            
            $servicos = $stmt->fetchAll();
            
            sendSuccess($servicos, 'Serviços listados com sucesso');

        } catch (PDOException $e) {
            error_log("Erro ao listar serviços: " . $e->getMessage());
            sendError('Erro ao listar serviços', 500);
        }
    }

    public function meusServicos() {
        try {
            $authController = new AuthController();
            $usuarioId = $authController->verificarAutenticacao();

            $query = "SELECT 
                        s.id,
                        s.titulo,
                        s.descricao,
                        s.categoria,
                        s.preco_estimado,
                        s.localizacao,
                        s.ativo,
                        s.data_cadastro,
                        (SELECT COUNT(*) FROM solicitacoes WHERE servico_id = s.id AND status = 'pendente') as solicitacoes_pendentes
                      FROM servicos s
                      WHERE s.usuario_id = :usuario_id
                      ORDER BY s.data_cadastro DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();
            
            $servicos = $stmt->fetchAll();
            
            sendSuccess($servicos, 'Serviços listados com sucesso');

        } catch (PDOException $e) {
            error_log("Erro ao listar meus serviços: " . $e->getMessage());
            sendError('Erro ao listar serviços', 500);
        }
    }

    public function criarServico($data) {
        try {
            $authController = new AuthController();
            $usuarioId = $authController->verificarAutenticacao();

            $requiredFields = ['titulo', 'descricao', 'categoria', 'localizacao'];
            $missingFields = validateRequiredFields($data, $requiredFields);
            
            if (!empty($missingFields)) {
                sendError('Campos obrigatórios faltando: ' . implode(', ', $missingFields), 400);
            }

            $titulo = sanitizeString($data['titulo']);
            $descricao = sanitizeString($data['descricao']);
            $categoria = sanitizeString($data['categoria']);
            $localizacao = sanitizeString($data['localizacao']);
            $precoEstimado = isset($data['preco_estimado']) ? floatval($data['preco_estimado']) : null;

            if (strlen($titulo) < 5) {
                sendError('Título deve ter no mínimo 5 caracteres', 400);
            }

            if (strlen($descricao) < 20) {
                sendError('Descrição deve ter no mínimo 20 caracteres', 400);
            }

            $query = "INSERT INTO servicos (usuario_id, titulo, descricao, categoria, preco_estimado, localizacao) 
                      VALUES (:usuario_id, :titulo, :descricao, :categoria, :preco_estimado, :localizacao)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':categoria', $categoria);
            $stmt->bindParam(':preco_estimado', $precoEstimado);
            $stmt->bindParam(':localizacao', $localizacao);

            if ($stmt->execute()) {
                sendSuccess([
                    'servico_id' => $this->conn->lastInsertId()
                ], 'Serviço cadastrado com sucesso!', 201);
            } else {
                sendError('Erro ao cadastrar serviço', 500);
            }

        } catch (PDOException $e) {
            error_log("Erro ao criar serviço: " . $e->getMessage());
            sendError('Erro ao processar cadastro', 500);
        }
    }

    public function atualizarServico($data) {
        try {
            $authController = new AuthController();
            $usuarioId = $authController->verificarAutenticacao();

            if (!isset($data['servico_id'])) {
                sendError('ID do serviço é obrigatório', 400);
            }

            $servicoId = intval($data['servico_id']);

            $query = "SELECT id FROM servicos WHERE id = :servico_id AND usuario_id = :usuario_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':servico_id', $servicoId, PDO::PARAM_INT);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                sendError('Serviço não encontrado', 404);
            }

            $titulo = isset($data['titulo']) ? sanitizeString($data['titulo']) : null;
            $descricao = isset($data['descricao']) ? sanitizeString($data['descricao']) : null;
            $categoria = isset($data['categoria']) ? sanitizeString($data['categoria']) : null;
            $localizacao = isset($data['localizacao']) ? sanitizeString($data['localizacao']) : null;
            $precoEstimado = isset($data['preco_estimado']) ? floatval($data['preco_estimado']) : null;
            $ativo = isset($data['ativo']) ? ($data['ativo'] ? 1 : 0) : null;

            $updates = [];
            $params = [':servico_id' => $servicoId];

            if ($titulo !== null) {
                $updates[] = "titulo = :titulo";
                $params[':titulo'] = $titulo;
            }
            if ($descricao !== null) {
                $updates[] = "descricao = :descricao";
                $params[':descricao'] = $descricao;
            }
            if ($categoria !== null) {
                $updates[] = "categoria = :categoria";
                $params[':categoria'] = $categoria;
            }
            if ($localizacao !== null) {
                $updates[] = "localizacao = :localizacao";
                $params[':localizacao'] = $localizacao;
            }
            if ($precoEstimado !== null) {
                $updates[] = "preco_estimado = :preco_estimado";
                $params[':preco_estimado'] = $precoEstimado;
            }
            if ($ativo !== null) {
                $updates[] = "ativo = :ativo";
                $params[':ativo'] = $ativo;
            }

            if (empty($updates)) {
                sendError('Nenhum campo para atualizar', 400);
            }

            $query = "UPDATE servicos SET " . implode(', ', $updates) . " WHERE id = :servico_id";
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            if ($stmt->execute()) {
                sendSuccess([], 'Serviço atualizado com sucesso!');
            } else {
                sendError('Erro ao atualizar serviço', 500);
            }

        } catch (PDOException $e) {
            error_log("Erro ao atualizar serviço: " . $e->getMessage());
            sendError('Erro ao processar atualização', 500);
        }
    }

    public function deletarServico($data) {
        try {
            $authController = new AuthController();
            $usuarioId = $authController->verificarAutenticacao();

            if (!isset($data['servico_id'])) {
                sendError('ID do serviço é obrigatório', 400);
            }

            $servicoId = intval($data['servico_id']);

            $query = "SELECT id FROM servicos WHERE id = :servico_id AND usuario_id = :usuario_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':servico_id', $servicoId, PDO::PARAM_INT);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                sendError('Serviço não encontrado', 404);
            }

            $query = "DELETE FROM servicos WHERE id = :servico_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':servico_id', $servicoId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                sendSuccess([], 'Serviço excluído com sucesso!');
            } else {
                sendError('Erro ao excluir serviço', 500);
            }

        } catch (PDOException $e) {
            error_log("Erro ao deletar serviço: " . $e->getMessage());
            sendError('Erro ao processar exclusão', 500);
        }
    }
}
?>
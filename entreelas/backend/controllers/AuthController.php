<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/response.php';

class AuthController {
    private $conn;
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Cadastra nova usuária
     */
    public function cadastro($data) {
        try {
            // Valida campos obrigatórios
            $requiredFields = ['nome', 'email', 'telefone', 'cidade', 'senha'];
            $missingFields = validateRequiredFields($data, $requiredFields);
            
            if (!empty($missingFields)) {
                sendError('Campos obrigatórios faltando: ' . implode(', ', $missingFields), 400);
            }

            // Sanitiza dados
            $nome = sanitizeString($data['nome']);
            $email = sanitizeString($data['email']);
            $telefone = sanitizeString($data['telefone']);
            $cidade = sanitizeString($data['cidade']);
            $senha = $data['senha'];
            
            // Campos opcionais de prestadora
            $ehPrestadora = isset($data['ehPrestadora']) && $data['ehPrestadora'] === true ? 1 : 0;
            $areaAtuacao = isset($data['areaAtuacao']) ? sanitizeString($data['areaAtuacao']) : null;
            $descricaoProfissional = isset($data['descricaoProfissional']) ? sanitizeString($data['descricaoProfissional']) : null;

            // Validações
            if (strlen($nome) < 3) {
                sendError('Nome deve ter no mínimo 3 caracteres', 400);
            }

            if (!validateEmail($email)) {
                sendError('Email inválido', 400);
            }

            if (strlen($senha) < 8) {
                sendError('Senha deve ter no mínimo 8 caracteres', 400);
            }

            // Verifica se email já existe
            $query = "SELECT id FROM usuarios WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                sendError('Email já cadastrado', 400);
            }

            // Hash da senha
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

            // Insere usuária
            $query = "INSERT INTO usuarios 
                      (nome, email, telefone, cidade, senha, eh_prestadora, area_atuacao, descricao_profissional) 
                      VALUES 
                      (:nome, :email, :telefone, :cidade, :senha, :eh_prestadora, :area_atuacao, :descricao_profissional)";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':telefone', $telefone);
            $stmt->bindParam(':cidade', $cidade);
            $stmt->bindParam(':senha', $senhaHash);
            $stmt->bindParam(':eh_prestadora', $ehPrestadora, PDO::PARAM_INT);
            $stmt->bindParam(':area_atuacao', $areaAtuacao);
            $stmt->bindParam(':descricao_profissional', $descricaoProfissional);

            if ($stmt->execute()) {
                $usuarioId = $this->conn->lastInsertId();
                
                // Se é prestadora E tem área de atuação, cria um serviço inicial automaticamente
                if ($ehPrestadora && $areaAtuacao) {
                    $tituloServico = "Serviço de " . $areaAtuacao;
                    $descricaoServico = $descricaoProfissional ?: "Profissional de " . $areaAtuacao . " oferecendo serviços de qualidade.";
                    
                    $queryServico = "INSERT INTO servicos (usuario_id, titulo, descricao, categoria, localizacao, ativo) 
                                     VALUES (:usuario_id, :titulo, :descricao, :categoria, :localizacao, 1)";
                    
                    $stmtServico = $this->conn->prepare($queryServico);
                    $stmtServico->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                    $stmtServico->bindParam(':titulo', $tituloServico);
                    $stmtServico->bindParam(':descricao', $descricaoServico);
                    $stmtServico->bindParam(':categoria', $areaAtuacao);
                    $stmtServico->bindParam(':localizacao', $cidade);
                    $stmtServico->execute();
                }
                
                sendSuccess([
                    'usuario_id' => $usuarioId
                ], 'Cadastro realizado com sucesso!', 201);
            } else {
                sendError('Erro ao cadastrar usuária', 500);
            }

        } catch (PDOException $e) {
            error_log("Erro no cadastro: " . $e->getMessage());
            sendError('Erro ao processar cadastro', 500);
        }
    }

    /**
     * Realiza login
     */
    public function login($data) {
        try {
            // Valida campos obrigatórios
            $requiredFields = ['email', 'senha'];
            $missingFields = validateRequiredFields($data, $requiredFields);
            
            if (!empty($missingFields)) {
                sendError('Email e senha são obrigatórios', 400);
            }

            $email = sanitizeString($data['email']);
            $senha = $data['senha'];

            // Valida email
            if (!validateEmail($email)) {
                sendError('Email inválido', 400);
            }

            // Busca usuária
            $query = "SELECT id, nome, email, telefone, cidade, senha, foto_perfil, 
                             eh_prestadora, area_atuacao, descricao_profissional 
                      FROM usuarios 
                      WHERE email = :email";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                sendError('Email ou senha incorretos', 401);
            }

            $usuario = $stmt->fetch();

            // Verifica senha
            if (!password_verify($senha, $usuario['senha'])) {
                sendError('Email ou senha incorretos', 401);
            }

            // Remove senha do retorno
            unset($usuario['senha']);

            // Inicia sessão
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['ultimo_acesso'] = time();

            sendSuccess([
                'usuario' => $usuario,
                'token' => session_id()
            ], 'Login realizado com sucesso!');

        } catch (PDOException $e) {
            error_log("Erro no login: " . $e->getMessage());
            sendError('Erro ao processar login', 500);
        }
    }

    /**
     * Realiza logout
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        session_unset();
        session_destroy();
        
        sendSuccess([], 'Logout realizado com sucesso!');
    }

    /**
     * Verifica se está autenticado
     */
    public function verificarAutenticacao() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id'])) {
            sendError('Não autenticado', 401);
        }

        // Verifica timeout de sessão (30 minutos)
        if (isset($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso']) > 1800) {
            session_unset();
            session_destroy();
            sendError('Sessão expirada', 401);
        }

        $_SESSION['ultimo_acesso'] = time();

        return $_SESSION['usuario_id'];
    }
}
?>
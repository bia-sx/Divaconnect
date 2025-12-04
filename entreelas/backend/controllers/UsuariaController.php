<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/AuthController.php';

class UsuariaController {
    private $conn;
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Retorna dados do perfil da usuária logada
     */
    public function meuPerfil() {
        try {
            $authController = new AuthController();
            $usuarioId = $authController->verificarAutenticacao();

            $query = "SELECT id, nome, email, telefone, cidade, foto_perfil, 
                             eh_prestadora, area_atuacao, descricao_profissional,
                             data_cadastro
                      FROM usuarios 
                      WHERE id = :usuario_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                sendError('Usuária não encontrada', 404);
            }

            $usuario = $stmt->fetch();
            
            // Remove senha (segurança extra)
            unset($usuario['senha']);
            
            sendSuccess($usuario, 'Perfil carregado com sucesso');

        } catch (PDOException $e) {
            error_log("Erro ao carregar perfil: " . $e->getMessage());
            sendError('Erro ao carregar perfil', 500);
        }
    }

    /**
     * Atualiza dados do perfil
     */
    public function atualizarPerfil($data) {
        try {
            $authController = new AuthController();
            $usuarioId = $authController->verificarAutenticacao();

            // Valida campos obrigatórios
            $requiredFields = ['nome', 'telefone', 'cidade'];
            $missingFields = validateRequiredFields($data, $requiredFields);
            
            if (!empty($missingFields)) {
                sendError('Campos obrigatórios faltando: ' . implode(', ', $missingFields), 400);
            }

            $nome = sanitizeString($data['nome']);
            $telefone = sanitizeString($data['telefone']);
            $cidade = sanitizeString($data['cidade']);
            $areaAtuacao = isset($data['area_atuacao']) ? sanitizeString($data['area_atuacao']) : null;
            $descricaoProfissional = isset($data['descricao_profissional']) ? sanitizeString($data['descricao_profissional']) : null;

            // Validações
            if (strlen($nome) < 3) {
                sendError('Nome deve ter no mínimo 3 caracteres', 400);
            }

            // Atualiza perfil
            $query = "UPDATE usuarios 
                      SET nome = :nome, 
                          telefone = :telefone, 
                          cidade = :cidade,
                          area_atuacao = :area_atuacao,
                          descricao_profissional = :descricao_profissional
                      WHERE id = :usuario_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':telefone', $telefone);
            $stmt->bindParam(':cidade', $cidade);
            $stmt->bindParam(':area_atuacao', $areaAtuacao);
            $stmt->bindParam(':descricao_profissional', $descricaoProfissional);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                sendSuccess([], 'Perfil atualizado com sucesso!');
            } else {
                sendError('Erro ao atualizar perfil', 500);
            }

        } catch (PDOException $e) {
            error_log("Erro ao atualizar perfil: " . $e->getMessage());
            sendError('Erro ao processar atualização', 500);
        }
    }

    /**
     * Upload de foto de perfil
     */
    public function uploadFoto() {
        try {
            $authController = new AuthController();
            $usuarioId = $authController->verificarAutenticacao();

            // Verifica se foi enviado um arquivo
            if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                sendError('Nenhuma foto foi enviada', 400);
            }

            $file = $_FILES['foto'];

            // Valida tipo de arquivo
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowedTypes)) {
                sendError('Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WEBP', 400);
            }

            // Valida tamanho (máximo 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                sendError('Arquivo muito grande. Máximo 5MB', 400);
            }

            // Cria diretório de uploads se não existir
            $uploadDir = __DIR__ . '/../uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Gera nome único para o arquivo
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'perfil_' . $usuarioId . '_' . time() . '.' . $extension;
            $uploadPath = $uploadDir . $fileName;

            // Remove foto antiga se existir
            $query = "SELECT foto_perfil FROM usuarios WHERE id = :usuario_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();
            $usuario = $stmt->fetch();

            if ($usuario && $usuario['foto_perfil']) {
                $oldFile = $uploadDir . $usuario['foto_perfil'];
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }

            // Move o arquivo
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                sendError('Erro ao salvar arquivo', 500);
            }

            // Atualiza banco de dados
            $query = "UPDATE usuarios SET foto_perfil = :foto_perfil WHERE id = :usuario_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':foto_perfil', $fileName);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                sendSuccess([
                    'foto_perfil' => $fileName
                ], 'Foto atualizada com sucesso!');
            } else {
                // Remove arquivo se falhar no banco
                unlink($uploadPath);
                sendError('Erro ao atualizar foto no banco de dados', 500);
            }

        } catch (Exception $e) {
            error_log("Erro no upload de foto: " . $e->getMessage());
            sendError('Erro ao processar upload', 500);
        }
    }

    /**
     * Remove foto de perfil
     */
    public function removerFoto() {
        try {
            $authController = new AuthController();
            $usuarioId = $authController->verificarAutenticacao();

            // Busca foto atual
            $query = "SELECT foto_perfil FROM usuarios WHERE id = :usuario_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();
            $usuario = $stmt->fetch();

            if (!$usuario || !$usuario['foto_perfil']) {
                sendError('Nenhuma foto para remover', 400);
            }

            // Remove arquivo
            $uploadDir = __DIR__ . '/../uploads/';
            $filePath = $uploadDir . $usuario['foto_perfil'];
            
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Remove do banco
            $query = "UPDATE usuarios SET foto_perfil = NULL WHERE id = :usuario_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                sendSuccess([], 'Foto removida com sucesso!');
            } else {
                sendError('Erro ao remover foto', 500);
            }

        } catch (PDOException $e) {
            error_log("Erro ao remover foto: " . $e->getMessage());
            sendError('Erro ao processar remoção', 500);
        }
    }

    /**
     * Altera senha
     */
    public function alterarSenha($data) {
        try {
            $authController = new AuthController();
            $usuarioId = $authController->verificarAutenticacao();

            // Valida campos
            if (!isset($data['senha_atual']) || !isset($data['senha_nova'])) {
                sendError('Senha atual e nova senha são obrigatórias', 400);
            }

            $senhaAtual = $data['senha_atual'];
            $senhaNova = $data['senha_nova'];

            // Valida tamanho da nova senha
            if (strlen($senhaNova) < 8) {
                sendError('Nova senha deve ter no mínimo 8 caracteres', 400);
            }

            // Busca senha atual
            $query = "SELECT senha FROM usuarios WHERE id = :usuario_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();
            $usuario = $stmt->fetch();

            // Verifica senha atual
            if (!password_verify($senhaAtual, $usuario['senha'])) {
                sendError('Senha atual incorreta', 401);
            }

            // Hash da nova senha
            $senhaHash = password_hash($senhaNova, PASSWORD_DEFAULT);

            // Atualiza senha
            $query = "UPDATE usuarios SET senha = :senha WHERE id = :usuario_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':senha', $senhaHash);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                sendSuccess([], 'Senha alterada com sucesso!');
            } else {
                sendError('Erro ao alterar senha', 500);
            }

        } catch (PDOException $e) {
            error_log("Erro ao alterar senha: " . $e->getMessage());
            sendError('Erro ao processar alteração de senha', 500);
        }
    }

    /**
     * Exclui conta permanentemente
     */
    public function excluirConta() {
        try {
            $authController = new AuthController();
            $usuarioId = $authController->verificarAutenticacao();

            // Busca foto para remover
            $query = "SELECT foto_perfil FROM usuarios WHERE id = :usuario_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();
            $usuario = $stmt->fetch();

            // Remove foto se existir
            if ($usuario && $usuario['foto_perfil']) {
                $uploadDir = __DIR__ . '/../uploads/';
                $filePath = $uploadDir . $usuario['foto_perfil'];
                
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Deleta usuária (CASCADE vai deletar serviços e solicitações)
            $query = "DELETE FROM usuarios WHERE id = :usuario_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                // Destrói sessão
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                session_destroy();

                sendSuccess([], 'Conta excluída com sucesso');
            } else {
                sendError('Erro ao excluir conta', 500);
            }

        } catch (PDOException $e) {
            error_log("Erro ao excluir conta: " . $e->getMessage());
            sendError('Erro ao processar exclusão', 500);
        }
    }
}
?>
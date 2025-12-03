<?php
/**
 * Configuração de Conexão com o Banco de Dados
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'entreelas';
    private $username = 'root';
    private $password = '';
    private $conn;

    /**
     * Obtém a conexão com o banco de dados
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            
            // Define o modo de erro do PDO para exceções
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Define o modo de fetch padrão como associativo
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Desabilita emulação de prepared statements
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
        } catch(PDOException $e) {
            error_log("Erro de conexão: " . $e->getMessage());
            return null;
        }

        return $this->conn;
    }

    /**
     * Fecha a conexão
     */
    public function closeConnection() {
        $this->conn = null;
    }
}
?>
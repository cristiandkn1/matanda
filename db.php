<?php
class Database {
    private $host = "localhost";
    private $user = "root";
    private $password = "";
    private $dbName = "mydb";
    public $conn;

    public function __construct() {
        $this->connectDB();
    }

    private function connectDB() {
        $this->conn = new mysqli($this->host, $this->user, $this->password, $this->dbName);
        
        // Si hay error, registramos pero NO enviamos nada al navegador
        if ($this->conn->connect_error) {
            error_log("❌ Error de conexión: " . $this->conn->connect_error);
            $this->conn = null; // importante para evitar errores posteriores
        }
    }

    public function prepare($sql) {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("❌ Error en prepare(): " . $this->conn->error);
            return false;
        }
        return $stmt;
    }

    public function query($sql) {
        $result = $this->conn->query($sql);
        if (!$result) {
            error_log("❌ Error en query(): " . $this->conn->error);
            return false;
        }
        return $result;
    }

    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

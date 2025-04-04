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
        
        // Si hay error, en lugar de detener el script con die(), devolvemos un mensaje
        if ($this->conn->connect_error) {
            error_log("Error de conexión: " . $this->conn->connect_error);
            echo json_encode(["error" => "No se pudo conectar a la base de datos"]);
            exit(); // Salimos para evitar ejecuciones incorrectas
        }
    }

    public function prepare($sql) {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Error en prepare(): " . $this->conn->error);
            echo json_encode(["error" => "Error en la consulta"]);
            exit();
        }
        return $stmt;
    }

    public function query($sql) {
        $result = $this->conn->query($sql);
        if (!$result) {
            error_log("Error en query(): " . $this->conn->error);
            echo json_encode(["error" => "Error en la consulta"]);
            exit();
        }
        return $result;
    }

    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>

<?php
class Database {
    private string $host = "213.171.200.34";
    private string $dbname = "bhenderson";
    private string $user = "bhenderson";
    private string $pass = "Password20*";
    private string $dsn;

    public function __construct() {
        $this->dsn = "mysql:host={$this->host};dbname={$this->dbname}";
    }

    public function connect(): PDO {
        try {
            $conn = new PDO($this->dsn, $this->user, $this->pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (PDOException $e) {
            header("Location: error_page.php?error=Database error.");
            exit;
        }
    }
}
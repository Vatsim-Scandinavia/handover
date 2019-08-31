<?php

class Database{
 
	private $host = "";
	private $dbName = "";
	private $username = "";
	private $password = "";
	public $conn;

	public function __construct($host, $db, $username, $password){
		$this->host = $host;
		$this->dbName = $db;
		$this->username = $username;
		$this->password = $password;
	}
 
	// Get the database connection
	public function getConnection(){

		$this->conn = null;

		// Config our PDO options to our likeing
		$options = [
		  PDO::ATTR_EMULATE_PREPARES   => false, // Turn off emulation mode for "real" prepared statements
		  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Turn on errors in the form of exceptions
		  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Make the default fetch be an associative array
		];

		try {

		  $this->conn = new PDO("mysql:host=".$this->host.";dbname=".$this->dbName.";charset=utf8mb4", $this->username, $this->password, $options);
		  $this->conn->exec("set names utf8");

		} catch (Exception $e) {

		  echo "Connection error: " . $e->getMessage();

		}
 
		return $this->conn;
	}
}

?>
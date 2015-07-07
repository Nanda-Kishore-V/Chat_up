<?php

require_once("constants.php");

class Database
{
	private $connection;
	public $last_query;

	public function __construct()
	{
		$this->open_connection();
	}

	public function open_connection()
	{
		$this->connection = mysqli_connect(DB_SERVER,DB_USER,DB_PASS,DB_NAME);
		if(!$this->connection)
		{
			die("Database connection failed." . mysqli_connect_error() . "(" . mysqli_connect_errno() . ")");
		}
	}

	public function query($sql="")
	{
		$this->last_query = $sql;
		$result = mysqli_query($this->connection,$sql);
		$this->confirm_query($result);
		return $result;
	}

	private function confirm_query($result)
	{
		if(!$result)
		{
			$message = "Query failed " . mysqli_error($this->connection) ;
			$message .= "<br> Last query was: " . $this->last_query;
			die($message);
		}
	}

	public function last_id()
	{
		return mysqli_insert_id($this->connection);
	}

	public function affected_rows()
	{
		return mysqli_affected_rows($this->connection);
	}

	public function num_rows()
	{
		return mysqli_num_rows($this->connection);
	}

	public function escape_value($string="")
	{
		if($string === null)
		{
			return null;
		}
		else
		{
			return mysqli_real_escape_string($this->connection,$string);
		}
	}

	public function fetch_assoc($result)
	{
		return mysqli_fetch_assoc($result);
	}

	public function close_connection()
	{
		mysqli_close($this->connection);
	}

	public function free_result($result)
	{
		mysqli_free_result($result);
	}
}

$database = new Database();

?>
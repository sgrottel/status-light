<?php
if (1 === preg_match('%/?sqlserver\.php$%i', $_SERVER['PHP_SELF']))
{
	http_response_code(404);
	die();
}

class SqlConnection
{
	private $dbConf = null;
	private $isRW = false;
	public $conn = null;

	public function __construct($dbConf)
	{
		$this->dbConf = $dbConf;
		if ($this->dbConf && $this->dbConf->prefix)
		{
			$this->dbConf->prefix = $this->Sanitize($this->dbConf->prefix);
		}
	}

	public function __destruct()
	{
		$this->Close();
		$this->dbConf = null;
	}

	public function GetPrefix()
	{
		if ($this->dbConf && $this->dbConf->prefix)
		{
			return $this->dbConf->prefix;
		}
		return '';
	}

	public function Sanitize($string)
	{
		return preg_replace('/[^A-Za-z0-9_]/', '', $string);
	}

	public function Close()
	{
		if ($this->conn !== null)
		{
			$this->conn->close();
			$this->conn = null;
		}
	}

	public function OpenRw()
	{
		if ($this->conn && $this->conn->host_info && $this->isRW === true)
		{
			return $this->conn;
		}
		$this->Close();
		$this->conn = new \mysqli(
			$this->dbConf->host,
			$this->dbConf->user_rw,
			$this->dbConf->pass_rw,
			$this->dbConf->name);
		if ($this->conn->connect_errno)
		{
			$this->conn = null;
		}
		else
		{
			$this->conn->set_charset("utf8mb4");
			$this->isRW = true;
		}
		return $this->conn;
	}

	public function OpenRo()
	{
		if ($this->conn && $this->conn->host_info)
		{
			return $this->conn;
		}
		$this->Close();
		$this->conn = new \mysqli(
			$this->dbConf->host,
			$this->dbConf->user_ro,
			$this->dbConf->pass_ro,
			$this->dbConf->name);
		if ($this->conn->connect_errno)
		{
			$this->conn = null;
		}
		else
		{
			$this->conn->set_charset("utf8mb4");
			$this->isRW = false;
		}
		return $this->conn;
	}
}
?>
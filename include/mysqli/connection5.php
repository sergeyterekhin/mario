<?php

require_once(dirname(__FILE__).'/statement.php');

class Connection
{
	static $_dbLink;

	function __construct($server = '', $databaseName = '', $userName = '', $password = '', $encoding = 'utf8')
	{
		self::$_dbLink = new mysqli($server, $userName, $password);		
		
		if (self::$_dbLink === false)
		{
			ErrorHandler::TriggerError('Can\'t connect to database server.', E_ERROR);
		}
		else
		{
			if (self::$_dbLink->select_db($databaseName) === false)
			{
				ErrorHandler::TriggerError("Can't select database", E_ERROR);
			}
		}
		self::$_dbLink->query("SET NAMES ".$this->GetSQLString($encoding));
	}

	function &CreateStatement($resultType = MYSQL_ASSOC, $errorLevel = E_USER_WARNING)
	{
		$stmt = new Statement(self::$_dbLink, $resultType, $errorLevel);
		return $stmt;
	}

	static function GetSQLString($str)
	{
		if (is_null($str))
		{
			return "NULL";
		}
		return "'".self::$_dbLink->real_escape_string($str)."'";		
	}

	static function GetSQLLike($str)
	{		
		return addcslashes(self::$_dbLink->real_escape_string($str), "\\_%'");
	}

	static function GetSQLArray($arr)
	{
		if (is_array($arr))
		{
			foreach ($arr as $key => $value)
				$arr[$key] = Connection::GetSQLString($value);
		}
		return $arr;
	}
}

?>
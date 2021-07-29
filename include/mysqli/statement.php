<?php

require_once(dirname(__FILE__).'/recordset.php');

class Statement
{
	var $_dbLink;
	var $_resultType;
	var $_affectedRows;
	var $_lastInsertID;
	var $_numRows;
	var $_errorLevel;

	function Statement($dbLink, $resultType = MYSQL_ASSOC, $errorLevel = E_USER_WARNING)
	{
		$this->_dbLink = $dbLink;
		$this->_resultType = $resultType;
		$this->_affectedRows = null;
		$this->_lastInsertID = null;
		$this->_numRows = null;
		$this->_errorLevel = $errorLevel;
	}

	function &Execute($query, $resultType = null)
	{
		$result = mysqli_query($this->_dbLink, $query);
		$retVal = null;
		$this->_affectedRows = null;
		$this->_lastInsertID = null;
		$this->_numRows = null;

		if ($result === true)
		{
			$this->_affectedRows = mysqli_affected_rows($this->_dbLink);
			$this->_lastInsertID = mysqli_insert_id($this->_dbLink);
			$retVal = true;
		}
		elseif (is_object($result))
		{
			$this->_numRows = mysqli_num_rows($result);
			if (is_null($resultType))
			{
				$retVal = new RecordSet($result, $this->_resultType);
			}
			else
			{
				$retVal = new RecordSet($result, $resultType);
			}
		}
		else
		{
			$this->_RaiseError($query, mysqli_errno($this->_dbLink), mysqli_error($this->_dbLink));
			$retVal = false;
		}

		return $retVal;
	}

	function GetAffectedRows()
	{
		return $this->_affectedRows;
	}

	function GetLastInsertID()
	{
		return $this->_lastInsertID;
	}

	function GetNumRows()
	{
		return $this->_numRows;
	}

	function FetchList($query, $resultType = null)
	{
		$rs =& $this->Execute($query, $resultType);
		if (strtolower(get_class($rs)) == 'recordset')
			return $rs->AllRows();
		elseif ($rs === false)
			return false;
		else
			return null;
	}

	function FetchIndexedList($query, $field = 0)
	{
		$rs =& $this->Execute($query, MYSQLI_BOTH);
		if (strtolower(get_class($rs)) == 'recordset')
		{
			$result = $rs->AllRows();
			$indexedResult = array();
			for ($i = 0; $i < count($result); $i++)
			{
				if (array_key_exists($field, $result[$i]))
					$indexedResult[$result[$i][$field]] = $result[$i];
			}
			return $indexedResult;
		}
		elseif ($rs === false)
		{
			return false;
		}
		else
		{
			return null;
		}
	}

	function FetchRow($query, $resultType = null)
	{
		$rs =& $this->Execute($query, $resultType);
		if (strtolower(get_class($rs)) == 'recordset')
			return $rs->NextRow();
		elseif ($rs === false)
			return false;
		else
			return null;
	}

	function FetchField($query, $field = 0)
	{
		$rs =& $this->Execute($query, MYSQLI_BOTH);
		if (strtolower(get_class($rs)) == 'recordset')
		{
			$row = $rs->NextRow();
			if (is_null($row))
			{
				return null;
			}
			else if (is_array($row) && array_key_exists($field, $row))
			{
				return $row[$field];
			}
			else
			{
				$this->_RaiseError($query, "0", "Field '".$field."' not found in field list");
				return false;
			}
		}
		elseif ($rs === false)
		{
			return false;
		}
		else
		{
			return null;
		}
	}

	function _ToLog($query)
	{
		$query = str_replace("\r", '', $query);
		$query = str_replace("\n", '', $query);
		$query = preg_replace("/[\s]+/", " ", $query);
		return trim($query);
	}

	function _RaiseError($query, $errNo, $errStr)
	{
		if ($this->_errorLevel)
			ErrorHandler::TriggerError('('.$errNo.') '.$errStr.' in query: '.$this->_ToLog($query), $this->_errorLevel);
	}
}

?>
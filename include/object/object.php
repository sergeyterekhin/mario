<?php

require_once(dirname(__FILE__)."/commonobject.php");

class BaseObject extends CommonObject
{

	var $_properties;

	function BaseObject($data = array(), $statement = null)
	{
		if (is_array($data))
		{
			$this->LoadFromArray($data);
		}
		else if (!is_null($data))
		{
			$this->LoadFromSQL($data, $statement);
		}
		else
		{
			$this->LoadFromArray(array());
		}
	}

	function LoadFromArray($data)
	{
		$this->_properties = $data;
	}

	function LoadFromSQL($query, $statement = null)
	{
		$properties = $statement->FetchRow($query);
		$this->_properties = is_array($properties) ? $properties : array();
	}

	function LoadFromObject($object, $properties = array())
	{
		if (is_array($properties) && count($properties) > 0)
		{
			for ($i = 0; $i < count($properties); $i++)
			{
				$this->_properties[$properties[$i]] = $object->GetProperty($properties[$i]);
			}
		}
		else
		{
			$this->_properties = $object->GetProperties();
		}
	}

	function AppendFromArray($data)
	{
		$this->_properties = array_merge($this->_properties, $data);
	}

	function AppendFromSQL($query, $statement = null)
	{
		$properties = $statement->FetchRow($query);
		if (is_array($properties))
		{
			$this->_properties = array_merge($this->_properties, $properties);
		}
	}

	function AppendFromObject($object, $properties = array())
	{
		if (is_array($properties) && count($properties) > 0)
		{
			for ($i = 0; $i < count($properties); $i++)
			{
				$this->_properties[$properties[$i]] = $object->GetProperty($properties[$i]);
			}
		}
		else
		{
			$this->_properties = array_merge($this->_properties, $object->GetProperties());
		}
	}

	function CountProperties()
	{
		return count($this->_properties);
	}

	function GetProperties()
	{
		return $this->_properties;
	}

	function SetProperty($name, $value)
	{
		$this->_properties[$name] = $value;
	}

	function RemoveProperty($name)
	{
		unset($this->_properties[$name]);
	}

	function IsPropertySet($name)
	{
		return isset($this->_properties[$name]);
	}

	function GetProperty($name)
	{
		$properties = $this->GetProperties();
		return isset($properties[$name]) ? $properties[$name] : null;
	}

	function GetPropertyForSQL($name)
	{
		return Connection::GetSQLString($this->GetProperty($name));
	}

	function GetPropertyForURL($name)
	{
		return urlencode($this->GetProperty($name));
	}

	function GetIntProperty($name)
	{
		return intval($this->GetProperty($name));
	}

	function GetFloatProperty($name)
	{
		return floatval($this->GetProperty($name));
	}

	function ValidateNotEmpty($name)
	{
		return strlen($this->GetProperty($name)) > 0;
	}

	function ValidateInt($name)
	{
//		return is_int($this->GetProperty($name));
		return ($this->GetIntProperty($name)."" == $this->GetProperty($name));
	}

	function ValidateFloat($name)
	{
		return is_float($this->GetProperty($name));
	}

	function ValidateEmail($name)
	{
		return preg_match("/^[a-z0-9\._-]+@([a-z0-9_-]+\.)+[a-z0-9_-]+/i", $this->GetProperty($name)); // improve this
	}

	function ValidateDate($name, $format)
	{
		switch($format)
		{
			case "dd-mm-yyyy":
				if (preg_match("/^(\d\d?)-(\d\d?)-(\d\d\d\d)$/i", $this->GetProperty($name), $matches))
				{
					return (checkdate($matches[2], $matches[1], $matches[3]));
				}
			break;
			case "mm-dd-yyyy":
				if (preg_match("/^(\d\d?)-(\d\d?)-(\d\d\d\d)$/i", $this->GetProperty($name), $matches))
				{
					return (checkdate($matches[1], $matches[2], $matches[3]));
				}
			break;
			case "dd.mm.yyyy":
				if (preg_match("/^(\d\d?)\.(\d\d?)\.(\d\d\d\d)$/i", $this->GetProperty($name), $matches))
				{
					return (checkdate($matches[2], $matches[1], $matches[3]));
				}
			break;
			case "mm.dd.yyyy":
				if (preg_match("/^(\d\d?)\.(\d\d?)\.(\d\d\d\d)$/i", $this->GetProperty($name), $matches))
				{
					return (checkdate($matches[1], $matches[2], $matches[3]));
				}
			break;
		}
		return false;

	}

}
?>
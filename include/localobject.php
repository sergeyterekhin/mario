<?php
es_include("object/object.php");

class LocalObject extends BaseObject
{

	function LocalObject($data = array(), $statement = null)
	{
		if ($statement === null && !is_array($data) && !is_null($data))
		{
			$statement = GetStatement();
		}
		$this->BaseObject($data, $statement);
	}

	function LoadFromSQL($query, $statement = null)
	{
		if ($statement === null)
		{
			$statement = GetStatement();
		}
		parent::LoadFromSQL($query, $statement);
	}

	function AppendFromSQL($query, $statement = null)
	{
		if ($statement === null)
		{
			$statement = GetStatement();
		}
		parent::AppendFromSQL($query, $statement);
	}

}
?>
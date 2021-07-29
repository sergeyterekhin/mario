<?php
es_include("object/objectlist.php");

class LocalObjectList extends ObjectList
{
	function LocalObjectList($data = array(), $statement = null)
	{
		if ($statement === null && !is_array($data) && !is_null($data))
		{
			$statement = GetStatement();
		}
		parent::ObjectList($data, $statement);
	}

	function LoadFromSQL($query, $statement = null)
	{
		if ($statement === null)
		{
			$statement = GetStatement();
		}
		parent::LoadFromSQL($query, $statement);
	}
}
?>
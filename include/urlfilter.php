<?php

/**
 * Class URLFilter stores GET/POST parameters
 *
 * @package SiteManager
 * @author Nikolay Nikolaev <nikolay.nikolaev@eurostudio.net>
 * @copyright 2009 Eurostudio
 * @since 2009-06-17
*/
es_include("localobject.php");

class URLFilter extends LocalObject
{
	/**
	 * Returns parameters as GET string (name1=value1&name2=value2)
	 * @access public
	 * @param array $exclude parameters to exclude from result
	 * @return string
	 */
	function GetForURL($exclude = array())
	{
		$result = $this->_GetAsArray($exclude);

		$p = array();
		$str = "";

		if (count($result) > 0)
		{
			for ($i = 0; $i < count($result); $i++)
			{
				$p[] = $result[$i]["Name"]."=".$result[$i]["Value"];
			}
			$str = implode("&", $p);
		}

		return $str;
	}

	/**
	 * Returns parameters as list of <input type="hidden" name="PropertyName" value="PropertyValue" />
	 * to be used on form
	 * @access public
	 * @param array $exclude parameters to exclude from result
	 * @return string
	 */
	function GetForForm($exclude = array())
	{
		$result = $this->_GetAsArray($exclude);

		$str = "";

		if (count($result) > 0)
		{
			for ($i = 0; $i < count($result); $i++)
			{
				$str .= "<input type=\"hidden\" name=\"".$result[$i]["Name"]."\" value=\"".$result[$i]["Value"]."\" />\r\n";
			}
		}

		return $str;
	}

	/**
	 * Returns parameters as array
	 * to be used on form
	 * @access private
	 * @return array
	 */
	function _GetAsArray($exclude = array())
	{
		$params = $this->GetProperties();

		$result = array();

		foreach ($params as $key => $value)
		{
			if (false == in_array($key, $exclude))
			{
				if (is_array($value))
				{
					if (count($value) > 0)
					{
						foreach ($value as $k => $v)
						{
							if (is_array($v))
								$result[] = array("Name" => $key."[".$k."]", "Value" => "Array");
							else
								$result[] = array("Name" => $key."[".$k."]", "Value" => urlencode($v));
						}
					}
				}
				else if (strlen($value) > 0)
				{
					$result[] = array("Name" => $key, "Value" => urlencode($value));
				}
			}
		}

		return $result;
	}
}

?>
<?php

es_include("vlibtemplate/vlibtemplate.php");

class Template extends VLibTemplateCache
{

	function Template($tmplFile = null, $options = null)
	{
		parent::VLibTemplate($tmplFile, $options);

		// date without time
		$this->formatTags['date'] = array('open' => '$this->_FormatDate(', 'close'=> ')');

		// date with time
		$this->formatTags['datetime'] = array('open' => '$this->_FormatDate(', 'close'=> ',true)');

		// time
		$this->formatTags['time'] = array('open' => '$this->_FormatTime(', 'close'=> ')');

		// rfc8222 date
		$this->formatTags['rfc2822'] = array('open' => '$this->_FormatRFC8222(', 'close'=> ')');

		/*@var language Language */
		$language =& GetLanguage();

		// Data language
		$this->SetVar("DATA_LANGCODE", DATA_LANGCODE);
		$this->SetVar("DATA_LANGNAME", $language->GetDataLanguageName());
		$lngList = $language->GetDataLanguageList();
		if (count($lngList) > 1)
			$this->SetLoop("DataLanguageList", array_values($lngList));

		// Interface language
		$this->SetVar("INTERFACE_LANGCODE", INTERFACE_LANGCODE);
		$this->SetVar("INTERFACE_LANGNAME", $language->GetInterfaceLanguageName());
		$lngList = $language->GetInterfaceLanguageList();
		if (count($lngList) > 1)
			$this->SetLoop("InterfaceLanguageList", array_values($lngList));

		$this->SetVar("CHARSET", $language->GetHTMLCharset());
		$this->SetVar("PROJECT_PATH", PROJECT_PATH);
		$this->SetVar("ADMIN_PATH", ADMIN_PATH);
		$this->SetVar("URL_PREFIX", GetUrlPrefix());
		$this->SetVar("INDEX_PAGE", INDEX_PAGE);
		$this->SetVar("HTML_EXTENSION", HTML_EXTENSION);
		$this->SetVar("WEBSITE_FOLDER", WEBSITE_FOLDER);
		$this->SetVar("WEBSITE_NAME", WEBSITE_NAME);
		$this->SetVar("DEV_MODE", GetFromConfig('DevMode', 'common'));

		$session =& GetSession();
		$user = $session->GetProperty("LoggedInUser");
		if (is_array($user))
		{
			// Do not show website list for users which are assigned to the website
			if (is_array($GLOBALS["WebsiteList"]) && count($GLOBALS["WebsiteList"]) > 1 && !isset($user["WebsiteID"]))
			{
				$this->SetLoop("WebsiteList", $GLOBALS["WebsiteList"]);
			}

			foreach ($user as $k => $v)
			{
				$this->SetVar("USER_".$k, $v);
			}
		}
	}

	function LoadFromArray($data)
	{
		foreach ($data as $k => $v)
		{
			if (is_array($v))
				$this->SetLoop($k, $v);
			else
				$this->SetVar($k, $v);
		}
	}

	function LoadFromObject($object, $properties = array())
	{
		if (is_array($properties) && count($properties) > 0)
		{
			for ($i = 0; $i < count($properties); $i++)
			{
				$v = $object->GetProperty($properties[$i]);

				if (is_array($v))
					$this->SetLoop($properties[$i], $v);
				else
					$this->SetVar($properties[$i], $v);
			}
		}
		else
		{
			$this->LoadFromArray($object->GetProperties());
		}
	}

	function SetLoop($k, $v)
	{
		// TODO: Create warning
		$result = true;

		if (is_array($v))
		{
			for ($i = 0; $i < count($v); $i++)
			{
				if (!isset($v[$i]) || !is_array($v[$i]))
				{
					$result = false;
					break;
				}
			}
		}
		else
		{
			$result = false;
		}

		if ($result)
		{
			parent::SetLoop($k, $v);
		}
	}

	function LoadFromObjectList($name, $object)
	{
		$this->SetLoop($name, $object->GetItems());
	}

	function LoadErrorsFromObject($object)
	{
		$this->SetLoop("ErrorList", $object->GetErrorsAsArray());
	}

	function LoadMessagesFromObject($object)
	{
		$this->SetLoop("MessageList", $object->GetMessagesAsArray());
	}

	function LoadTemplateList($template = "")
	{
		$templateDir = PROJECT_DIR."website/".WEBSITE_FOLDER."/template/";
		$templateList = array();
		if ($dh = opendir($templateDir))
		{
			while (($file = readdir($dh)) !== false)
			{
				if (preg_match("/^page(.*)\.html$/", $file))
				{
					if (substr($file, 5, -5) == '')
						$templateList[] = array("FileName" => $file, "Template" => GetTranslation('template-general'), "Selected" => ($file == $template));
					else
						$templateList[] = array("FileName" => $file, "Template" => GetTranslation('template-'.substr($file, 5, -5)), "Selected" => ($file == $template));
				}
			}
			closedir($dh);
		}
		if (count($templateList) > 1)
		{
			$this->SetLoop("TemplateList", $templateList);
		}
		else if (count($templateList) == 1)
		{
			$this->SetVar("TemplateOne", $templateList[0]['Template']);
			$this->SetVar("Template", $templateList[0]['FileName']);
		}
		else
		{
			$this->SetLoop("ErrorList", array(0 => array('Message' => GetTranslation('no-templates', array('Folder' => PROJECT_PATH."website/".WEBSITE_FOLDER."/template/")))));
		}
	}


	function GetTemplateSets($module, $set = "")
	{
		$templateDir = PROJECT_DIR."website/".WEBSITE_FOLDER."/template/";
		$templateSets = array();
		$l = strlen($module);
		if ($dh = opendir($templateDir))
		{
			while (($file = readdir($dh)) !== false)
			{
				if (is_dir($templateDir.$file) && substr($file, 0, $l) == $module)
				{
					$cSet = substr($file, $l + 1);
					$templateSets[] = array("SetName" => $cSet, "SetTitle" => GetTranslation($cSet.'-title', $module), "Selected" => ($cSet == $set));
				}
			}
			closedir($dh);
		}
		return $templateSets;
	}

	function LoadModuleTemplateSets($module, $set = "")
	{
		$templateSets = $this->GetTemplateSets($module, $set);

		if (count($templateSets) > 1)
		{
			$this->SetLoop("TemplateSets", $templateSets);
		}
		else if (count($templateSets) == 1)
		{
			$this->SetVar("Template", $templateSets[0]["SetName"]);
		}
		else
		{
			$this->SetVar("Template", "");
		}
	}

	function _FormatDate($date, $showTime = false)
	{
		if (empty($date))
			return null;

		$language =& GetLanguage();

		if ($showTime)
			$format = $language->GetDateFormat()." ".$language->GetTimeFormat();
		else
			$format = $language->GetDateFormat();

		return LocalDate($format, strtotime($date));
	}

	function _FormatTime($date)
	{
		if (empty($date))
			return null;

		$language =& GetLanguage();
		$format = $language->GetTimeFormat();

		return LocalDate($format, strtotime($date));
	}

	function _FormatRFC8222($date)
	{
		if (empty($date))
			return null;

		return date("r", strtotime($date));
	}
}

?>
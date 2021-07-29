<?php

es_include("urlparser.php");

class Website
{
	var $_tags;
	var $_values;
	var $_websiteList = null;
	var $_cacheLifeTime = 604800;
	var $_cacheFile;

	function Website()
	{
		// Load Website & Language lists
		$file = PROJECT_DIR."website/configure.xml";
		if ($this->_CheckCache($file))
		{
			$data = fread($fp = fopen($this->_cacheFile, 'r'), filesize($this->_cacheFile));
			$this->_websiteList = unserialize($data);
		}
		else
		{
			$this->_LoadXML($file);
			$this->_websiteList = $this->_GetWebsiteXMLAsArray($file);
			$this->_CreateCache(serialize($this->_websiteList));
		}

		$urlParser =& GetURLParser();
		$path = $urlParser->GetFullPathAsString();
		$host = $urlParser->GetHostName();

		$current = array();
		$default = array();
		$loadFromConfigure = false;

		$request = new LocalObject(array_merge($_GET, $_POST));
		$cookie = new LocalObject($_COOKIE);
		$aDLangCode = '';

		if (strlen($cookie->GetProperty("DLangCode")) > 0)
		{
			// Use data language from cookie
			$aDLangCode = $cookie->GetProperty("DLangCode");
		}

		$GLOBALS["WebsiteList"] = array();
		$GLOBALS["WebsiteLogo"] = "";

		if (defined('IS_ADMIN'))
		{
			// Define WebsiteID for admin
			$adminWebsiteID = null;

			foreach ($this->_websiteList as $websiteID => $website)
			{
				$webHost = null;
				$len = strlen($website['WebDir']);
				foreach ($website["Language"] as $k => $v)
				{
					foreach ($v["Domain"] as $k1 => $v1)
					{
						if (is_null($webHost)) $webHost = $k1;

						if (substr($path, 0, $len) == $website['WebDir'] && $k1 == $host)
						{
							$adminWebsiteID = $websiteID;

							// check if redirect is needed							
							if (isset($v['Domain'][$host]))
							{	
								$this->CheckForRedirect($v['Domain'], $host);					
							}
						}
					}
				}

				if (!is_null($webHost))
					$adminURL = "http://".$webHost.$website["WebDir"].ADMIN_FOLDER."/index.php";
				else
					$adminURL = "";

				$GLOBALS["WebsiteList"][] = array("WebsiteID" => $websiteID,
					"WebDir" => $website["WebDir"], "Name" => $website["Name"],
					"AdminURL" => $adminURL, "Selected" => false);
			}

			if (!is_null($adminWebsiteID))
			{
				for ($i = 0; $i < count($GLOBALS["WebsiteList"]); $i++)
				{
					if ($GLOBALS["WebsiteList"][$i]["WebsiteID"] == $adminWebsiteID)
						$GLOBALS["WebsiteList"][$i]["Selected"] = true;
				}

				// Get language list for defined WebsiteID
				$lngList = $this->_websiteList[$adminWebsiteID]["Language"];

				// Go through language list to define default & current languages
				$j = 0;
				foreach ($lngList as $lngCode => $lngDetails)
				{
					if ((isset($lngDetails['Default']) && $lngDetails['Default'] == true) || $j == 0)
					{
						$default['WebsiteID'] = $adminWebsiteID;
						$default['Folder'] = $this->_websiteList[$adminWebsiteID]["Folder"];
						$default['Name'] = $this->_websiteList[$adminWebsiteID]["Name"];
						$default['ProjectPath'] = $this->_websiteList[$adminWebsiteID]['WebDir'];
						$default['DLangCode'] = $lngDetails['Folder'];
						$default['LanguageList'] = $lngList;
						$default['ModuleList'] = $this->_websiteList[$adminWebsiteID]['ModuleList'];
					}

					// For admin use data language to determine current language
					if ($aDLangCode == $lngDetails['Folder'])
					{
						$current['WebsiteID'] = $adminWebsiteID;
						$current['Folder'] = $this->_websiteList[$adminWebsiteID]["Folder"];
						$current['Name'] = $this->_websiteList[$adminWebsiteID]["Name"];
						$current['ProjectPath'] = $this->_websiteList[$adminWebsiteID]['WebDir'];
						$current['DLangCode'] = $lngDetails['Folder'];
						$current['LanguageList'] = $lngList;
						$current['ModuleList'] = $this->_websiteList[$adminWebsiteID]['ModuleList'];

						// For admin we found language by cookie, we can finish further searching
						break;
					}
					$j++;
				}
			}
		}
		else
		{
			foreach ($this->_websiteList as $websiteID => $website)
			{
				$lngList = $website["Language"];

				$j = 0;
				foreach ($lngList as $lngCode => $lngDetails)
				{
					if (!isset($lngDetails['Domain']))
					{
						$loadFromConfigure = true;
						break 2;
					}

					if (isset($lngDetails['Domain'][$host]))
					{		
						// Check if redirect is needed
						$this->CheckForRedirect($lngDetails['Domain'], $host);
						
						$langDir = $lngDetails['Domain'][$host]['LangDir'];
						$len = strlen($website['WebDir']);
						// By WebDir we define WebsiteID
						if (substr($path, 0, $len) == $website['WebDir'])
						{
							// In case current will not be found will be used default or first
							if ((isset($lngDetails['Default']) && $lngDetails['Default'] == true) || $j == 0)
							{
								$default['WebsiteID'] = $websiteID;
								$default['Folder'] = $website["Folder"];
								$default['Name'] = $website["Name"];
								$default['ProjectPath'] = $website['WebDir'];
								$default['DLangCode'] = $lngDetails['Folder'];
								$default['LanguageList'] = $lngList;
								$default['ModuleList'] = $website["ModuleList"];
							}

							// By full path we define language
							$len = strlen($website['WebDir'].$langDir);
							if (substr($path, 0, $len) == $website['WebDir'].$langDir)
							{
								$current['WebsiteID'] = $websiteID;
								$current['Folder'] = $website["Folder"];
								$current['Name'] = $website["Name"];
								$current['ProjectPath'] = $website['WebDir'];
								$current['DLangCode'] = $lngDetails['Folder'];
								$current['LanguageList'] = $lngList;
								$current['ModuleList'] = $website["ModuleList"];

								// Continue to search next language because it can be further in the path
								if ($langDir != '')
								{
									// For public we found language by path, we can finish further searching
									break 2;
								}
							}
						}
					}
					$j++;
				}
			}
		}

		if ($loadFromConfigure)
		{
			echo "TODO: Load From Configure\r\n";
			exit();
		}
		else
		{
			if (count($current) == 0 && count($default) > 0)
			{
				$current = $default;
			}

			if (count($current) > 0)
			{
				define("PROJECT_PATH", $current['ProjectPath']);
				define("WEBSITE_ID", $current['WebsiteID']);
				define("WEBSITE_FOLDER", $current['Folder']);
				define("WEBSITE_NAME", $current['Name']);
				define("DATA_LANGCODE", $current['DLangCode']);
				$GLOBALS["AvailableModuleList"] = $current['ModuleList'];
				$GLOBALS["WebsiteLogo"] = $this->_websiteList[$current['WebsiteID']]["Logo"];

				// Prepare language list for class Language
				$lngList = array();
				foreach ($current['LanguageList'] as $k => $v)
				{
					// If we have for language record for current domain use it, else get first domain
					if (isset($v['Domain'][$host]))
						$domainInfo = $v['Domain'][$host];
					else
						$domainInfo = array_shift($v['Domain']);

					unset($v['Domain']);

					$domainInfo['LangURL'] = "http://".$domainInfo['HostName']."/".$domainInfo['LangDir'];

					$lngList[$k] = array_merge($v, $domainInfo);
				}

				$language =& GetLanguage();

				// For XML use UTF-8
				if ($urlParser->contentType == "text/xml")
					$language->SetLanguageList($lngList, true);
				else
					$language->SetLanguageList($lngList, false);

				header("Content-Type: ".$urlParser->contentType."; charset=".$language->GetHTMLCharset());
			}
			else
			{
				Send404();
			}
		}
	}

	function CheckForRedirect($domainList, $currentDomain) 
	{
		$hostData = $domainList[$currentDomain];
		
		// if we have redirect issue in configure.xml
		if (isset($hostData['Redirect']))
		{
			if ($hostData['Redirect'] != 'no')
			{
				Send301("http://".$hostData['Redirect'].$_SERVER['REQUEST_URI']);
				exit;
			}
		}
		// other way, 301 redirect to first domain in configure.xml
		// if current domain is not first
		else
		{
			foreach($domainList as $k => $v)
			{
				if ($k != $currentDomain)
				{
					Send301("http://".$k.$_SERVER['REQUEST_URI']);
					exit;
				}
				break;
			}
		}
		
	}
	
	function _GetWebsiteXMLAsArray($file)
	{
		$websites = array();
		$websiteID = null;
		foreach ($this->_values as $id => $value)
		{
			if ($value["level"] == 2 && $value["tag"] == "Website")
			{
				if (isset($value["attributes"]["WebsiteID"]) &&
					isset($value["attributes"]["Folder"]) &&
					isset($value["attributes"]["Name"]) &&
					isset($value["attributes"]["WebDir"]) && $value["type"] == "open")
				{
					$websiteID = $value["attributes"]["WebsiteID"];
					$websites[$websiteID]["Folder"] = $value["attributes"]["Folder"];
					$websites[$websiteID]["Name"] = $value["attributes"]["Name"];
					$websites[$websiteID]["WebDir"] = $value["attributes"]["WebDir"];
					$websites[$websiteID]["ModuleList"] = array();
					if (isset($value["attributes"]["ModuleList"]) &&
						strlen($value["attributes"]["ModuleList"]) > 0)
					{
						$moduleList = explode(",", $value["attributes"]["ModuleList"]);
						for ($i = 0; $i < count($moduleList); $i++)
						{
							$moduleList[$i] = strtolower(($moduleList[$i]));
						}
						$websites[$websiteID]["ModuleList"] = $moduleList;
					}
					$websites[$websiteID]["Logo"] = "";
					if (isset($value["attributes"]["Logo"]) &&
						strlen($value["attributes"]["Logo"]) > 0)
					{
						$websites[$websiteID]["Logo"] = $value["attributes"]["Logo"];
					}

					$websites[$websiteID][$value["type"]] = $id;
				}
				else if ($value["type"] == "close" && !is_null($websiteID))
				{
					$websites[$websiteID][$value["type"]] = $id;
					$websiteID = null;
				}
			}
		}

		if (count($websites) == 0)
		{
			ErrorHandler::TriggerError("Tag <Website> is not defined in the XML file \"".$file."\"", E_ERROR);
		}

		$requiredLangAttributes = array("Folder", "Name", "NativeName", "Encoding", "DateFormat", "TimeFormat");
		$requiredDomainAttributes = array("HostName", "LangDir");
		$langCode = null;

		foreach ($websites as $id => $details)
		{
			$websites[$id]["Language"] = array();
			if (isset($details["open"]) && isset($details["close"]))
			{
				for ($i = $details["open"] + 1; $i < $details["close"]; $i++)
				{
					if ($this->_values[$i]["level"] == 3 && $this->_values[$i]["tag"] == "Language" && ($this->_values[$i]["type"] == "open" || $this->_values[$i]["type"] == "complete"))
					{
						for ($j = 0; $j < sizeof($requiredLangAttributes); $j++)
						{
							if (!isset($this->_values[$i]["attributes"][$requiredLangAttributes[$j]]))
							{
								ErrorHandler::TriggerError("Attribute \"".$requiredLangAttributes[$j]."\" is absent for tag <Language> in file \"".$file."\"!", E_ERROR);
							}
						}

						$langCode = $this->_values[$i]["attributes"]["Folder"];
						$websites[$id]["Language"][$langCode] = $this->_values[$i]["attributes"];
						$websites[$id]["Language"][$langCode]["Domain"] = array();
					}
					else if ($this->_values[$i]["level"] == 3 && $this->_values[$i]["tag"] == "Language" && $this->_values[$i]["type"] == "close")
					{
						$langCode = null;
					}

					if ($this->_values[$i]["level"] == 4 && $this->_values[$i]["tag"] == "Domain" && $this->_values[$i]["type"] == "complete" && !is_null($langCode))
					{
						for ($j = 0; $j < sizeof($requiredDomainAttributes); $j++)
						{
							if (!isset($this->_values[$i]["attributes"][$requiredDomainAttributes[$j]]))
							{
								ErrorHandler::TriggerError("Attribute \"".$requiredDomainAttributes[$j]."\" is absent for tag <Domain> in file \"".$file."\"!", E_ERROR);
							}
						}

						$websites[$id]["Language"][$langCode]["Domain"][$this->_values[$i]["attributes"]["HostName"]] = $this->_values[$i]["attributes"];
					}
				}
			}
			if (count($websites[$id]["Language"]) == 0)
			{
				ErrorHandler::TriggerError("No tag <Language> is defined under tag <Website> in the XML file \"".$file."\"", E_ERROR);
			}
			unset($websites[$id]["open"]);
			unset($websites[$id]["close"]);
		}

		return $websites;
	}

	function _LoadXML($file)
	{
		if (!file_exists($file))
		{
			ErrorHandler::TriggerError("XML file \"".$file."\" does not exists!", E_ERROR);
		}
		else
		{
			$parser = xml_parser_create("UTF-8");
			xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
			xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
			xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
			xml_parse_into_struct($parser, implode("", file($file)), $this->_values, $this->_tags);
			xml_parser_free($parser);
		}
	}

	function _CheckCache($xmlFile)
	{
		$this->_cacheFile = $this->_GetFilename($xmlFile);

		if (file_exists($this->_cacheFile))
		{
			// if it's expired
			if ((filemtime($this->_cacheFile) + $this->_cacheLifeTime) < date('U') ||
				filemtime($this->_cacheFile) < filemtime($xmlFile))
			{
				return false; // so that we know to recache
			}
			else
			{
				return true;
			}
		}
		else
		{
			return false;
		}
	}


	function _GetFilename($xmlFile)
	{
		return XML_CACHE_DIR.md5('XMLCachestaR'.$xmlFile).'.xtc';
	}

	function _CreateCache($data)
	{
		$cacheFile = $this->_cacheFile;

		$f = fopen($cacheFile, "w");
		if ($f)
		{
			flock($f, 2); // set an exclusive lock
			fputs($f, $data); // write the serialized array
			flock($f, 3); // unlock file
			fclose($f);
			touch($cacheFile);
			@chmod($cacheFile, 0666);
			return true;
		}
		else
		{
			return false;
		}
	}
}

?>
<?php

class Module
{
	var $_moduleList = null;
	var $_handlerClass;

	function Module()
	{
		$this->_moduleList = array();
		$i = 0;
		if ($dh = opendir(PROJECT_DIR."module/"))
		{
			while (false !== ($file = readdir($dh)))
			{
				if ($file != "." && $file != ".." && is_dir(PROJECT_DIR."module/".$file))
				{
					$name = strtolower($file);
					if (!in_array($name, $GLOBALS["AvailableModuleList"])) continue;

					$this->_moduleList[$name]["Folder"] = $name;
					$this->_moduleList[$name]["Class"] = ucfirst($name)."Handler";
					$this->_moduleList[$name]["AdminClass"] = "Admin".ucfirst($name)."Handler";
					$this->_moduleList[$name]["Title"] = GetTranslation("module-title", $name);
					$this->_moduleList[$name]["AdminTitle"] = GetTranslation("module-admin-title", $name);
					$i++;
				}
			}
			closedir($dh);
		}
	}

	function GetModuleList($selected = '', $adminOnly = false, $withConfig = false)
	{
		$mLinks = array();
		$i = 0;
		foreach ($this->_moduleList as $k => $v)
		{
			$file = PROJECT_DIR.'module/'.$v['Folder'].'/admin.php';
			if (($adminOnly == true && file_exists($file)) || $adminOnly == false)
			{
				$mLinks[$i] = $v;
				$mLinks[$i]['Link'] = 'module.php?load='.$v['Folder'];
				if ($v['Folder'] == $selected)
				{
					$mLinks[$i]['Selected'] = true;
				}
				require_once(PROJECT_DIR.'module/'.$v['Folder'].'/init.php');
				$data = $GLOBALS['moduleConfig'][$v['Folder']];
				$mLinks[$i]['ColorA'] = $data['ColorA'];
				$mLinks[$i]['ColorI'] = $data['ColorI'];
				if ($withConfig)
					$mLinks[$i]['Config'] = $data['Config'];
				$i++;
			}
		}
		return $mLinks;
	}

	function ModuleExists($folder)
	{
		if (isset($this->_moduleList[$folder]))
			return true;
		else
			return false;
	}

	function LoadForPublic($folder, $templateSet, $pathToModule, $pathInsideModule, $header, $pageID, $config)
	{
		if (!$this->_ValidateModule($folder))
			return false;

		eval("\$m = new ".$this->_handlerClass."();");

		if (!method_exists($m, "InitPublic"))
		{
			return false;
		}
		else
		{
			$m->InitPublic($folder, $templateSet, $pathToModule, $pathInsideModule, $header, $pageID, $config);
			return true;
		}
	}

	function LoadForAdmin($folder, $pageID, $config)
	{
		if (!$this->_ValidateModule($folder))
			return false;

		eval("\$m = new ".$this->_handlerClass."();");

		if (!method_exists($m, "InitAdmin"))
		{
			return false;
		}
		else
		{
			$m->InitAdmin($folder, $pageID, $config);
			return $m;
		}
	}

	function LoadForHeader($folder)
	{
		if (!$this->_ValidateModule($folder))
			return false;

		eval("\$m = new ".$this->_handlerClass."();");

		if (!method_exists($m, "ProcessHeader"))
		{
			return false;
		}
		else
		{
			return $m->ProcessHeader($folder);
		}
	}

	function LoadModuleMap($folder, $templateSet, $pathToModule, $pageID, $config, $level)
	{
		if (!$this->_ValidateModule($folder))
			return false;

		eval("\$m = new ".$this->_handlerClass."(\$folder, \$templateSet, \$pathToModule, array(), array(), \$pageID, \$config);");

		if (!method_exists($m, "LoadMap"))
		{
			return array();
		}
		else
		{
			return $m->LoadMap($level);
		}
	}

	function _ValidateModule($folder)
	{
		if (!isset($this->_moduleList[$folder]))
		{
			ErrorHandler::TriggerError("Module \"".$folder."\" doesn't exist", E_USER_WARNING);
			return false;
		}

		$fileName = dirname(__FILE__)."/../module/".$folder."/public.php";
		if (!file_exists($fileName))
		{
			ErrorHandler::TriggerError("File \"".$fileName."\" doesn't exist", E_USER_WARNING);
			return false;
		}

		require_once($fileName);

		if (!class_exists($this->_moduleList[$folder]["Class"]))
		{
			ErrorHandler::TriggerError("Class \"".$this->_moduleList[$folder]["Class"]."\" is not found", E_USER_WARNING);
			return false;
		}

		$this->_handlerClass = $this->_moduleList[$folder]["Class"];

		return true;
	}
}

?>
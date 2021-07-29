<?php

es_include("localobject.php");
es_include("pagelist.php");
es_include("filesys.php");
es_include("module.php");
es_include("template.php");

class Page extends LocalObject
{
	var $_acceptMimeTypes = array(
	'image/png',
	'image/x-png',
	'image/gif',
	'image/jpeg',
	'image/pjpeg'
	);
	var $params;

	function Page($data = array())
	{
		parent::LocalObject($data);

		$this->params = array();
		if ($images = GetFromConfig('MenuImages'))
		{
			$this->params = LoadImageConfig('MenuImage', 'page', $images);
		}
	}

	function LoadByID($pageID)
	{
		$query = "SELECT PageID, Path2Root, ".GetImageFields('MenuImage', count($this->params))."
				Title, Description, TitleH1, MetaTitle, MetaKeywords, MetaDescription,
				Content, Template, SortOrder, StaticPath, LanguageCode,
				Active, Link, Target, Type, Config
			FROM `page`
			WHERE WebsiteID=".intval(WEBSITE_ID)."
				AND PageID=".Connection::GetSQLString($pageID);
		$this->LoadFromSQL($query);

		$this->_PrepareContentBeforeShow();

		if ($this->GetProperty("PageID"))
			return true;
		else
			return false;
	}

	function LoadIndexPage()
	{
		$query = "SELECT PageID, Path2Root, ".GetImageFields('MenuImage', count($this->params))."
				Title, Description, TitleH1, MetaTitle, MetaKeywords, MetaDescription,
				Content, Template, SortOrder, StaticPath, LanguageCode,
				Active, Link, Target, Type, Config
			FROM `page`
			WHERE WebsiteID=".intval(WEBSITE_ID)."
				AND StaticPath=".Connection::GetSQLString(INDEX_PAGE)." AND Path2Root!='#'
				AND LanguageCode=".Connection::GetSQLString(DATA_LANGCODE)."
			ORDER BY Path2Root LIMIT 1";
		$this->LoadFromSQL($query);

		$this->_PrepareContentBeforeShow();

		if ($this->GetProperty("PageID"))
			return true;
		else
			return false;
	}

	function _PrepareContentBeforeShow()
	{
		$this->SetProperty("ParentID", $this->GetParentID());

		if ($this->GetProperty("Type") == 1 || $this->GetProperty("Type") == 2)
		$this->SetProperty("Content", str_replace("<P_T_R>", PROJECT_PATH, $this->GetProperty("Content")));

		if ($this->GetProperty("Type") == 3)
		$this->SetProperty("Link", str_replace("<P_T_R>", PROJECT_PATH, $this->GetProperty("Link")));

		$chunks = explode("&", $this->GetProperty("Description"));
		for ($i = 0; $i < count($chunks); $i++)
		{
			$pair = explode("=", $chunks[$i]);
			if (count($pair) == 2)
				$this->SetProperty($pair[0], value_decode($pair[1]));
		}

		if ($this->GetProperty("PageID"))
		{
			$this->SetProperty("FullPath2Root", $this->GetProperty("Path2Root").$this->GetProperty("PageID")."#");

			// When we load page we have to define path, width & height for menu images
			for ($i = 0; $i < count($this->params); $i++)
			{
				$v = $this->params[$i];
				if ($this->GetProperty($v["Name"]))
				{
					$imageConfig = LoadImageConfigValues($v["Name"], $this->GetProperty($v["Name"]."Config"));
					$this->AppendFromArray($imageConfig);
					
					$origW = $this->GetProperty($v["Name"]."Width");
					$origH = $this->GetProperty($v["Name"]."Height");
					
					if($this->params[$i]["Resize"] == 13)
						$this->SetProperty($v["Name"]."Path", InsertCropParams($v["Path"], $this->GetIntProperty($v["Name"]."OneSizeX1"), $this->GetIntProperty($v["Name"]."OneSizeY1"), $this->GetIntProperty($v["Name"]."OneSizeX2"), $this->GetIntProperty($v["Name"]."OneSizeY2")).$this->GetProperty($v["Name"]));
					else
						$this->SetProperty($v["Name"]."Path", $v["Path"].$this->GetProperty($v["Name"]));
					
					list($dstW, $dstH) = GetRealImageSize($v["Resize"], $origW, $origH, $v["Width"], $v["Height"]);
					$this->SetProperty($v["Name"]."Width", $dstW);
					$this->SetProperty($v["Name"]."Height", $dstH);
				}
			}
		}
	}

	function SetConfig($defaultConfig)
	{
		if ($this->GetProperty("Type") != 2) return;

		$savedConfig = $this->GetProperty('Config');
		if (!is_array($savedConfig))
		{
			$chunks = explode("&", $savedConfig);
			$savedConfig = array();
			for ($i = 0; $i < count($chunks); $i++)
			{
				$pair = explode("=", $chunks[$i]);
				if (count($pair) == 2)
					$savedConfig[$pair[0]] = value_decode($pair[1]);
			}
		}

		$config = array();
		foreach ($defaultConfig as $k => $v)
		{
			if (isset($savedConfig[$k]))
			{
				$config[] = array('Key' => $k, 'Value' => $savedConfig[$k], 'Title' => GetTranslation('config-'.$k, $this->GetProperty('Link')));
				$this->SetProperty('Config'.$k, $savedConfig[$k]);
			}
			else
			{
				$config[] = array('Key' => $k, 'Value' => $v, 'Title' => GetTranslation('config-'.$k, $this->GetProperty('Link')));
				$this->SetProperty('Config'.$k, $v);
			}
		}

		$this->SetProperty('Config', $config);
	}

	function GetConfig()
	{
		if ($this->GetProperty("Type") != 2) false;

		$data = GetPageData($this->GetProperty('Link'));
		$defaultConfig = $data['Config'];

		$savedConfig = $this->GetProperty('Config');
		if (!is_array($savedConfig))
		{
			$chunks = explode("&", $savedConfig);
			$savedConfig = array();
			for ($i = 0; $i < count($chunks); $i++)
			{
				$pair = explode("=", $chunks[$i]);
				if (count($pair) == 2)
					$savedConfig[$pair[0]] = value_decode($pair[1]);
			}

			// We have to use default config as main structure
			foreach ($defaultConfig as $k => $v)
			{
				if (isset($savedConfig[$k]))
					$defaultConfig[$k] = $savedConfig[$k];
			}
		}

		return $defaultConfig;
	}

	function GetMenuImages()
	{
		$menuImages = $this->params;

		for ($i = 0; $i < count($menuImages); $i++)
		{
			$menuImages[$i]['Title'] = GetTranslation('menu-image'.($i+1));
			$menuImages[$i]['Path'] = $this->GetProperty($menuImages[$i]['Name'].'Path');
			$menuImages[$i]['Value'] = $this->GetProperty($menuImages[$i]['Name']);
			$menuImages[$i]['FullPath'] = MENU_IMAGE_PATH.$this->GetProperty($menuImages[$i]['Name']);
			$menuImages[$i]['MenuImageParamList'] = array(array(
				"Name" => "OneSize",
				"Width" => $menuImages[$i]['Width'],
				"Height" => $menuImages[$i]['Height'],
				"Resize" => $menuImages[$i]['Resize'],
				"X1" => $this->GetIntProperty($menuImages[$i]['Name']."OneSizeX1"),
				"Y1" => $this->GetIntProperty($menuImages[$i]['Name']."OneSizeY1"),
				"X2" => $this->GetIntProperty($menuImages[$i]['Name']."OneSizeX2"),
				"Y2" => $this->GetIntProperty($menuImages[$i]['Name']."OneSizeY2")
			));
		}

		return $menuImages;
	}

	function SaveMenuImages($savedImages = array())
	{
		if ($this->GetProperty('Type') == 0)
		{
			for ($i = 0; $i < count($this->params); $i++)
			{
				$this->SetProperty($this->params[$i]['Name']."Config", null);
			}
			return true;
		}

		$fileSys = new FileSys();

		for ($i = 0; $i < count($this->params); $i++)
		{
			$v = $this->params[$i];
			$newImage = $fileSys->Upload($v['Name'], MENU_IMAGE_DIR, false, $this->_acceptMimeTypes);
			if ($newImage)
			{
				$this->SetProperty($v['Name'], $newImage["FileName"]);
				$this->SetProperty($v['Name'].'Path', $v['Path'].$newImage["FileName"]);

				// Remove old image
				if (isset($savedImages[$i]) && $savedImages[$i])
					@unlink(MENU_IMAGE_DIR.$savedImages[$i]);
			}
			else
			{
				if (isset($savedImages[$i]) && $savedImages[$i])
				{
					$this->SetProperty($v['Name'], $savedImages[$i]);
					$this->SetProperty($v['Name'].'Path', $v['Path'].$savedImages[$i]);
				}
				else
				{
					$this->SetProperty($v['Name'], null);
					$this->SetProperty($v['Name'].'Path', null);
				}
			}

			$this->_properties[$v['Name']."Config"]["Width"] = 0;
			$this->_properties[$v['Name']."Config"]["Height"] = 0;
			
			if ($this->GetProperty($v['Name']))
			{
				if ($info = @getimagesize(MENU_IMAGE_DIR.$this->GetProperty($v['Name'])))
				{
					$this->_properties[$v['Name']."Config"]["Width"] = $info[0];
					$this->_properties[$v['Name']."Config"]["Height"] = $info[1];
				}
			}
		}

		$this->LoadErrorsFromObject($fileSys);

		return !$fileSys->HasErrors();
	}

	function Save()
	{
		$savedImages = array();
		for ($i = 0; $i < count($this->params); $i++)
			$savedImages[] = $this->GetProperty("Saved".$this->params[$i]["Name"]);

		$result1 = $this->SaveMenuImages($savedImages);
		$result2 = $this->Validate();
		if (!$result1 || !$result2)
		{
			return false;
		}

		$pageOld = new Page();
		if ($pageOld->LoadByID($this->GetIntProperty("PageID")))
		{
			$currentPath2Root = $pageOld->GetProperty("Path2Root");
			$currentSortOrder = $pageOld->GetProperty("SortOrder");
			$currentPath = $pageOld->GetPathAsArray();

			$queryShift = null;
			$queryUpdate = null;
			$newSortOrder = null;

			if ($currentPath2Root != $this->GetProperty("Path2Root"))
			{
				// Shift position of old elements
				$queryShift = "UPDATE `page` SET SortOrder=SortOrder-1 WHERE
					WebsiteID=".intval(WEBSITE_ID)."
					AND Path2Root=".Connection::GetSQLString($currentPath2Root)."
					AND SortOrder>".Connection::GetSQLString($currentSortOrder)."
					AND LanguageCode=".$this->GetPropertyForSQL("LanguageCode");

				// Update Path2Root for children
				$queryUpdate = "UPDATE `page`
					SET Path2Root=REPLACE(Path2Root,
					".Connection::GetSQLString($currentPath2Root.$this->GetProperty("PageID")."#").",
					".Connection::GetSQLString($this->GetProperty("Path2Root").$this->GetProperty("PageID")."#").")
					WHERE WebsiteID=".intval(WEBSITE_ID);

				// In new location page will be last
				$this->SetProperty("SortOrder", $this->GetCountBrother());
				$newSortOrder = $this->GetProperty("SortOrder");
			}

			// We do not update LanguageCode of the page
			$query = "UPDATE `page` SET
				Title=".$this->GetPropertyForSQL("Title").",
				Description=".$this->GetPropertyForSQL("Description").",
				TitleH1=".$this->GetPropertyForSQL("TitleH1").",
				MetaTitle=".$this->GetPropertyForSQL("MetaTitle").",
				MetaKeywords=".$this->GetPropertyForSQL("MetaKeywords").",
				MetaDescription=".$this->GetPropertyForSQL("MetaDescription").",
				Path2Root=".$this->GetPropertyForSQL("Path2Root").",
				Content=".$this->GetPropertyForSQL("Content").",
				Template=".$this->GetPropertyForSQL("Template").",
				StaticPath=".$this->GetPropertyForSQL("StaticPath").",
				LanguageCode=".$this->GetPropertyForSQL("LanguageCode").",
				".(is_null($newSortOrder) ? "" : "SortOrder=".$this->GetIntProperty("SortOrder").",")."
				".($this->GetProperty('ConfigString') ? "Config=".$this->GetPropertyForSQL("ConfigString")."," : "")."
				Type=".$this->GetPropertyForSQL("Type").",
				Link=".$this->GetPropertyForSQL("Link").",
				Target=".$this->GetPropertyForSQL("Target").",
				Active=".$this->GetPropertyForSQL("Active").", ";
			for ($i = 0; $i < count($this->params); $i++)
			{
				$query .= $this->params[$i]["Name"]."=".$this->GetPropertyForSQL($this->params[$i]["Name"]).", ";
				$query .= $this->params[$i]["Name"]."Config=".Connection::GetSQLString(json_encode($this->GetProperty($this->params[$i]["Name"].'Config'))).", ";
			}
			$query .= "Modified=NOW() WHERE WebsiteID=".intval(WEBSITE_ID)."
					AND PageID=".$this->GetIntProperty("PageID");
		}
		else
		{
			$this->SetProperty("PageID", 0);

			// Newly added page is last
		$this->SetProperty("SortOrder", $this->GetCountBrother());

			$query = "INSERT INTO `page` (WebsiteID, Title, Description,
				TitleH1, MetaTitle, MetaKeywords, MetaDescription, Path2Root, Content,
				Template, StaticPath, LanguageCode, SortOrder, Type, Link, Config, Target,
				Active, ";
			for ($i = 0; $i < count($this->params); $i++)
			{
				$query .= $this->params[$i]['Name'].",
					".$this->params[$i]['Name']."Config, ";
			}
			$query .= "Created) VALUES (
				".intval(WEBSITE_ID).",
				".$this->GetPropertyForSQL("Title").",
				".$this->GetPropertyForSQL("Description").",
				".$this->GetPropertyForSQL("TitleH1").",
				".$this->GetPropertyForSQL("MetaTitle").",
				".$this->GetPropertyForSQL("MetaKeywords").",
				".$this->GetPropertyForSQL("MetaDescription").",
				".$this->GetPropertyForSQL("Path2Root").",
				".$this->GetPropertyForSQL("Content").",
				".$this->GetPropertyForSQL("Template").",
				".$this->GetPropertyForSQL("StaticPath").",
				".$this->GetPropertyForSQL("LanguageCode").",
				".$this->GetPropertyForSQL("SortOrder").",
				".$this->GetPropertyForSQL("Type").",
				".$this->GetPropertyForSQL("Link").",
				".$this->GetPropertyForSQL("ConfigString").",
				".$this->GetPropertyForSQL("Target").",
				".$this->GetPropertyForSQL("Active").", ";
			for ($i = 0; $i < count($this->params); $i++)
			{
				$query .= $this->GetPropertyForSQL($this->params[$i]["Name"]).",
				".Connection::GetSQLString(json_encode($this->GetProperty($this->params[$i]["Name"].'Config'))).", ";
			}
			$query .= "NOW())";
		}

		/*@var stmt Statement */
		$stmt = GetStatement();

		if (!$stmt->Execute($query))
		{
			$this->AddError("sql-error");
			$this->_PrepareContentBeforeShow();
			return false;
		}

		if ($this->GetIntProperty('PageID') > 0)
		{
			if (!is_null($queryShift))
			{
				$stmt->Execute($queryShift);
			}
			if (!is_null($queryUpdate))
			{
				$stmt->Execute($queryUpdate);
			}
			$this->SetProperty("FullPath2Root", $this->GetProperty("Path2Root").$this->GetProperty("PageID")."#");

			if ($this->GetProperty("Type") == 1 || $this->GetProperty("Type") == 2)
			{
				// Current URL of the page (it is not full URL, but with % it includes all links to subpages also)
				array_shift($currentPath);
				$cPath = "<P_T_R>".GetLangDir($this->GetProperty("LanguageCode"));
				if (count($currentPath) > 0)
				{
					foreach ($currentPath as $v)
					$cPath .= $v["StaticPath"]."/";
				}
				$cPath = substr($cPath, 0, strlen($cPath) - 1);

				// New URL of the page (it is not full URL, but with % it includes all links to subpages also)
				$newPath = $this->GetPathAsArray();
				array_shift($newPath);
				$nPath = "<P_T_R>".GetLangDir($this->GetProperty("LanguageCode"));
				if (count($newPath) > 0)
				{
					foreach ($newPath as $v)
					$nPath .= $v["StaticPath"]."/";
				}
				$nPath = substr($nPath, 0, strlen($nPath) - 1);

				if ($nPath != $cPath)
				{
					// Update all links to this page & links to all subpages
					$query = "UPDATE `page` SET
						Content=REPLACE(Content, ".Connection::GetSQLString("href=\"".$cPath).",
						".Connection::GetSQLString("href=\"".$nPath)."),
						Link=REPLACE(Link, ".Connection::GetSQLString($cPath).",
						".Connection::GetSQLString($nPath).")
						WHERE WebsiteID=".intval(WEBSITE_ID);
					$stmt->Execute($query);
				}
			}
		}
		else
		{
			$this->SetProperty("PageID", $stmt->GetLastInsertID());
			$this->SetProperty("FullPath2Root", $this->GetProperty("Path2Root").$this->GetProperty("PageID")."#");
		}
		return true;
	}

	function Validate()
	{
		switch($this->GetIntProperty("Type"))
		{
			case 0:
				// Menu
				$this->SetProperty("Link", null);
				$this->SetProperty("Target", "");
				$this->SetProperty("Content", "");
				$this->SetProperty("Template", null);
				$this->SetProperty("TitleH1", "");
				$this->SetProperty("MetaTitle", "");
				$this->SetProperty("MetaKeywords", "");
				$this->SetProperty("MetaDescription", "");
				$this->SetProperty("Path2Root", "#");
				$this->SetProperty("ParentID", 0);
				break;
			case 1:
				// Page
				$this->SetProperty("Link", null);
				$this->SetProperty("Target", "");
				// Template must be defined for page
				if (strlen($this->GetProperty("Template")) == 0)
				{
					$this->AddError("template-is-not-defined");
				}
				break;
			case 2:
				// Module
				$module = new Module();
				if (!$module->ModuleExists($this->GetProperty("Link")))
				{
					$this->AddError("unknown-module", array("Module" => $this->GetProperty("Link")));
					return false;
				}
				$this->SetProperty("Target", "");
				// Template must be defined for module if module has at least one template set
				$template = new Template();
				$templateSets = $template->GetTemplateSets($this->GetProperty("Link"));
				if (count($templateSets) > 0 && strlen($this->GetProperty("Template")) == 0)
				{
					$this->AddError("templateset-is-not-defined");
				}
				$config = $this->GetProperty('Config');
				if (is_array($config))
				{
					$configString = array();
					for ($i = 0; $i < count($config); $i++)
					{
						$configString[] = $config[$i]['Key'].'='.value_encode($config[$i]['Value']);
					}
					$this->SetProperty('ConfigString', implode('&', $configString));
				}
				break;
			case 3:
				$this->SetProperty("StaticPath", null);
				$this->SetProperty("Content", "");
				$this->SetProperty("Template", null);
				$this->SetProperty("TitleH1", "");
				$this->SetProperty("MetaTitle", "");
				$this->SetProperty("MetaKeywords", "");
				$this->SetProperty("MetaDescription", "");
				break;
			default:
				$this->AddError("page-type-is-undefined");
				return false;
				break;
		}
		//checkbox of Active
		if ($this->GetProperty("Active") != "Y")
			$this->SetProperty("Active", "N");
		
		// Title is required
		$this->SetProperty("Title", trim($this->GetProperty("Title")));
		if (!$this->GetProperty("Title"))
		{
			$this->AddError("title-empty");
		}

		// For page, module & link parent must be defined
		if ($this->GetIntProperty("Type") > 0)
		{
			$page = new Page();
			if ($page->LoadByID($this->GetProperty("ParentID")))
			{
				if ($page->GetProperty("LanguageCode") != $this->GetProperty("LanguageCode"))
				{
					// Language of the parent element is not equal to current
					$this->AddError("parent-language-different");
					return false;
				}
				$this->SetProperty("Path2Root", $page->GetProperty("FullPath2Root"));
			}
			else
			{
				// We can't go further to check unique path until parent is defined
				$this->AddError("parent-is-not-defined");
				return false;
			}
		}

		// For menu, page & module field StaticPath must be defined correctly
		if ($this->GetIntProperty("Type") >= 0 && $this->GetIntProperty("Type") <= 2 )
		{
			// Field StaticPath must be defined and consists only latin chars, numbers, hyphens (-), dots (.), understrikes (_)
			if (!$this->GetProperty("StaticPath"))
			{
				if ($this->GetIntProperty("Type") == 0)
					$this->AddError("static-path-empty-menu");
				else
					$this->AddError("static-path-empty");
			}
			else if (!preg_match("/^[a-z0-9\._-]+$/i", $this->GetProperty("StaticPath")))
			{
				if ($this->GetIntProperty("Type") == 0)
					$this->AddError("static-path-incorrect-menu");
				else
					$this->AddError("static-path-incorrect");
			}
			else
			{
				/*@var stmt Statement */
				$stmt = GetStatement();

				// StaticPath must be unique
				if ($this->GetProperty("StaticPath") == INDEX_PAGE && $this->GetIntProperty("Type") > 0)
				{
					// Only one index page is allowed
					$query = "SELECT COUNT(PageID) FROM `page` WHERE
						WebsiteID=".intval(WEBSITE_ID)."
						AND StaticPath=".$this->GetPropertyForSQL("StaticPath")."
						AND LanguageCode=".$this->GetPropertyForSQL("LanguageCode")."
						AND Path2Root!='#' AND PageID!=".$this->GetPropertyForSQL("PageID");
				}
				else if (count(explode("#", $this->GetProperty("Path2Root"))) == 3)
				{
					// All pages for second level must be unique, because first level pages are excluded from the path
					$query = "SELECT COUNT(PageID) FROM `page` WHERE
						WebsiteID=".intval(WEBSITE_ID)."
						AND StaticPath=".$this->GetPropertyForSQL("StaticPath")."
						AND LanguageCode=".$this->GetPropertyForSQL("LanguageCode")."
						AND Path2Root REGEXP '^#[0-9]+#$'
						AND PageID!=".$this->GetPropertyForSQL("PageID");
				}
				else
				{
					$query = "SELECT COUNT(PageID) FROM `page` WHERE
						WebsiteID=".intval(WEBSITE_ID)."
						AND StaticPath=".$this->GetPropertyForSQL("StaticPath")."
						AND LanguageCode=".$this->GetPropertyForSQL("LanguageCode")."
						AND Path2Root=".$this->GetPropertyForSQL("Path2Root")."
						AND PageID!=".$this->GetPropertyForSQL("PageID");
				}
				if ($stmt->FetchField($query))
				{
					if ($this->GetIntProperty("Type") == 0)
						$this->AddError("static-path-is-not-unique-menu");
					else
						$this->AddError("static-path-is-not-unique");
				}
			}
		}

		if (!$this->HasErrors())
		{
			if ($this->GetIntProperty("Type") == 3)
			{
				// Link
				if (substr($this->GetProperty("Link"), 0, strlen(PROJECT_PATH)) == PROJECT_PATH)
				{
					$this->SetProperty("Link", "<P_T_R>".substr($this->GetProperty("Link"), strlen(PROJECT_PATH)));
				}
			}
			else if ($this->GetProperty('Type') > 0)
			{
				// Page, Module
				$this->SetProperty("Content", PrepareContentBeforeSave($this->GetProperty("Content")));
			}

			$description = $this->GetProperty("Description");
			if (is_array($description) && count($description) > 0)
			{
				$forSave = array();
				for ($i = 0; $i < count($description); $i++)
				{
					if ($i > 0)
						$forSave[] = "Description".($i+1)."=".value_encode($description[$i]);
					else
						$forSave[] = "Description=".value_encode($description[$i]);
				}
				$this->SetProperty("Description", implode("&", $forSave));
			}
			else
			{
				$this->SetProperty("Description", "");
			}

			return true;
		}
		else
		{
			$description = $this->GetProperty("Description");
			if (is_array($description) && count($description) > 0)
			{
				for ($i = 0; $i < count($description); $i++)
				{
					if ($i > 0)
						$this->SetProperty("Description".($i+1), $description[$i]);
					else
						$this->SetProperty("Description", $description[$i]);
				}
			}
			else
			{
				$this->SetProperty("Description", "");
			}

			return false;
		}
	}

	function OpenParents()
	{
		$expandedNodes = array();
		$newExpandedNodes = array();
		if (isset($_COOKIE['expandedNodes']))
		{
			if (preg_match("/\['\d+'(,'\d+')*\\]/", $_COOKIE['expandedNodes']))
			{
				$tmpStr = str_replace("[", "", $_COOKIE['expandedNodes']);
				$tmpStr = str_replace("]", "", $tmpStr);
				$tmpStr = str_replace("'", "", $tmpStr);
				$expandedNodes = explode(",", $tmpStr);
			}
		}

		$path2Root = $this->GetProperty("Path2Root");
		if ($path2Root != "#")
		{
			$path2Root = substr($path2Root, 1);
			$path2Root = substr($path2Root, 0, strlen($path2Root)-1);
			$newExpandedNodes = explode("#", $path2Root);
		}

		if (count($newExpandedNodes) > 0)
		{
			for ($i = 0; $i < count($newExpandedNodes); $i++)
			{
				if (!in_array($newExpandedNodes[$i], $expandedNodes))
				{
					$expandedNodes[] = $newExpandedNodes[$i];
				}
			}
		}

		if (count($expandedNodes) > 0)
		{
			$expandedNodesJs = "['".implode("','", $expandedNodes)."']";
		}
		else
		{
			$expandedNodesJs = "";
		}

		setcookie("expandedNodes", $expandedNodesJs, time()+60*60*24*30*COOKIE_EXPIRE, PROJECT_PATH);
	}

	function GetSEO($pageID)
	{
		/*@var stmt Statement */
		$stmt = GetStatement();
		$query = "SELECT PageID, TitleH1, MetaTitle, MetaKeywords, MetaDescription
			FROM `page`
			WHERE WebsiteID=".intval(WEBSITE_ID)."
				AND PageID=".Connection::GetSQLString($pageID);
		return $stmt->FetchRow($query);
	}

	function SaveSEO()
	{
		/*@var stmt Statement */
		$stmt = GetStatement();
		$query = "UPDATE `page` SET TitleH1=".$this->GetPropertyForSQL('TitleH1').",
				MetaTitle=".$this->GetPropertyForSQL('MetaTitle').",
				MetaKeywords=".$this->GetPropertyForSQL('MetaKeywords').",
				MetaDescription=".$this->GetPropertyForSQL('MetaDescription')."
			WHERE WebsiteID=".intval(WEBSITE_ID)." AND PageID=".$this->GetIntProperty('PageID');
		$stmt->Execute($query);
		return $stmt->GetAffectedRows();
	}

	function Remove($pageID, $mainPage = true)
	{
		if (!$this->LoadByID($pageID)) return;

		/*@var stmt Statement */
		$stmt = GetStatement();

		if ($this->GetProperty('Type') == 2)
		{
			// Delete module data related to current page
			$module = new Module();
			if ($module->ModuleExists($this->GetProperty("Link")) && $m = $module->LoadForAdmin($this->GetProperty("Link"), $this->GetIntProperty("PageID"), $this->GetConfig()))
			{
				if (method_exists($m, "RemoveModuleData"))
				{
					$m->RemoveModuleData();
				}
			}
		}

		// Delete menu images
		for ($i = 0; $i < count($this->params); $i++)
		{
			if ($this->GetProperty($this->params[$i]["Name"]))
			@unlink(MENU_IMAGE_DIR.$this->GetProperty($this->params[$i]["Name"]));
		}

		// Delete current page
		$query = "DELETE FROM `page` WHERE PageID=".$this->GetPropertyForSQL("PageID");
		$stmt->Execute($query);

		if ($mainPage)
		{
			// Shift brothers after removing main page
			$query = "UPDATE `page` SET SortOrder=SortOrder-1 WHERE
				WebsiteID=".intval(WEBSITE_ID)."
				AND Path2Root=".$this->GetPropertyForSQL("Path2Root")."
				AND SortOrder>".$this->GetPropertyForSQL("SortOrder")."
				AND LanguageCode=".$this->GetPropertyForSQL("LanguageCode");
			$stmt->Execute($query);

			// Get list of main page children
			$query = "SELECT PageID FROM `page` WHERE
				Path2Root LIKE '".Connection::GetSQLLike($this->GetProperty("FullPath2Root"))."%'
				ORDER BY Path2Root, SortOrder";
			if ($result = $stmt->FetchList($query))
			{
				for ($i = 0; $i < count($result); $i++)
				{
					$this->Remove($result[$i]["PageID"], false);
				}
			}
		}

		$this->AddMessage('page-is-removed', array('Title' => $this->GetProperty('Title')));
		// TODO: Mark somehow all links (menu links & links in content) to deleted static pages & module pages
	}

	function SwitchActive($pageID, $active)
	{
		if ($active != 'Y') $active = 'N';
		/*@var stmt Statement */
		$stmt = GetStatement();
		$query = "UPDATE `page` SET Active=".Connection::GetSQLString($active)."
			WHERE WebsiteID=".intval(WEBSITE_ID)." AND PageID=".Connection::GetSQLString($pageID);
		$stmt->Execute($query);
		return $stmt->GetAffectedRows();
	}

	function GetParentID()
	{
		if ($this->GetProperty("Path2Root") == "#") return 0;
		if (!$this->GetProperty("Path2Root")) return 0;
		$parentIDs = explode("#", $this->GetProperty("Path2Root"));
		return ($parentIDs[count($parentIDs) - 2]);
	}

	function GetPathAsArray()
	{
		$path = array();
		// Need to define title of the home page
		$path[] = array("StaticPath" => INDEX_PAGE, "PageURL" => GetDirPrefix($this->GetProperty("LanguageCode")), "Title" => GetTranslation("home-page"));

		if ($this->GetProperty("Path2Root"))
			$parents = explode("#", $this->GetProperty("Path2Root"));
		else
			$parents = array("", "");

		if (count($parents) > 2)
		{
			// Remove first empty element & first level
			array_shift($parents);
			array_shift($parents);
			// Replace last empty element by current PageID
			$parents[count($parents) - 1] = $this->GetProperty("PageID");
			$parents = Connection::GetSQLArray($parents);
			/*@var stmt Statement */
			$stmt = GetStatement();
			$query = "SELECT PageID, Title, Description, StaticPath, Link, Type
				FROM `page`
				WHERE WebsiteID=".intval(WEBSITE_ID)."
					AND PageID IN (".implode(",", $parents).")
					AND LanguageCode=".$this->GetPropertyForSQL("LanguageCode")."
				ORDER BY Path2Root";
			if ($pathPages = $stmt->FetchList($query))
			{
				$currPath = substr(GetDirPrefix($this->GetProperty("LanguageCode")), 0, strlen(GetDirPrefix($this->GetProperty("LanguageCode"))) - 1);
				$linkInPath = false;
				for ($i = 0; $i < count($pathPages); $i++)
				{
					$chunks = explode("&", $pathPages[$i]["Description"]);
					for ($j = 0; $j < count($chunks); $j++)
					{
						$pair = explode("=", $chunks[$j]);
						if (count($pair) == 2)
							$pathPages[$i][$pair[0]] = value_decode($pair[1]);
					}

					if ($linkInPath)
					{
						// Page has no URL if link in page path is found
						$path[] = array("StaticPath" => $pathPages[$i]["StaticPath"], "PageURL" => GetDirPrefix($this->GetProperty("LanguageCode")).INDEX_PAGE.HTML_EXTENSION, "Title" => "", "Description" => "");
					}
					else if ($pathPages[$i]["Type"] >= 0 && $pathPages[$i]["Type"] <= 2)
					{
						$currPath .= "/".$pathPages[$i]["StaticPath"];
						if ($pathPages[$i]["PageID"] == $this->GetProperty("PageID"))
						{
							// Current page
							if ($this->GetCountChildren() > 0)
								$path[] = array("StaticPath" => $pathPages[$i]["StaticPath"], "PageURL" => $currPath."/".INDEX_PAGE.HTML_EXTENSION, "Title" => $pathPages[$i]["Title"], "Description" => $pathPages[$i]["Description"], "HasChildren" => 1);
							else
								$path[] = array("StaticPath" => $pathPages[$i]["StaticPath"], "PageURL" => $currPath.HTML_EXTENSION, "Title" => $pathPages[$i]["Title"], "Description" => $pathPages[$i]["Description"], "HasChildren" => 0);
						}
						else
						{
							// Parent page
							$path[] = array("StaticPath" => $pathPages[$i]["StaticPath"], "PageURL" => $currPath."/".INDEX_PAGE.HTML_EXTENSION, "Title" => $pathPages[$i]["Title"], "Description" => $pathPages[$i]["Description"], "HasChildren" => 1);
						}
					}
					else
					{
						// External link
						$path[] = array("StaticPath" => $pathPages[$i]["StaticPath"], "PageURL" => $pathPages[$i]["Link"], "Title" => $pathPages[$i]["Title"], "Description" => $pathPages[$i]["Description"]);
						$linkInPath = true;
					}
				}
			}
		}
		return $path;
	}

	function GetCountChildren()
	{
		/*@var stmt Statement */
		$stmt = GetStatement();
		$query = "SELECT COUNT(PageID) FROM `page` WHERE
			WebsiteID=".intval(WEBSITE_ID)."
			AND Path2Root=".$this->GetPropertyForSQL("FullPath2Root");
		$res = $stmt->FetchField($query);
		return $res;
	}

	function GetCountBrother()
	{
		/*@var stmt Statement */
		$stmt = GetStatement();
		$query = "SELECT COUNT(PageID) FROM `page` WHERE
			WebsiteID=".intval(WEBSITE_ID)."
			AND Path2Root=".$this->GetPropertyForSQL("Path2Root")."
			AND LanguageCode=".$this->GetPropertyForSQL("LanguageCode");
		$res = $stmt->FetchField($query);
		return $res;
	}

	function GetParentLists($type)
	{
		if ($this->GetProperty("Path2Root"))
			$parents = explode("#", $this->GetProperty("Path2Root"));
		else
			$parents = array("", "");


		$parentList = array();
		$path2Root = "";
		for ($i = 0; $i < count($parents) - 1; $i++)
		{
			$path2Root .= $parents[$i]."#";
			$parentList[] = Connection::GetSQLString($path2Root);
		}

		$in = "NULL";
		if ($type == 1)
			$in = "0,1,2";
		else if ($type == 2)
			$in = "0,1";
		else if ($type == 3)
			$in = "0,1,2,3";

		$query = "SELECT PageID, ".GetImageFields('MenuImage', count($this->params))."
				Title, Path2Root
			FROM `page` WHERE WebsiteID=".intval(WEBSITE_ID)."
				AND Path2Root IN(".implode(",", $parentList).")
				AND LanguageCode=".$this->GetPropertyForSQL("LanguageCode")."
				AND Type IN (".$in.")
			".($this->GetProperty("PageID") ? "AND PageID<>".$this->GetPropertyForSQL("PageID") : "")."
			ORDER BY Path2Root, SortOrder";
		/*@var stmt Statement */
		$stmt = GetStatement();
		$parentLists = array();
		if ($result = $stmt->FetchList($query))
		{
			$pageList = array();
			$prevPath2Root = "#";
			$j = 0;
			for ($i = 0; $i < count($result); $i++)
			{
				if ($prevPath2Root != $result[$i]["Path2Root"])
				{
					$parentLists[] = array("id" => $j++, "PageList" => $pageList);
					$prevPath2Root = $result[$i]["Path2Root"];
					$pageList = array();
				}
				if ($result[$i]["PageID"] == $parents[$j + 1])
				{
					$result[$i]["Selected"] = 1;
				}
				$result[$i]["Title"] = SmallString($result[$i]["Title"], 20);
				$pageList[] = $result[$i];
			}
			$parentLists[] = array("id" => $j++, "PageList" => $pageList);
		}

		return $parentLists;
	}

	function GetChildren($type)
	{
		if ($this->GetIntProperty("PageID") > 0)
			$path2Root = $this->GetProperty("Path2Root").$this->GetIntProperty("PageID")."#";
		else
			$path2Root = "#";

		$in = "NULL";
		if ($type == 1)
			$in = "0,1,2";
		else if ($type == 2)
			$in = "0,1";
		else if ($type == 3)
			$in = "0,1,2,3";

		$query = "SELECT PageID, Title, StaticPath
			FROM `page` WHERE WebsiteID=".intval(WEBSITE_ID)."
				AND Path2Root=".Connection::GetSQLString($path2Root)."
				AND LanguageCode=".$this->GetPropertyForSQL("LanguageCode")."
				AND Type IN (".$in.")
			ORDER BY SortOrder";
		/*@var stmt Statement */
		$stmt = GetStatement();
		$result = $stmt->FetchList($query);
		for ($i = 0; $i < count($result); $i++)
		{
			$result[$i]["Title"] = SmallString($result[$i]["Title"], 20);
		}
		return $result;
	}

	function GetPageURL($withHost = true)
	{
		$pathChunks = $this->GetPathAsArray();

		array_shift($pathChunks);
		if ($withHost)
			$url = GetUrlPrefix();
		else
			$url = GetDirPrefix();

		if (count($pathChunks) > 0)
		{
			foreach ($pathChunks as $k => $v)
			{
				$url .= $v["StaticPath"]."/";
			}

			$last = $pathChunks[count($pathChunks) - 1];

			if (isset($last["HasChildren"]) && $last["HasChildren"] == 1)
				$url .= INDEX_PAGE.HTML_EXTENSION;
			else
				$url = substr($url, 0, strlen($url) - 1).HTML_EXTENSION;
		}

		return $url;
	}

	function GetPagePrefix()
	{
		$pathChunks = $this->GetPathAsArray();
		array_shift($pathChunks);
		$prefix = GetUrlPrefix();
		foreach ($pathChunks as $k => $v)
		{
			$prefix .= $v["StaticPath"]."/";
		}

		return substr($prefix, 0, strlen($prefix) - 1);
	}
}
?>
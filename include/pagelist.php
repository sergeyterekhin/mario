<?php

es_include("page.php");

class PageList extends LocalObjectList
{
	var $treeForSelectBox;
	var $params;

	function PageList($data = array())
	{
		$this->params = array();
		if ($images = GetFromConfig('MenuImages'))
		{
			$this->params = LoadImageConfig('MenuImage', 'page', $images);
		}
	}

	function GetPageList()
	{
		$stmt = GetStatement();
		$query = "SELECT PageID, Path2Root, Title, Active, Type, Link, LanguageCode
			FROM `page`
			WHERE WebsiteID=".intval(WEBSITE_ID)."
				AND LanguageCode=".Connection::GetSQLString(DATA_LANGCODE)."
			ORDER BY Path2Root, SortOrder";
		if (!$items = $stmt->FetchList($query))
			return array();

		$expandedMenu = isset($_COOKIE['expandedMenu']) ? $_COOKIE['expandedMenu'] : 0;
		$opened = false;
		for ($i = 0; $i < count($items); $i++)
		{
			// Identify parent id & level
			$parents = explode("#", $items[$i]["Path2Root"]);
			$items[$i]["Level"] = count($parents) - 2;
			$parentID = $parents[$items[$i]["Level"]];
			if (empty($parentID)) $parentID = 0;
			$items[$i]["ParentID"] = $parentID;

			$items[$i]["Opened"] = 0;

			$items[$i]['ColorA'] = '';
			$items[$i]['ColorI'] = '';
			$items[$i]['IconTitle'] = '';

			if ($parentID > 0)
			{
				$items[$i]["ShortTitle"] = SmallString($items[$i]["Title"], 60);
				if ($items[$i]["Link"])
					$items[$i]["Link"] = str_replace("<P_T_R>", GetUrlPrefix($items[$i]["LanguageCode"], false), $items[$i]["Link"]);

				switch($items[$i]['Type'])
				{
					case 2:
						$title = GetTranslation('module-title', $items[$i]['Link']);
						$icon = GetPageData($items[$i]['Link']);
						break;
					case 3:
						$title = GetTranslation('link');
						$icon = GetPageData('link');
						break;
					default:
						$title = GetTranslation('page');
						$icon = GetPageData('page');
						break;
				}
				$items[$i]['ColorA'] = $icon['ColorA'];
				$items[$i]['ColorI'] = $icon['ColorI'];
				$items[$i]['IconTitle'] = $title;
			}
			else
			{
				$items[$i]["ShortTitle"] = SmallString($items[$i]["Title"], 15);
				if ($expandedMenu == $items[$i]['PageID'])
				{
					$items[$i]["Opened"] = 1;
					$opened = true;
				}
			}
		}

		if (!$opened && count($items) > 0)
		{
			$items[0]["Opened"] = 1;
		}

		return $items;
	}

	function LoadPageListForModule($module)
	{
		$query = "SELECT PageID, Title AS PageTitle, Description AS PageDescription,
				StaticPath AS PageStaticPath, Config AS PageConfig
			FROM `page`
			WHERE WebsiteID=".intval(WEBSITE_ID)." AND Type=2
				AND Link=".Connection::GetSQLString($module)."
				AND LanguageCode=".Connection::GetSQLString(DATA_LANGCODE)."
			ORDER BY Title ASC";
		$this->LoadFromSQL($query);

		for ($i = 0; $i < count($this->_items); $i++)
		{
			$chunks = explode("&", $this->_items[$i]["PageDescription"]);
			for ($k = 0; $k < count($chunks); $k++)
			{
				$pair = explode("=", $chunks[$k]);
				if (count($pair) == 2)
					$this->_items[$i]['Page'.$pair[0]] = value_decode($pair[1]);
			}
		}
	}

	function GetPageTree($pageID = null, $asList = false, $defineCurrent = true, $active = 'Y')
	{
		$parentPath = "'#'";
		$parentIDs = array();
		$stmt = GetStatement();
		if ($pageID > 0)
		{
			$parents = $stmt->FetchField("SELECT Path2Root FROM `page`
				WHERE WebsiteID=".intval(WEBSITE_ID)."
					AND PageID=".Connection::GetSQLString($pageID));
			if ($parents)
			{
				$parentIDs = explode("#", $parents);
				unset($parentIDs[count($parentIDs)-1]);
				unset($parentIDs[0]);
			}
			$parentIDs[] = $pageID;
		}

		$fields = "";
		for ($i = 0; $i < count($this->params); $i++)
		{
			$fields .= "MenuImage".($i+1).", MenuImage".($i+1)."Config, ";
		}

		$query = "SELECT SUBSTRING(UCASE(Title), 1, 1) AS FirstChar,
				PageID, Title, Description, TitleH1, MetaTitle, Path2Root, SortOrder, Active, Type,
				StaticPath, ".$fields."Type, Link, Target, Content,
				".(count($parentIDs) ? "IF (PageID IN (".implode(",", $parentIDs).") OR REPLACE(Link, '<P_T_R>', '".PROJECT_PATH."')=".Connection::GetSQLString($_SERVER['REQUEST_URI']).", 1, 0) AS Opened " : " 0 AS Opened").",
				".(!is_null($pageID) && $defineCurrent ? "IF (PageID=".Connection::GetSQLString($pageID).", 1, 0) AS Current" : "0 AS Current").",
				".(!is_null($pageID) ? "IF (PageID=".Connection::GetSQLString($pageID).", 1, 0) AS Current2" : "0 AS Current2").",
				IF (Modified IS NULL, Created, Modified) AS LastModified, Template, Config
			FROM `page`
			WHERE WebsiteID=".intval(WEBSITE_ID)."
				AND Path2Root LIKE (CONCAT(".$parentPath.", '%'))
				AND LanguageCode=".Connection::GetSQLString(DATA_LANGCODE)."
				".($active == 'Y' ? "AND Active=".Connection::GetSQLString($active) : "")."
			ORDER BY Path2Root, SortOrder";
		$items = $stmt->FetchList($query);

		// Define which item is before opened & which item is after opened
		for ($i = 0; $i < count($items); $i++)
		{
			if ($i > 0 && $items[$i]["Opened"] == 1 && $items[$i]["Path2Root"] == $items[$i - 1]["Path2Root"])
				$items[$i - 1]["BeforeOpened"] = 1;
			if ($i < count($items) - 1 && $items[$i]["Opened"] == 1 && $items[$i]["Path2Root"] == $items[$i + 1]["Path2Root"])
				$items[$i + 1]["AfterOpened"] = 1;

			for ($j = 0; $j < count($this->params); $j++)
			{
				$v = $this->params[$j];
				$imageConfig = LoadImageConfigValues($v["Name"], $items[$i][$v["Name"]."Config"]);
				$items[$i] = array_merge($items[$i], $imageConfig);
				if ($items[$i][$v["Name"]])
				{
					$origW = isset($items[$i][$v["Name"]."Width"]) ? $items[$i][$v["Name"]."Width"] : null;
					$origH = isset($items[$i][$v["Name"]."Height"]) ? $items[$i][$v["Name"]."Height"] : null;

					if($this->params[$j]["Resize"] == 13)
						$items[$i][$v["Name"]."Path"] = InsertCropParams($v["Path"], 
																		isset($items[$i][$v["Name"]."OneSizeX1"]) ? intval($items[$i][$v["Name"]."OneSizeX1"]) : 0, 
																		isset($items[$i][$v["Name"]."OneSizeY1"]) ? intval($items[$i][$v["Name"]."OneSizeY1"]) : 0,
																		isset($items[$i][$v["Name"]."OneSizeX2"]) ? intval($items[$i][$v["Name"]."OneSizeX2"]) : 0,
																		isset($items[$i][$v["Name"]."OneSizeY2"]) ? intval($items[$i][$v["Name"]."OneSizeY2"]) : 0).$items[$i][$v["Name"]];
					else 	
						$items[$i][$v["Name"]."Path"] = $v["Path"].$items[$i][$v["Name"]];

					list($dstW, $dstH) = GetRealImageSize($v["Resize"], $origW, $origH, $v["Width"], $v["Height"]);
					$items[$i][$v["Name"]."Width"] = $dstW;
					$items[$i][$v["Name"]."Height"] = $dstH;
				}
			}

			$chunks = explode("&", $items[$i]["Description"]);
			for ($k = 0; $k < count($chunks); $k++)
			{
				$pair = explode("=", $chunks[$k]);
				if (count($pair) == 2)
					$items[$i][$pair[0]] = value_decode($pair[1]);
			}
		}

		// Root page (virtual)
		$result[0]["Children"] = array();
		$result[0]["PageURL"] = substr(GetDirPrefix(DATA_LANGCODE), 0, strlen(GetDirPrefix(DATA_LANGCODE)) - 1);
		$result[0]["Type"] = 0;
		$result[0]["Link"] = null;
		$result[0]["Target"] = "";
		$result[0]["Description"] = "";
		$result[0]["StaticPath"] = INDEX_PAGE;

		$bPath2Root = "";
		$cPath2Root = "";
		$cNumber = 0;
		for ($i = 0; $i < count($items); $i++)
		{
			// Identify parent id
			$parents = explode("#", $items[$i]["Path2Root"]);
			$items[$i]["Level"] = count($parents) - 2;
			$parent = $parents[$items[$i]["Level"]];
			if (empty($parent)) $parent = 0;

			// Exclude children of inactive nodes
			if (!isset($result[$parent]))
				continue;

			// Exclude top level nodes from path
			if ($items[$i]["Path2Root"] != "#")
				$items[$i]["PageURL"] = $result[$parent]["PageURL"]."/".$items[$i]["StaticPath"];
			else
				$items[$i]["PageURL"] = $result[$parent]["PageURL"];
			$items[$i]["Level"] = count($parents) - 2;

			// Form all possible menus
			$result[$parent]["Children"][] = $items[$i];
			$result[$items[$i]["PageID"]] =& $result[$parent]["Children"][count($result[$parent]["Children"]) - 1];

			if ($items[$i]["Current2"])
			{
				$bPath2Root = $items[$i]["Path2Root"];
				$cPath2Root = $items[$i]["Path2Root"].$items[$i]["PageID"]."#";
			}

			if ($cPath2Root == $items[$i]["Path2Root"])
			{
				$cNumber++;
			}
		}

		// Define URLs for pages
		foreach($result as $id => $node)
		{
			if ($node["Type"] == 1 || $node["Type"] == 2)
			{
				if ($node["StaticPath"] == INDEX_PAGE)
				{
					// Index page
					$result[$id]["PageURL"] = GetDirPrefix(DATA_LANGCODE)/*.INDEX_PAGE.HTML_EXTENSION*/;
				}
				else if ($node["Path2Root"] == "#")
				{
					// Top level nodes has no URL
					$result[$id]["PageURL"] = "#";
				}
				else if (isset($node["Children"]) && count($node["Children"]))
				{
					// Page which has sub pages
					$result[$id]["PageURL"] .= "/".INDEX_PAGE.HTML_EXTENSION;
				}
				else
				{
					// Page which has no sub pages
					$result[$id]["PageURL"] .= HTML_EXTENSION;
				}
			}
			else
			{
				// Link
				$result[$id]["PageURL"] = str_replace("<P_T_R>", PROJECT_PATH, $node["Link"]);

				// Email link
				if (substr($node["Link"], 0, 7) == "mailto:")
				{
					$mailTo = "";
					for ($i = 0; $i < strlen($node["Link"]); $i++)
					{
						$mailTo .= "%".dechex(ord(substr($node["Link"], $i, 1)));
					}
					$result[$id]['PageURL'] = "javascript:document.location.href=unescape('".$mailTo."');";
				}
			}

			$result[$id]['Attributes'] = ' href="'.$result[$id]['PageURL'].'"';
			if ($result[$id]['Description'])
				$result[$id]['Attributes'] .= ' title="'.$result[$id]["Description"].'"';
			if ($result[$id]['Target'])
				$result[$id]['Attributes'] .= ' target="'.$result[$id]["Target"].'"';
		}

		// Prepare successor menu (children list)
		$menuSuccessor = array();
		if ($cNumber > 0)
		{
			foreach($result as $id => $node)
			{
				if (isset($node["Current2"]) && $node["Current2"])
				{
					// Get current page as first element & its children
					$menuSuccessor[0] = $result[$id];
					unset($menuSuccessor[0]["Children"]);
					for ($j = 0; $j < count($result[$id]["Children"]); $j++)
					{
						$menuSuccessor[] = $result[$id]["Children"][$j];
					}
				}
			}
		}

		// Prepare current menu (children or brothers list)
		$menuCurrent = array();
		if (count($menuSuccessor) > 0)
		{
			$menuCurrent = $menuSuccessor;
		}
		else
		{
			$fill = false;
			foreach($result as $id => $node)
			{
				if (isset($result[$id]["Path2Root"]))
				{
					// Get brothers of the current page & its parent as first element
					$parentIDs = explode("#", $bPath2Root);
					if (count($parentIDs) > 3 && $parentIDs[count($parentIDs)-2] == $result[$id]["PageID"])
					{
						$menuCurrent[0] = $result[$id];
						unset($menuCurrent[0]["Children"]);
						$fill = true;
					}

					if ($bPath2Root == $result[$id]["Path2Root"] && $fill)
						$menuCurrent[] = $result[$id];
				}
			}
		}

		if ($asList)
		{
			array_shift($result);
			foreach ($result as $k => $v)
			{
				if (isset($result[$k]["Children"]))
					unset($result[$k]["Children"]);
			}
			return $result;
		}
		else
		{
			return array("full" => $result[0]["Children"], "menu_successor" => $menuSuccessor, "menu_current" => $menuCurrent);
		}
	}

	function GetConfig($module, $savedConfig)
	{
		$data = GetPageData($module);
		$defaultConfig = $data['Config'];

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

		return $defaultConfig;
	}

	// List of pages for LinkForm in ContentTree & Link Dialog of FCKeditor
	function GetPageListForLink()
	{
		$type = GetFromConfig("PageListType");

		if ($type == "list")
		{
			$listTemp = $this->GetPageTree(null, true, true, '');
			foreach ($listTemp as $k => $v)
			{
				if ($listTemp[$k]["Path2Root"] == "#")
					unset($listTemp[$k]);
			}
			$listTemp = MultiSort($listTemp, "Title", true, 4);
			// Create option groups
			$prevFirstChar = "";
			$list = array();
			$i = 0;
			foreach ($listTemp as $k => $v)
			{
				if ($prevFirstChar != $v["FirstChar"])
				{
					$prevFirstChar = $v["FirstChar"];
					$list[$i]["Title"] = $prevFirstChar;
					$list[$i]["PageURL"] = null;
					$i++;
				}
				$list[$i]["Title"] = $v["Title"];
				$list[$i]["PageURL"] = $v["PageURL"];
				$list[$i]["Disabled"] = $v["Disabled"];
				$list[$i]["Type"] = $v["Type"];
				$i++;
			}
		}
		else
		{
			$tree = $this->GetPageTree(null, false, true, '');
			$list = array();
			foreach ($tree["full"] as $menu)
			{
				if (isset($menu["Children"]) && is_array($menu["Children"]))
				{
					// Exclude top level pages from tree
					$list = array_merge($list, $menu["Children"]);
				}
			}
			$this->treeForSelectBox = array();
			$this->PrepareForSelectBox($list);
			$listTemp = $this->treeForSelectBox;
			$list = array();
			$i = 0;
			foreach ($listTemp as $k => $v)
			{
				$list[$i]["Title"] = $v["Title"];
				$list[$i]["PageURL"] = $v["PageURL"];
				$list[$i]["Disabled"] = $v["Disabled"];
				$list[$i]["Type"] = $v["Type"];
				$i++;
			}
		}
		return $list;
	}
	
	// List of pages for parent selection
	function GetPageListForParentSelection()
	{
		$type = GetFromConfig("PageListType");

		if ($type == "list")
		{
			$listTemp = $this->GetPageTree(null, true, true, '');
			$listTemp = MultiSort($listTemp, "Title", true, 4);
			// Create option groups
			$prevFirstChar = "";
			$list = array();
			$i = 0;
			foreach ($listTemp as $k => $v)
			{
				if ($prevFirstChar != $v["FirstChar"])
				{
					$prevFirstChar = $v["FirstChar"];
					$list[$i]["Title"] = $prevFirstChar;
					$list[$i]["PageURL"] = null;
					$i++;
				}
				$list[$i]["Title"] = $v["Title"];
				$list[$i]["PageURL"] = $v["PageURL"];
				$list[$i]["PageID"] = $v["PageID"];
				$list[$i]["Level"] = $v["Level"];
				$list[$i]["StaticPath"] = $v["StaticPath"];
				$i++;
			}
		}
		else
		{
			$list = $this->GetPageTree(null, false, true, '');
			$list = $list["full"];
			$this->treeForSelectBox = array();
			$this->PrepareForParentSelectBox($list);
			$listTemp = $this->treeForSelectBox;
			$list = array();
			$i = 0;
			foreach ($listTemp as $k => $v)
			{
				$list[$i]["Title"] = $v["Title"];
				$list[$i]["PageURL"] = $v["PageURL"];
				$list[$i]["PageID"] = $v["PageID"];
				$list[$i]["Level"] = $v["Level"];
				$list[$i]["StaticPath"] = $v["StaticPath"];
				$i++;
			}
		}
		return $list;
	}
	
	function PrepareForParentSelectBox($tree)
	{
		foreach ($tree as $id => $node)
		{
			if (isset($node["Children"]) && count($node["Children"]))
			{
				unset($node["Children"]);
			}
			if ($node["Level"] > 0)
			{
				$prefix = "";
				for ($i = 0; $i < $node["Level"]; $i++)
					$prefix .= "&nbsp;&nbsp;&nbsp;&nbsp;";
				$node["Title"] = $prefix.$node["Title"];
			}
			$this->treeForSelectBox[] = $node;

			if (isset($tree[$id]["Children"]) && count($tree[$id]["Children"]))
			{
				$this->PrepareForParentSelectBox($tree[$id]["Children"]);
			}
		}
	}

	function PrepareForSelectBox($tree)
	{
		foreach ($tree as $id => $node)
		{
			if (isset($node["Children"]) && count($node["Children"]))
			{
				unset($node["Children"]);
			}
			if($node["Type"] == 3)
			{
				$node["Disabled"] = 1; 
			}
			else
			{
				$node["Disabled"] = 0;
			}
			if ($node["Level"] > 1)
			{
				$prefix = "";
				for ($i = 0; $i < $node["Level"] - 1; $i++)
					$prefix .= "&nbsp;&nbsp;&nbsp;&nbsp;";
				$node["Title"] = $prefix.$node["Title"];
			}
			$this->treeForSelectBox[] = $node;

			if (isset($tree[$id]["Children"]) && count($tree[$id]["Children"]))
			{
				$this->PrepareForSelectBox($tree[$id]["Children"]);
			}
		}
	}

	function GetMenuList($pageID, $defineCurrent = true)
	{
		$tree = $this->GetPageTree($pageID, false, $defineCurrent);

		$fTree = $tree["full"];
		$sTree = $tree["menu_successor"];
		$cTree = $tree["menu_current"];

		$this->PrepareMenu($fTree, 0);
		$this->PrepareMenu($sTree, 1);
		$this->PrepareMenu($cTree, 1);

		return array("full" => $fTree, "menu_successor" => $sTree, "menu_current" => $cTree);
	}

	function PrepareMenu(&$menuList, $level)
	{
		foreach ($menuList as $id => $menu)
		{
			if (isset($menu["Children"]))
			{
				$menuList[$id]["Children".$level] = $menu["Children"];
				$this->PrepareMenu($menuList[$id]["Children".$level], $level + 1);
				unset($menuList[$id]["Children"]);
			}
		}
	}

	function SaveSort($parentID, $pageList)
	{
		$stmt = GetStatement();

		$query = "SELECT PageID, SortOrder FROM `page`
			WHERE Path2Root LIKE ('#".intval($parentID)."#%')
				AND WebsiteID=".intval(WEBSITE_ID)."
			ORDER BY SortOrder";
		$result = $stmt->FetchIndexedList($query);

		$queries = array();
		$stop = false;
		for ($i = 0; $i < count($pageList); $i++)
		{
			$path2root = "#" . implode("#", $pageList[$i]["Path"]) . "#";
			$queries[] = "UPDATE `page` SET SortOrder=".intval($pageList[$i]["SortOrder"]).",
											Path2Root=".Connection::GetSQLString($path2root)." 
								WHERE PageID=".intval($pageList[$i]["PageID"]);
			if (array_key_exists($pageList[$i]["PageID"], $result))
			{
				unset($result[$pageList[$i]["PageID"]]);
			}
			else
			{
				$stop = true;
				break;
			}
		}

		if (count($result) > 0 || $stop)
		{
			// Do not update because tree content were changed
			return false;
		}
		else
		{
			// Update sort orders
			for ($i = 0; $i < count($queries); $i++)
				$stmt->Execute($queries[$i]);

			return true;
		}
	}
}

?>
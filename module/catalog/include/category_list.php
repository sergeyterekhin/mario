<?php

require_once(dirname(__FILE__)."/../init.php");
require_once(dirname(__FILE__)."/item_list.php");
es_include("localobjectlist.php");

class CatalogCategoryList extends LocalObjectList
{
	var $treeForSelectBox;
	var $module;
	var $config;
	var $params;

	function CatalogCategoryList($module, $baseURL = "", $config = array(), $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->baseURL = $baseURL;
		$this->config = is_array($config) ? $config : array();
		if (count($this->config) > 0)
		{
			$this->params = LoadImageConfig('CategoryImage', $this->module, $this->config['CategoryImage'].",".CATALOG_CATEGORY_IMAGE);
			$this->baseURL .= "/".$this->config['CategoryURLPrefix'];
		}
	}

	function LoadCategoryListForContentTree($pageID)
	{
		$this->LoadFromSQL("SELECT SUBSTRING(UCASE(c.Title), 1, 1) AS FirstChar,
			c.CategoryID, c.PageID, c.Path2Root, c.Title, c.Description,
			c.MetaTitle, c.MetaKeywords, c.MetaDescription,
			c.StaticPath, c.Content, c.SortOrder, c.Created, c.Modified, c.Active,
			(LENGTH(c.Path2Root)-LENGTH(REPLACE(c.Path2Root,'#',''))-1) as Level,
			COUNT(i2c.Item2CategoryID) AS ItemCount
			FROM `catalog_category` AS c
				LEFT JOIN `catalog_item2category` AS i2c ON i2c.CategoryID=c.CategoryID
			WHERE PageID=".intval($pageID)."
			GROUP BY c.CategoryID
			ORDER BY Path2Root, SortOrder");
		foreach($this->_items as $id => $item)
		{
			if (!$item["Level"])
			{
				$this->_items[$id]["ParentID"] = 0;
				continue;
			}
			$parents = explode("#", $item["Path2Root"]);
			$this->_items[$id]["ParentID"] = $parents[$item["Level"]];
		}
	}

	function PrepareForJS()
	{
		foreach($this->_items as $id => $item)
		{
			$this->_items[$id]["jsTitle"] = SmallString($item["Title"], 60);
			$this->_items[$id]["jsDescription"] = SmallString($item["Description"], 90);
		}
	}

	function GetCategoryTree($pageID, $categoryID = null, $onlyChild = false)
	{
		$parentPath = "'#'";
		$parentIDs = array();
		$stmt = GetStatement();
		if ($categoryID > 0)
		{
			$parents = $stmt->FetchField("SELECT Path2Root FROM `catalog_category`
				WHERE PageID=".intval($pageID)."
					AND CategoryID=".intval($categoryID));
			if ($parents)
			{
				$parentIDs = explode("#", $parents);
				unset($parentIDs[count($parentIDs)-1]);
				unset($parentIDs[0]);
			}
			$parentIDs[] = $categoryID;
		}

		$query = "SELECT SUBSTRING(UCASE(Title), 1, 1) AS FirstChar,
				CategoryID, PageID, Path2Root, Title, Description,
				CategoryImage, CategoryImageConfig, 
				StaticPath, SortOrder, Active,
				".(count($parentIDs) ? "IF (CategoryID IN (".implode(",", $parentIDs)."), 1, 0)" : "0")." AS Opened,
				IF (CategoryID=".Connection::GetSQLString($categoryID).", 1, 0) AS Current
			FROM `catalog_category`
			WHERE PageID=".intval($pageID)."
				AND Active='Y'
			ORDER BY Path2Root, SortOrder";
		$items = $stmt->FetchList($query);

		// Define which item is before opened & which item is after opened
		for ($i = 0; $i < count($items); $i++)
		{
			if ($i > 0 && $items[$i]["Opened"] == 1 && $items[$i]["Path2Root"] == $items[$i - 1]["Path2Root"])
				$items[$i - 1]["BeforeOpened"] = 1;
			if ($i < count($items) - 1 && $items[$i]["Opened"] == 1 && $items[$i]["Path2Root"] == $items[$i + 1]["Path2Root"])
				$items[$i + 1]["AfterOpened"] = 1;


			if ($items[$i]['CategoryImage'])
			{
				$imageConfig = LoadImageConfigValues("CategoryImage", $items[$i]["CategoryImageConfig"]);
				$items[$i] = array_merge($items[$i], $imageConfig);
				
				$origW = $items[$i]['CategoryImageWidth'];
				$origH = $items[$i]['CategoryImageHeight'];

				for ($j = 0; $j < count($this->params); $j++)
				{
					$v = $this->params[$j];
					
					if($v["Resize"] == 13)
						$items[$i][$v["Name"]."Path"] = InsertCropParams($v["Path"]."category/", 
																		isset($items[$i][$v["Name"]."X1"]) ? intval($items[$i][$v["Name"]."X1"]) : 0, 
																		isset($items[$i][$v["Name"]."Y1"]) ? intval($items[$i][$v["Name"]."Y1"]) : 0,
																		isset($items[$i][$v["Name"]."X2"]) ? intval($items[$i][$v["Name"]."X2"]) : 0,
																		isset($items[$i][$v["Name"]."Y2"]) ? intval($items[$i][$v["Name"]."Y2"]) : 0).$items[$i]["CategoryImage"];
					else 
						$items[$i][$v["Name"]."Path"] = $v["Path"]."category/".$items[$i]['CategoryImage'];

					if ($v["Name"] != 'CategoryImage')
					{
						list($dstW, $dstH) = GetRealImageSize($v["Resize"], $origW, $origH, $v["Width"], $v["Height"]);
						$items[$i][$v["Name"]."Width"] = $dstW;
						$items[$i][$v["Name"]."Height"] = $dstH;
					}
				}
			}
		}
		
		$result = array();

		for ($i = 0; $i < count($items); $i++)
		{
			// Identify parent id
			$parents = explode("#", $items[$i]["Path2Root"]);
			$items[$i]["Level"] = count($parents) - 2;
			$parent = $parents[$items[$i]["Level"]];
			if (empty($parent))
			{
				$items[$i]["CategoryURL"] = $items[$i]["StaticPath"];
				$result[$items[$i]["CategoryID"]] = $items[$i];
			}
			else
			{
				// Exclude children of inactive nodes
				if (!isset($result[$parent]))
					continue;

				$items[$i]["CategoryURL"] = $result[$parent]["CategoryURL"]."/".$items[$i]["StaticPath"];
                                
                if($onlyChild){
                	$items[$i]['ParentID'] = $parent;
                	$result[$items[$i]["CategoryID"]] = $items[$i];
               	}else{
                	// Form all possible trees
                	$result[$parent]["Children"][] = $items[$i];
               		$result[$items[$i]["CategoryID"]] =& $result[$parent]["Children"][count($result[$parent]["Children"]) - 1];
               	}
                                
				
			}
		}

		$finalResult = array();
		// Define URLs for categories
		foreach($result as $id => $node)
		{
			if($onlyChild){
				
				$result[$id]["CategoryURL"] = $this->baseURL.'/'.$result[$id]["CategoryURL"].'/';
				
				if(!empty($result[$id]['ParentID']) && $result[$id]['ParentID'] == $categoryID)
				$finalResult[] = $result[$id];
				
			}else{
				$result[$id]["CategoryURL"] = $this->baseURL.'/'.$result[$id]["CategoryURL"].'/';
				if ($result[$id]["Level"] == 0)
						$finalResult[] = $result[$id];
			}
			
		}
				
		return $finalResult;
                
	}

	function GetCategoryTreeHash($pageID)
	{
		$stmt = GetStatement();
		$query = "SELECT CategoryID, Path2Root, SortOrder FROM `catalog_category` 
						WHERE PageID=".Connection::GetSQLString($pageID)." ORDER BY CategoryID ASC";
		$categoryList = $stmt->FetchList($query);
		
		return md5(serialize($categoryList));
	}

	// List of categories for LinkForm in ContentTree & Link Dialog of FCKeditor
	function GetCategoryListForLink($pageID, $categoryID = null)
	{
		$tree = $this->GetCategoryTree($pageID, $categoryID);

		$this->treeForSelectBox = array();

		$this->PrepareForSelectBox($tree);

		return $this->treeForSelectBox;
	}

	function PrepareForSelectBox($tree)
	{
		foreach ($tree as $id => $node)
		{
			if (isset($node["Children"]) && count($node["Children"]))
			{
				unset($node["Children"]);
			}

			$node["TitlePrefix"] = "";
			if ($node["Level"] > 0)
			{
				for ($i = 0; $i < $node["Level"]; $i++)
				{
					$node["TitlePrefix"] .= "&nbsp;&nbsp;&nbsp;&nbsp;";
				}
			}
			$node["TitleForSelectBox"] = $node["TitlePrefix"].$node["Title"];

			if (isset($tree[$id]["Children"]) && count($tree[$id]["Children"]))
			{
				$this->treeForSelectBox[] = array_merge($node, array("HasChildren" => true));
				$this->PrepareForSelectBox($tree[$id]["Children"]);
			}
			else
			{
				$this->treeForSelectBox[] = $node;
			}
		}
	}
	
	function GetCategoryListForParentSelection($pageID, $currentCategoryID = null, $selectedCategoryID = null)
	{
		$tree = $this->GetCategoryTree($pageID);

		$this->treeForSelectBox = array();

		$this->PrepareForParentSelection($tree, $currentCategoryID, $selectedCategoryID);

		return $this->treeForSelectBox;
	}

	function PrepareForParentSelection($tree, $currentCategoryID, $selectedCategoryID)
	{
		foreach ($tree as $id => $node)
		{
			if (isset($node["Children"]) && count($node["Children"]))
			{
				unset($node["Children"]);
			}

			$node["TitlePrefix"] = "";
			if ($node["Level"] > 0)
			{
				for ($i = 0; $i < $node["Level"]; $i++)
				{
					$node["TitlePrefix"] .= "&nbsp;&nbsp;&nbsp;&nbsp;";
				}
			}
			$node["TitleForSelectBox"] = $node["TitlePrefix"].$node["Title"];
			if($node["CategoryID"] == $selectedCategoryID)
			{
				$node["Selected"] = 1;
			}
			
			if($node["CategoryID"] == $currentCategoryID)
			{
				continue;
			}
			
			if (isset($tree[$id]["Children"]) && count($tree[$id]["Children"]))
			{
				$this->treeForSelectBox[] = array_merge($node, array("HasChildren" => true));
				$this->PrepareForParentSelection($tree[$id]["Children"], $currentCategoryID, $selectedCategoryID);
			}
			else
			{
				$this->treeForSelectBox[] = $node;
			}
		}
	}

	function GetCategoryListForTemplate($pageID, $categoryID = null, $onlyChild = false)
	{
		$tree = $this->GetCategoryTree($pageID, $categoryID, $onlyChild);
		return $this->PrepareForTemplate($tree);
	}

	function PrepareForTemplate($tree, $level = 0)
	{
		foreach ($tree as $k => $v)
		{
			if (isset($v["Children"]))
			{
				$tree[$k]["Children".$level] = $this->PrepareForTemplate($v["Children"], $level + 1);
				unset($tree[$k]["Children"]);
			}
		}
		return $tree;
	}

	function SwitchPages($upCategoryID, $downCategoryID)
	{
		$stmt = GetStatement();
		$query = "SELECT CategoryID, Path2Root, SortOrder FROM `catalog_category` WHERE
			CategoryID=".Connection::GetSQLString($upCategoryID)." OR
			CategoryID=".Connection::GetSQLString($downCategoryID)."";
		if ($categories = $stmt->FetchIndexedList($query, "CategoryID"))
		{
			if (count($categories) == 2 && $categories[$upCategoryID]["Path2Root"] == $categories[$downCategoryID]["Path2Root"] &&
				$categories[$upCategoryID]["SortOrder"] - 1 == $categories[$downCategoryID]["SortOrder"])
			{
				$query = "UPDATE `catalog_category` SET SortOrder=".Connection::GetSQLString($categories[$downCategoryID]["SortOrder"])."
					WHERE CategoryID=".Connection::GetSQLString($upCategoryID);
				$stmt->Execute($query);
				$query = "UPDATE `catalog_category` SET SortOrder=".Connection::GetSQLString($categories[$upCategoryID]["SortOrder"])."
					WHERE CategoryID=".Connection::GetSQLString($downCategoryID);
				$stmt->Execute($query);
				return true;
			}
		}
		return false;
	}
	
	function SaveCategoryPosition($categoryID, $parentID, $sortOrder)
	{
		$updateCategoryList = array();
		
		$stmt = GetStatement();
		$pageID = $stmt->FetchField("SELECT PageID FROM `catalog_category` WHERE CategoryID=".Connection::GetSQLString($categoryID));
		
		$sortOrder = intval($sortOrder);
		if($sortOrder < 0) $sortOrder = 0;
		
		$query = "SELECT Path2Root, SortOrder FROM `catalog_category` WHERE CategoryID=".Connection::GetSQLString($categoryID);
		$categoryInfo = $stmt->FetchRow($query);
		
		$parentCategory = new CatalogCategory($this->module, $pageID, '');
		$parentCategory->LoadByID($parentID);
		$path2root = $parentCategory->GetFullPath2Root();
		
		//if new parent is set
		if ($path2root != $categoryInfo["Path2Root"])
		{
			//update path2root for current category and her children
			$query = "UPDATE `catalog_category` SET Path2Root=".Connection::GetSQLString($path2root)." 
							WHERE CategoryID=".Connection::GetSQLString($categoryID);
			$stmt->Execute($query);
			
			$query = "UPDATE `catalog_category`
				SET Path2Root=REPLACE(Path2Root,
				".Connection::GetSQLString($categoryInfo["Path2Root"].$categoryID."#").",
				".Connection::GetSQLString($path2root.$categoryID."#").")
				WHERE PageID=".$pageID;
			$stmt->Execute($query);
			
			//move up categories left on old path2root level
			$query = "UPDATE `catalog_category` SET SortOrder=SortOrder-1 WHERE
				Path2Root=".Connection::GetSQLString($categoryInfo["Path2Root"])."
				AND SortOrder>".Connection::GetSQLString($categoryInfo["SortOrder"]);
			$stmt->Execute($query);
			
			//update sortorder for current category
			$query = "UPDATE `catalog_category` SET SortOrder=".$sortOrder." WHERE CategoryID=".Connection::GetSQLString($categoryID);
			$stmt->Execute($query);
	
			//move down categories on new path2root level
			$query = "UPDATE `catalog_category` SET SortOrder=SortOrder+1
				WHERE SortOrder>=".$sortOrder." 
					AND CategoryID<>".$categoryID." 
					AND PageID=".$pageID." 
					AND Path2Root=".Connection::GetSQLString($path2root);
			$stmt->Execute($query);
			
			//update item2category links
			$query = "SELECT ItemID FROM `catalog_item2category` WHERE CategoryID=".Connection::GetSQLString($categoryID);
			$itemList = $stmt->FetchList($query);
			
			if(is_array($itemList) && count($itemList) > 0)
			{
				$updateCategoryIDs = array();			
				foreach ($itemList as $v)
				{
					$query = "SELECT CategoryID FROM `catalog_item2category` WHERE ItemID=".Connection::GetSQLString($v["ItemID"])." AND `Real`='Y'";
					$categoryIDs = array_keys($stmt->FetchIndexedList($query));
					
					$item = new CatalogItem($this->module, $pageID);
					$item->SetProperty("ItemID", $v["ItemID"]);
					$item->SetProperty("CategoryIDs", $categoryIDs);
					$item->SetProperty("PageID", $pageID);
					$ids = $item->SaveItem2Category();
					$updateCategoryIDs = array_merge($updateCategoryIDs, $ids);
				}
				$updateCategoryIDs = array_unique($updateCategoryIDs);
				if(count($updateCategoryIDs) > 0)
				{
					$query = "SELECT COUNT(i2c.Item2CategoryID) AS ItemCount, c.CategoryID FROM `catalog_category` AS c 
								LEFT JOIN `catalog_item2category` AS i2c ON i2c.CategoryID=c.CategoryID  
								WHERE c.CategoryID IN(".implode(", ", Connection::GetSQLArray($updateCategoryIDs)).")
								GROUP BY c.CategoryID";
					$updateCategoryList = $stmt->FetchList($query);
				}
			}
		}
		else
		{
			$query = "UPDATE `catalog_category` SET SortOrder=".$sortOrder." WHERE CategoryID=".Connection::GetSQLString($categoryID);
			$stmt->Execute($query);
	
			if ($sortOrder > $categoryInfo["SortOrder"])
			{
				$query = "UPDATE `catalog_category` SET SortOrder=SortOrder-1
					WHERE SortOrder<=".$sortOrder." 
						AND SortOrder>".$categoryInfo["SortOrder"]."
						AND CategoryID<>".$categoryID." 
						AND PageID=".$pageID." 
						AND Path2Root=".Connection::GetSQLString($path2root);
				$stmt->Execute($query);
			}
			else if ($sortOrder < $categoryInfo["SortOrder"])
			{
				$query = "UPDATE `catalog_category` SET SortOrder=SortOrder+1
					WHERE SortOrder>=".$sortOrder." 
						AND SortOrder<".$categoryInfo["SortOrder"]."
						AND CategoryID<>".$categoryID." 
						AND PageID=".$pageID." 
						AND Path2Root=".Connection::GetSQLString($path2root);
				$stmt->Execute($query);
			}
		}
		
		return $updateCategoryList;
	}

	function RemoveByPageID($pageID)
	{
		/*@var stmt Statement */
		$stmt = GetStatement();

		$query = "SELECT CategoryID, Title, CategoryImage
			FROM `catalog_category`
			WHERE PageID=".intval($pageID);
		if ($result = $stmt->FetchList($query))
		{
			// Remove category images
			$categoriesToRemove = array();
			for ($i = 0; $i < count($result); $i++)
			{
				if ($result[$i]["CategoryImage"])
				{
					@unlink(CATALOG_IMAGE_DIR."category/".$result[$i]["CategoryImage"]);
				}
				$categoriesToRemove[] = $result[$i]["CategoryID"];
			}

			if (count($categoriesToRemove) > 0)
			{
				// Remove all links to categories
				$stmt->Execute("DELETE FROM `catalog_item2category`
					WHERE CategoryID IN (".implode(",", Connection::GetSQLArray($categoriesToRemove)).")");

				// Remove items which are not related to categories (orphans)
				$query = "SELECT i.ItemID, COUNT(i2c.Item2CategoryID) AS CategoryCount
					FROM catalog_item AS i
					LEFT JOIN catalog_item2category AS i2c ON i2c.ItemID = i.ItemID
					GROUP BY i.ItemID
					HAVING CategoryCount=0";
				if ($result = $stmt->FetchList($query))
				{
					$itemList = new CatalogItemList($this->module);
					$itemsToRemove = array();
					for ($i = 0; $i < count($result); $i++)
					{
						$itemsToRemove[] = $result[$i]["ItemID"];
					}
					$itemList->Remove($itemsToRemove);
				}

				// And finaly remove categories
				$stmt->Execute("DELETE FROM `catalog_category` WHERE PageID=".intval($pageID));
			}
		}
	}
}

?>
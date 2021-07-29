<?php

require_once(dirname(__FILE__)."/../init.php");
require_once(dirname(__FILE__)."/item_list.php");
es_include("localobject.php");
es_include("filesys.php");

class CatalogCategory extends LocalObject
{
	var $_acceptMimeTypes = array(
		'image/png',
		'image/x-png',
		'image/gif',
		'image/jpeg',
		'image/pjpeg'
	);
	var $module;
	var $pageID;
	var $config;
	var $params;

	function CatalogCategory($module, $pageID, $baseURL, $config = array(), $data = array())
	{
		parent::LocalObject($data);

		$this->module = $module;
		$this->pageID = intval($pageID);
		$this->baseURL = $baseURL;
		$this->config = is_array($config) ? $config : array();

		if (count($this->config) > 0)
		{
			$this->params = LoadImageConfig('CategoryImage', $this->module, $this->config['CategoryImage'].",".CATALOG_CATEGORY_IMAGE);
			$this->baseURL .= "/".$this->config['CategoryURLPrefix'];
		}
	}

	function LoadByID($categoryID)
	{
		$query = "SELECT CategoryID, PageID, Path2Root, Title, Description, CategoryImage, CategoryImageConfig, 				 
				TitleH1, MetaTitle, MetaKeywords, MetaDescription, StaticPath, Content, SortOrder, Created, Modified, Active 				
			FROM `catalog_category`
			WHERE CategoryID=".intval($categoryID)."
				AND PageID=".$this->pageID;
		$this->LoadFromSQL($query);

		$this->_PrepareContentBeforeShow();

		if ($this->GetProperty("CategoryID"))
			return true;
		else
			return false;
	}

	function LoadByPath($path)
	{
		// Try to find category by path
		$query = "SELECT CategoryID, Path2Root, StaticPath
			FROM `catalog_category` WHERE
				PageID=".$this->pageID."
				AND StaticPath IN (".implode(", ", Connection::GetSQLArray($path)).")
			ORDER BY Path2Root";
		$stmt = GetStatement();
		$result = $stmt->FetchList($query);

		$currentCategoryID = null;
		$levels = 0;

		for ($i = 0; $i < count($result); $i++)
		{
			$p = explode("#", $result[$i]["Path2Root"]);
			$c = count($p);
			// First page of the path is found
			if ($result[$i]["StaticPath"] == $path[0] && $c == 2)
			{
				$currentCategoryID = $result[$i]["CategoryID"];
				$levels++;
				continue;
			}
			// Find other pages
			if (!is_null($currentCategoryID) && count($path) > $levels)
			{
				if ($result[$i]["StaticPath"] == $path[$levels] && $p[$c - 2] == $currentCategoryID)
				{
					$currentCategoryID = $result[$i]["CategoryID"];
					$levels++;
				}
			}
		}

		if ($levels == count($path))
		{
			return $this->LoadByID($currentCategoryID);
		}
		else
		{
			return false;
		}
	}

	function GetFullPath2Root()
	{
		if ($this->GetIntProperty("CategoryID") > 0)
			return $this->GetProperty("Path2Root").$this->GetIntProperty("CategoryID")."#";
		else
			return "#";
	}

	function _PrepareContentBeforeShow()
	{
		if ($this->GetProperty("CategoryID"))
		{
			$this->SetProperty("ParentID", $this->GetParentID());
			$this->SetProperty("Content", str_replace("<P_T_R>", PROJECT_PATH, $this->GetProperty("Content")));
		}

		if ($this->GetProperty("CategoryImage"))
		{
			$imageConfig = LoadImageConfigValues("CategoryImage", $this->GetProperty("CategoryImageConfig"));
			$this->AppendFromArray($imageConfig);
			$origW = $this->GetProperty("CategoryImageWidth");
			$origH = $this->GetProperty("CategoryImageHeight");

			for ($i = 0; $i < count($this->params); $i++)
			{
				$v = $this->params[$i];

				if($v["Resize"] == 13)
					$this->SetProperty($v["Name"]."Path", InsertCropParams($v["Path"]."category/", 
																		$this->GetIntProperty($v["Name"]."X1"), 
																		$this->GetIntProperty($v["Name"]."Y1"), 
																		$this->GetIntProperty($v["Name"]."X2"), 
																		$this->GetIntProperty($v["Name"]."Y2")).$this->GetProperty("CategoryImage"));
				else
					$this->SetProperty($v["Name"]."Path", $v["Path"]."category/".$this->GetProperty("CategoryImage"));

				if ($v["Name"] != 'CategoryImage')
				{
					list($dstW, $dstH) = GetRealImageSize($v["Resize"], $origW, $origH, $v["Width"], $v["Height"]);
					$this->SetProperty($v["Name"]."Width", $dstW);
					$this->SetProperty($v["Name"]."Height", $dstH);
				}
			}
		}
	}

	function GetImageParams()
	{
		$paramList = array();
		for ($i = 0; $i < count($this->params); $i++)
		{
			$paramList[] = array(
				"Name" => $this->params[$i]['Name'],
				"SourceName" => $this->params[$i]['SourceName'],
				"Width" => $this->params[$i]['Width'],
				"Height" => $this->params[$i]['Height'],
				"Resize" => $this->params[$i]['Resize'],
				"X1" => $this->GetIntProperty("CategoryImage".$this->params[$i]['SourceName']."X1"),
				"Y1" => $this->GetIntProperty("CategoryImage".$this->params[$i]['SourceName']."Y1"),
				"X2" => $this->GetIntProperty("CategoryImage".$this->params[$i]['SourceName']."X2"),
				"Y2" => $this->GetIntProperty("CategoryImage".$this->params[$i]['SourceName']."Y2")
			);
		}
		return $paramList;
	}

	function Save()
	{
		$result1 = $this->SaveCategoryImage($this->GetProperty("SavedCategoryImage"));
		$result2 = $this->Validate();
		if (!$result1 || !$result2)
		{
			$this->_PrepareContentBeforeShow();
			return false;
		}

		/*@var stmt Statement */
		$stmt = GetStatement();

		$content = PrepareContentBeforeSave($this->GetProperty("Content"));

		$categoryOld = new CatalogCategory($this->module, $this->GetIntProperty("PageID"), $this->config);
		if ($categoryOld->LoadByID($this->GetIntProperty("CategoryID")))
		{
			$currentPath2Root = $categoryOld->GetProperty("Path2Root");
			$currentSortOrder = $categoryOld->GetProperty("SortOrder");

			$queryShift = null;
			$queryUpdate = null;
			$newSortOrder = null;

			if ($currentPath2Root != $this->GetProperty("Path2Root"))
			{
				// Shift position of old elements
				$queryShift = "UPDATE `catalog_category` SET SortOrder=SortOrder-1 WHERE
					Path2Root=".Connection::GetSQLString($currentPath2Root)."
					AND SortOrder>".Connection::GetSQLString($currentSortOrder);

				// Update Path2Root for children
				$queryUpdate = "UPDATE `catalog_category`
					SET Path2Root=REPLACE(Path2Root,
					".Connection::GetSQLString($currentPath2Root.$this->GetProperty("CategoryID")."#").",
					".Connection::GetSQLString($this->GetProperty("Path2Root").$this->GetProperty("CategoryID")."#").")
					WHERE PageID=".$this->pageID;

				// In new location page will be last
				$this->SetProperty("SortOrder", $this->GetCountBrother());
				$newSortOrder = $this->GetProperty("SortOrder");
			}

			if (!$this->IsPropertySet("MetaTitle"))
				$this->SetProperty("MetaTitle", $categoryOld->GetProperty("MetaTitle"));
			if (!$this->IsPropertySet("MetaKeywords"))
				$this->SetProperty("MetaKeywords", $categoryOld->GetProperty("MetaKeywords"));
			if (!$this->IsPropertySet("MetaDescription"))
				$this->SetProperty("MetaDescription", $categoryOld->GetProperty("MetaDescription"));

			if (strlen($this->GetProperty("MetaTitle")) == 0)
			{
				$this->SetProperty("MetaTitle", $this->GetProperty("Title"));
			}

			$query = "UPDATE `catalog_category` SET
					Path2Root=".$this->GetPropertyForSQL("Path2Root").",
					Title=".$this->GetPropertyForSQL("Title").",
					Description=".$this->GetPropertyForSQL("Description").",
					CategoryImage=".$this->GetPropertyForSQL("CategoryImage").",
					CategoryImageConfig=".Connection::GetSQLString(json_encode($this->GetProperty("CategoryImageConfig"))).",
					TitleH1=".$this->GetPropertyForSQL("TitleH1").",
					MetaTitle=".$this->GetPropertyForSQL("MetaTitle").",
					MetaKeywords=".$this->GetPropertyForSQL("MetaKeywords").",
					MetaDescription=".$this->GetPropertyForSQL("MetaDescription").",
					StaticPath=".$this->GetPropertyForSQL("StaticPath").",
					Content=".Connection::GetSQLString($content).",
					".(is_null($newSortOrder) ? "" : "SortOrder=".$this->GetPropertyForSQL("SortOrder").",")."
					Modified=NOW(),
					Active=".$this->GetPropertyForSQL("Active")."
				WHERE CategoryID=".$this->GetIntProperty("CategoryID")."
					AND PageID=".$this->pageID;

			if (!$stmt->Execute($query))
			{
				$this->AddError("sql-error");
				return false;
			}
			else
			{
				if (!is_null($queryShift))
				{
					$stmt->Execute($queryShift);
				}
				if (!is_null($queryUpdate))
				{
					$stmt->Execute($queryUpdate);
				}
				if ($currentPath2Root != $this->GetProperty("Path2Root"))
				{
					// If category saved successfully and parent is changed then update item2category links 
					$query = "SELECT ItemID FROM `catalog_item2category` WHERE CategoryID=".$this->GetPropertyForSQL("CategoryID");
					$itemList = $stmt->FetchList($query);
					
					if(is_array($itemList) && count($itemList) > 0)
					{			
						foreach ($itemList as $v)
						{
							$query = "SELECT CategoryID FROM `catalog_item2category` WHERE ItemID=".Connection::GetSQLString($v["ItemID"])." AND `Real`='Y'";
							$categoryIDs = array_keys($stmt->FetchIndexedList($query));
							
							$item = new CatalogItem($this->module, $this->GetProperty("PageID"));
							$item->SetProperty("ItemID", $v["ItemID"]);
							$item->SetProperty("CategoryIDs", $categoryIDs);
							$item->SetProperty("PageID", $this->GetProperty("PageID"));
							$item->SaveItem2Category();
						}
					}
				}
			}
		}
		else
		{
			$this->SetProperty("CategoryID", 0);

			// Newly added page is last
			$this->SetProperty("SortOrder", $this->GetCountBrother());

			if (!$this->IsPropertySet("MetaTitle"))
				$this->SetProperty("MetaTitle", "");
			if (!$this->IsPropertySet("MetaKeywords"))
				$this->SetProperty("MetaKeywords", "");
			if (!$this->IsPropertySet("MetaDescription"))
				$this->SetProperty("MetaDescription", "");

			if (strlen($this->GetProperty("MetaTitle")) == 0)
			{
				$this->SetProperty("MetaTitle", $this->GetProperty("Title"));
			}

			$query = "INSERT INTO `catalog_category` (PageID, Path2Root, Title,
				Description, CategoryImage, CategoryImageConfig, 
				TitleH1, MetaTitle, MetaKeywords, MetaDescription, StaticPath, Content,
				SortOrder, Created, Active)
				VALUES (
					".$this->pageID.",
					".$this->GetPropertyForSQL("Path2Root").",
					".$this->GetPropertyForSQL("Title").",
					".$this->GetPropertyForSQL("Description").",
					".$this->GetPropertyForSQL("CategoryImage").",
					".Connection::GetSQLString(json_encode($this->GetProperty("CategoryImageConfig"))).",
					".$this->GetPropertyForSQL("TitleH1").",
					".$this->GetPropertyForSQL("MetaTitle").",
					".$this->GetPropertyForSQL("MetaKeywords").",
					".$this->GetPropertyForSQL("MetaDescription").",
					".$this->GetPropertyForSQL("StaticPath").",
					".Connection::GetSQLString($content).",
					".$this->GetPropertyForSQL("SortOrder").",
					NOW(),
					".$this->GetPropertyForSQL("Active").")";
			if (!$stmt->Execute($query))
			{
				$this->AddError("sql-error");
				return false;
			}
			$this->SetProperty("CategoryID", $stmt->GetLastInsertID());
		}

		return true;
	}

	function OpenParents()
	{
		$expandedNodes = array();
		$newExpandedNodes = array();
		if (isset($_COOKIE['cen']))
		{
			if (preg_match("/\['\d+'(,'\d+')*\\]/", $_COOKIE['cen']))
			{
				$tmpStr = str_replace("[", "", $_COOKIE['cen']);
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

		setcookie("cen", $expandedNodesJs, time()+60*60*24*30*COOKIE_EXPIRE, PROJECT_PATH);
	}

	function LoadMeta($categoryID)
	{
		/*@var stmt Statement */
		$stmt = GetStatement();
		$query = "SELECT MetaTitle, MetaKeywords, MetaDescription FROM `catalog_category`
			WHERE CategoryID=".intval($categoryID)." AND PageID=".$this->pageID;
		return $stmt->FetchRow($query);
	}

	function UpdateMeta($categoryID, $metaTitle, $metaKeywords, $metaDescription)
	{
		/*@var stmt Statement */
		$stmt = GetStatement();
		$query = "UPDATE `catalog_category` SET
				MetaTitle=".Connection::GetSQLString($metaTitle).",
				MetaKeywords=".Connection::GetSQLString($metaKeywords).",
				MetaDescription=".Connection::GetSQLString($metaDescription)."
			WHERE CategoryID=".intval($categoryID)." AND PageID=".$this->pageID;
		$stmt->Execute($query);
	}

	function Remove($categoryID)
	{
		/*@var stmt Statement */
		$stmt = GetStatement();

		$query = "SELECT CategoryID, CategoryImage, Path2Root, SortOrder
			FROM `catalog_category`
			WHERE CategoryID=".intval($categoryID)." AND PageID=".$this->pageID;
		if ($result = $stmt->FetchRow($query))
		{
			$fullPath2Root = $result["Path2Root"].$result["CategoryID"]."#";
			$categoriesToRemove = array();
			$categoriesToRemove[] = $result;

			// Shift brothers before removing current category
			$stmt->Execute("UPDATE `catalog_category` SET SortOrder=SortOrder-1 WHERE
				Path2Root=".Connection::GetSQLString($result["Path2Root"])."
				AND PageID=".$this->pageID."
				AND SortOrder>".intval($result["SortOrder"]));

			// Prepare list of subcategories to remove
			$query = "SELECT CategoryID, CategoryImage FROM `catalog_category`
				WHERE Path2Root LIKE ('".Connection::GetSQLLike($fullPath2Root)."%')
					AND PageID=".$this->pageID;
			$result = $stmt->FetchList($query);
			if ($result)
			{
				$categoriesToRemove = array_merge($categoriesToRemove, $result);
			}

			// Remove category images
			$categoriesToRemoveForSQL = array();
			for ($i = 0; $i < count($categoriesToRemove); $i++)
			{
				if ($categoriesToRemove[$i]["CategoryImage"])
				{
					@unlink(CATALOG_IMAGE_DIR."category/".$categoriesToRemove[$i]["CategoryImage"]);
				}
				$categoriesToRemoveForSQL[] = $categoriesToRemove[$i]["CategoryID"];
			}

			// Delete current category & all subcategories
			$stmt->Execute("DELETE FROM `catalog_category` WHERE
				PageID=".$this->pageID." AND
				(CategoryID=".intval($categoryID)." OR
				Path2Root LIKE ('".Connection::GetSQLLike($fullPath2Root)."%'))");

			// Remove all links to categories
			$stmt->Execute("DELETE FROM `catalog_item2category`
				WHERE CategoryID IN (".implode(",", Connection::GetSQLArray($categoriesToRemoveForSQL)).")");

			// Remove items which are not related to categories (orphans)
			$query = "SELECT i.ItemID, COUNT(i2c.Item2CategoryID) AS CategoryCount
				FROM catalog_item AS i
				LEFT JOIN catalog_item2category AS i2c ON i2c.ItemID = i.ItemID
				GROUP BY i.ItemID
				HAVING CategoryCount=0";
			if ($result = $stmt->FetchList($query))
			{
				$itemsToRemove = array();
				for ($i = 0; $i < count($result); $i++)
				{
					$itemsToRemove[] = $result[$i]["ItemID"];
				}
				$itemList = new ItemList($this->module);
				$itemList->Remove($itemsToRemove);
			}
		}
	}

	function Validate()
	{
		if ($this->GetProperty("Active") != "Y")
			$this->SetProperty("Active", "N");
		
		$this->SetProperty("Title", trim($this->GetProperty("Title")));
		if (!$this->GetProperty("Title"))
		{
			$this->AddError("category-title-empty", $this->module);
		}

		// If Category with CategoryID=ParentID is not found it is first level category
		$category = new CatalogCategory($this->module, $this->pageID, $this->config);
		if ($category->LoadByID($this->GetProperty("ParentID")))
		{
			$this->SetProperty("Path2Root", $category->GetFullPath2Root());
			$this->SetProperty("PageID", $category->GetProperty("PageID"));
		}
		else
		{
			$this->SetProperty("Path2Root", "#");
			$this->SetProperty("ParentID", 0);
		}

		// Field StaticPath must be defined and consists only latin chars, numbers, hyphens (-), dots (.), understrikes (_)
		if (!$this->GetProperty("StaticPath"))
		{
			$this->AddError("category-static-path-empty", $this->module);
		}
		else if (!preg_match("/^[a-z0-9\._-]+$/i", $this->GetProperty("StaticPath")))
		{
			$this->AddError("static-path-incorrect");
		}
		else
		{
			/*@var stmt Statement */
			$stmt = GetStatement();

			$query = "SELECT COUNT(CategoryID) FROM `catalog_category` WHERE
				StaticPath=".$this->GetPropertyForSQL("StaticPath")."
				AND Path2Root=".$this->GetPropertyForSQL("Path2Root")."
				AND CategoryID!=".$this->GetPropertyForSQL("CategoryID")."
				AND PageID=".$this->pageID;
			if ($stmt->FetchField($query))
			{
				$this->AddError("static-path-is-not-unique");
			}
		}

		return !$this->HasErrors();
	}

	function GetParentID()
	{
		if ($this->GetProperty("Path2Root") == "#") return 0;
		if (!$this->GetProperty("Path2Root")) return 0;
		$parentIDs = explode("#", $this->GetProperty("Path2Root"));
		return ($parentIDs[count($parentIDs)-2]);
	}

	function GetPathAsArray($baseURL = '')
	{
		$path = array();

		if ($this->GetProperty("Path2Root"))
			$parents = explode("#", $this->GetProperty("Path2Root"));
		else
			$parents = array("", "");

		// Delete first empty element
		array_shift($parents);

		// Replace last empty element by current CategoryID
		$parents[count($parents) - 1] = $this->GetProperty("CategoryID");
		$parents = Connection::GetSQLArray($parents);
		/*@var stmt Statement */
		$stmt = GetStatement();
		$query = "SELECT CategoryID, Title, Description, StaticPath
			FROM `catalog_category`
			WHERE CategoryID IN (".implode(",", $parents).")
				AND PageID=".$this->pageID."
			ORDER BY Path2Root";
		if ($pathPages = $stmt->FetchList($query))
		{
			$currPath = $baseURL ? $baseURL : $this->baseURL;

			for ($i = 0; $i < count($pathPages); $i++)
			{
				$currPath .= "/".$pathPages[$i]["StaticPath"];
				$path[] = array("StaticPath" => $pathPages[$i]["StaticPath"], "PageURL" => $currPath."/", "Title" => $pathPages[$i]["Title"], "Description" => $pathPages[$i]["Description"]);
			}
		}
		return $path;
	}

	function GetCountChildren()
	{
		/*@var stmt Statement */
		$stmt = GetStatement();
		$query = "SELECT COUNT(CategoryID) FROM `catalog_category` WHERE
			Path2Root=".Connection::GetSQLString($this->GetFullPath2Root())."
			AND PageID=".$this->pageID;
		$res = $stmt->FetchField($query);
		return $res;
	}

	function GetCountBrother()
	{
		/*@var stmt Statement */
		$stmt = GetStatement();
		$query = "SELECT COUNT(CategoryID) FROM `catalog_category` WHERE
			Path2Root=".$this->GetPropertyForSQL("Path2Root")."
			AND PageID=".$this->pageID;
		$res = $stmt->FetchField($query);
		return $res;
	}

	function GetParentLists()
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

		$query = "SELECT CategoryID, Title, Path2Root
			FROM `catalog_category` WHERE Path2Root IN(".implode(",", $parentList).")
			".($this->GetProperty("CategoryID") ? "AND CategoryID<>".$this->GetIntProperty("CategoryID") : "")."
			AND PageID=".$this->pageID."
			ORDER BY Path2Root, SortOrder";
		/*@var stmt Statement */
		$stmt = GetStatement();
		$parentLists = array();
		if ($result = $stmt->FetchList($query))
		{
			$categoryList = array();
			$prevPath2Root = "#";
			$j = 0;
			for ($i = 0; $i < count($result); $i++)
			{
				if ($prevPath2Root != $result[$i]["Path2Root"])
				{
					$parentLists[] = array("id" => $j++, "CategoryList" => $categoryList);
					$prevPath2Root = $result[$i]["Path2Root"];
					$categoryList = array();
				}
				if ($result[$i]["CategoryID"] == $parents[$j + 1])
				{
					$result[$i]["Selected"] = 1;
				}
				$result[$i]["Title"] = SmallString($result[$i]["Title"], 20);
				$categoryList[] = $result[$i];
			}
			$parentLists[] = array("id" => $j++, "CategoryList" => $categoryList);
		}

		return $parentLists;
	}

	function SaveCategoryImage($savedImage = "")
	{
		$fileSys = new FileSys();

		$original = false;
		if ($this->config['CategoryImageKeepFileName'])
		{
			if ($savedImage)
				$original = $savedImage;
			else
				$original = true;
		}

		$newCategoryImage = $fileSys->Upload("CategoryImage", CATALOG_IMAGE_DIR."category/", $original, $this->_acceptMimeTypes);
		if ($newCategoryImage)
		{
			$this->SetProperty("CategoryImage", $newCategoryImage["FileName"]);

			// Remove old image if it has different name
			if ($savedImage && $savedImage != $newCategoryImage["FileName"])
				@unlink(CATALOG_IMAGE_DIR."category/".$savedImage);
		}
		else
		{
			if ($savedImage)
				$this->SetProperty("CategoryImage", $savedImage);
			else
				$this->SetProperty("CategoryImage", null);
		}

		$this->_properties["CategoryImageConfig"]["Width"] = 0;
		$this->_properties["CategoryImageConfig"]["Height"] = 0;

		if ($this->GetProperty('CategoryImage'))
		{
			if ($info = @getimagesize(CATALOG_IMAGE_DIR."category/".$this->GetProperty('CategoryImage')))
			{
				$this->_properties["CategoryImageConfig"]["Width"] = $info[0];
				$this->_properties["CategoryImageConfig"]["Height"] = $info[1];
			}
		}

		$this->LoadErrorsFromObject($fileSys);

		return !$fileSys->HasErrors();
	}

	function RemoveCategoryImage($categoryID, $savedImage)
	{
		if ($savedImage)
		{
			@unlink(CATALOG_IMAGE_DIR."category/".$savedImage);
		}

		$categoryID = intval($categoryID);
		if ($categoryID > 0)
		{
			$stmt = GetStatement();
			$imageFile = $stmt->FetchField("SELECT CategoryImage
				FROM `catalog_category`
				WHERE CategoryID=".$categoryID." AND PageID=".$this->pageID);

			if ($imageFile)
				@unlink(CATALOG_IMAGE_DIR."category/".$imageFile);

			$stmt->Execute("UPDATE `catalog_category` SET
				CategoryImage=NULL, CategoryImageConfig=NULL 
				WHERE CategoryID=".$categoryID." AND PageID=".$this->pageID);
		}
	}
}
?>
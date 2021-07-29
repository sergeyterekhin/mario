<?php

require_once(dirname(__FILE__)."/../init.php");
es_include("localobjectlist.php");
require_once(dirname(__FILE__)."/media_list.php");

class GalleryCategoryList extends LocalObjectList
{
	var $module;
	var $config;
	var $params;

	function GalleryCategoryList($module, $config = array(), $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->config = is_array($config) ? $config : array();

		if (count($this->config) > 0)
		{
			$this->params = LoadImageConfig('CategoryImage', $this->module, $this->config['CategoryImage'].",".GALLERY_CATEGORY_IMAGE);
		}
	}

	function Load($request)
	{
		if (is_null($request->GetProperty('BaseURL')))
			$request->SetProperty('BaseURL', '');

		$where = array();
		if ($request->GetProperty('ShowActive') == 'N' || $request->GetProperty('ShowActive') == 'Y')
			$where[] = "Active=".$request->GetPropertyForSQL('ShowActive');

		$query = "SELECT CategoryID, PageID, Title, Description, StaticPath,
				Content, Created, Modified, CategoryImage,
				CategoryImageConfig, 
				IF (Modified IS NULL, Created, Modified) AS LastModified,
				CONCAT(".$request->GetPropertyForSQL('BaseURL').", '/', StaticPath, ".Connection::GetSQLString("/".INDEX_PAGE.HTML_EXTENSION).") AS CategoryURL,
				IF(CategoryID=".$request->GetIntProperty('CategoryID').", 1, 0) AS Selected,
				MetaTitle, MetaKeywords, MetaDescription, Active 
			FROM `gallery_category`
			WHERE PageID=".$request->GetIntProperty("PageID")."
			".(count($where) > 0 ? "AND ".implode(" AND ", $where) : "")."
			ORDER BY SortOrder ASC";

		$this->LoadFromSQL($query);

		for ($i = 0; $i < count($this->_items); $i++)
		{
			$this->_items[$i]['Content'] = str_replace("<P_T_R>", PROJECT_PATH, $this->_items[$i]['Content']);
			if ($this->_items[$i]['CategoryImage'])
			{
				$imageConfig = LoadImageConfigValues("CategoryImage", $this->_items[$i]["CategoryImageConfig"]);
				$this->_items[$i] = array_merge($this->_items[$i], $imageConfig);
			
				$origW = $this->_items[$i]['CategoryImageWidth'];
				$origH = $this->_items[$i]['CategoryImageHeight'];

				for ($j = 0; $j < count($this->params); $j++)
				{
					$v = $this->params[$j];

					if($v["Resize"] == 13)
						$this->_items[$i][$v["Name"]."Path"] = InsertCropParams($v["Path"]."category/", 
																		isset($this->_items[$i][$v["Name"]."X1"]) ? intval($this->_items[$i][$v["Name"]."X1"]) : 0, 
																		isset($this->_items[$i][$v["Name"]."Y1"]) ? intval($this->_items[$i][$v["Name"]."Y1"]) : 0,
																		isset($this->_items[$i][$v["Name"]."X2"]) ? intval($this->_items[$i][$v["Name"]."X2"]) : 0,
																		isset($this->_items[$i][$v["Name"]."Y2"]) ? intval($this->_items[$i][$v["Name"]."Y2"]) : 0).$this->_items[$i]["CategoryImage"];
					else 	
						$this->_items[$i][$v["Name"]."Path"] = $v["Path"]."category/".$this->_items[$i]['CategoryImage'];

					if ($v["Name"] != 'CategoryImage')
					{
						list($dstW, $dstH) = GetRealImageSize($v["Resize"], $origW, $origH, $v["Width"], $v["Height"]);
						$this->_items[$i][$v["Name"]."Width"] = $dstW;
						$this->_items[$i][$v["Name"]."Height"] = $dstH;
					}
				}
			}
		}
	}

	function RemoveByCategoryIDs($ids)
	{
		if (is_array($ids) && count($ids) > 0)
		{
			$categoriesToRemove = array();
			$categoriesRemoved = array();
			$forResort = array();

			/*@var stmt Statement */
			$stmt = GetStatement();

			$page = new Page();
			$config = array();

			$query = "SELECT CategoryID, Title, CategoryImage, PageID, SortOrder
				FROM `gallery_category`
				WHERE CategoryID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			if ($result = $stmt->FetchList($query))
			{
				for ($i = 0; $i < count($result); $i++)
				{
					if ($result[$i]["CategoryImage"])
					{
						@unlink(GALLERY_IMAGE_DIR."category/".$result[$i]["CategoryImage"]);
					}
					$categoriesRemoved[] = $result[$i]['Title'];
					$forResort[$result[$i]['CategoryID']] = array($result[$i]['PageID'], $result[$i]['SortOrder']);
					if (!isset($config[$result[$i]['PageID']]))
					{
						if ($page->LoadByID($result[$i]['PageID']))
							$config[$result[$i]['PageID']] = $page->GetConfig();
					}

					if (!isset($categoriesToRemove[$result[$i]['PageID']]))
						$categoriesToRemove[$result[$i]['PageID']] = array();
					$categoriesToRemove[$result[$i]['PageID']][] = $result[$i]['CategoryID'];
				}
			}

			if (count($categoriesToRemove) > 0)
			{
				// Remove by PageID
				foreach ($config as $pageID => $pageConfig)
				{
					$mediaList = new GalleryMediaList($this->module, $pageID);
					$mediaList->RemoveByCategoryIDs($categoriesToRemove[$pageID]);
				}

				foreach ($forResort as $categoryID => $data)
				{
					$query = "DELETE FROM `gallery_category`
						WHERE CategoryID=".intval($categoryID);
					$stmt->Execute($query);

					$query = "UPDATE `gallery_category` SET SortOrder=SortOrder-1
						WHERE PageID=".intval($data[0])." AND SortOrder>".intval($data[1]);
					$stmt->Execute($query);
				}

				if (count($categoriesRemoved) > 1)
					$key = 'categories-are-removed';
				else
					$key = 'category-is-removed';

				$this->AddMessage($key, $this->module, array('CategoryList' => '"'.implode('", "', $categoriesRemoved).'"', 'CategoryCount' => count($categoriesRemoved)));
				$this->AppendMessagesFromObject($mediaList);
			}
		}
	}

	function RemoveByPageID($pageID)
	{
		/*@var stmt Statement */
		$stmt = GetStatement();

		// Remove category images
		$query = "SELECT CategoryImage FROM `gallery_category`
			WHERE PageID=".intval($pageID);
		if ($result = $stmt->FetchList($query))
		{
			for ($i = 0; $i < count($result); $i++)
			{
				if ($result[$i]["CategoryImage"])
				{
					@unlink(GALLERY_IMAGE_DIR."category/".$result[$i]["CategoryImage"]);
				}
			}
		}

		// Remove categories
		$query = "DELETE FROM `gallery_category` WHERE PageID=".intval($pageID);
		$stmt->Execute($query);

		// Remove media
		$query = "SELECT MediaID FROM `gallery_media` WHERE
			PageID=".intval($pageID);
		if ($result = $stmt->FetchList($query))
		{
			$ids = array();
			for ($i = 0; $i < count($result); $i++)
			{
				$ids[] = $result[$i]['MediaID'];
			}

			$mediaList = new GalleryMediaList($this->module, $pageID);
			$mediaList->RemoveByMediaIDs($ids);
		}
	}

	function SetSortOrder($categoryID, $pageID, $diff)
	{
		/*@var stmt Statement */
		$stmt = GetStatement();
		
		$query = "SELECT SortOrder FROM `gallery_category` WHERE CategoryID=".Connection::GetSQLString($categoryID);
		
		$sortOrder = $stmt->FetchField($query);
		$sortOrder = $sortOrder + $diff;

		if ($sortOrder < 1) $sortOrder = 1;
		
		$categoryID = intval($categoryID);
		$pageID = intval($pageID);

		$query = "SELECT COUNT(SortOrder) FROM `gallery_category`
			WHERE PageID=".$pageID;
		
		if ($maxSortOrder = $stmt->FetchField($query))
		{
			if ($sortOrder > $maxSortOrder) $sortOrder = $maxSortOrder;

			$query = "SELECT SortOrder FROM `gallery_category`
				WHERE PageID=".$pageID." AND CategoryID=".$categoryID;
			if ($currentSortOrder = $stmt->FetchField($query))
			{
				if ($sortOrder == $currentSortOrder)
					return true;

				$query = "UPDATE `gallery_category`
					SET SortOrder=".$sortOrder."
					WHERE PageID=".$pageID." AND CategoryID=".$categoryID;
				$stmt->Execute($query);

				if ($sortOrder > $currentSortOrder)
				{
					$query = "UPDATE `gallery_category` SET SortOrder=SortOrder-1
						WHERE SortOrder<=".$sortOrder." AND SortOrder>".$currentSortOrder."
							AND PageID=".$pageID." AND CategoryID<>".$categoryID;
				}
				else if ($sortOrder < $currentSortOrder)
				{
					$query = "UPDATE `gallery_category` SET SortOrder=SortOrder+1
						WHERE SortOrder>=".$sortOrder." AND SortOrder<".$currentSortOrder."
							AND PageID=".$pageID." AND CategoryID<>".$categoryID;
				}
				$stmt->Execute($query);

				return true;
			}
		}

		return false;
	}
}

?>
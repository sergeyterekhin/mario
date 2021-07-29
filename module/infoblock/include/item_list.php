<?php

require_once(dirname(__FILE__)."/../init.php");
es_include("localobjectlist.php");

class InfoblockItemList extends LocalObjectList
{
	var $module;
	var $config;
	var $params;

	function InfoblockItemList($module, $config = array(), $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->config = is_array($config) ? $config : array();
		if (count($this->config) > 0)
		{
			$this->config = $config;
			$this->config['ItemsPerPage'] = abs(intval($this->config['ItemsPerPage']));

			$this->SetSortOrderFields(array(
				"ItemDateDesc" => "i.ItemDate DESC, i.ItemID DESC",
				"ItemDateAsc" => "i.ItemDate ASC, i.ItemID ASC",
				"TitleAsc" => "i.Title ASC",
				"TitleDesc" => "i.Title DESC",
				"Position" => "i.SortOrder ASC",
				"Random" => "RAND()"));

			$this->params = LoadImageConfig('ItemImage', $this->module, $this->config['ItemImage'].",".INFOBLOCK_ITEM_IMAGE);
		}
	}

	function Load($request)
	{
		$this->SetOrderBy(isset($_REQUEST[$this->GetOrderByParam()]) ? $_REQUEST[$this->GetOrderByParam()] : $this->config['ItemsOrderBy']);
		if ($request->GetProperty('FullList'))
			$this->SetItemsOnPage(0);
		else
			$this->SetItemsOnPage($this->config['ItemsPerPage']);

		if (is_null($request->GetProperty('BaseURL')))
			$request->SetProperty('BaseURL', '');

		$where = array();
		$where[] = "i.PageID=".$request->GetIntProperty('PageID');
		if ($request->GetProperty('ShowActive') == 'N' || $request->GetProperty('ShowActive') == 'Y')
			$where[] = "i.Active=".$request->GetPropertyForSQL('ShowActive');
		if ($request->GetIntProperty('ViewCategoryID') > 0)
			$where[] = "i.CategoryID=".$request->GetIntProperty('ViewCategoryID');
		else
			$where[] = "i.CategoryID IS NULL";

		$query = "SELECT i.ItemID, i.CategoryID, i.ItemDate, i.Title, i.Active,
				i.ItemImage, i.ItemImageConfig,  
				i.Description, i.FieldList, i.StaticPath, i.Content, i.SortOrder,
				CONCAT(".$request->GetPropertyForSQL('BaseURL').", '/', i.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).") AS ItemURL,
				c.Title AS CategoryTitle, c.StaticPath AS CategoryStaticPath
			FROM `infoblock_item` i
				LEFT JOIN `infoblock_category` c ON c.CategoryID=i.CategoryID
			".(count($where) > 0 ? "WHERE ".implode(" AND ", $where) : "");

		$this->SetCurrentPage();
		$this->LoadFromSQL($query);
		$this-> _PrepareContentBeforeShow();
		$this->SetItemImages();
	}

	function LoadAnnouncementList($request)
	{
		$this->SetOrderBy($this->config['AnnouncementOrderBy']);
		$limit = intval($this->config['AnnouncementCount']);
		if ($limit < 1) $limit = 3;
		$this->SetItemsOnPage($limit);

		if (is_null($request->GetProperty('BaseURL')))
			$request->SetProperty('BaseURL', '');

		$where = array();
		$where[] = "i.PageID=".$request->GetIntProperty('PageID');
		$where[] = "i.Active='Y'";
		if ($request->GetIntProperty('ViewCategoryID') > 0)
			$where[] = "i.CategoryID=".$request->GetIntProperty('ViewCategoryID');
		else
			$where[] = "i.CategoryID IS NULL";

		$query = "SELECT i.ItemID, i.CategoryID, i.ItemDate, i.Title, i.Active,
				i.ItemImage, i.ItemImageConfig,  
				i.Description, i.FieldList, i.StaticPath, i.Content, i.SortOrder,
				CONCAT(".$request->GetPropertyForSQL('BaseURL').", IF (c.CategoryID IS NULL, '/', CONCAT('/', c.StaticPath, '/')), i.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).") AS ItemURL,
				c.Title AS CategoryTitle
			FROM `infoblock_item` i
				LEFT JOIN `infoblock_category` c ON c.CategoryID=i.CategoryID
			".(count($where) > 0 ? "WHERE ".implode(" AND ", $where) : "");

		$this->SetCurrentPage(1);
		$this->LoadFromSQL($query);
		$this->_PrepareContentBeforeShow();
		$this->SetItemImages();
	}

	function SetItemImages()
	{
		for ($i = 0; $i < count($this->_items); $i++)
		{
			if ($this->_items[$i]['ItemImage'])
			{
				$imageConfig = LoadImageConfigValues("ItemImage", $this->_items[$i]["ItemImageConfig"]);
				$this->_items[$i] = array_merge($this->_items[$i], $imageConfig);
				
				$origW = $this->_items[$i]['ItemImageWidth'];
				$origH = $this->_items[$i]['ItemImageHeight'];

				for ($j = 0; $j < count($this->params); $j++)
				{
					$v = $this->params[$j];

					if($v["Resize"] == 13)
						$this->_items[$i][$v["Name"]."Path"] = InsertCropParams($v["Path"]."item/", 
																		isset($this->_items[$i][$v["Name"]."X1"]) ? intval($this->_items[$i][$v["Name"]."X1"]) : 0, 
																		isset($this->_items[$i][$v["Name"]."Y1"]) ? intval($this->_items[$i][$v["Name"]."Y1"]) : 0,
																		isset($this->_items[$i][$v["Name"]."X2"]) ? intval($this->_items[$i][$v["Name"]."X2"]) : 0,
																		isset($this->_items[$i][$v["Name"]."Y2"]) ? intval($this->_items[$i][$v["Name"]."Y2"]) : 0).$this->_items[$i]["ItemImage"];
					else 	
						$this->_items[$i][$v["Name"]."Path"] = $v["Path"]."item/".$this->_items[$i]['ItemImage'];

					if ($v["Name"] != 'ItemImage')
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
		/*@var stmt Statement */
		$stmt = GetStatement();

		$query = "SELECT ItemID FROM `infoblock_item` WHERE
			CategoryID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
		if ($result = $stmt->FetchList($query))
		{
			$ids = array();
			for ($i = 0; $i < count($result); $i++)
			{
				$ids[] = $result[$i]["ItemID"];
			}
			$this->RemoveByItemIDs($ids);
		}
	}

	function RemoveByItemIDs($ids)
	{
		if (!(is_array($ids) && count($ids) > 0)) return;

		/*@var stmt Statement */
		$stmt = GetStatement();

		$itemsToRemove = array();
		$forResort = array();

		$query = "SELECT ItemImage, ItemID, CategoryID, SortOrder, Title
			FROM `infoblock_item`
			WHERE ItemID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
		if ($result = $stmt->FetchList($query))
		{
			for ($i = 0; $i < count($result); $i++)
			{
				if ($result[$i]['ItemImage'])
					@unlink(INFOBLOCK_IMAGE_DIR."item/".$result[$i]["ItemImage"]);
				$itemsToRemove[] = $result[$i]['Title'];
				$forResort[$result[$i]['ItemID']] = array($result[$i]['CategoryID'], $result[$i]['SortOrder']);
			}
		}

		if (count($itemsToRemove) > 0)
		{
			foreach ($forResort as $itemID => $data)
			{
				$query = "DELETE FROM `infoblock_item`
					WHERE ItemID=".intval($itemID);
				$stmt->Execute($query);

				$query = "UPDATE `infoblock_item` SET SortOrder=SortOrder-1
					WHERE CategoryID".(is_null($data[0]) ? " IS NULL" : "=".intval($data[0]))."
						AND SortOrder>".intval($data[1]);
				$stmt->Execute($query);
			}

			if (count($itemsToRemove) > 1)
				$this->AddMessage('items-are-removed', $this->module, array("ItemCount" => count($itemsToRemove)));
			else
				$this->AddMessage('item-is-removed', $this->module, array("Title" => $itemsToRemove[0]));
		}
	}

	function MoveTo($ids, $pageID, $to)
	{
		if (!(is_array($ids) && count($ids) > 0)) return;

		/*@var stmt Statement */
		$stmt = GetStatement();

		$query = "SELECT ItemID, SortOrder, Title, CategoryID
			FROM `infoblock_item`
			WHERE ItemID IN (".implode(", ", Connection::GetSQLArray($ids)).")
				AND PageID=".intval($pageID)."
			ORDER BY SortOrder DESC";
		if ($result = $stmt->FetchList($query))
		{
			// Check that category where items has to be moved does exist
			$query = "SELECT CategoryID, Title FROM `infoblock_category`
				WHERE PageID=".intval($pageID)." AND CategoryID=".intval($to);
			$row = $stmt->FetchRow($query);
			$to = array();
			if ($row['CategoryID'] > 0)
			{
				$whereTo = "=".intval($row['CategoryID']);
				$to['CategoryID'] = intval($row['CategoryID']);
				$to['Title'] = $row['Title'];
			}
			else
			{
				$whereTo = " IS NULL";
				$to['CategoryID'] = null;
				$to['Title'] = $stmt->FetchField("SELECT Title FROM `page` WHERE PageID=".intval($pageID));
			}

			// Get sort order from which we have to start
			$query = "SELECT MAX(SortOrder) FROM `infoblock_item` WHERE CategoryID".$whereTo."
				AND PageID=".intval($pageID);
			if ($sortOrder = $stmt->FetchField($query))
				$sortOrder = $sortOrder + 1;
			else
				$sortOrder = 1;

			$itemsToMove = array();
			for ($i = 0; $i < count($result); $i++)
			{
				if ($to['CategoryID'] == $result[$i]['CategoryID'])
					continue;

				// Move to new category
				$query = "UPDATE `infoblock_item` SET
						SortOrder=".$sortOrder.",
						CategoryID=".Connection::GetSQLString($to['CategoryID'])."
					WHERE ItemID=".$result[$i]['ItemID'];
				$stmt->Execute($query);
				$sortOrder++;

				if (is_null($result[$i]['CategoryID']))
					$whereFrom = " IS NULL";
				else
					$whereFrom = "=".intval($result[$i]['CategoryID']);

				// Update sort orders in old category
				$query = "UPDATE `infoblock_item` SET SortOrder=SortOrder-1
					WHERE CategoryID".$whereFrom."
						AND PageID=".intval($pageID)."
						AND SortOrder>".$result[$i]['SortOrder'];
				$stmt->Execute($query);

				$itemsToMove[] = $result[$i]['Title'];
			}

			if (count($itemsToMove) > 1)
				$this->AddMessage('items-are-moved', $this->module, array("ItemCount" => count($itemsToMove), "Category" => $to['Title']));
			else if (count($itemsToMove) > 0)
				$this->AddMessage('item-is-moved', $this->module, array("Title" => $itemsToMove[0], "Category" => $to['Title']));
		}
	}

	function SetSortOrder($itemID, $categoryID, $diff)
	{
		/*@var stmt Statement */
		$stmt = GetStatement();
		
		$query = "SELECT SortOrder FROM `infoblock_item` WHERE ItemID=".Connection::GetSQLString($itemID);
		
		$sortOrder = $stmt->FetchField($query);
		$sortOrder = $sortOrder + $diff;

		if ($sortOrder < 1) $sortOrder = 1;

		$itemID = intval($itemID);
		$categoryID = intval($categoryID);

		$pageID = intval($stmt->FetchField("SELECT PageID FROM `infoblock_item` WHERE ItemID=".$itemID));

		$whereCategory = ($categoryID > 0 ? "=".$categoryID : " IS NULL");

		$query = "SELECT COUNT(SortOrder) FROM `infoblock_item`
			WHERE CategoryID".$whereCategory;

		$query .= " AND PageID=".$pageID;

		if ($maxSortOrder = $stmt->FetchField($query))
		{
			if ($sortOrder > $maxSortOrder) $sortOrder = $maxSortOrder;

			$query = "SELECT SortOrder FROM `infoblock_item`
				WHERE CategoryID".$whereCategory." AND ItemID=".$itemID;
			if ($currentSortOrder = $stmt->FetchField($query))
			{
				if ($sortOrder == $currentSortOrder)
					return true;

				$query = "UPDATE `infoblock_item`
					SET SortOrder=".$sortOrder."
					WHERE CategoryID".$whereCategory." AND ItemID=".$itemID;
				$stmt->Execute($query);

				if ($sortOrder > $currentSortOrder)
				{
					$query = "UPDATE `infoblock_item` SET SortOrder=SortOrder-1
						WHERE SortOrder<=".$sortOrder." AND SortOrder>".$currentSortOrder."
							AND CategoryID".$whereCategory." AND ItemID<>".$itemID;
					$query .= " AND PageID=".$pageID;

				}
				else if ($sortOrder < $currentSortOrder)
				{
					$query = "UPDATE `infoblock_item` SET SortOrder=SortOrder+1
						WHERE SortOrder>=".$sortOrder." AND SortOrder<".$currentSortOrder."
							AND CategoryID".$whereCategory." AND ItemID<>".$itemID;
					$query .= " AND PageID=".$pageID;
				}
				$stmt->Execute($query);

				return true;
			}
		}

		return false;
	}

	function GetLastModified($pageID, $categoryID)
	{
		$stmt = GetStatement();
		return $stmt->FetchField("SELECT MAX(ItemDate) FROM `infoblock_item`
			WHERE PageID=".intval($pageID)." AND CategoryID".(is_null($categoryID) ? " IS NULL" : "=".intval($categoryID)));
	}

	function _PrepareContentBeforeShow()
	{
		for ($i = 0; $i < count($this->_items); $i++)
		{
			$this->_items[$i]['Content'] = str_replace("<P_T_R>", PROJECT_PATH, $this->_items[$i]['Content']);
			
			if($this->_items[$i]["FieldList"]){
				$chunks = explode("&", $this->_items[$i]["FieldList"]);
		
				for ($j = 0; $j < count($chunks); $j++)
				{
					$pair = explode("=", $chunks[$j]);
					if (count($pair) == 2)
						$this->_items[$i][$pair[0]] = value_decode($pair[1]);
				}
			}
		}
	}

}

?>
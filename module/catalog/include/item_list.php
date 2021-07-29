<?php

require_once(dirname(__FILE__)."/../init.php");
require_once(dirname(__FILE__)."/media_list.php");
es_include("localobjectlist.php");

class CatalogItemList extends LocalObjectList
{
	var $module;
	var $params;
	var $config;

	function CatalogItemList($module, $config = array(), $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->config = is_array($config) ? $config : array();

		$this->SetSortOrderFields(array(
			"c_sortorder_asc" => "i2c.SortOrder ASC",
			"c_sortorder_desc" => "i2c.SortOrder DESC",
			"i_sortorder_asc" => "i.SortOrder ASC",
			"i_sortorder_desc" => "i.SortOrder DESC",
			"itemid_asc" => "i.ItemID ASC",
			"itemid_desc" => "i.ItemID DESC",
			"sku_asc" => "i.SKU ASC",
			"sku_desc" => "i.SKU DESC",
			"price_asc" => "i.price ASC",
			"price_desc" => "i.price DESC",
			"title_asc" => "i.Title ASC",
			"title_desc" => "i.Title DESC",
			"staticpath_asc" => "i.StaticPath ASC",
			"staticpath_desc" => "i.StaticPath DESC",
			"itemdate_asc" => "i.ItemDate ASC",
			"itemdate_desc" => "i.ItemDate DESC",
			"created_asc" => "i.Created ASC",
			"created_desc" => "i.Created DESC",
			"updated_asc" => "i.Updated ASC",
			"updated_desc" => "i.Updated DESC",
			"relevance_desc" => "RelevanceBool DESC, Relevance DESC"));

		if (count($this->config) > 0)
		{
			$this->params = array('Item' => array(), 'Featured' => array());
			$this->params['Item'] = LoadImageConfig('ItemImage', $this->module, $this->config['ItemImage'].",".CATALOG_ITEM_IMAGE);
			$this->params['Featured'] = LoadImageConfig('FeaturedImage', $this->module, $this->config['FeaturedImage'].",".CATALOG_FEATURED_IMAGE);
			$this->SetOrderBy($this->config['ItemsOrderBy']);
		}
	}

	// need to check
	function LoadItemListByCategory($request, $fullList = false)
	{
		if ($fullList)
			$this->SetItemsOnPage(0);
		else
			$this->SetItemsOnPage(abs(intval($this->config["ItemsPerPage"])));

		$where = array();
		if ($request->GetProperty('ShowActive') == 'N' || $request->GetProperty('ShowActive') == 'Y')
			$where[] = "i.Active=".$request->GetPropertyForSQL('ShowActive');
		if ($request->GetProperty('Year'))
			$where[] = "YEAR(i.ItemDate)=".$request->GetPropertyForSQL('Year');

		if (!($orderBy = $request->GetProperty('OrderBy')))
			$orderBy = $this->config["ItemsOrderBy"];

		$this->SetOrderBy($orderBy, $this->module);

		if ($request->GetIntProperty("ViewCategoryID") > 0)
		{
			$where[] = "c.PageID=".$request->GetIntProperty('PageID');
			if (substr($orderBy, 0, 9) == 'sortorder')
				$this->SetOrderBy('c_'.$orderBy, $this->module);
			$query = "SELECT i.ItemID, i.Title, i.SKU, i.Price, i.Description,
			        i.ItemImage, i.ItemImageConfig, i.FeaturedImage, i.FeaturedImageConfig, 
					i.MetaTitle, i.MetaKeywords, i.MetaDescription,
					i.StaticPath, i.ItemDate, i.Created, i.Updated, i.Active, i.Featured,
					CONCAT(".$request->GetPropertyForSQL("BaseURL").", ".Connection::GetSQLString("/".$this->config["ItemURLPrefix"]."/").", i.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).") AS ItemURL,
					i2c.Item2CategoryID, i2c.SortOrder, c.CategoryID,
					c.Title AS CategoryTitle, c.Description AS CategoryDescription
				FROM `catalog_item` AS i
					JOIN `catalog_item2category` AS i2c ON i2c.ItemID=i.ItemID
					JOIN `catalog_category` AS c ON c.CategoryID=i2c.CategoryID
						AND c.CategoryID=".$request->GetIntProperty("ViewCategoryID")."
				".(count($where) > 0 ? "WHERE ".implode(" AND ", $where) : "");
		}
		else
		{
			$where[] = "i.PageID=".$request->GetIntProperty('PageID');
			if (substr($orderBy, 0, 9) == 'sortorder')
				$this->SetOrderBy('i_'.$orderBy, $this->module);
			$query = "SELECT i.ItemID, i.Title, i.SKU, i.Price, i.Description,
				    i.ItemImage, i.ItemImageConfig, i.FeaturedImage, i.FeaturedImageConfig,
					i.MetaTitle, i.MetaKeywords, i.MetaDescription,
					i.StaticPath, i.ItemDate, i.Created, i.Updated, i.Active, i.Featured,
					CONCAT(".$request->GetPropertyForSQL("BaseURL").", ".Connection::GetSQLString("/".$this->config["ItemURLPrefix"]."/").", i.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).") AS ItemURL
				FROM `catalog_item` AS i
				".(count($where) > 0 ? "WHERE ".implode(" AND ", $where) : "");
		}
		$this->SetCurrentPage();
		$this->LoadFromSQL($query);

		$this->_PrepareContentBeforeShow();
	}

	//Need to improve and fix
	function LoadItemListBySearchQuery($request, $fullList = false)
	{
		$searchQuery = $request->GetProperty("Search");
		$searchQuery = strip_tags(trim($searchQuery));
		$request->SetProperty("Search", $searchQuery);

		if ($fullList)
			$this->SetItemsOnPage(0);
		else
			$this->SetItemsOnPage(abs(intval($this->config["ItemsPerPage"])));


		$this->SetOrderBy("relevance_desc", $this->module);
		$where = array();
		$where[] = "i.Active = 'Y'";
		$where[] = "i.PageID=".$request->GetIntProperty('PageID');

		$query = "SELECT i.ItemID, i.Title, i.SKU, i.Description,
				MATCH(i.Title, i.Description, i.SKU, i.Price) AGAINST (".$request->GetPropertyForSQL('Search').") as Relevance,
				MATCH(i.Title, i.Description, i.SKU, i.Price) AGAINST (".$request->GetPropertyForSQL('Search')." IN BOOLEAN MODE) as RelevanceBool,
			    i.ItemImage, i.ItemImageConfig, i.FeaturedImage, i.FeaturedImageConfig, 
				i.MetaTitle, i.MetaKeywords, i.MetaDescription,
				i.StaticPath, i.ItemDate, i.Created, i.Updated, i.Active, i.Price,
				CONCAT(".$request->GetPropertyForSQL("BaseURL").", ".Connection::GetSQLString("/".$this->config["ItemURLPrefix"]."/").", i.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).") AS ItemURL
			FROM `catalog_item` AS i
			WHERE ".implode(" AND ", $where)."
			HAVING Relevance > 1 OR RelevanceBool = 1";
		
		$this->SetCurrentPage();
		$this->LoadFromSQL($query);

		$this->_PrepareContentBeforeShow();
	}

	function LoadFeaturedItemList($request, $groupByCategory = false)
	{
		$this->SetItemsOnPage(0);

		$where = array();
		$where[] = "c.PageID=".$request->GetIntProperty('PageID');
		$where[] = "i.Active='Y'";
		$where[] = "i.Featured='Y'";
		$where[] = "i.FeaturedImage<>''";

		$query = "SELECT i.ItemID, i.Title, i.SKU, i.Description,
		        i.ItemImage, i.ItemImageConfig, i.FeaturedImage, i.FeaturedImageConfig, 
				i.MetaTitle, i.MetaKeywords, i.MetaDescription,
				i.StaticPath, i.ItemDate, i.Created, i.Updated, i.Active, i.Price,
				CONCAT(".$request->GetPropertyForSQL("BaseURL").", ".Connection::GetSQLString("/".$this->config["ItemURLPrefix"]."/").", i.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).") AS ItemURL,
				i2c.Item2CategoryID, i2c.SortOrder, c.CategoryID,
				c.Title AS CategoryTitle, c.Description AS CategoryDescription
			FROM `catalog_item` AS i
				JOIN `catalog_item2category` AS i2c ON i2c.ItemID=i.ItemID
				JOIN `catalog_category` AS c ON c.CategoryID=i2c.CategoryID AND c.Path2Root='#'
			".(count($where) > 0 ? "WHERE ".implode(" AND ", $where) : "") . (!$groupByCategory ? " GROUP BY i.ItemID" : "");

		if ($groupByCategory)
		{
			$stmt = GetStatement();

			if ($result = $stmt->FetchList($query.$this->GetOrderBySQLString()))
			{
				$prevCategoryID = null;

				$itemList = array();

				$j = -1;
				for ($i = 0; $i < count($result); $i++)
				{
					if ($result[$i]["CategoryID"] != $prevCategoryID)
					{
						$j++;
						$prevCategoryID = $result[$i]["CategoryID"];
						$itemList[$j] = array("CategoryID" => $result[$i]["CategoryID"],
							"CategoryTitle" => $result[$i]["CategoryTitle"],
							"CategoryDescription" => $result[$i]["CategoryDescription"]);
					}

					//Descriptions
					$chunks = explode("&", $result[$i]["Description"]);
					$result[$i]["Description"] = "";
					if(count($chunks) > 0)
					{
						for ($k = 0; $k < count($chunks); $k++)
						{
							$pair = explode("=", $chunks[$k]);
							if (count($pair) == 2)
								$this->_items[$i][$pair[0]] = value_decode($pair[1]);
						}
					}
					//Images
					foreach ($this->params as $k => $v)
					{
						if ($result[$i][$k.'Image'])
						{
							$imageConfig = LoadImageConfigValues($k.'Image', $result[$i]["ItemImageConfig"]);
							$result[$i] = array_merge($result[$i], $imageConfig);
							
							$origW = $result[$i][$k.'ImageWidth'];
							$origH = $result[$i][$k.'ImageHeight'];

							for ($l = 0; $l < count($v); $l++)
							{
								if($v[$l]["Resize"] == 13)
									$result[$i][$v[$l]["Name"]."Path"] = InsertCropParams($v[$l]["Path"]."item/", 
																					isset($result[$i][$v[$l]["Name"]."X1"]) ? intval($result[$i][$v[$l]["Name"]."X1"]) : 0, 
																					isset($result[$i][$v[$l]["Name"]."Y1"]) ? intval($result[$i][$v[$l]["Name"]."Y1"]) : 0,
																					isset($result[$i][$v[$l]["Name"]."X2"]) ? intval($result[$i][$v[$l]["Name"]."X2"]) : 0,
																					isset($result[$i][$v[$l]["Name"]."Y2"]) ? intval($result[$i][$v[$l]["Name"]."Y2"]) : 0).$result[$i][$k."Image"];
								else 	
									$result[$i][$v[$l]["Name"]."Path"] = $v[$l]["Path"]."item/".$result[$i][$k.'Image'];
								
								list($dstW, $dstH) = GetRealImageSize($v[$l]["Resize"], $origW, $origH, $v[$l]["Width"], $v[$l]["Height"]);
								$result[$i][$v[$l]["Name"]."Width"] = $dstW;
								$result[$i][$v[$l]["Name"]."Height"] = $dstH;
							}
						}
					}

					$itemList[$j]["ItemList"][] = $result[$i];
				}
				$this->LoadFromArray($itemList);
			}
		}
		else
		{
			$this->LoadFromSQL($query);
			$this->_PrepareContentBeforeShow();
		}
	}

	function _PrepareContentBeforeShow()
	{
		if (count($this->_items) == 0) return;

		for ($i = 0; $i < count($this->_items); $i++)
		{
			//Descriptions
			$chunks = explode("&", $this->_items[$i]["Description"]);
			$this->_items[$i]["Description"] = "";

			if(count($chunks) > 0)
			{
				for ($k = 0; $k < count($chunks); $k++)
				{
					$pair = explode("=", $chunks[$k]);
					if (count($pair) == 2)
						$this->_items[$i][$pair[0]] = value_decode($pair[1]);
				}
			}

			foreach ($this->params as $k => $v)
			{
				if ($this->_items[$i][$k.'Image'])
				{
					$imageConfig = LoadImageConfigValues($k.'Image', $this->_items[$i][$k."ImageConfig"]);
					$this->_items[$i] = array_merge($this->_items[$i], $imageConfig);
					
					for ($j = 0; $j < count($v); $j++)
					{
					    if($v[$j]["Resize"] == 13)
							$this->_items[$i][$v[$j]["Name"]."Path"] = InsertCropParams($v[$j]["Path"]."item/", 
																			isset($this->_items[$i][$v[$j]["Name"]."X1"]) ? intval($this->_items[$i][$v[$j]["Name"]."X1"]) : 0, 
																			isset($this->_items[$i][$v[$j]["Name"]."Y1"]) ? intval($this->_items[$i][$v[$j]["Name"]."Y1"]) : 0,
																			isset($this->_items[$i][$v[$j]["Name"]."X2"]) ? intval($this->_items[$i][$v[$j]["Name"]."X2"]) : 0,
																			isset($this->_items[$i][$v[$j]["Name"]."Y2"]) ? intval($this->_items[$i][$v[$j]["Name"]."Y2"]) : 0).$this->_items[$i][$k."Image"];
						else 	
							$this->_items[$i][$v[$j]["Name"]."Path"] = $v[$j]["Path"]."item/".$this->_items[$i][$k.'Image'];
					}
				}
				for ($j = 0; $j < count($v); $j++)
				{
					$this->_items[$i][$v[$j]["Name"]."Width"] = $v[$j]["Width"];
					$this->_items[$i][$v[$j]["Name"]."Height"] = $v[$j]["Height"];
				}
			}
		}
	}

	function GetYearList($request)
	{
		$result = array(0 => array("Value" => "", "Title" => GetTranslation("all-years", $this->module)));

		$categoryIDs = $request->GetProperty("ViewCategoryIDs");
		if (is_array($categoryIDs) && count($categoryIDs) > 0)
		{
			$categorySQL = " IN (".implode(",", Connection::GetSQLArray($categoryIDs)).")";
		}
		else
		{
			$categorySQL = "=".$request->GetIntProperty("ViewCategoryID");
		}

		$stmt = GetStatement();
		$query = "SELECT DISTINCT YEAR(i.ItemDate) AS Value,
				YEAR(i.ItemDate) AS Title,
				IF(YEAR(i.ItemDate)=".$request->GetIntProperty("Year").", 1, 0) AS Selected
			FROM `catalog_item` AS i
				JOIN `catalog_item2category` AS i2c ON i2c.ItemID=i.ItemID
			WHERE i2c.CategoryID".$categorySQL."
			ORDER BY i.ItemDate DESC";
		$result2 = $stmt->FetchList($query);

		for ($i = 0; $i < count($result2); $i++)
		{
			$result2[$i]["Title"] = $result2[$i]["Title"]." ".GetTranslation("year", $this->module);
		}

		return array_merge($result, $result2);
	}

	function Remove($ids)
	{
		if (is_array($ids) && count($ids) > 0)
		{
			$itemsToRemove = array();
			$itemsRemoved = array();

			/*@var stmt Statement */
			$stmt = GetStatement();

			$query = "SELECT ItemID, Title, ItemImage, FeaturedImage
				FROM `catalog_item`
				WHERE ItemID IN(".implode(", ", Connection::GetSQLArray($ids)).")";
			if ($result = $stmt->FetchList($query))
			{
				for ($i = 0; $i < count($result); $i++)
				{
					foreach ($this->params as $k => $v)
					{
						if ($result[$i][$k.'Image'])
						{
							for ($j = 0; $j < count($v); $j++)
							{
								@unlink(CATALOG_IMAGE_DIR.'item/'.$result[$i][$k."Image"]);
							}
						}
					}

					$itemsToRemove[] = $result[$i]['ItemID'];
					$itemsRemoved[] = $result[$i]['Title'];
				}
			}

			if (count($itemsToRemove) > 0)
			{
				$mediaList = new CatalogMediaList($this->module);
				$mediaList->RemoveByItemIDs($itemsToRemove);

				$query = "SELECT ItemID, CategoryID, SortOrder FROM `catalog_item2category`
					WHERE ItemID IN (".implode(", ", Connection::GetSQLArray($itemsToRemove)).")";
				if ($result = $stmt->FetchList($query))
				{
					$query = "DELETE FROM `catalog_item2category`
						WHERE ItemID IN (".implode(", ", Connection::GetSQLArray($itemsToRemove)).")";
					$stmt->Execute($query);

					for ($i = 0; $i < count($result); $i++)
					{
						$query = "UPDATE `catalog_item2category` SET SortOrder=SortOrder-1
							WHERE SortOrder>".intval($result[$i]['SortOrder'])."
								AND CategoryID=".intval($result[$i]['CategoryID']);
						$stmt->Execute($query);
					}
				}

				$query = "DELETE FROM `catalog_item`
					WHERE ItemID IN (".implode(", ", Connection::GetSQLArray($itemsToRemove)).")";
				$stmt->Execute($query);

				if (count($itemsRemoved) > 1)
					$key = "items-are-removed";
				else
					$key = "item-is-removed";

				$this->AddMessage($key, $this->module, array("ItemList" => "\"".implode("\", \"", $itemsRemoved)."\"", "ItemCount" => count($itemsRemoved)));
				$this->AppendMessagesFromObject($mediaList);
			}
		}
	}

	function SetSortOrder($itemID, $categoryID, $diff)
	{
		/*@var stmt Statement */
		$stmt = GetStatement();
		$query = "SELECT SortOrder FROM `catalog_item2category` WHERE ItemID=".Connection::GetSQLString($itemID)." 
																	AND CategoryID=".Connection::GetSQLString($categoryID);
		$sortOrder = $stmt->FetchField($query);
		$sortOrder = $sortOrder + $diff;
		if ($sortOrder < 1) $sortOrder = 1;

		$itemID = intval($itemID);
		$categoryID = intval($categoryID);
		
		$query = "SELECT COUNT(SortOrder) FROM `catalog_item2category`
			WHERE CategoryID=".$categoryID;
		if ($maxSortOrder = $stmt->FetchField($query))
		{
			if ($sortOrder > $maxSortOrder) $sortOrder = $maxSortOrder;

			$query = "SELECT SortOrder FROM `catalog_item2category`
				WHERE CategoryID=".$categoryID." AND ItemID=".$itemID;
			if ($currentSortOrder = $stmt->FetchField($query))
			{
				if ($sortOrder == $currentSortOrder)
					return true;

				$query = "UPDATE `catalog_item2category`
					SET SortOrder=".$sortOrder."
					WHERE CategoryID=".$categoryID." AND ItemID=".$itemID;
				$stmt->Execute($query);

				if ($sortOrder > $currentSortOrder)
				{
					$query = "UPDATE `catalog_item2category` SET SortOrder=SortOrder-1
						WHERE SortOrder<=".$sortOrder." AND SortOrder>".$currentSortOrder."
							AND CategoryID=".$categoryID." AND ItemID<>".$itemID;
				}
				else if ($sortOrder < $currentSortOrder)
				{
					$query = "UPDATE `catalog_item2category` SET SortOrder=SortOrder+1
						WHERE SortOrder>=".$sortOrder." AND SortOrder<".$currentSortOrder."
							AND CategoryID=".$categoryID." AND ItemID<>".$itemID;
				}
				$stmt->Execute($query);

				return true;
			}
		}

		return false;
	}
}

?>
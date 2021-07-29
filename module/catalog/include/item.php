<?php

require_once(dirname(__FILE__)."/../init.php");
require_once(dirname(__FILE__)."/category.php");
es_include("localobject.php");

class CatalogItem extends LocalObject
{
	var $_acceptMimeTypes = array(
		'image/png',
		'image/x-png',
		'image/gif',
		'image/jpeg',
		'image/pjpeg'
	);
	var $module;
	var $params;
	var $pageID;
	var $config;

	function CatalogItem($module, $pageID, $config = array(), $data = array())
	{
		parent::LocalObject($data);

		$this->module = $module;
		$this->pageID = intval($pageID);
		$this->config = is_array($config) ? $config : array();

		if (count($this->config) > 0)
		{
		    $this->params = LoadImageConfig('CategoryImage', $this->module, $this->config['CategoryImage']);

			$this->params = array('Item' => array(), 'Featured' => array());
			$this->params['Item'] = LoadImageConfig('ItemImage', $this->module, $this->config['ItemImage'].",".CATALOG_ITEM_IMAGE);
    		$this->params['Featured'] = LoadImageConfig('FeaturedImage', $this->module, $this->config['FeaturedImage'].",".CATALOG_FEATURED_IMAGE);
		}
	}

	function LoadByID($id, $request = null, $replaceURLs = false)
	{
		$query = "SELECT i.ItemID, i.Title, i.SKU, i.Description,
				i.ItemImage, i.ItemImageConfig, i.FeaturedImage, i.FeaturedImageConfig, 
				i.Content, i.TitleH1, i.MetaTitle, i.MetaKeywords,
				i.MetaDescription, i.StaticPath, i.ItemDate, i.Created,i.Price,
				i.Updated, i.Active, i.Featured, YEAR(i.ItemDate) AS ItemYear
			FROM `catalog_item` AS i
			WHERE i.ItemID=".Connection::GetSQLString($id);
		$this->LoadFromSQL($query);

		foreach ($this->params as $k => $v)
		{
			for ($i = 0; $i < count($v); $i++)
			{
				$this->SetProperty($v[$i]["Name"]."Width", $v[$i]["Width"]);
				$this->SetProperty($v[$i]["Name"]."Height", $v[$i]["Height"]);
			}
		}

		if ($this->GetProperty("ItemID"))
		{
			$this->_PrepareContentBeforeShow($replaceURLs);
			return true;
		}
		else
		{
			return false;
		}
	}

	function LoadByStaticPath($request)
	{
		$query = "SELECT i.ItemID, i.Title, i.SKU, i.Description,
				i.ItemImage, i.ItemImageConfig, i.FeaturedImage, i.FeaturedImageConfig, i.Content, i.TitleH1, i.MetaTitle, i.MetaKeywords,
				i.MetaDescription, i.StaticPath, i.ItemDate, i.Created, i.Price,
				i.Updated, i.Active, i.Featured, YEAR(i.ItemDate) AS ItemYear
			FROM `catalog_item` AS i
				JOIN `catalog_item2category` AS i2c ON i2c.ItemID=i.ItemID
				JOIN `catalog_category` AS c ON c.CategoryID=i2c.CategoryID AND c.PageID=".$request->GetIntProperty("PageID")."
			WHERE i.StaticPath=".$request->GetPropertyForSQL("ItemStaticPath")."
			GROUP BY i.ItemID";
		$this->LoadFromSQL($query);

		foreach ($this->params as $k => $v)
		{
			for ($i = 0; $i < count($v); $i++)
			{
				$this->SetProperty($v[$i]["Name"]."Width", $v[$i]["Width"]);
				$this->SetProperty($v[$i]["Name"]."Height", $v[$i]["Height"]);
			}
		}

		if ($this->GetProperty("ItemID"))
		{
			$this->_PrepareContentBeforeShow(true);
			return true;
		}
		else
		{
			return false;
		}
	}

	function LoadRandomFeatured($request)
	{
		$query = "SELECT i.ItemID, i.Title, i.SKU, i.Description,
				i.ItemImage, i.ItemImageConfig,	i.FeaturedImage, i.FeaturedImageConfig, 
				i.Content, i.TitleH1, i.MetaTitle, i.MetaKeywords,
				i.MetaDescription, i.StaticPath, i.ItemDate, i.Created,
				i.Updated, i.Active, i.Featured, YEAR(i.ItemDate) AS ItemYear,  i.Price,
				CONCAT(".$request->GetPropertyForSQL("BaseURL").", ".Connection::GetSQLString("/".$this->config["ItemURLPrefix"]."/").", i.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).") AS ItemURL
			FROM `catalog_item` AS i
				JOIN `catalog_item2category` AS i2c ON i2c.ItemID=i.ItemID
				JOIN `catalog_category` AS c ON c.CategoryID=i2c.CategoryID AND c.PageID=".$request->GetIntProperty("PageID")."
			WHERE i.Active='Y' AND i.Featured='Y' AND i.FeaturedImage<>''
			GROUP BY i.ItemID
			ORDER BY RAND()
			LIMIT 1";
		$this->LoadFromSQL($query);

		foreach ($this->params as $k => $v)
		{
			for ($i = 0; $i < count($v); $i++)
			{
				$this->SetProperty($v[$i]["Name"]."Width", $v[$i]["Width"]);
				$this->SetProperty($v[$i]["Name"]."Height", $v[$i]["Height"]);
			}
		}

		if ($this->GetProperty("ItemID"))
		{
			$this->_PrepareContentBeforeShow(true);
			return true;
		}
		else
		{
			return false;
		}
	}

	function GetItemCategoryList($id, $request = null)
	{
		$stmt = GetStatement();
		$query = "SELECT c.CategoryID, c.Path2Root, c.Title, i2c.SortOrder
			FROM `catalog_category` c
			JOIN `catalog_item2category` i2c ON i2c.CategoryID=c.CategoryID
			WHERE i2c.ItemID=".intval($id)."
			ORDER BY c.Path2Root";

		if (!is_null($request))
		{
			$result = $stmt->FetchList($query);
			$category = new CatalogCategory($this->module, $request->GetIntProperty("PageID"), $this->config, $request->GetPropertyForSQL("CategoryURL"));
			for ($i = 0; $i < count($result); $i++)
			{
				$category->LoadByID($result[$i]["CategoryID"]);
				$path = $category->GetPathAsArray();
				$categoryPath = $request->GetProperty("CategoryURL");
				if (is_array($path) && count($path) > 0)
				{
					for ($j = 0; $j < count($path); $j++)
					{
						$categoryPath .= "/".$path[$j]["StaticPath"];
					}
				}
				$result[$i]["CategoryURL"] = $categoryPath.'/';
			}
		}
		else
		{
			$result = $stmt->FetchIndexedList($query, "CategoryID");
		}

		return $result;
	}

	function _PrepareContentBeforeShow($replaceURLs)
	{
		$this->SetProperty("Content", str_replace("<P_T_R>", PROJECT_PATH, $this->GetProperty("Content")));

		//Descriptions
		$chunks = explode("&", $this->GetProperty("Description"));

		for ($i = 0; $i < count($chunks); $i++)
		{
			$pair = explode("=", $chunks[$i]);
			if (count($pair) == 2)
				$this->SetProperty($pair[0], value_decode($pair[1]));
		}

		//Images
		$this->_PrepareImages("Item");
		$this->_PrepareImages("Featured");
	}

	function _PrepareImages($key)
	{
	    if($key != 'Item' && $key != 'Featured')
	    {
	        return false;
	    }

	    if ($this->GetProperty($key . "Image"))
		{
			$imageConfig = LoadImageConfigValues($key."Image", $this->GetProperty($key."ImageConfig"));
			$this->AppendFromArray($imageConfig);
			$origW = $this->GetProperty($key . "ImageWidth");
			$origH = $this->GetProperty($key . "ImageHeight");

			for ($i = 0; $i < count($this->params[$key]); $i++)
			{
				$v = $this->params[$key][$i];

				if($v["Resize"] == 13)
					$this->SetProperty($v["Name"]."Path", InsertCropParams($v["Path"]."item/", 
																			$this->GetIntProperty($v["Name"]."X1"), 
																			$this->GetIntProperty($v["Name"]."Y1"), 
																			$this->GetIntProperty($v["Name"]."X2"), 
																			$this->GetIntProperty($v["Name"]."Y2")).$this->GetProperty($key."Image"));
				else
					$this->SetProperty($v["Name"]."Path", $v["Path"]."item/".$this->GetProperty($key . "Image"));

				if ($v["Name"] != $key . 'Image')
				{
					list($dstW, $dstH) = GetRealImageSize($v["Resize"], $origW, $origH, $v["Width"], $v["Height"]);
					$this->SetProperty($v["Name"]."Width", $dstW);
					$this->SetProperty($v["Name"]."Height", $dstH);
				}
			}
		}
	}
	
	function GetImageParams($key)
	{
		$paramList = array();
		for ($i = 0; $i < count($this->params[$key]); $i++)
		{
			$paramList[] = array(
				"Name" => $this->params[$key][$i]['Name'],
				"SourceName" => $this->params[$key][$i]['SourceName'],
				"Width" => $this->params[$key][$i]['Width'],
				"Height" => $this->params[$key][$i]['Height'],
				"Resize" => $this->params[$key][$i]['Resize'],
				"X1" => $this->GetIntProperty($key."Image".$this->params[$key][$i]['SourceName']."X1"),
				"Y1" => $this->GetIntProperty($key."Image".$this->params[$key][$i]['SourceName']."Y1"),
				"X2" => $this->GetIntProperty($key."Image".$this->params[$key][$i]['SourceName']."X2"),
				"Y2" => $this->GetIntProperty($key."Image".$this->params[$key][$i]['SourceName']."Y2")
			);
		}
		return $paramList;
	}

	function Save()
	{
	    $result1 = $this->SaveItemImage($this->GetProperty("SavedItemImage"), "Item");
	    $result2 = $this->SaveItemImage($this->GetProperty("SavedFeaturedImage"), "Featured");
	    $result3 = $this->Validate();

		if (!$result1 || !$result2 || !$result3)
		{
		    $this->_PrepareContentBeforeShow();
			return false;
		}

		/*@var stmt Statement */
		$stmt = GetStatement();

		$content = PrepareContentBeforeSave($this->GetProperty("Content"));

		if ($this->GetIntProperty("ItemID") > 0)
		{
			$query = "UPDATE `catalog_item` SET
				Title=".$this->GetPropertyForSQL("Title").",
				SKU=".$this->GetPropertyForSQL("SKU").",
				Description=".$this->GetPropertyForSQL("Description").",
				ItemImage=".$this->GetPropertyForSQL("ItemImage").",
				ItemImageConfig=".Connection::GetSQLString(json_encode($this->GetProperty("ItemImageConfig"))).",
				FeaturedImage=".$this->GetPropertyForSQL("FeaturedImage").",
				FeaturedImageConfig=".Connection::GetSQLString(json_encode($this->GetProperty("FeaturedImageConfig"))).",
				Content=".Connection::GetSQLString($content).",
				TitleH1=".$this->GetPropertyForSQL("TitleH1").",
				MetaTitle=".$this->GetPropertyForSQL("MetaTitle").",
				MetaKeywords=".$this->GetPropertyForSQL("MetaKeywords").",
				MetaDescription=".$this->GetPropertyForSQL("MetaDescription").",
				StaticPath=".$this->GetPropertyForSQL("StaticPath").",
				ItemDate=".$this->GetPropertyForSQL("ItemDate").",
				Updated=NOW(),
				Featured=".$this->GetPropertyForSQL("Featured").",
				Active=".$this->GetPropertyForSQL("Active").",
				Price=".$this->GetPropertyForSQL("Price")."
				WHERE ItemID=".$this->GetIntProperty("ItemID");
		}
		else
		{
			$query = "INSERT INTO `catalog_item` (PageID, Title, SKU, Description, ItemImage,
			    ItemImageConfig, FeaturedImage, FeaturedImageConfig, 
				Content, TitleH1, MetaTitle, MetaKeywords, MetaDescription,
				StaticPath, ItemDate, Created, Featured, Active, Price)
				VALUES (
				".$this->GetIntProperty("PageID").",
				".$this->GetPropertyForSQL("Title").",
				".$this->GetPropertyForSQL("SKU").",
				".$this->GetPropertyForSQL("Description").",
				".$this->GetPropertyForSQL("ItemImage").",
				".Connection::GetSQLString(json_encode($this->GetProperty("ItemImageConfig"))).",
				".$this->GetPropertyForSQL("FeaturedImage").",
				".Connection::GetSQLString(json_encode($this->GetProperty("FeaturedImageConfig"))).",
				".Connection::GetSQLString($content).",
				".$this->GetPropertyForSQL("TitleH1").",
				".$this->GetPropertyForSQL("MetaTitle").",
				".$this->GetPropertyForSQL("MetaKeywords").",
				".$this->GetPropertyForSQL("MetaDescription").",
				".$this->GetPropertyForSQL("StaticPath").",
				".$this->GetPropertyForSQL("ItemDate").",
				NOW(),
				".$this->GetPropertyForSQL("Featured").",
				".$this->GetPropertyForSQL("Active").",
				".$this->GetPropertyForSQL("Price").")";
		}

		if ($stmt->Execute($query))
		{
			if (!$this->GetIntProperty("ItemID") > 0)
				$this->SetProperty("ItemID", $stmt->GetLastInsertID());

			$this->SaveItem2Category();

			return true;
		}
		else
		{
			$this->AddError("sql-error");
			$this->_PrepareContentBeforeShow();
			return false;
		}
	}
	
	function SaveItem2Category()
	{
		//collect category ids which changed their item count
		$updatedCategoryIDs = array();
		
		$stmt = GetStatement();
		$categoryIDs = $realCategoryIDs = $this->GetProperty("CategoryIDs");

			$query = "SELECT DISTINCT Path2Root FROM `catalog_category`
				WHERE CategoryID IN (".implode(",", Connection::GetSQLArray($categoryIDs)).")
					AND PageID=".$this->GetIntProperty("PageID");
			$parentCategories = $stmt->FetchList($query);
			if ($parentCategories)
			{
				// We also have to add item to all parent categories
				for ($i = 0; $i < count($parentCategories); $i++)
				{
					$ids = explode("#", $parentCategories[$i]["Path2Root"]);
					if (count($ids) > 2)
					{
						$ids = array_slice($ids, 1, count($ids) - 2);
						$categoryIDs = array_merge($categoryIDs, $ids);
					}
				}
			}

			// Define sort order for item in each category it is related to
			// Delete item from categories it is not related to anymore
			$query = "SELECT ItemID, CategoryID, SortOrder FROM `catalog_item2category`
				WHERE CategoryID NOT IN (".implode(",", Connection::GetSQLArray($categoryIDs)).")
				AND ItemID=".$this->GetIntProperty("ItemID");
			if ($result = $stmt->FetchList($query))
			{
			$query = "SELECT DISTINCT(CategoryID) FROM `catalog_item2category` WHERE 
							CategoryID NOT IN (".implode(",", Connection::GetSQLArray($categoryIDs)).")
							AND ItemID=".$this->GetIntProperty("ItemID");
			$updatedCategoryIDs = array_keys($stmt->FetchIndexedList($query));
			
				$query = "DELETE FROM `catalog_item2category` WHERE
					CategoryID NOT IN (".implode(",", Connection::GetSQLArray($categoryIDs)).")
					AND ItemID=".$this->GetIntProperty("ItemID");
				$stmt->Execute($query);

				for ($i = 0; $i < count($result); $i++)
				{
					$query = "UPDATE `catalog_item2category` SET SortOrder=SortOrder-1
						WHERE SortOrder>".intval($result[$i]['SortOrder'])."
							AND CategoryID=".intval($result[$i]['CategoryID']);
					$stmt->Execute($query);
				}
			}

			$query = "SELECT CategoryID, MAX(SortOrder) AS SortOrder
				FROM `catalog_item2category` WHERE
				CategoryID IN (".implode(",", Connection::GetSQLArray($categoryIDs)).")
				GROUP BY CategoryID";
			$notEmptyCategories = $stmt->FetchIndexedList($query, "CategoryID");

			$existInCategories = $this->GetItemCategoryList($this->GetIntProperty("ItemID"));

			for ($i = 0; $i < count($categoryIDs); $i++)
			{
				$sortOrder = null;
				if (is_array($notEmptyCategories) && array_key_exists($categoryIDs[$i], $notEmptyCategories))
				{
					if (is_array($existInCategories) && !array_key_exists($categoryIDs[$i], $existInCategories))
						$sortOrder = $notEmptyCategories[$categoryIDs[$i]]["SortOrder"] + 1;
				}
				else
				{
					$sortOrder = 1;
				}

				if (!is_null($sortOrder))
				{
				$query = "INSERT INTO `catalog_item2category` (ItemID, CategoryID, SortOrder, `Real`)
					VALUES (".$this->GetIntProperty("ItemID").", ".
					intval($categoryIDs[$i]).", ".
					intval($sortOrder).", ".
					Connection::GetSQLString((in_array($categoryIDs[$i], $realCategoryIDs) ? "Y" : "N")).")";
					$stmt->Execute($query);
				$updatedCategoryIDs[] = $categoryIDs[$i];
				}
			}

		return array_unique($updatedCategoryIDs);
	}

	function Validate()
	{
		if ($this->GetProperty("Active") != "Y")
			$this->SetProperty("Active", "N");

		if ($this->GetProperty("Featured") != "Y")
			$this->SetProperty("Featured", "N");

		if (!$this->GetProperty("PageID"))
			$this->AddError("page-empty", $this->module);

		if (!$this->GetProperty("Title"))
			$this->AddError("item-title-empty", $this->module);

		if (strlen($this->GetProperty("MetaTitle")) == 0)
			$this->SetProperty("MetaTitle", $this->GetProperty("Title"));

		$categoryIDs = $this->GetProperty("CategoryIDs");
		if (!(is_array($categoryIDs) && count($categoryIDs) > 0))
			$this->AddError("category-empty", $this->module);

		if (!$this->GetProperty("StaticPath"))
		{
			$this->AddError("item-static-path-empty", $this->module);
		}
		else if (!preg_match("/^[a-z0-9\._-]+$/i", $this->GetProperty("StaticPath")))
		{
			$this->AddError("static-path-incorrect");
		}
		else
		{
			/*@var stmt Statement */
			$stmt = GetStatement();
			$query = "SELECT COUNT(ItemID) FROM `catalog_item` WHERE
				StaticPath=".$this->GetPropertyForSQL("StaticPath")." AND
				PageID=".$this->GetIntProperty("PageID")." AND
				ItemID!=".$this->GetIntProperty("ItemID");
			if ($stmt->FetchField($query) > 0)
			{
				$this->AddError("static-path-is-not-unique");
			}
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

		if ($this->HasErrors())
			return false;
		else
			return true;
	}

	function SaveItemImage($savedImage = "", $type = "Item")
	{
		$fileSys = new FileSys();

		$original = false;
		if ($this->config[$type . 'ImageKeepFileName'])
		{
			if ($savedImage)
				$original = $savedImage;
			else
				$original = true;
		}

        $newItemImage = $fileSys->Upload($type . "Image", CATALOG_IMAGE_DIR."item/", $original, $this->_acceptMimeTypes);
		if ($newItemImage)
		{
			$this->SetProperty($type . "Image", $newItemImage["FileName"]);

			// Remove old image if it has different name
			if ($savedImage && $savedImage != $newItemImage["FileName"])
				@unlink(CATALOG_IMAGE_DIR."item/".$savedImage);
		}
		else
		{
			if ($savedImage)
				$this->SetProperty($type . "Image", $savedImage);
			else
				$this->SetProperty($type . "Image", null);
		}

		$this->_properties[$type."ImageConfig"]["Width"] = 0;
		$this->_properties[$type."ImageConfig"]["Height"] = 0;

		if ($this->GetProperty($type . 'Image'))
		{
			if ($info = @getimagesize(CATALOG_IMAGE_DIR."item/".$this->GetProperty($type . 'Image')))
			{
				$this->_properties[$type."ImageConfig"]["Width"] = $info[0];
				$this->_properties[$type."ImageConfig"]["Height"] = $info[1];
			}
		}

		$this->LoadErrorsFromObject($fileSys);

		return !$fileSys->HasErrors();
	}

	function RemoveItemImage($itemID, $savedImage, $type = "ItemImage")
	{
	    if ($savedImage)
		{
			@unlink(CATALOG_IMAGE_DIR."item/".$savedImage);
		}

		$stmt = GetStatement();
		if ($type == 'FeaturedImage')
			$key = 'Featured';
		else
			$key = 'Item';

	    $itemID = intval($itemID);
		if ($itemID > 0)
		{
			$stmt = GetStatement();
			$imageFile = $stmt->FetchField("SELECT " . $key . "Image
				FROM `catalog_item`
				WHERE ItemID=".$itemID." AND PageID=".$this->pageID);

			if ($imageFile)
				@unlink(CATALOG_IMAGE_DIR."item/".$imageFile);

			$stmt->Execute("UPDATE `catalog_item` SET
				" . $key . "Image=NULL, " . $key . "ImageConfig=NULL 
				WHERE ItemID=".$itemID." AND PageID=".$this->pageID);
		}
	}

	function SwitchActive($itemID, $active)
	{
		if ($active != 'Y') $active = 'N';
		$stmt = GetStatement();
		$stmt->Execute("UPDATE `catalog_item` SET Active='".$active."'
			WHERE ItemID=".intval($itemID));
	}
	
	function SwitchFeatured($itemID, $featured)
	{
		if ($featured != 'Y') $featured = 'N';
		$stmt = GetStatement();
		$stmt->Execute("UPDATE `catalog_item` SET Featured='".$featured."'
			WHERE ItemID=".intval($itemID));
	}
}

?>
<?php

require_once(dirname(__FILE__)."/../init.php");
es_include("localobject.php");

class InfoblockItem extends LocalObject
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
	var $params;
	var $config;

	function InfoblockItem($module, $pageID, $config = array(), $data = array())
	{
		parent::LocalObject($data);

		$this->module = $module;
		$this->pageID = intval($pageID);
		$this->config = is_array($config) ? $config : array();

		if (count($this->config) > 0)
		{
			$this->params = LoadImageConfig('ItemImage', $this->module, $this->config['ItemImage'].",".INFOBLOCK_ITEM_IMAGE);
		}
	}

	function LoadByID($itemID)
	{
		$query = "SELECT ItemID, PageID, CategoryID, ItemDate, Title, Description, FieldList, 
				ItemImage, ItemImageConfig, TitleH1, MetaTitle, MetaKeywords,
				MetaDescription, StaticPath, Content, SortOrder, Active
			FROM `infoblock_item`
			WHERE ItemID=".Connection::GetSQLString($itemID);
		$this->LoadFromSQL($query);

		$this->_PrepareContentBeforeShow();

		if ($this->GetProperty("ItemID"))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function LoadByStaticPath($request)
	{
		if (is_null($request->GetProperty('BaseURL')))
			$request->SetProperty('BaseURL', '');

		$query = "SELECT ItemID, PageID, CategoryID, ItemDate, Title, Description, FieldList, 
				ItemImage, ItemImageConfig, TitleH1, MetaTitle, MetaKeywords,
				MetaDescription, StaticPath, Content, SortOrder, Active,
				CONCAT(".$request->GetPropertyForSQL('BaseURL').", '/', StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).") AS ItemURL
			FROM `infoblock_item`
			WHERE PageID=".$this->pageID." AND
				StaticPath=".$request->GetPropertyForSQL('StaticPath');

		if ($request->GetIntProperty('CategoryID') > 0)
			$query .= " AND CategoryID=".$request->GetIntProperty('CategoryID');

		$this->LoadFromSQL($query);

		$this->_PrepareContentBeforeShow();

		if ($this->GetProperty("ItemID"))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function _PrepareContentBeforeShow()
	{
		if ($this->GetProperty("ItemID"))
		{
			$this->SetProperty("Content", str_replace("<P_T_R>", PROJECT_PATH, $this->GetProperty("Content")));
		}

		if ($this->GetProperty("ItemImage"))
		{
			$imageConfig = LoadImageConfigValues("ItemImage", $this->GetProperty("ItemImageConfig"));
			$this->AppendFromArray($imageConfig);
			$origW = $this->GetProperty("ItemImageWidth");
			$origH = $this->GetProperty("ItemImageHeight");

			for ($i = 0; $i < count($this->params); $i++)
			{
				$v = $this->params[$i];

				if($v["Resize"] == 13)
					$this->SetProperty($v["Name"]."Path", InsertCropParams($v["Path"]."item/", 
																		$this->GetIntProperty($v["Name"]."X1"), 
																		$this->GetIntProperty($v["Name"]."Y1"), 
																		$this->GetIntProperty($v["Name"]."X2"), 
																		$this->GetIntProperty($v["Name"]."Y2")).$this->GetProperty("ItemImage"));
				else
					$this->SetProperty($v["Name"]."Path", $v["Path"]."item/".$this->GetProperty("ItemImage"));

				if ($v["Name"] != 'ItemImage')
				{
					list($dstW, $dstH) = GetRealImageSize($v["Resize"], $origW, $origH, $v["Width"], $v["Height"]);
					$this->SetProperty($v["Name"]."Width", $dstW);
					$this->SetProperty($v["Name"]."Height", $dstH);
				}
			}
		}
		
		$chunks = explode("&", $this->GetProperty("FieldList"));
	
		for ($i = 0; $i < count($chunks); $i++)
		{
			$pair = explode("=", $chunks[$i]);
			if (count($pair) == 2)
				$this->SetProperty($pair[0], value_decode($pair[1]));
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
				"X1" => $this->GetIntProperty("ItemImage".$this->params[$i]['SourceName']."X1"),
				"Y1" => $this->GetIntProperty("ItemImage".$this->params[$i]['SourceName']."Y1"),
				"X2" => $this->GetIntProperty("ItemImage".$this->params[$i]['SourceName']."X2"),
				"Y2" => $this->GetIntProperty("ItemImage".$this->params[$i]['SourceName']."Y2")
			);
		}
		return $paramList;
	}

	function Save()
	{
		$result1 = $this->SaveItemImage($this->GetProperty("SavedItemImage"));
		$result2 = $this->Validate();
		if (!$result1 || !$result2)
		{
			$this->_PrepareContentBeforeShow();
			return false;
		}

		/*@var stmt Statement */
		$stmt = GetStatement();

		$content = PrepareContentBeforeSave($this->GetProperty("Content"));

		if ($this->GetIntProperty("ItemID") > 0)
		{
			$query = "SELECT CategoryID FROM `infoblock_item` WHERE ItemID=".$this->GetIntProperty("ItemID");
			$oldCategoryID = $stmt->FetchField($query);
			// Move to new category (to update SortOrder correctly)
			if ($oldCategoryID != $this->GetProperty('CategoryID'))
			{
				$itemList = new InfoblockItemList($this->module, $this->config);
				$itemList->MoveTo(array($this->GetProperty('ItemID')), $this->pageID, $this->GetProperty('CategoryID'));
			}

			$query = "UPDATE `infoblock_item` SET
					ItemDate=".$this->GetPropertyForSQL("ItemDate").",
					Title=".$this->GetPropertyForSQL("Title").",
					Description=".$this->GetPropertyForSQL("Description").",
					FieldList=".$this->GetPropertyForSQL("FieldList").",
					ItemImage=".$this->GetPropertyForSQL("ItemImage").",
					ItemImageConfig=".Connection::GetSQLString(json_encode($this->GetProperty("ItemImageConfig"))).",
					TitleH1=".$this->GetPropertyForSQL("TitleH1").",
					MetaTitle=".$this->GetPropertyForSQL("MetaTitle").",
					MetaKeywords=".$this->GetPropertyForSQL("MetaKeywords").",
					MetaDescription=".$this->GetPropertyForSQL("MetaDescription").",
					StaticPath=".$this->GetPropertyForSQL("StaticPath").",
					Content=".Connection::GetSQLString($content).",
					Active=".$this->GetPropertyForSQL("Active")."
				WHERE ItemID=".$this->GetIntProperty("ItemID");
		}
		else
		{
			$query = "SELECT MAX(SortOrder) FROM `infoblock_item`
				WHERE PageID=".$this->pageID." AND
					CategoryID".(is_null($this->GetProperty("CategoryID")) ? " IS NULL" : "=".$this->GetIntProperty("CategoryID"));
			if ($sortOrder = $stmt->FetchField($query))
				$sortOrder = $sortOrder + 1;
			else
				$sortOrder = 1;

			$query = "INSERT INTO `infoblock_item` (PageID, CategoryID, ItemDate,
				Title, Description, FieldList, ItemImage, ItemImageConfig,  
				TitleH1, MetaTitle, MetaKeywords, MetaDescription,
				StaticPath, Content, SortOrder, Active)
				VALUES (
				".$this->pageID.",
				".$this->GetPropertyForSQL("CategoryID").",
				".$this->GetPropertyForSQL("ItemDate").",
				".$this->GetPropertyForSQL("Title").",
				".$this->GetPropertyForSQL("Description").",
				".$this->GetPropertyForSQL("FieldList").",
				".$this->GetPropertyForSQL("ItemImage").",
				".Connection::GetSQLString(json_encode($this->GetProperty("ItemImageConfig"))).",
				".$this->GetPropertyForSQL("TitleH1").",
				".$this->GetPropertyForSQL("MetaTitle").",
				".$this->GetPropertyForSQL("MetaKeywords").",
				".$this->GetPropertyForSQL("MetaDescription").",
				".$this->GetPropertyForSQL("StaticPath").",
				".Connection::GetSQLString($content).",
				".$sortOrder.",
				".$this->GetPropertyForSQL("Active").")";
		}

		if ($stmt->Execute($query))
		{
			if (!($this->GetIntProperty("ItemID") > 0))
			{
				$this->SetProperty("ItemID", $stmt->GetLastInsertID());
			}
			if ($this->GetIntProperty("CategoryID"))
			{
				$query = "UPDATE `infoblock_category` SET Modified=NOW()
					WHERE CategoryID=".$this->GetIntProperty("CategoryID");
				$stmt->Execute($query);
			}
			return true;
		}
		else
		{
			$this->AddError("sql-error");
			return false;
		}
	}

	function Validate()
	{
		if ($this->GetProperty("Active") != "Y")
			$this->SetProperty("Active", "N");

		if (!$this->GetProperty("ItemDate"))
			$this->AddError("item-date-empty", $this->module);

		if ($this->IsPropertySet("CategoryID") && $this->GetIntProperty("CategoryID") < 1)
			$this->SetProperty("CategoryID", null);

		if (!$this->GetProperty("Title"))
			$this->AddError("item-title-empty", $this->module);

		if (!$this->GetProperty("StaticPath"))
		{
			$this->AddError("item-static-path-empty", $this->module);
		}
		else if (!preg_match("/^[a-z0-9\._-]+$/i", $this->GetProperty("StaticPath")))
		{
			$this->AddError("static-path-incorrect");
		}
		else if (preg_match("/^[0-9]+$/", $this->GetProperty("StaticPath")))
		{
			$this->AddError("item-static-path-incorrect", $this->module);
		}
		else
		{
			/*@var stmt Statement */
			$stmt = GetStatement();

			$query = "SELECT COUNT(ItemID) FROM `infoblock_item` WHERE
				PageID=".$this->pageID." AND
				StaticPath=".$this->GetPropertyForSQL("StaticPath")." AND
				CategoryID".(is_null($this->GetProperty("CategoryID")) ? " IS NULL" : "=".$this->GetIntProperty("CategoryID"))."
				AND ItemID!=".$this->GetIntProperty("ItemID");
			if ($stmt->FetchField($query) > 0)
			{
				$this->AddError("static-path-is-not-unique");
			}
		}

		if($this->config["FieldList"]){
			$fieldList = explode(",", $this->config["FieldList"]);
			$forSave = array();
			foreach ($fieldList as $field){
				$forSave[] = $field."=".value_encode($this->GetProperty($field));
			}
			$this->SetProperty("FieldList", implode("&", $forSave));
		}else{
			$this->SetProperty("FieldList", "");
		}

		return !$this->HasErrors();
	}

	function SwitchActive($itemID, $active)
	{
		if ($active != 'Y') $active = 'N';
		$stmt = GetStatement();
		$stmt->Execute("UPDATE `infoblock_item` SET Active='".$active."'
			WHERE ItemID=".intval($itemID));
	}

	function SaveItemImage($savedImage = "")
	{
		$fileSys = new FileSys();

		$original = false;
		if ($this->config['ItemImageKeepFileName'])
		{
			if ($savedImage)
				$original = $savedImage;
			else
				$original = true;
		}

		$newItemImage = $fileSys->Upload("ItemImage", INFOBLOCK_IMAGE_DIR."item/", $original, $this->_acceptMimeTypes);
		if ($newItemImage)
		{
			$this->SetProperty("ItemImage", $newItemImage["FileName"]);

			// Remove old image if it has different name
			if ($savedImage && $savedImage != $newItemImage["FileName"])
				@unlink(INFOBLOCK_IMAGE_DIR."item/".$savedImage);
		}
		else
		{
			if ($savedImage)
				$this->SetProperty("ItemImage", $savedImage);
			else
				$this->SetProperty("ItemImage", null);
		}

		$this->_properties["ItemImageConfig"]["Width"] = 0;
		$this->_properties["ItemImageConfig"]["Height"] = 0;

		if ($this->GetProperty('ItemImage'))
		{
			if ($info = @getimagesize(INFOBLOCK_IMAGE_DIR."item/".$this->GetProperty('ItemImage')))
			{
				$this->_properties["ItemImageConfig"]["Width"] = $info[0];
				$this->_properties["ItemImageConfig"]["Height"] = $info[1];
			}
		}

		$this->LoadErrorsFromObject($fileSys);

		return !$fileSys->HasErrors();
	}

	function RemoveItemImage($itemID, $savedImage)
	{
		if ($savedImage)
		{
			@unlink(INFOBLOCK_IMAGE_DIR."item/".$savedImage);
		}

		$itemID = intval($itemID);
		if ($itemID > 0)
		{
			$stmt = GetStatement();
			$imageFile = $stmt->FetchField("SELECT ItemImage
				FROM `infoblock_item`
				WHERE ItemID=".$itemID." AND PageID=".$this->pageID);

			if ($imageFile)
				@unlink(INFOBLOCK_IMAGE_DIR."item/".$imageFile);

			$stmt->Execute("UPDATE `infoblock_item` SET
				ItemImage=NULL, ItemImageConfig=NULL 
				WHERE ItemID=".$itemID." AND PageID=".$this->pageID);
		}
	}
}

?>
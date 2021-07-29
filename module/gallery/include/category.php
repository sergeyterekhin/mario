<?php

require_once(dirname(__FILE__)."/../init.php");
es_include("localobject.php");

class GalleryCategory extends LocalObject
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

	function GalleryCategory($module, $pageID, $config = array(), $data = array())
	{
		parent::LocalObject($data);

		$this->module = $module;
		$this->pageID = intval($pageID);
		$this->config = is_array($config) ? $config : array();

		if (count($this->config) > 0)
		{
			$this->params = LoadImageConfig('CategoryImage', $this->module, $this->config['CategoryImage'].",".GALLERY_CATEGORY_IMAGE);
		}
	}

	function LoadByID($categoryID)
	{
		$query = "SELECT CategoryID, PageID, Title, Description,
				CategoryImage, CategoryImageConfig, 
				TitleH1, MetaTitle, MetaKeywords, MetaDescription,
				StaticPath, Content, SortOrder, Created, Modified, Active
			FROM `gallery_category`
			WHERE CategoryID=".intval($categoryID)."
				AND PageID=".$this->pageID;
		$this->LoadFromSQL($query);

		$this->_PrepareContentBeforeShow();

		if ($this->GetProperty("CategoryID"))
			return true;
		else
			return false;
	}

	function _PrepareContentBeforeShow()
	{
		if ($this->GetProperty("CategoryID"))
		{
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

		if ($this->GetIntProperty('CategoryID') > 0)
		{
			$query = "UPDATE `gallery_category` SET
					Title=".$this->GetPropertyForSQL('Title').",
					Description=".$this->GetPropertyForSQL('Description').",
					CategoryImage=".$this->GetPropertyForSQL("CategoryImage").",
					CategoryImageConfig=".Connection::GetSQLString(json_encode($this->GetProperty("CategoryImageConfig"))).",
					TitleH1=".$this->GetPropertyForSQL('TitleH1').",
					MetaTitle=".$this->GetPropertyForSQL('MetaTitle').",
					MetaKeywords=".$this->GetPropertyForSQL('MetaKeywords').",
					MetaDescription=".$this->GetPropertyForSQL('MetaDescription').",
					StaticPath=".$this->GetPropertyForSQL('StaticPath').",
					Content=".Connection::GetSQLString($content).",
					Modified=NOW(),
					Active=".$this->GetPropertyForSQL('Active')."
				WHERE CategoryID=".$this->GetIntProperty('CategoryID')."
					AND PageID=".$this->pageID;
		}
		else
		{
			$query = "SELECT MAX(SortOrder) FROM `gallery_category` WHERE PageID=".$this->pageID;
			if ($sortOrder = $stmt->FetchField($query))
				$sortOrder = $sortOrder + 1;
			else
				$sortOrder = 1;
			$query = "INSERT INTO `gallery_category` (PageID, Title, Description,
				CategoryImage, CategoryImageConfig, 
				TitleH1, MetaTitle, MetaKeywords, MetaDescription, StaticPath,
				Content, SortOrder, Created, Active)
				VALUES (
				".$this->pageID.",
				".$this->GetPropertyForSQL('Title').",
				".$this->GetPropertyForSQL('Description').",
				".$this->GetPropertyForSQL("CategoryImage").",
				".Connection::GetSQLString(json_encode($this->GetProperty("CategoryImageConfig"))).",
				".$this->GetPropertyForSQL('TitleH1').",
				".$this->GetPropertyForSQL('MetaTitle').",
				".$this->GetPropertyForSQL('MetaKeywords').",
				".$this->GetPropertyForSQL('MetaDescription').",
				".$this->GetPropertyForSQL('StaticPath').",
				".Connection::GetSQLString($content).",
				".$sortOrder.", 
				NOW(),
				".$this->GetPropertyForSQL('Active').")";
		}

		if ($stmt->Execute($query))
		{
			if (!($this->GetIntProperty('CategoryID') > 0))
			{
				$this->SetProperty('CategoryID', $stmt->GetLastInsertID());
			}
			return true;
		}
		else
		{
			$this->AddError('sql-error');
			return false;
		}
	}

	function Validate()
	{
		if ($this->GetProperty("Active") != "Y")
			$this->SetProperty("Active", "N");

		if (!$this->GetProperty('Title'))
			$this->AddError('category-title-empty', $this->module);

		if (!$this->GetProperty('StaticPath'))
		{
			$this->AddError('category-static-path-empty', $this->module);
		}
		else if (!preg_match("/^[a-z0-9\._-]+$/i", $this->GetProperty('StaticPath')))
		{
			$this->AddError('static-path-incorrect', $this->module);
		}
		else
		{
			/*@var stmt Statement */
			$stmt = GetStatement();
			$query = "SELECT COUNT(CategoryID) FROM `gallery_category` WHERE
				PageID=".$this->pageID."
				AND StaticPath=".$this->GetPropertyForSQL('StaticPath')."
				AND CategoryID!=".$this->GetIntProperty('CategoryID');
			if ($stmt->FetchField($query) > 0)
			{
				$this->AddError('static-path-is-not-unique', $this->module);
			}
		}

		if ($this->HasErrors())
			return false;
		else
			return true;
	}

	function SwitchActive($categoryID, $active)
	{
		if ($active != 'Y') $active = 'N';
		$stmt = GetStatement();
		$stmt->Execute("UPDATE `gallery_category` SET Active='".$active."'
			WHERE CategoryID=".intval($categoryID));
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

		$newCategoryImage = $fileSys->Upload("CategoryImage", GALLERY_IMAGE_DIR."category/", $original, $this->_acceptMimeTypes);
		if ($newCategoryImage)
		{
			$this->SetProperty("CategoryImage", $newCategoryImage["FileName"]);

			// Remove old image if it has different name
			if ($savedImage && $savedImage != $newCategoryImage["FileName"])
				@unlink(GALLERY_IMAGE_DIR."category/".$savedImage);
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
			if ($info = @getimagesize(GALLERY_IMAGE_DIR."category/".$this->GetProperty('CategoryImage')))
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
			@unlink(GALLERY_IMAGE_DIR."category/".$savedImage);
		}

		$categoryID = intval($categoryID);
		if ($categoryID > 0)
		{
			$stmt = GetStatement();
			$imageFile = $stmt->FetchField("SELECT CategoryImage
				FROM `gallery_category`
				WHERE CategoryID=".$categoryID." AND PageID=".$this->pageID);

			if ($imageFile)
				@unlink(GALLERY_IMAGE_DIR."category/".$imageFile);

			$stmt->Execute("UPDATE `gallery_category` SET
				CategoryImage=NULL, CategoryImageConfig=NULL 
				WHERE CategoryID=".$categoryID." AND PageID=".$this->pageID);
		}
	}
}

?>
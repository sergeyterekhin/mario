<?php

require_once(dirname(__FILE__)."/../init.php");
es_include("localobjectlist.php");

class GalleryMediaList extends LocalObjectList
{
	var $module;
	var $pageID;
	var $config;
	var $params;

	function GalleryMediaList($module, $pageID, $config = array(), $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->pageID = intval($pageID);
		$this->config = is_array($config) ? $config : array();

		if (count($this->config) > 0)
		{
			$this->params = LoadImageConfig('MediaFile', $this->module, $this->config['MediaFile'].",".GALLERY_MEDIA_FILE);
			$this->SetItemsOnPage(abs(intval($this->config["MediaPerPage"])));
		}
	}

	function Load($request)
	{
		$where = array();
		$where[] = "PageID=".$this->pageID;
		if ($request->GetIntProperty('ViewCategoryID') > 0)
			$where[] = "CategoryID=".$request->GetIntProperty('ViewCategoryID');
		else
			$where[] = "CategoryID IS NULL";
		if ($request->GetProperty('ViewType'))
			$where[] = "Type=".$request->GetPropertyForSQL('ViewType');

		$query = "SELECT MediaID, PageID, CategoryID, Title, Description,
				MediaFile, MediaFileConfig, VideoSnapshot,
				Type, SortOrder
			FROM `gallery_media`
			".(count($where) > 0 ? "WHERE ".implode(" AND ", $where) : "")."
			ORDER BY ".($this->config["AnnouncementOrderBy"] == "Position" ? "SortOrder ASC, Title ASC" : "RAND()");

		if ($request->GetProperty("FullList"))
		{
			$this->SetItemsOnPage(0);
			$this->SetCurrentPage();
		}
		else if ($request->GetProperty("AnnouncementList"))
		{
			$this->SetItemsOnPage(abs(intval($this->config["AnnouncementCount"])));
			$this->SetCurrentPage(1);
		}
		else
		{
			$this->SetItemsOnPage(abs(intval($this->config["MediaPerPage"])));
			$this->SetCurrentPage();
		}

		$this->LoadFromSQL($query);

		// Flash preview image
		$fW = $fH = 0;
		if ($s = @getimagesize(GALLERY_IMAGE_DIR."flash.jpg"))
		{
			$fW = $s[0];
			$fH = $s[1];
		}

		// Video preview image
		$vW = $vH = 0;
		if ($s = @getimagesize(GALLERY_IMAGE_DIR."flv.jpg"))
		{
			$vW = $s[0];
			$vH = $s[1];
		}

		for ($i = 0; $i < count($this->_items); $i++)
		{
		    $imageConfig = LoadImageConfigValues('MediaFile', $this->_items[$i]["MediaFileConfig"]);
			$this->_items[$i] = array_merge($this->_items[$i], $imageConfig);
			
			$origW = $this->_items[$i]["MediaFileWidth"];
			$origH = $this->_items[$i]["MediaFileHeight"];

			$this->_items[$i]["MediaFilePath"] = PROJECT_PATH."website/".WEBSITE_FOLDER."/var/gallery/media/".$this->_items[$i]["MediaFile"];

			if ($this->_items[$i]["Type"] == 'video' && $this->_items[$i]["VideoSnapshot"])
			{
				$this->_items[$i]["VideoSnapshotPath"] = PROJECT_PATH."website/".WEBSITE_FOLDER."/var/gallery/media/".$this->_items[$i]["VideoSnapshot"];
			}

			for ($j = 0; $j < count($this->params); $j++)
			{
				$v = $this->params[$j];

				if ($v["Name"] == 'MediaFile') continue;

				if ($this->_items[$i]["Type"] == 'image')
				{
					// Define sizes for resized image
    					if($v["Resize"] == 13)
						$this->_items[$i][$v["Name"]."Path"] = InsertCropParams($v["Path"]."media/", 
																		isset($this->_items[$i][$v["Name"]."X1"]) ? intval($this->_items[$i][$v["Name"]."X1"]) : 0, 
																		isset($this->_items[$i][$v["Name"]."Y1"]) ? intval($this->_items[$i][$v["Name"]."Y1"]) : 0,
																		isset($this->_items[$i][$v["Name"]."X2"]) ? intval($this->_items[$i][$v["Name"]."X2"]) : 0,
																		isset($this->_items[$i][$v["Name"]."Y2"]) ? intval($this->_items[$i][$v["Name"]."Y2"]) : 0).$this->_items[$i]["MediaFile"];
					else 	
						$this->_items[$i][$v["Name"]."Path"] = $v["Path"]."media/".$this->_items[$i]["MediaFile"];
					list($dstW, $dstH) = GetRealImageSize($v["Resize"], $origW, $origH, $v["Width"], $v["Height"]);
					$this->_items[$i][$v["Name"]."Width"] = $dstW;
					$this->_items[$i][$v["Name"]."Height"] = $dstH;
				}
				else if ($this->_items[$i]["Type"] == 'flash')
				{
					// Prepare preview image of the flash logo
					$this->_items[$i][$v["Name"]."Path"] = $v["Path"]."flash.jpg";
					list($dstW, $dstH) = GetRealImageSize($v["Resize"], $fW, $fH, $v["Width"], $v["Height"]);
					$this->_items[$i][$v["Name"]."Width"] = $dstW;
					$this->_items[$i][$v["Name"]."Height"] = $dstH;
				}
				else if ($this->_items[$i]["Type"] == 'video')
				{
					// Prepare preview image of the video logo or snapshot
					if ($this->_items[$i]["VideoSnapshot"])
					{
						$this->_items[$i][$v["Name"]."Path"] = $v["Path"]."media/".$this->_items[$i]["VideoSnapshot"];
						list($dstW, $dstH) = GetRealImageSize($v["Resize"], $origW, $origH, $v["Width"], $v["Height"]);
					}
					else
					{
						$this->_items[$i][$v["Name"]."Path"] = $v["Path"]."flv.png";
						list($dstW, $dstH) = GetRealImageSize($v["Resize"], $vW, $vH, $v["Width"], $v["Height"]);
					}
					$this->_items[$i][$v["Name"]."Width"] = $dstW;
					$this->_items[$i][$v["Name"]."Height"] = $dstH;
				}
			}
		}
	}
	
	function SetSortOrder($mediaID, $categoryID, $diff)
	{
		/*@var stmt Statement */
		$stmt = GetStatement();
		
		$query = "SELECT SortOrder FROM `gallery_media` WHERE MediaID=".Connection::GetSQLString($mediaID);
		
		$sortOrder = $stmt->FetchField($query);
		$sortOrder = $sortOrder + $diff;

		if ($sortOrder < 1) $sortOrder = 1;

		$mediaID = intval($mediaID);
		$categoryID = intval($categoryID);

		$pageID = intval($stmt->FetchField("SELECT PageID FROM `gallery_media` WHERE MediaID=".$mediaID));

		$whereCategory = ($categoryID > 0 ? "=".$categoryID : " IS NULL");

		$query = "SELECT COUNT(SortOrder) FROM `gallery_media`
			WHERE CategoryID".$whereCategory;

		$query .= " AND PageID=".$pageID;

		if ($maxSortOrder = $stmt->FetchField($query))
		{
			if ($sortOrder > $maxSortOrder) $sortOrder = $maxSortOrder;

			$query = "SELECT SortOrder FROM `gallery_media`
				WHERE CategoryID".$whereCategory." AND MediaID=".$mediaID;
			if ($currentSortOrder = $stmt->FetchField($query))
			{
				if ($sortOrder == $currentSortOrder)
					return true;

				$query = "UPDATE `gallery_media`
					SET SortOrder=".$sortOrder."
					WHERE CategoryID".$whereCategory." AND MediaID=".$mediaID;
				$stmt->Execute($query);

				if ($sortOrder > $currentSortOrder)
				{
					$query = "UPDATE `gallery_media` SET SortOrder=SortOrder-1
						WHERE SortOrder<=".$sortOrder." AND SortOrder>".$currentSortOrder."
							AND CategoryID".$whereCategory." AND MediaID<>".$mediaID;
					$query .= " AND PageID=".$pageID;

				}
				else if ($sortOrder < $currentSortOrder)
				{
					$query = "UPDATE `gallery_media` SET SortOrder=SortOrder+1
						WHERE SortOrder>=".$sortOrder." AND SortOrder<".$currentSortOrder."
							AND CategoryID".$whereCategory." AND MediaID<>".$mediaID;
					$query .= " AND PageID=".$pageID;
				}
				$stmt->Execute($query);

				return true;
			}
		}

		return false;
	}

	function RemoveByCategoryIDs($ids)
	{
		if (!(is_array($ids) && count($ids) > 0))
			return;

		/*@var stmt Statement */
		$stmt = GetStatement();

		$query = "SELECT MediaID, CategoryID, Title, MediaFile,
				VideoSnapshot, Type, SortOrder
			FROM `gallery_media`
			WHERE CategoryID IN (".implode(", ", Connection::GetSQLArray($ids)).")
				AND PageID=".$this->pageID."
			ORDER BY CategoryID";
		if ($result = $stmt->FetchList($query))
		{
			$this->_Remove($result);
		}
	}

	function RemoveByMediaIDs($ids)
	{
		if (!(is_array($ids) && count($ids) > 0))
			$ids = array($ids);

		/*@var stmt Statement */
		$stmt = GetStatement();

		$query = "SELECT MediaID, CategoryID, Title, MediaFile,
				VideoSnapshot, Type, SortOrder
			FROM `gallery_media`
			WHERE MediaID IN (".implode(", ", Connection::GetSQLArray($ids)).")
				AND PageID=".$this->pageID."
			ORDER BY CategoryID";
		if ($result = $stmt->FetchList($query))
		{
			$this->_Remove($result);
		}
	}

	function _Remove($result)
	{
		$filesToRemove = array();
		$filesRemoved = array();

		for ($i = 0; $i < count($result); $i++)
		{
			@unlink(GALLERY_IMAGE_DIR."media/".$result[$i]['MediaFile']);
			if ($result[$i]['Type'] == 'video' && $result[$i]['VideoSnapshot'])
				@unlink(GALLERY_IMAGE_DIR."media/".$result[$i]['VideoSnapshot']);
			$filesToRemove[] = $result[$i]['MediaID'];
			$filesRemoved[] = $result[$i]['Title'];
		}

		if (count($filesRemoved) > 0)
		{
			/*@var stmt Statement */
			$stmt = GetStatement();

			for ($i = 0; $i < count($result); $i++)
			{
				$query = "UPDATE `gallery_media` SET SortOrder=SortOrder-1
					WHERE PageID=".$this->pageID."
						AND CategoryID".(is_null($result[$i]['CategoryID']) ? " IS NULL" : "=".intval($result[$i]['CategoryID']))."
						AND SortOrder>".intval($result[$i]['SortOrder']);
				$stmt->Execute($query);
			}

			$stmt->Execute("DELETE FROM `gallery_media` WHERE MediaID
				IN (".implode(", ", Connection::GetSQLArray($filesToRemove)).")");

			if (count($filesRemoved) > 1)
				$key = "files-are-removed";
			else
				$key = "file-is-removed";

			$this->AddMessage($key, $this->module, array("FileList" => "\"".implode("\", \"", $filesRemoved)."\"", "FileCount" => count($filesRemoved)));
		}
	}

}

?>
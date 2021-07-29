<?php

require_once(dirname(__FILE__)."/../init.php");
es_include("localobjectlist.php");

class CatalogMediaList extends LocalObjectList
{
	var $module;
	var $params;
	var $config;

	function CatalogMediaList($module, $config = array(), $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;

		$this->SetPageParam("MPage");
		
		$this->config = is_array($config) ? $config : array();
		
		if (count($this->config) > 0)
		{
		    $this->SetItemsOnPage(abs(intval($this->config["MediaPerPage"])));

    		$this->params = array();
    		$this->params = LoadImageConfig('MediaFile', $this->module, $this->config['MediaFile'].",".CATALOG_MEDIA_FILE);
		}
	}

	function LoadMediaList($request)
	{
		$query = "SELECT MediaID, ItemID, Title, Description, MediaFile,
		          MediaFileConfig, VideoSnapshot, Type, SortOrder
			FROM `catalog_media`
			WHERE ItemID=".$request->GetIntProperty('ViewItemID');

		if ($request->GetProperty('ViewType'))
			$query .= " AND Type=".$request->GetPropertyForSQL('ViewType');

		$query .= " ORDER BY SortOrder ASC, Title ASC";

		if ($request->GetProperty('NoPaging'))
			$this->SetItemsOnPage(0);

		$this->LoadFromSQL($query);
		
		// Flash preview image
		$fW = $fH = 0;
		if ($s = @getimagesize(CATALOG_IMAGE_DIR."flash.jpg"))
		{
			$fW = $s[0];
			$fH = $s[1];
		}

		// Video preview image
		$vW = $vH = 0;
		if ($s = @getimagesize(CATALOG_IMAGE_DIR."flv.jpg"))
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

			$this->_items[$i]["MediaFilePath"] = PROJECT_PATH."website/".WEBSITE_FOLDER."/var/catalog/media/".$this->_items[$i]["MediaFile"];

			if ($this->_items[$i]["Type"] == 'video' && $this->_items[$i]["VideoSnapshot"])
			{
				$this->_items[$i]["VideoSnapshotPath"] = PROJECT_PATH."website/".WEBSITE_FOLDER."/var/catalog/media/".$this->_items[$i]["VideoSnapshot"];
			}			
		    
			for ($j = 0; $j < count($this->params); $j++)
			{
				$v = $this->params[$j];

				switch($this->_items[$i]['Type'])
				{
					case "image":
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
						break;
					case "video":
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
						break;
					case "flash":
					    // Prepare preview image of the flash logo
    					$this->_items[$i][$v["Name"]."Path"] = $v["Path"]."flash.jpg";
    					list($dstW, $dstH) = GetRealImageSize($v["Resize"], $fW, $fH, $v["Width"], $v["Height"]);
    					$this->_items[$i][$v["Name"]."Width"] = $dstW;
    					$this->_items[$i][$v["Name"]."Height"] = $dstH;
						break;
				}
			}
		}
	}
	
	function SetSortOrder($mediaID, $diff)
	{
		/*@var stmt Statement */
		$stmt = GetStatement();
		
		$query = "SELECT SortOrder FROM `catalog_media` WHERE MediaID=".Connection::GetSQLString($mediaID);
		
		$sortOrder = $stmt->FetchField($query);
		$sortOrder = $sortOrder + $diff;

		if ($sortOrder < 1) $sortOrder = 1;

		$mediaID = intval($mediaID);

		$itemID = intval($stmt->FetchField("SELECT ItemID FROM `catalog_media` WHERE MediaID=".$mediaID));

		$query = "SELECT COUNT(SortOrder) FROM `catalog_media`
			WHERE ItemID=".$itemID;

		if ($maxSortOrder = $stmt->FetchField($query))
		{
			if ($sortOrder > $maxSortOrder) $sortOrder = $maxSortOrder;

			$query = "SELECT SortOrder FROM `catalog_media`
				WHERE MediaID=".$mediaID;
			if ($currentSortOrder = $stmt->FetchField($query))
			{
				if ($sortOrder == $currentSortOrder)
					return true;

				$query = "UPDATE `catalog_media`
					SET SortOrder=".$sortOrder."
					WHERE MediaID=".$mediaID;
				$stmt->Execute($query);

				if ($sortOrder > $currentSortOrder)
				{
					$query = "UPDATE `catalog_media` SET SortOrder=SortOrder-1
						WHERE SortOrder<=".$sortOrder." AND SortOrder>".$currentSortOrder."
							AND MediaID<>".$mediaID;
					$query .= " AND ItemID=".$itemID;

				}
				else if ($sortOrder < $currentSortOrder)
				{
					$query = "UPDATE `catalog_media` SET SortOrder=SortOrder+1
						WHERE SortOrder>=".$sortOrder." AND SortOrder<".$currentSortOrder."
							 AND MediaID<>".$mediaID." AND ItemID=".$itemID;
				}
				$stmt->Execute($query);

				return true;
			}
		}

		return false;
	}

	function RemoveByItemIDs($ids)
	{
		if (is_array($ids) && count($ids) > 0)
		{
			/*@var stmt Statement */
			$stmt = GetStatement();

			$query = "SELECT MediaID, ItemID, MediaFile, Title, Type, SortOrder
				FROM `catalog_media`
				WHERE ItemID IN (".implode(", ", Connection::GetSQLArray($ids)).")
				ORDER BY ItemID";
			if ($result = $stmt->FetchList($query))
			{
				$this->_Remove($result);
			}
		}
	}

	function RemoveByMediaIDs($ids)
	{
		if (!(is_array($ids) && count($ids) > 0))
			$ids = array($ids);

		/*@var stmt Statement */
		$stmt = GetStatement();

		$query = "SELECT MediaID, ItemID, MediaFile, Title, Type, SortOrder
			FROM `catalog_media`
			WHERE MediaID IN (".implode(", ", Connection::GetSQLArray($ids)).")
			ORDER BY ItemID";
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
		    @unlink(CATALOG_IMAGE_DIR."media/".$result[$i]['MediaFile']);
			if ($result[$i]['Type'] == 'video' && $result[$i]['VideoSnapshot'])
				@unlink(CATALOG_IMAGE_DIR."media/".$result[$i]['VideoSnapshot']);
			$filesToRemove[] = $result[$i]['MediaID'];
			$filesRemoved[] = $result[$i]['Title'];
		}

		if (count($filesRemoved) > 0)
		{
			/*@var stmt Statement */
			$stmt = GetStatement();

			for ($i = 0; $i < count($result); $i++)
			{
				$query = "UPDATE `catalog_media` SET SortOrder=SortOrder-1
					WHERE SortOrder>".intval($result[$i]['SortOrder'])."
						AND ItemID=".intval($result[$i]['ItemID']);
				$stmt->Execute($query);
			}

			$stmt->Execute("DELETE FROM `catalog_media` WHERE MediaID
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
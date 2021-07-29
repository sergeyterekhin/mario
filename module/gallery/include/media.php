<?php

es_include("localobject.php");
es_include('filesys.php');

class GalleryMedia extends LocalObject
{
	var $_acceptMimeTypes = array(
		'image/png',
		'image/x-png',
		'image/gif',
		'image/jpeg',
		'image/pjpeg',
		'video/quicktime',
		'video/avi',
		'video/mpeg',
		'video/mp4',
		'video/webm',
		'video/ogg',
		'video/x-msvideo',
		'video/x-flv',
		'video/x-ms-wmv',
		'video/x-msvideo',
		'video/x-flv',
		'video/x-ms-wmv',
		'audio/x-pn-realaudio',
		'audio/x-ms-wmv',
		'application/x-troff-msvideo',
		'application/octet-stream',
		'application/x-shockwave-flash'
	);

	var $_mediaTypes = array(
		'image/png' => 'image',
		'image/x-png' => 'image',
		'image/gif' => 'image',
		'image/jpeg' => 'image',
		'image/pjpeg' => 'image',
		'video/quicktime' => 'video',
		'video/avi' => 'video',
		'video/mpeg' => 'video',
		'video/mp4' => 'video',
		'video/webm' => 'video',
		'video/ogg' => 'video',
		'video/x-msvideo' => 'video',
		'video/x-flv' => 'video',
		'video/x-ms-wmv' => 'video',
		'video/x-msvideo' => 'video',
		'video/x-flv' => 'video',
		'video/x-ms-wmv' => 'video',
		'audio/x-pn-realaudio' => 'video',
		'audio/x-ms-wmv' => 'video',
		'application/x-troff-msvideo' => 'video',
		'application/octet-stream' => 'flash',
		'application/x-shockwave-flash' => 'flash'
	);
	var $module;
	var $pageID;
	var $config;
	var $params;
	var $snapshotFrameNumber = 20;

	function GalleryMedia($module, $pageID, $config = array(), $data = array())
	{
		parent::LocalObject($data);

		$this->module = $module;
		$this->pageID = intval($pageID);
		$this->config = (is_array($config) ? $config : array());
		$this->params = (count($this->config) > 0 ? LoadImageConfig('MediaFile', $this->module, $this->config['MediaFile'].",".GALLERY_MEDIA_FILE) : array());
	}

	function LoadByID($id)
	{
		$query = "SELECT MediaID, PageID, CategoryID, Title, Description,
				MediaFile, MediaFileConfig, VideoSnapshot,
				Type, SortOrder
			FROM `gallery_media`
			WHERE PageID=".$this->pageID." AND MediaID=".intval($id);
		$this->LoadFromSQL($query);

		if ($this->GetProperty("MediaID"))
		{
			$imageConfig = LoadImageConfigValues("MediaFile", $this->GetProperty("MediaFileConfig"));
			$this->AppendFromArray($imageConfig);
			
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

			$origW = $this->GetProperty("MediaFileWidth");
			$origH = $this->GetProperty("MediaFileHeight");

			$this->SetProperty("MediaFilePath", PROJECT_PATH."website/".WEBSITE_FOLDER."/var/gallery/media/".$this->GetProperty("MediaFile"));

			if ($this->GetProperty("Type") == 'video' && $this->GetProperty("VideoSnapshot"))
			{
				$this->SetProperty("VideoSnapshotPath", PROJECT_PATH."website/".WEBSITE_FOLDER."/var/gallery/media/".$this->GetProperty("VideoSnapshot"));
			}

			for ($j = 0; $j < count($this->params); $j++)
			{
				$v = $this->params[$j];

				if ($v["Name"] == 'MediaFile') continue;

				if ($this->GetProperty("Type") == 'image')
				{
					// Define sizes for resized image
					if($v["Resize"] == 13)
						$this->SetProperty($v["Name"]."Path", InsertCropParams($v["Path"]."media/", 
																			$this->GetIntProperty($v["Name"]."X1"), 
																			$this->GetIntProperty($v["Name"]."Y1"), 
																			$this->GetIntProperty($v["Name"]."X2"), 
																			$this->GetIntProperty($v["Name"]."Y2")).$this->GetProperty("MediaFile"));
					else
						$this->SetProperty($v["Name"]."Path", $v["Path"]."media/".$this->GetProperty("MediaFile"));
					list($dstW, $dstH) = GetRealImageSize($v["Resize"], $origW, $origH, $v["Width"], $v["Height"]);
					$this->SetProperty($v["Name"]."Width", $dstW);
					$this->SetProperty($v["Name"]."Height", $dstH);
				}
				else if ($this->GetProperty("Type") == 'flash')
				{
					// Prepare preview image of the flash logo
					$this->SetProperty($v["Name"]."Path", $v["Path"]."flash.jpg");
					list($dstW, $dstH) = GetRealImageSize($v["Resize"], $fW, $fH, $v["Width"], $v["Height"]);
					$this->SetProperty($v["Name"]."Width", $dstW);
					$this->SetProperty($v["Name"]."Height", $dstH);
				}
				else if ($this->GetProperty("Type") == 'video')
				{
					// Prepare preview image of the video logo or snapshot
					if ($this->GetProperty("VideoSnapshot"))
					{
						$this->SetProperty($v["Name"]."Path", $v["Path"]."media/".$this->GetProperty("VideoSnapshot"));
						list($dstW, $dstH) = GetRealImageSize($v["Resize"], $origW, $origH, $v["Width"], $v["Height"]);
					}
					else
					{
						$this->SetProperty($v["Name"]."Path", $v["Path"]."flv.png");
						list($dstW, $dstH) = GetRealImageSize($v["Resize"], $vW, $vH, $v["Width"], $v["Height"]);
					}
					$this->SetProperty($v["Name"]."Width", $dstW);
					$this->SetProperty($v["Name"]."Height", $dstH);
				}
			}
			return true;
		}
		else
		{
			return false;
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
				"X1" => $this->GetIntProperty("MediaFile".$this->params[$i]['SourceName']."X1"),
				"Y1" => $this->GetIntProperty("MediaFile".$this->params[$i]['SourceName']."Y1"),
				"X2" => $this->GetIntProperty("MediaFile".$this->params[$i]['SourceName']."X2"),
				"Y2" => $this->GetIntProperty("MediaFile".$this->params[$i]['SourceName']."Y2")
			);
		}
		return $paramList;
	}

	function UpdateMediaInfo()
	{
		/*@var stmt Statement */
		$stmt = GetStatement();

		$query = "SELECT MediaID, PageID, CategoryID, Title, SortOrder
			FROM `gallery_media`
			WHERE PageID=".$this->pageID." AND MediaID=".$this->GetIntProperty("MediaID");

		if (!$oldData = $stmt->FetchRow($query))
		{
			$this->AddError('unknown-media-id', $this->module, array('MediaID' => $this->GetProperty('MediaID')));
			return false;
		}

		if (!$this->GetProperty("Title"))
		{
			$this->AddError("media-title-empty", $this->module);
			return false;
		}

		$categoryID = $this->GetIntProperty("CategoryID");
		if ($categoryID <= 0) $categoryID = null;

		// Update media data
		$query = "UPDATE `gallery_media` SET
				Title=".$this->GetPropertyForSQL('Title').",
				Description=".$this->GetPropertyForSQL('Description').",
				CategoryID=".Connection::GetSQLString($categoryID).",
				MediaFileConfig=".Connection::GetSQLString(json_encode($this->GetProperty('MediaFileConfig')))."
			WHERE PageID=".$this->pageID." AND MediaID=".$this->GetIntProperty("MediaID");
		$stmt->Execute($query);

		return true;
	}

	function ConvertVideo($from, $width, $height)
	{
		$file = $from.".flv";
		$to = GALLERY_IMAGE_DIR."media/".$from.".flv";
		$from = GALLERY_IMAGE_DIR."media/".$from;

		// Try with ffmpreg
		exec($this->config['ffmpeg'].' -i '.escapeshellarg($from).' -y -b 360 -r 25 -s '.$width.'x'.$height.' -deinterlace -ab 56 -ar 22050 -ac 1 '.escapeshellarg($to), $output, $retval);
		if ($retval)
		{
			// Try with mencoder
			exec($this->config['mencoder'].' '.escapeshellarg($from).' -o '.escapeshellarg($from.'.mp4').' -ovc xvid -xvidencopts pass=1 -oac lavc', $output, $retval);
			if ($retval)
			{
				// Try with ffmpreg (no sound)
				exec($this->config['ffmpeg'].' -i '.escapeshellarg($from).' -y -b 360 -r 25 -s '.$width.'x'.$height.' -deinterlace -an '.escapeshellarg($to), $output, $retval);
				if ($retval)
				{
					@unlink($from.'.mp4');
					@unlink($to);
					return false;
				}
			}
			else
			{
				// After mencoder go with ffmpreg again
				exec($this->config['ffmpeg'].' -i '.escapeshellarg($from.'.mp4').' -y -b 360 -r 25 -s '.$width.'x'.$height.' -deinterlace -ab 56 -ar 22050 -ac 1 '.escapeshellarg($to), $output, $retval);
				if ($retval)
				{
					// Try with ffmpreg (no sound)
					exec($this->config['ffmpeg'].' -i '.escapeshellarg($from.'.mp4').' -y -b 360 -r 25 -s '.$width.'x'.$height.' -deinterlace -an '.escapeshellarg($to), $output, $retval);
					if ($retval)
					{
						@unlink($from.'.mp4');
						@unlink($to);
						return false;
					}
				}
				@unlink($from.'.mp4');
			}
		}

		// Now flvtool2
		@exec($this->config['flvtool2'].' -U '.escapeshellarg($to));

		return $file;
	}

	function CreateSnapshot($from, $width = 0, $height = 0)
	{
		if (!class_exists('ffmpeg_movie'))
			return false;

		if ($width == 0 || $height == 0)
			return false;

		$to = GALLERY_IMAGE_DIR."media/".$from.".jpg";

		$movie = new ffmpeg_movie(GALLERY_IMAGE_DIR."media/".$from, false);
		if ($movie->getFrameCount() < $this->snapshotFrameNumber)
			$frame = $movie->getFrame($movie->getFrameCount());
		else
			$frame = $movie->getFrame($this->snapshotFrameNumber);

		$gdImage = $frame->toGDImage();

		if ($gdImage)
		{
			$origW = imagesx($gdImage);
			$origH = imagesy($gdImage);

			if ($origW == $width && $origH == $height)
			{
				imagejpeg($gdImage, $to, 90);
			}
			else
			{
				$srcX = 0;
				$srcY = 0;
				$srcW = $origW;
				$srcH = $origH;

				if ($origW/$width > $origH/$height)
				{
					$r = $origH/$height;
					$srcW = round($width*$r);
					$srcX = round(($origW - $srcW)/2);
				}
				else
				{
					$r = $origW/$width;
					$srcH = round($height*$r);
					$srcY = round(($origH - $srcH)/2);
				}

				$image = imagecreatetruecolor($width, $height);
				imagecopyresampled($image, $gdImage, 0, 0, $srcX, $srcY, $width, $height, $srcW, $srcH);
				imagejpeg($image, $to, 90);
				imagedestroy($image);
			}
			imagedestroy($gdImage);
			return true;
		}
		return false;
	}

	function Save()
	{
		$fileSys = new FileSys();

		if ($this->config['MediaKeepFileName'])
			$original = true;
		else
			$original = false;

		if ($fileList = $fileSys->Upload("MediaFile", GALLERY_IMAGE_DIR."media/", $original, $this->_acceptMimeTypes))
		{
			$titles = $this->GetProperty('MediaTitle');
			$descriptions = $this->GetProperty('MediaDescription');

			/*@var stmt Statement */
			$stmt = GetStatement();

			$query = "SELECT MAX(SortOrder)+1
				FROM `gallery_media`
				WHERE PageID=".$this->pageID."
					AND CategoryID".($this->GetIntProperty('ViewCategoryID') > 0 ? "=".$this->GetIntProperty('ViewCategoryID') : " IS NULL");
			$sortOrder = intval($stmt->FetchField($query));
			if ($sortOrder < 1) $sortOrder = 1;

			for ($i = 0; $i < count($fileList); $i++)
			{
				if (isset($fileList[$i]["ErrorInfo"])) continue;

				$file = $fileList[$i]["FileName"];
				$type = $this->_mediaTypes[$fileList[$i]["type"]];

				$saved = array();

				switch($type)
				{
					case "image":
						if ($info = @getimagesize(GALLERY_IMAGE_DIR."media/".$file))
						{
							$saved = array("Type" => "image", "MediaFile" => $file, "MediaFileConfig" => array("Width" => $info[0], "Height" => $info[1]));
						}
						else
						{
							$fileList[$i]["ErrorInfo"] = GetTranslation("filesys-getimagesize-error");
							@unlink(GALLERY_IMAGE_DIR."media/".$file);
						}

						break;
					case "video":
						// Get width & height from config
						$width = $height = 0;
						$s = explode("x", $this->config["MediaVideo"]);
						if (count($s) == 2)
						{
							$width = abs(intval($s[0]));
							$height = abs(intval($s[1]));
						}

						$savedFile = "";

						// Convert video to FLV
						if ($width > 0 && $height > 0)
						{
							if ($fileList[$i]["type"] == "video/x-flv")
								$savedFile = $file;
							else
								$savedFile = $this->ConvertVideo($file, $width, $height);

							if ($savedFile)
							{
								$saved = array("Type" => "video", "MediaFile" => $savedFile, "MediaFileConfig" => array("Width" => $width, "Height" => $height));
								if ($this->CreateSnapshot($savedFile, $width, $height))
								{
									$saved['VideoSnaphost'] = $savedFile.".jpg";
								}
							}
						}

						if (count($saved) == 0)
							$fileList[$i]["ErrorInfo"] = GetTranslation("video-convert-error", $this->module, array("FileName" => $fileList[$i]["name"]));

						// Remove original file
						if ($file != $savedFile)
							@unlink(GALLERY_IMAGE_DIR."media/".$file);

						break;
					case "flash":
						$width = $height = 0;
						if ($info = @getimagesize(GALLERY_IMAGE_DIR."media/".$file))
						{
							$width = $info[0];
							$height = $info[1];
						}
						$saved = array("Type" => "flash", "MediaFile" => $file, "MediaFileConfig" => array("Width" => $width, "Height" => $height));

						break;
					default:
						$fileList[$i]["ErrorInfo"] = GetTranslation("unknown-media-format", $this->module, array("MimeType" => $fileList[$i]["type"]));
						break;
				}

				if (count($saved) > 0)
				{
					// Set Title to original file name if it is not entered by user
					if (!isset($titles[$i]) || strlen($titles[$i]) == 0)
						$titles[$i] = $fileList[$i]["name"];

					if (!isset($descriptions[$i]))
						$descriptions[$i] = "";

					$snapshot = (isset($saved['VideoSnaphost']) ? $saved['VideoSnaphost'] : "");

					if ($this->GetIntProperty('ViewCategoryID') > 0)
						$categoryID = $this->GetIntProperty('ViewCategoryID');
					else
						$categoryID = null;

					$query = "INSERT INTO `gallery_media` (PageID,
						CategoryID, Title, Description, MediaFile,
						MediaFileConfig, VideoSnapshot,
						Type, SortOrder)
						VALUES (
						".$this->pageID.",
						".Connection::GetSQLString($categoryID).",
						".Connection::GetSQLString($titles[$i]).",
						".Connection::GetSQLString($descriptions[$i]).",
						".Connection::GetSQLString($saved['MediaFile']).",
						".Connection::GetSQLString(json_encode($saved['MediaFileConfig'])).",
						".Connection::GetSQLString($snapshot).",
						".Connection::GetSQLString($type).",
						".intval($sortOrder).")";
					if ($stmt->Execute($query))
					{
						$sortOrder++;
					}
					else
					{
						$fileList[$i]["ErrorInfo"] = GetTranslation("sql-error");
						@unlink(GALLERY_IMAGE_DIR."media/".$saved['MediaFile']);
						if ($snapshot)
						{
							@unlink(GALLERY_IMAGE_DIR."media/".$snapshot);
						}
					}
				}
			}

			// Prepare message info
			$failed = 0;
			$saved = 0;
			for ($i = 0; $i < count($fileList); $i++)
			{
				if (isset($fileList[$i]["ErrorInfo"]) && $fileList[$i]["error"] != 4)
				{
					$this->AddError($fileList[$i]["ErrorInfo"], $this->module);
					$failed++;
				}
			}
			$saved = count($fileList) - $failed;
			if ($saved == 0)
			{
				$this->AddError("media-save-failed", $this->module, array("Saved" => $saved, "Failed" => $failed));
				return false;
			}
			else if ($failed > 0)
			{
				$this->AddMessage("media-save-partial", $this->module, array("Saved" => $saved, "Failed" => $failed));
				return true;
			}
			else
			{
				$this->AddMessage("media-save-complete", $this->module, array("Saved" => $saved));
				return true;
			}
		}
		else
		{
			$this->LoadErrorsFromObject($fileSys);
			return false;
		}
	}
}

?>
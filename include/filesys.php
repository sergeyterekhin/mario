<?php

es_include("localobjectlist.php");

define('RESIZE_NO_RESIZE', 0);
define('RESIZE_PROPORTIONAL', 1);
define('RESIZE_PROPORTIONAL_FIXED_WIDTH', 2);
define('RESIZE_PROPORTIONAL_FIXED_HEIGHT', 3);
define('RESIZE_NOCUT_TOP_LEFT', 4);
define('RESIZE_NOCUT_CENTER', 5);
define('RESIZE_NOCUT_BOTTOM_RIGHT', 6);
define('RESIZE_CUT_TOP_LEFT', 7);
define('RESIZE_CUT_CENTER', 8);
define('RESIZE_CUT_BOTTOM_RIGHT', 9);
define('RESIZE_NOCUTDRAW_TOP_LEFT', 10);
define('RESIZE_NOCUTDRAW_CENTER', 11);
define('RESIZE_NOCUTDRAW_BOTTOM_RIGHT', 12);
define('RESIZE_PROPORTIONAL_CROP', 13);

class FileSys extends LocalObject
{
	function Upload($paramName, $toDir, $saveOriginalFileName = false, $acceptMimeTypes = array('image/png', 'image/x-png', 'image/gif', 'image/jpeg', 'image/pjpeg'))
	{
		if (!isset($_FILES[$paramName]))
		{
			return false;
		}

		$files = array();
		if (is_array($_FILES[$paramName]["name"]))
		{
			foreach ($_FILES[$paramName] as $k => $v)
			{
				for ($i = 0; $i < count($v); $i++)
				{
					$files[$i][$k] = $v[$i];
				}
			}
			$single = false;
		}
		else
		{
			$files[] = $_FILES[$paramName];
			$single = true;
		}

		$uploaded = false;
		for ($i = 0; $i < count($files); $i++)
		{
			if (!($files[$i]["name"] == '' && $files[$i]["error"] == 4 && $files[$i]["size"] == 0))
			{
				$uploaded = true;
			}
		}

		if (!$uploaded)
		{
			// If no uploaded files return false, but do not generate error
			return false;
		}

		for ($i = 0; $i < count($files); $i++)
		{
			if ($files[$i]["error"] > 0)
			{
				$files[$i]["ErrorInfo"] = GetTranslation("filesys-file-upload-error", array("ErrorNumber" => $files[$i]["error"]));
				continue;
			}

			if (!preg_match('/\.([^.]*?)$/i' , $files[$i]['name'], $extension))
			{
				$files[$i]["ErrorInfo"] = GetTranslation("filesys-incorrect-file-name", array('FileName' => $files[$i]['name']));
				continue;
			}

			if (!in_array($files[$i]['type'], $acceptMimeTypes))
			{
				$files[$i]["ErrorInfo"] = GetTranslation("filesys-unsupported-file-mime-type", array('MimeType' => $files[$i]['type']));
				continue;
			}

			if ($saveOriginalFileName)
			{
				// $saveOriginalFileName - the name of the file we try to update
				// If it is equal to new file name -> skip error, just overwrite it
				$fileName = $files[$i]['name'];
				if (file_exists($toDir.$fileName) && $fileName !== $saveOriginalFileName)
				{
					$files[$i]["ErrorInfo"] = GetTranslation("filesys-file-exists", array('FileName' => $fileName, 'FolderName' => $toDir));
					continue;
				}
			}
			else
			{
				$fileName = $this->GenerateUniqueName($toDir, strtolower($extension[1]));
			}

			$files[$i]["FileName"] = $fileName;
			$files[$i]["FileExtension"] = strtolower($extension[1]);

			if (@move_uploaded_file($files[$i]["tmp_name"], $toDir.$fileName))
			{
				@chmod($toDir.$fileName, 0666);
			}
			else
			{
				$files[$i]["ErrorInfo"] = GetTranslation("filesys-copy-error", array('From' => $files[$i]["tmp_name"], 'To' => $toDir.$fileName));
				continue;
			}
		}

		if ($single)
		{
			if (isset($files[0]["ErrorInfo"]))
			{
				$this->AddError($files[0]["ErrorInfo"]);
				return false;
			}
			else
			{
				return $files[0];
			}
		}
		else
		{
			return $files;
		}
	}

	function RandStr($size)
	{
		$feed = "0123456789abcdefghijklmnopqrstuvwxyz";
		$randStr = "";
		for ($i = 0; $i < $size; $i++)
		{
			$randStr .= substr($feed, rand(0, strlen($feed) - 1), 1);
		}
		return $randStr;
	}

	function GenerateUniqueName($toDir, $extension)
	{
		$fileName = $this->RandStr(10).'.'.$extension;
		if (file_exists($toDir.$fileName))
		{
			return $this->GenerateUniqueName($toDir, $extension);
		}
		else
		{
			return $fileName;
		}
	}

	function Resize($from, $to, $newW, $newH, $resize, $cropX1 = null, $cropY1 = null, $cropX2 = null, $cropY2 = null)
	{
		if (!file_exists($from))
		{
			$this->AddError("filesys-file-doesnt-exist");
			return false;
		}

		if (!$this->CheckFunction("getimagesize")) return false;
		$imgInfo = @getimagesize($from);

		if (!$imgInfo)
		{
			$this->AddError('filesys-getimagesize-error', array('File' => $from));
			return false;
		}

		$origW = $imgInfo[0];
		$origH = $imgInfo[1];
		$mimeType = $imgInfo['mime'];

		if ($newW > 0 && $newH > 0 && ($origW != $newW || $origH != $newH) && ($resize != RESIZE_NO_RESIZE))
		{
			$dstX = 0;
			$dstY = 0;
			$srcX = 0;
			$srcY = 0;
			$dstW = $newW;
			$dstH = $newH;
			$srcW = $origW;
			$srcH = $origH;

			if($resize == RESIZE_PROPORTIONAL_CROP && (!$cropX2 || !$cropY2))
				$resize = RESIZE_CUT_CENTER;
			
			$flag = true;
			switch($resize)
			{
				case RESIZE_PROPORTIONAL:
					if ($origW/$newW > $origH/$newH)
					{
						$k = $newW/$origW;
						$dstH = round($origH*$k);
					}
					else
					{
						$k = $newH/$origH;
						$dstW = round($origW*$k);
					}
					$flag = false;
					break;
				case RESIZE_PROPORTIONAL_FIXED_WIDTH:
					$k = $newW/$origW;
					$dstH = round($origH*$k);
					$flag = false;
					break;
				case RESIZE_PROPORTIONAL_FIXED_HEIGHT:
					$k = $newH/$origH;
					$dstW = round($origW*$k);
					$flag = false;
					break;
				case RESIZE_NOCUT_TOP_LEFT:
					if ($origW/$newW > $origH/$newH)
					{
						$k = $newW/$origW;
						$dstH = round($origH*$k);
					}
					else
					{
						$k = $newH/$origH;
						$dstW = round($origW*$k);
					}
					break;
				case RESIZE_NOCUT_CENTER:
					if ($origW/$newW > $origH/$newH)
					{
						$k = $newW/$origW;
						$dstH = round($origH*$k);
						$dstY = round(($newH - $dstH)/2);
					}
					else
					{
						$k = $newH/$origH;
						$dstW = round($origW*$k);
						$dstX = round(($newW - $dstW)/2);
					}
					break;
				case RESIZE_NOCUT_BOTTOM_RIGHT:
					if ($origW/$newW > $origH/$newH)
					{
						$k = $newW/$origW;
						$dstH = round($origH*$k);
						$dstY = $newH - $dstH;
					}
					else
					{
						$k = $newH/$origH;
						$dstW = round($origW*$k);
						$dstX = $newW - $dstW;
					}
					break;
				case RESIZE_CUT_TOP_LEFT:
					if ($origW/$newW > $origH/$newH)
					{
						$k = $origH/$newH;
						$srcW = round($newW*$k);
					}
					else
					{
						$k = $origW/$newW;
						$srcH = round($newH*$k);
					}
					break;
				case RESIZE_CUT_CENTER:
					if ($origW/$newW > $origH/$newH)
					{
						$k = $origH/$newH;
						$srcW = round($newW*$k);
						$srcX = round(($origW - $srcW)/2);
					}
					else
					{
						$k = $origW/$newW;
						$srcH = round($newH*$k);
						$srcY = round(($origH - $srcH)/2);
					}
					break;
				case RESIZE_CUT_BOTTOM_RIGHT:
					if ($origW/$newW > $origH/$newH)
					{
						$k = $origH/$newH;
						$srcW = round($newW*$k);
						$srcX = $origW - $srcW;
					}
					else
					{
						$k = $origW/$newW;
						$srcH = round($newH*$k);
						$srcY = $origH - $srcH;
					}
					break;
				case RESIZE_NOCUTDRAW_TOP_LEFT:
					if ($origW > $newW || $origH > $newH)
					{
						if ($origW/$newW > $origH/$newH)
						{
							$k = $newW/$origW;
							$dstH = round($origH*$k);
						}
						else
						{
							$k = $newH/$origH;
							$dstW = round($origW*$k);
						}
					}
					else
					{
						$dstW = $origW;
						$dstH = $origH;
					}
					break;
				case RESIZE_NOCUTDRAW_CENTER:
					if ($origW > $newW || $origH > $newH)
					{
						if ($origW/$newW > $origH/$newH)
						{
							$k = $newW/$origW;
							$dstH = round($origH*$k);
							$dstY = round(($newH - $dstH)/2);
						}
						else
						{
							$k = $newH/$origH;
							$dstW = round($origW*$k);
							$dstX = round(($newW - $dstW)/2);
						}
					}
					else
					{
						$dstW = $origW;
						$dstH = $origH;
						$dstX = $newW/2 - $origW/2;
						$dstY = $newH/2 - $origH/2;
					}
					break;
				case RESIZE_NOCUTDRAW_BOTTOM_RIGHT:
					if ($origW > $newW || $origH > $newH)
					{
						if ($origW/$newW > $origH/$newH)
						{
							$k = $newW/$origW;
							$dstH = round($origH*$k);
							$dstY = $newH - $dstH;
						}
						else
						{
							$k = $newH/$origH;
							$dstW = round($origW*$k);
							$dstX = $newW - $dstW;
						}
					}
					else
					{
						$dstW = $origW;
						$dstH = $origH;
						$dstX = $newW - $origW;
						$dstY = $newH - $origH;
					}
					break;
				case RESIZE_PROPORTIONAL_CROP:
					if ($origW/$newW > $origH/$newH)
					{
						$k = $newW/$origW;
						$dstH = round($origH*$k);
					}
					else
					{
						$k = $newH/$origH;
						$dstW = round($origW*$k);
					}
					break;
			}

			if (!$this->CheckFunction("imagecreatetruecolor")) return false;

			if (!$this->CheckFunction("imagecopyresampled")) return false;

			if ($flag)
				$dstImg = imagecreatetruecolor($newW, $newH);
			else
				$dstImg = imagecreatetruecolor($dstW, $dstH);

			
			if ($bgString = GetFromConfig("ImageBackground"))
			{
				$rgbBg = explode(",", $bgString);
				if (count($rgbBg == 3))
					imagefill($dstImg, 0, 0, imagecolorallocate($dstImg, $rgbBg[0], $rgbBg[1], $rgbBg[2]));
			}
			
			@imagecolortransparent($dstImg, imagecolorallocate($dstImg, 0, 0, 0));

			switch ($mimeType)
			{
				case "image/jpeg":
				case "image/pjpeg":
					if (!$this->CheckFunction("imagecreatefromjpeg")) return false;
					$srcImg = imagecreatefromjpeg($from);
					imagecopyresampled($dstImg, $srcImg, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
					
					if ($resize == RESIZE_PROPORTIONAL_CROP)
					{	
						imagecopyresampled($dstImg, $srcImg, 0, 0, $cropX1, $cropY1, $newW, $newH, $cropX2 - $cropX1, $cropY2 - $cropY1);
					}
					
					if (!$this->CheckFunction("imagejpeg")) return false;
					imagejpeg($dstImg, $to, 90);
					@chmod($to, 0666);
					break;
				case "image/gif":
					if (!$this->CheckFunction("imagecreatefromgif")) return false;
					$srcImg = imagecreatefromgif($from);
					imagecopyresampled($dstImg, $srcImg, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
					
					if ($resize == RESIZE_PROPORTIONAL_CROP)
					{	
						imagecopyresampled($dstImg, $srcImg, 0, 0, $cropX1, $cropY1, $newW, $newH, $cropX2 - $cropX1, $cropY2 - $cropY1);
					}
					
					if (!$this->CheckFunction("imagegif")) return false;
					imagegif($dstImg, $to);
					@chmod($to, 0666);
					break;
				case "image/png":
				case "image/x-png":
					if (!$this->CheckFunction("imagecreatefrompng")) return false;
					$srcImg = imagecreatefrompng($from);

					imageAlphaBlending($dstImg, false);
					imageSaveAlpha($dstImg, true);

					imagecopyresampled($dstImg, $srcImg, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
					
					if ($resize == RESIZE_PROPORTIONAL_CROP)
					{	
						imagecopyresampled($dstImg, $srcImg, 0, 0, $cropX1, $cropY1, $newW, $newH, $cropX2 - $cropX1, $cropY2 - $cropY1);
					}
					
					if (!$this->CheckFunction("imagepng")) return false;
					imagepng($dstImg, $to);
					@chmod($to, 0666);
					break;
				default:
					$this->AddError("filesys-unsupported-image-mime-type", array('MimeType' => $mimeType));
					break;
			}
		}
		else
		{
			$this->Copy($from, $to);
		}

		return !$this->HasErrors();
	}

	function MoveResized($from, $to, $newW, $newH, $resize)
	{
		$result = $this->Resize($from, $to, $newW, $newH, $resize);
		if ($result && $from != $to) @unlink($from);
		return $result;
	}

	function CopyResized($from, $to, $newW, $newH, $resize)
	{
		return $this->Resize($from, $to, $newW, $newH, $resize);
	}

	function Copy($from, $to)
	{
		if ($from == $to) return true;

		if (copy($from, $to))
		{
			@chmod($to, 0666);
			return true;
		}
		else
		{
			$this->AddError("filesys-copy-error", array('From' => $from, 'To' => $to));
			return false;
		}
	}

	function Move($from, $to)
	{
		if ($from == $to) return true;

		if ($this->Copy($from, $to))
		{
			@unlink($from);
			return true;
		}
		else
		{
			return false;
		}
	}

	function CheckFunction($functionName)
	{
		if (!function_exists($functionName))
		{
			$this->AddError("filesys-unsupported-image-function", array('Function' => $functionName.'()'));
			return false;
		}
		return true;
	}
}

?>
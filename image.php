<?php

require_once(dirname(__FILE__)."/include/init.php");
es_include("filesys.php");

$request = new LocalObject($_GET);

function BlankImage()
{
	header("Content-type: image/png");
	$im = @imagecreate(1, 1);
	$bg = imagecolorallocate($im, 255, 255, 255);
	imagepng($im);
	imagedestroy($im);
	exit;
}

function CachedImage($file)
{
	$imgInfo = @getimagesize($file);
	header("Content-Type: ".$imgInfo['mime']);
	readfile($file);
	exit;
}

if ($s = $request->GetProperty('s'))
{
	$chunks = explode("/", $s);
	if (count($chunks) > 1)
	{
		if (preg_match("/^([a-zA-Z0-9\-]+)-([a-zA-Z0-9]+)-(\d+)x(\d+)(_(\d+)_(\d+)_(\d+)_(\d+))*_(\d+)$/", $chunks[0], $matches))
		{
			$image = PROJECT_DIR."website/".$matches[1]."/var/".$matches[2]."/".implode("/", array_slice($chunks, 1));
			if (file_exists($image))
			{
				$fileSys = new FileSys();
				$cached = PROJECT_DIR."var/image/".md5($s).".img";

				$cacheExists = false;
				if (file_exists($cached))
				{
					$cacheExists = true;
					if ((filemtime($cached) + 604800) < date ('U') || filemtime($cached) < filemtime($image))
					{
						$cacheExists = false;
						@unlink($cached);
					}
				}

				if ($cacheExists)
				{
					CachedImage($cached);
				}
				else
				{
					if (strlen($matches[5]) > 0) 
					{											
						if ($fileSys->Resize($image, $cached, $matches[3], $matches[4], $matches[10], $matches[6], $matches[7], $matches[8], $matches[9]))
						{
							CachedImage($cached);
						}
					}						
					else
					{
						if ($fileSys->Resize($image, $cached, $matches[3], $matches[4], $matches[10]))
						{
							CachedImage($cached);
						}
					}
				}
			}
		}
	}
}

BlankImage();

?>
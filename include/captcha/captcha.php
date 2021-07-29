<?php

require_once(dirname(__FILE__)."/../init.php");

$symbolsList = '23456789BCDFGHJKMNPQSTVWXYZ';

$session =& GetSession();

$websiteDir = PROJECT_DIR."website/".WEBSITE_FOLDER."/var/captcha/";
if (!is_dir($websiteDir)) $websiteDir = dirname(__FILE__);

$dh = opendir($websiteDir);

$bgImages = array();
$ttfFonts = array();
while (($file = readdir($dh)) !== false)
{
	if (is_file($websiteDir.$file) && $file != "." && $file != "..")
	{
		$info = getimagesize($websiteDir.$file);
		if ($info)
			$bgImages[] = array('File' => $file, 'Info' => $info);
		else if (strtolower(substr($file, -4, 4)) == '.ttf')
			$ttfFonts[] = $websiteDir.$file;
	}
}

if (count($ttfFonts) > 0)
{
	srand((float)microtime()*1000000);
	shuffle($ttfFonts);
}

$srcImg = false;

if (count($bgImages) > 0)
{
	srand((float)microtime()*1000000);
	shuffle($bgImages);
	$bgFile = $websiteDir.$bgImages[0]['File'];
	$bgWidth = $bgImages[0]['Info'][0];
	$bgHeight = $bgImages[0]['Info'][1];

	switch ($bgImages[0]['Info']['mime'])
	{
		case "image/jpeg":
		case "image/pjpeg":
			$srcImg = @imagecreatefromjpeg($bgFile);
			break;
		case "image/gif":
			$srcImg = @imagecreatefromgif($bgFile);
			break;
		case "image/png":
		case "image/x-png":
			$srcImg = @imagecreatefrompng($bgFile);
			@imagecolortransparent($srcImg, imagecolorallocate($srcImg, 0, 0, 0));
			break;
	}
}

//$img = @imagecreatetruecolor(140, 50);
//@imagecolortransparent($img, imagecolorallocate($img, 0, 0, 0));

//if ($srcImg)
//{
//	@imagecopyresampled($img, $srcImg, 0, 0, 0, 0, 140, 50, $bgWidth, $bgHeight);
//	imagedestroy($srcImg);
//}

$data = explode('|', GetFromConfig("CaptchaTextColors"));
$textColors = array();
if (is_array($data) && count($data) > 0)
{
	for ($i = 0; $i < count($data); $i++)
	{
		$rgb = explode(',', $data[$i]);
		if (count($rgb) == 3)
		{
			for ($j = 0; $j < count($rgb); $j++)
			{
				if ($rgb[$j] < 0 || $rgb[$j] > 255) $rgb[$j] = 0;
			}
			$textColors[] = imagecolorallocate($srcImg, $rgb[0], $rgb[1], $rgb[2]);
		}
	}
}
if (count($textColors) == 0) $textColors = array(imagecolorallocate($srcImg, 0, 0, 0));
srand((float)microtime() * 1000000);
shuffle($textColors);

$code = array();
$symbols = abs(intval(GetFromConfig("CaptchaLettersNum")));
if ($symbols < 2) $symbols = 2;
if ($symbols > 8) $symbols = 8;
for ($i = 0; $i < $symbols; $i++)
{
	$code[] = substr($symbolsList, mt_rand(0, strlen($symbolsList)-1), 1);
}

if (count($ttfFonts) > 0)
{
	$size = abs(intval(GetFromConfig("CaptchaTextSize")));
	if ($size < 8 || $size > 30) $size = 20;
	$angle = abs(intval(GetFromConfig("CaptchaDanceLetters")));
	if ($angle > 90) $angle = 90;

	for ($i = 0; $i < count($code); $i++)
	{
		if ($angle > 0)
			$rotate = mt_rand(-1*$angle, $angle);
		else
			$rotate = 0;
		imagettftext($srcImg, $size, $rotate, ($i+1) * 27 - 5, 35, $textColors[mt_rand(0, count($textColors) - 1)], $ttfFonts[0], $code[$i]);
	}
}
else
{
	imagestring($srcImg, 5, 35, 18, implode(" ", $code), $textColors[0]);
}

$session->SetProperty("CaptchaCode", implode("", $code));
$session->SaveToDB();

header('Content-Type: image/png');
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")."GMT");
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

switch ($bgImages[0]['Info']['mime'])
{
	case "image/jpeg":
	case "image/pjpeg":
		imagejpeg($srcImg, null, 90);
		break;
	case "image/gif":
		imagegif($srcImg, null);
		break;
	case "image/png":
	case "image/x-png":
		imagepng($srcImg, null);
		break;
}
imagedestroy($srcImg);

?>
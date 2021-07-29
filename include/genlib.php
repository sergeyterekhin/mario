<?php

es_include("mysqli/connection5.php");
es_include("object/session.php");
es_include("localobject.php");
es_include("localobjectlist.php");
es_include("language.php");

function &GetConnection()
{
	static $instance;
	if (is_null($instance))
	{
		$language =& GetLanguage();
		$instance = new Connection(GetFromConfig("Host", "mysql"), GetFromConfig("Database", "mysql"), GetFromConfig("User", "mysql"), GetFromConfig("Password", "mysql"), $language->GetMySQLEncoding());
	}
	return $instance;
}

function GetStatement()
{
	$instance = GetConnection();
	return $instance->CreateStatement(MYSQLI_ASSOC, E_USER_WARNING);
}

function &GetLanguage()
{
	static $language;
	if (is_null($language))
	{
		$language = new Language();
	}
	return $language;
}

function &GetURLParser()
{
	static $parser;
	if (is_null($parser))
	{
		$parser = new URLParser();
	}
	return $parser;
}

function GetTranslation($key, $module = null, $replacements = array())
{
	$language =& GetLanguage();

	if (is_array($module))
	{
		$replacements = $module;
		$module = null;
	}

	return $language->GetTranslation($key, $module, $replacements);
}

function &GetSession()
{
	static $session;
	if (is_null($session))
	{
		$session = new Session("sm");
	}
	return $session;
}

function GetFromConfig($param, $section = "common")
{
	static $websiteConfig;

	if (is_null($websiteConfig) && defined("WEBSITE_FOLDER"))
	{
		$configFile = dirname(__FILE__)."/../website/".WEBSITE_FOLDER."/configure.ini";
		if (is_file($configFile))
			$websiteConfig = parse_ini_file($configFile, true);
	}

	if (isset($websiteConfig[$section][$param]))
		return $websiteConfig[$section][$param];
	else
		return null;
}

function LocalDate($format, $timeStamp = null)
{
	$text = array('F', 'M', 'l', 'D');
	$found = array();

	// Find text representations of week & month in date format
	for ($i = 0; $i < count($text); $i++)
	{
		$pos = strpos($format, $text[$i]);
		if ($pos !== false && substr($format, $pos - 1, 1) != "\\")
		{
			$format = str_replace($text[$i], "__\\".$text[$i]."__", $format);
			$found[] = $text[$i];
		}
	}

	if (is_null($timeStamp))
		$result = date($format);
	else
		$result = date($format, $timeStamp);

	// For found text representations replace it by correct language
	for ($i = 0; $i < count($found); $i++)
	{
		if (is_null($timeStamp))
			$textInLang = GetTranslation("date-".date($found[$i]));
		else
			$textInLang = GetTranslation("date-".date($found[$i], $timeStamp));
		$result = str_replace("__".$found[$i]."__", $textInLang, $result);
	}

	return $result;
}

function SmallString($str, $size)
{
	if (mb_strlen($str, "UTF-8") <= $size) return $str;
	return mb_substr($str, 0, $size-3, "UTF-8")."...";
}

function SendMailFromAdmin($to, $subject, $text, $attachments = array())
{
	es_include("phpmailer/phpmailer.php");

	$language =& GetLanguage();

	$phpmailer = new PHPMailer();

	$mailer = GetFromConfig("Mailer", "phpmailer");
	switch ($mailer)
	{
		case 'smtp':
			$phpmailer->IsSMTP();
			if (GetFromConfig("SMTP_Debug", "phpmailer"))
			{
				$phpmailer->SMTPDebug = true;
			}
			else
			{
				$phpmailer->SMTPDebug = false;
			}
			break;
		case 'mail':
			$phpmailer->IsMail();
			break;
		case 'sendmail':
			$phpmailer->IsSendmail();
			break;
	}

	$login = GetFromConfig("SMTP_Login", "phpmailer");
	$password = GetFromConfig("SMTP_Password", "phpmailer");
	$phpmailer->Host = GetFromConfig("SMTP_Host", "phpmailer");
	$phpmailer->Port = GetFromConfig("SMTP_Port", "phpmailer");

	if ($login && $password)
	{
		$phpmailer->SMTPAuth = true;
		$phpmailer->Username = $login;
		$phpmailer->Password = $password;
	}
	else
	{
		$phpmailer->SMTPAuth = false;
	}

	$phpmailer->ContentType = "text/html";
	$phpmailer->CharSet = $language->GetHTMLCharset();

	$phpmailer->From = GetFromConfig("FromEmail");
	$phpmailer->FromName = GetFromConfig("FromName");
	$phpmailer->AddReplyTo($phpmailer->From, $phpmailer->FromName);
	$phpmailer->Subject = $subject;
	$phpmailer->Body = $text;
	$phpmailer->AddAddress($to);

	if (is_array($attachments) && count($attachments) > 0)
	{
		foreach ($attachments as $v)
		{
			$phpmailer->AddAttachment($v);
		}
	}

	$result = true;

	if (!$phpmailer->Send())
	{
		$result = $phpmailer->ErrorInfo;
	}
	$phpmailer->ClearAllRecipients();

	// Log message
	$fp = fopen(PROJECT_DIR."website/".WEBSITE_FOLDER."/var/mail/".date("Y-m-d-H-i-s").".txt", "a");
	$logMessage = "Time: ".date("d.m.Y H:i:s")."\n";
	$logMessage .= "Status: ".($result === true ? "success" : "failed")."\n";
	$logMessage .= "Browser: ".$_SERVER['HTTP_USER_AGENT']."\n";
	$logMessage .= "From: ".GetFromConfig("FromEmail")."\n";
	$logMessage .= "From Name: ".GetFromConfig("FromName")."\n";
	$logMessage .= "To: ".$to."\n";
	$logMessage .= "Subject: ".$subject."\n";
	$logMessage .= "Body: ".$text."\n\n";
	fwrite($fp, $logMessage);	
	fclose($fp);

	return $result;
}

function GetDirPrefix($langCode = DATA_LANGCODE)
{
	$language =& GetLanguage();
	if ($lng = $language->GetDataLanguageByCode($langCode))
		return PROJECT_PATH.$lng['LangDir'];
	else
		return PROJECT_PATH;
}

function GetUrlPrefix($langCode = DATA_LANGCODE, $withLangDir = true)
{
	$language =& GetLanguage();
	if ($lng = $language->GetDataLanguageByCode($langCode))
	{
		if ($withLangDir)
			return "http://".$lng['HostName'].PROJECT_PATH.$lng['LangDir'];
		else
			return "http://".$lng['HostName'].PROJECT_PATH;
	}
	else
	{
		return "http://".$_SERVER["HTTP_HOST"].PROJECT_PATH;
	}
}

function GetLangDir($langCode)
{
	$language =& GetLanguage();
	if ($lng = $language->GetDataLanguageByCode($langCode))
		return $lng['LangDir'];
	else
		return "";
}

function Send301($newURL)
{
	$language =& GetLanguage();
	header("Content-Type: text/html; charset=".$language->GetHTMLCharset());
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: ".$newURL);
	echo "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">
<html><head>
<title>301 Moved Permanently</title>
</head><body>
<h1>Moved Permanently</h1>
<p>The document has moved <a href=\"".$newURL."\">here</a>.</p>
<hr>
".$_SERVER['SERVER_SIGNATURE']."</body></html>";
	exit();
}

function Send403()
{
	$language =& GetLanguage();
	header("Content-Type: text/html; charset=".$language->GetHTMLCharset());
	header("HTTP/1.1 403 Forbidden");

	$customFile = GetFromConfig("Error403Document");
	if (strlen($customFile) > 0 && is_file(PROJECT_DIR.$customFile))
	{
		$handle = fopen(PROJECT_DIR.$customFile, "rb");
		$contents = fread($handle, filesize(PROJECT_DIR.$customFile));
		fclose($handle);
		$contents = str_replace("%REQUEST_URI%", htmlspecialchars($_SERVER['REQUEST_URI']), $contents);
		$contents = str_replace("%SERVER_SIGNATURE%", htmlspecialchars($_SERVER['SERVER_SIGNATURE']), $contents);
		echo $contents;
	}
	else
	{
		echo "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">
<html><head>
<title>403 Forbidden</title>
</head><body>
<h1>Forbidden</h1>
<p>You don't have permission to access ".htmlspecialchars($_SERVER['REQUEST_URI'])." on this server.</p>
<hr>
".$_SERVER['SERVER_SIGNATURE']."</body></html>";
	}
	exit();
}

function Send404()
{
	$language =& GetLanguage();
	header("Content-Type: text/html; charset=".$language->GetHTMLCharset());
	header("HTTP/1.1 404 Not Found");

	$customTemplate = GetFromConfig("Error404Template");

	if (strlen($customTemplate) > 0 && is_file(PROJECT_DIR."website/".WEBSITE_FOLDER."/template/".$customTemplate))
	{
		$page = new Page();
		$header = array("MetaTitle" => "404 Page Not Found", "Page404" => "1");

		$module = new Module();
		$moduleList = $module->GetModuleList();
		for ($i = 0; $i < count($moduleList); $i++)
		{
			$data = $module->LoadForHeader($moduleList[$i]["Folder"]);
			if (is_array($data) && count($data) > 0)
			{
				// Put module data to header/footer
				$header = array_merge($header, $data);
				// Put module data to content (page.html) of the static pages
				$page->AppendFromArray($data);
			}
		}
		$publicPage = new PublicPage();
		$content = $publicPage->Load($customTemplate, $header);
		$content->LoadFromObject($page);
		$publicPage->Output($content);
		exit();		
	}
	else
	{
		echo "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL ".htmlspecialchars($_SERVER['REQUEST_URI'])." was not found on this server.</p>
<hr>
".$_SERVER['SERVER_SIGNATURE']."</body></html>";
	}
	exit();
}

function MultiSort($array)
{
	for ($i = 1; $i < func_num_args(); $i += 3)
	{
		$key = func_get_arg($i);
  		if (is_string($key)) $key = '"'.$key.'"';

		$order = true;
		if ($i + 1 < func_num_args())
			 $order = func_get_arg($i + 1);

		$type = 0;
		if ($i + 2 < func_num_args())
			 $type = func_get_arg($i + 2);
		switch($type)
		{
			 case 1: // Case insensitive natural.
				  $t = 'strcasecmp($a[' . $key . '], $b[' . $key . '])';
				  break;
			 case 2: // Numeric.
				  $t = '($a[' . $key . '] == $b[' . $key . ']) ? 0:(($a[' . $key . '] < $b[' . $key . ']) ? -1 : 1)';
				  break;
			 case 3: // Case sensitive string.
				  $t = 'strcmp($a[' . $key . '], $b[' . $key . '])';
				  break;
			 case 4: // Case insensitive string.
				  $t = 'strcasecmp($a[' . $key . '], $b[' . $key . '])';
				  break;
			 default: // Case sensitive natural.
				  $t = 'strnatcmp($a[' . $key . '], $b[' . $key . '])';
				  break;
		}
		usort($array, create_function('$a, $b', '; return ' . ($order ? '' : '-') . '(' . $t . ');'));
	}
	return $array;
}

function GetImageFields($prefix = '', $num)
{
	$result = array();
	for ($i = 1; $i < $num + 1; $i++)
	{
		$result[] = $prefix.$i;
		$result[] = $prefix.$i."Config";
	}
	if (count($result) > 0)
		return implode(", ", $result).", ";
	else
		return "";
}

function PrepareContentBeforeSave($content)
{
	// Replace PROJECT_PATH by <P_T_R> (no need to update content when you move site from one folder to another)
	if (strlen($content) > 0)
	{
		$content = str_replace("href=\"".PROJECT_PATH, "href=\"<P_T_R>", $content);
		$content = str_replace("href='".PROJECT_PATH, "href='<P_T_R>", $content);
		$content = str_replace("href=".PROJECT_PATH, "href=<P_T_R>", $content);

		$content = str_replace("src=\"".PROJECT_PATH, "src=\"<P_T_R>", $content);
		$content = str_replace("src='".PROJECT_PATH, "src='<P_T_R>", $content);
		$content = str_replace("src=".PROJECT_PATH, "src=<P_T_R>", $content);

		$content = str_replace("background=\"".PROJECT_PATH, "background=\"<P_T_R>", $content);
		$content = str_replace("background='".PROJECT_PATH, "background='<P_T_R>", $content);
		$content = str_replace("background=".PROJECT_PATH, "background=<P_T_R>", $content);
	}
	return $content;
}

function PrepareContentBeforeShow($content)
{
	// Replace <P_T_R> by PROJECT_PATH
	if (strlen($content) > 0)
	{
		$content = str_replace("<P_T_R>", PROJECT_PATH, $content);
	}
	return $content;
}

function LoadImageConfig($name, $folder, $configString)
{
	$imageConfig = explode(',', $configString);
	if (is_array($imageConfig) && count($imageConfig) > 0)
	{
		for ($i = 0; $i < count($imageConfig); $i++)
		{
			$data = explode('|', $imageConfig[$i]);
			if (is_array($data) && count($data) > 0)
			{
				if (isset($data[2]) && strlen($data[2]) > 0)
				{
					$params[$i] = array('Width' => 0, 'Height' => 0,
						'Resize' => 8, 'Name' => $name.$data[2], 'SourceName' => $data[2], 'Path' => '');

					$s = explode("x", $data[0]);
					if (count($s) == 2)
					{
						$params[$i]['Width'] = abs(intval($s[0]));
						$params[$i]['Height'] = abs(intval($s[1]));
					}

					// Resize way
					$params[$i]['Resize'] = abs(intval($data[1]));

					if($params[$i]['Resize'] == 13)
						$cropPart = "_#X1#_#Y1#_#X2#_#Y2#";
					else 
						$cropPart = "";
						
					$params[$i]['Path'] = PROJECT_PATH."images/".WEBSITE_FOLDER."-".$folder."-".$params[$i]['Width']."x".$params[$i]['Height'].$cropPart."_".$params[$i]['Resize']."/";
				}
			}
		}
	}
	return $params;
}

function InsertCropParams($path, $x1, $y1, $x2, $y2)
{
	$path = str_replace("#X1#", $x1, $path);
	$path = str_replace("#Y1#", $y1, $path);
	$path = str_replace("#X2#", $x2, $path);
	$path = str_replace("#Y2#", $y2, $path);
	return $path;
}

function LoadImageConfigValues($imageName, $value)
{
	$result = array();
	if(strlen($value) > 0)
	{
		$value = json_decode($value, true);
		if(!is_null($value))
		{
			foreach ($value as $k => $v)
			{
				if(is_array($v))
				{
					foreach ($v as $k2 => $v2)
					{
						$result[$imageName.$k.$k2] = $v2;
					}
				}
				else
				{
					$result[$imageName.$k] = $v;
				}
			}
		}
	}
	return $result;
}

function GetRealImageSize($resize, $origW, $origH, $dstW, $dstH)
{
	if (!($origW > 0 && $origH > 0 && $dstW > 0 && $dstH > 0))
		return array($dstW, $dstH);

	switch ($resize)
	{
		case RESIZE_PROPORTIONAL:
			if ($origW/$dstW > $origH/$dstH)
			{
				$k = $dstW/$origW;
				$dstH = round($origH*$k);
			}
			else
			{
				$k = $dstH/$origH;
				$dstW = round($origW*$k);
			}
			break;
		case RESIZE_PROPORTIONAL_FIXED_WIDTH:
			$k = $dstW/$origW;
			$dstH = round($origH*$k);
			break;
		case RESIZE_PROPORTIONAL_FIXED_HEIGHT:
			$k = $dstH/$origH;
			$dstW = round($origW*$k);
			break;
	}

	return array($dstW, $dstH);
}

function GetPageData($what)
{
	$default = array('ColorA' => '#000000', 'ColorI' => '#bcbcbc');

	$data = array(
		'page' => array('ColorA' => '#000000', 'ColorI' => '#bcbcbc'),
		'link' => array('ColorA' => '#0055ff', 'ColorI' => '#bcbcbc')
	);

	if (isset($data[$what])) return $data[$what];

	es_include('module.php');
	$module = new Module();
	$mList = $module->GetModuleList('', false, true);
	for ($i = 0; $i < count($mList); $i++)
	{
		if ($mList[$i]['Folder'] == $what)
			return $mList[$i];
	}

	return $default;
}

function GetPriority($level)
{
	switch($level)
	{
		case 1:
			$priority = 1;
			break;
		case 2:
			$priority = 0.8;
			break;
		case 3:
			$priority = 0.6;
			break;
		case 4:
			$priority = 0.4;
			break;
		default:
			$priority = 0.2;
			break;
	}
	return $priority;
}

function GetUploadMaxFileSize()
{
	$val = ini_get("upload_max_filesize");
	$val = strtolower(trim($val));
	$val = str_replace("m", " Mb", $val);
	$val = str_replace("g", " Gb", $val);
	$val = str_replace("k", " Kb", $val);

	return $val;
}


function ConvertURL2Value()
{
	$stmt = GetStatement();
	$page = new LocalObjectList();
	$page->LoadFromSQL("SELECT PageID, Config, Description FROM `page`");
	$pages = $page->GetItems();
	for ($i = 0; $i < count($pages); $i++)
	{
		$query = "UPDATE `page` SET Config=".Connection::GetSQLString(value_encode(urldecode($pages[$i]['Config'])))."
			,Description=".Connection::GetSQLString("Description=".value_encode(substr(urldecode($pages[$i]['Description']),12)))." 
			WHERE PageID=".$pages[$i]['PageID'];
		$stmt->Execute($query);		
	}
	
	$catalogItem = new LocalObjectList();
	$catalogItem->LoadFromSQL("SELECT ItemID, Description FROM `catalog_item`");
	$catalogItems = $catalogItem->GetItems();
	for ($i = 0; $i < count($catalogItems); $i++)
	{
	 	$query = "UPDATE `catalog_item` SET Description=".Connection::GetSQLString("Description=".value_encode(substr(urldecode($catalogItems[$i]['Description']),12)))." 
			WHERE ItemID=".$catalogItems[$i]['ItemID'];
		$stmt->Execute($query);		

	}

}

/**
* array_merge_recursive2()
*
* Similar to array_merge_recursive but keyed-valued are always overwritten.
* Priority goes to the 2nd array.
*
* @static yes
* @public yes
* @param $paArray1 array
* @param $paArray2 array
* @return array
*/
function array_merge_recursive2($paArray1, $paArray2)
{
   if (!is_array($paArray1) or !is_array($paArray2)) { return $paArray2; }
   foreach ($paArray2 AS $sKey2 => $sValue2)
   {
       $paArray1[$sKey2] = array_merge_recursive2(@$paArray1[$sKey2], $sValue2);
   }
   return $paArray1;
}

function value_encode($str)
{
	$str = str_replace("=", "%3D", $str);
	$str = str_replace("&", "%26", $str);
	return $str;
}

function value_decode($str)
{
	$str = str_replace("%3D", "=", $str);
	$str = str_replace("%26", "&", $str);
	return $str;
}

function GetValidStaticPath($staticPath, $table)
{
	$stmt = GetStatement();	
	$i = 1;
	$validStaticPath = $staticPath;
	$query = "SELECT COUNT(*) FROM `" . $table . "` WHERE StaticPath=".Connection::GetSQLString($staticPath);
	while(($result = $stmt->FetchField($query)) > 0)
	{
		if($result === false || $result === null)
			break;
		$i++;
		$validStaticPath = $staticPath . "-" . $i;
		$query = "SELECT COUNT(*) FROM `" . $table . "` WHERE StaticPath=".Connection::GetSQLString($validStaticPath);
	}
	return $validStaticPath;	
}

?>

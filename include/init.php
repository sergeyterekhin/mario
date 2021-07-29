<?php

if (version_compare(phpversion(), '5', '>='))
{
    define('SM_PHP_MODE', 5);
}
else
{
    define('SM_PHP_MODE', 4);
}

// PROJECT_DIR is used in es_include() function, so it must be defined before function
define("PROJECT_DIR", realpath(dirname(__FILE__)."/../")."/");
define("VLIB_CACHE_DIR", PROJECT_DIR."var/cache/");
define("XML_CACHE_DIR", PROJECT_DIR."var/xml/");

// genlib.php must be included here to define GetFromConfig() function
function es_include($fileName)
{
	require_once(PROJECT_DIR."include/".$fileName);
}
es_include("genlib.php");

// Define timezone for PHP 5.1.0 & higher (before ErrorHandler because ErrorHandler is using date functions)
if (version_compare(phpversion(), '5.1.0', '>='))
{
	$timeZone = 'GMT';
	if (is_file(dirname(__FILE__).'/../timezone.txt'))
	{
		$lines = file(dirname(__FILE__).'/../timezone.txt');
		if (is_array($lines) && count($lines) > 0 && strlen(trim($lines[0])) > 0)
			$timeZone = $lines[0];
	}
	date_default_timezone_set($timeZone);
}

// Set error handler
if (SM_PHP_MODE == 5)
	require_once(dirname(__FILE__)."/error_handler/error_handler5.php");
else
	require_once(dirname(__FILE__)."/error_handler/error_handler.php");
ErrorHandler::SetErrorHandler();

function RemoveQuotes($variable)
{
	if (is_array($variable))
	{
		foreach($variable as $key=>$value)
		{
			$variable[$key] = RemoveQuotes($value);
		}
	}
	else
	{
		$variable = stripslashes($variable);
	}
	return $variable;
}

if (get_magic_quotes_gpc())
{
	$_POST = RemoveQuotes($_POST);
	$_GET = RemoveQuotes($_GET);
	$_COOKIE = RemoveQuotes($_COOKIE);
	$_REQUEST = RemoveQuotes($_REQUEST);
}

// Cookie expire (before new Website() because constructor of Website is using this parameter)
define("COOKIE_EXPIRE", 3);

define("ADMIN_FOLDER", "admin");

// Identify website
es_include("website.php");
$website = new Website();

define("ADMIN_PATH", PROJECT_PATH.ADMIN_FOLDER."/");

// Index page (after new Website() because WEBSITE_ID is defined constructor of class Website and it is needed to load website configuration)
$indexPage = GetFromConfig("IndexPage");
if (is_null($indexPage) || strlen($indexPage) == 0)
	define("INDEX_PAGE", "index");
else
	define("INDEX_PAGE", $indexPage);

// HTML Extension (after new Website() because WEBSITE_ID is defined constructor of class Website and it is needed to load website configuration)
$htmlExtension = GetFromConfig("HTMLExtension");
if (is_null($htmlExtension) || strlen($htmlExtension) == 0)
	define("HTML_EXTENSION", ".html");
else
	define("HTML_EXTENSION", $htmlExtension);

// Other paths (after new Website() because PROJECT_PATH is defined constructor of class Website)
define("CKEDITOR_PATH", ADMIN_PATH."template/plugins/ckeditor/");

define("MENU_IMAGE_PATH", PROJECT_PATH."website/".WEBSITE_FOLDER."/var/page/");
define("MENU_IMAGE_DIR", PROJECT_DIR."website/".WEBSITE_FOLDER."/var/page/");
define("USER_IMAGE_DIR", PROJECT_DIR."website/".WEBSITE_FOLDER."/var/user/");

// User roles
define("INTEGRATOR", "integrator");
define("ADMINISTRATOR", "administrator");
define("MODERATOR", "moderator");
define("USER", "user");

$GLOBALS['moduleConfig'] = array();

// Initial database connection
$stmt = GetStatement();

?>
<?php

define("IS_ADMIN", true);
require_once(dirname(__FILE__)."/../include/init.php");
es_include("user.php");
es_include("localpage.php");
es_include("page.php");
es_include("pagelist.php");

$user = new User();
$user->ValidateAccess(array(INTEGRATOR, ADMINISTRATOR, MODERATOR));

// Function to determine correct PageID for DATA_LANGCODE
function DefineInitialPage($request)
{
	$page = new Page();
	if ($page->LoadByID($request->GetProperty("PageID")))
	{
		if ($page->GetProperty("Type") == 2 && $page->GetProperty("Link") == $request->GetProperty('load'))
		{
			return $page;			
		}
	}
	return null;
}

$request = new LocalObject(array_merge($_GET, $_POST));

$adminFile = dirname(__FILE__)."/../module/".$request->GetProperty('load')."/admin.php";

if ($request->GetProperty('load') && is_file($adminFile))
{
	$moduleURL = "module.php?load=".$request->GetProperty('load');
	require_once($adminFile);
}
else
{
	echo "Module is not specified";
}

?>
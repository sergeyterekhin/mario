<?php

define("IS_ADMIN", true);
require_once(dirname(__FILE__)."/../include/init.php");
es_include("localpage.php");
es_include("urlfilter.php");
es_include("user.php");

$auth = new User();
$auth->ValidateAccess(array(INTEGRATOR, ADMINISTRATOR, MODERATOR));

$request = new LocalObject(array_merge($_GET, $_POST));

$adminPage = new AdminPage();

$title = GetTranslation('admin-menu-template-robots');
$navigation = array(
	array("Title" => $title, "Link" => "robots.php")
);
$header = array(
	"Title" => $title,
	"Navigation" => $navigation
);
$content = $adminPage->Load("robots.html", $header);

if ($request->GetProperty("Save") == 1)
{
	//Save file and load message
	$fp = fopen(PROJECT_DIR."website/".WEBSITE_FOLDER."/robots.txt", "w");
	if(fwrite($fp, $request->GetProperty("RobotsContent")))
	{
		$content->SetVar("Message", GetTranslation("robots-saved"));
	}
	else
	{
		$content->SetVar("Error", GetTranslation("robots-error"));
	}
	fclose($fp);
	$content->SetVar("RobotsContent", $request->GetProperty("RobotsContent"));	
}
else 
{
	//Load robots.txt file
	$robotsContent = file_get_contents(PROJECT_DIR."website/".WEBSITE_FOLDER."/robots.txt");
	$content->SetVar("RobotsContent", $robotsContent);
}

$adminPage->Output($content);

?>
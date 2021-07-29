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

$title = GetTranslation('admin-menu-template-sitemap');
$navigation = array(
	array("Title" => $title, "Link" => "sitemap.php")
);
$header = array(
	"Title" => $title,
	"Navigation" => $navigation
);
$content = $adminPage->Load("sitemap.html", $header);

if ($request->GetProperty("Save") == 1)
{
	if(strlen($request->GetProperty("SitemapContent")) == 0)
	{
		unlink(PROJECT_DIR."website/".WEBSITE_FOLDER."/sitemap.xml");
		$content->SetVar("Message", GetTranslation("sitemap-saved"));
	}
	else 
	{
		//Save file and load message
		$fp = fopen(PROJECT_DIR."website/".WEBSITE_FOLDER."/sitemap.xml", "w");
		if(fwrite($fp, $request->GetProperty("SitemapContent")))
		{
			$content->SetVar("Message", GetTranslation("sitemap-saved"));
		}
		else
		{
			$content->SetVar("Error", GetTranslation("sitemap-error"));
		}
		fclose($fp);
	}
	$content->SetVar("SitemapContent", $request->GetProperty("SitemapContent"));	
}
else 
{
	$sitemapContent = file_get_contents(PROJECT_DIR."website/".WEBSITE_FOLDER."/sitemap.xml");
	$content->SetVar("SitemapContent", $sitemapContent);
}

$adminPage->Output($content);

?>
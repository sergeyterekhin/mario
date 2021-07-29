<?php

require_once(dirname(__FILE__)."/../include/init.php");
es_include("localpage.php");
es_include("page.php");
es_include("module.php");

$request = new LocalObject($_GET);
$page = new Page();

if ($page->LoadByID($request->GetProperty('PageID')))
{
	$header = array();
	$header["Title"] = $page->GetProperty("Title");
	$header["Description"] = $page->GetProperty("Description");
	for ($i = 1; $i < abs(intval(GetFromConfig("PageDescriptionCount"))); $i++)
	{
		$header["Description".($i+1)] = $page->GetProperty("Description".($i+1));
	}
	$header["MetaTitle"] = $page->GetProperty("MetaTitle");
	$header["MetaKeywords"] = $page->GetProperty("MetaKeywords");
	$header["MetaDescription"] = $page->GetProperty("MetaDescription");
	$header["Navigation"] = $page->GetPathAsArray();
	for ($i = 0; $i < count($page->params); $i++)
	{
		$header[$page->params[$i]["Name"]] = $page->GetProperty($page->params[$i]["Name"]);
		$header[$page->params[$i]["Name"]."Path"] = $page->GetProperty($page->params[$i]["Name"]."Path");
	}
	if ($page->GetProperty('StaticPath') == INDEX_PAGE)
		$header["IndexPage"] = 1;

	if ($page->GetProperty('Type') == 1)
	{
		$publicPage = new PublicPage();
		$content = $publicPage->Load($page->GetProperty("Template"), $header, $page->GetProperty("PageID"));
		$content->SetLoop("Navigation", $header["Navigation"]);
		$content->LoadFromObject($page);

		if ($page->GetProperty("StaticPath") == INDEX_PAGE)
			$content->SetVar("IndexPage", 1);

		$pageURL = parse_url($_SERVER['REQUEST_URI']);
		$content->SetVar("PageURL", $pageURL["path"]);

		$publicPage->Output($content);
	}
	else if ($page->GetProperty('Type') == 2)
	{
		$header['Module'] = $page->GetProperty('Link');
		$header['Content'] = $page->GetProperty('Content');

		$pathToModule = array();
		$a = $header["Navigation"];
		array_shift($a);
		for ($i = 0; $i < count($a); $i++)
		{
			$pathToModule[] = $a[$i]['StaticPath'];
		}

		$module = new Module();
		$urlParser =& GetURLParser();
		$urlParser->Emulate();

		if (!$module->LoadForPublic($page->GetProperty('Link'), $page->GetProperty("Template"), $pathToModule, array(), $header, $page->GetProperty("PageID")))
		{
			Send404();
		}
	}
	else
	{
		Send404();
	}
}
else
{
	Send404();
}

?>
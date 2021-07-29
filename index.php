<?php
require_once(dirname(__FILE__)."/include/init.php");
es_include("localpage.php");
es_include("page.php");
es_include("module.php");
/*@var parser URLParser */
$urlParser =& GetURLParser();
$path = $urlParser->GetShortPathAsArray();
$fixedPath = $urlParser->GetFixedPathAsArray();

$levels = 0;
$header = array();

$page = new Page();
$pageFound = false;

$module = new Module();
$moduleFound = false;

$pageDescriptionCount = abs(intval(GetFromConfig("PageDescriptionCount")));

if (count($fixedPath) == 1 && $fixedPath[0] == INDEX_PAGE)
{
	$newURL = $urlParser->GetRedirectURL();
	if ($newURL)
	{
		Send301($newURL);
	}
	// Load index page for selected language
	$pageFound = $page->LoadIndexPage();
	if ($pageFound)
	{
		if ($module->ModuleExists($page->GetProperty("Link")))
			$moduleFound = $page->GetProperty("Link");
		$levels = 1;
		$header["Title"] = $page->GetProperty("Title");
		$header["Description"] = $page->GetProperty("Description");
		$header["StaticPath"] = $page->GetProperty("StaticPath");
		$header["Template"] = $page->GetProperty("Template");
		$header["Content"] = $page->GetProperty("Content");
		for ($i = 1; $i < $pageDescriptionCount; $i++)
		{
			$header["Description".($i+1)] = $page->GetProperty("Description".($i+1));
		}
		$header["TitleH1"] = $page->GetProperty("TitleH1");
		$header["MetaTitle"] = $page->GetProperty("MetaTitle");
		$header["MetaKeywords"] = $page->GetProperty("MetaKeywords");
		$header["MetaDescription"] = $page->GetProperty("MetaDescription");
		$header["Navigation"] = $page->GetPathAsArray();
		$header["MenuImageCount"] = count($page->params);
		for ($i = 0; $i < count($page->params); $i++)
		{
			$header[$page->params[$i]["Name"]] = $page->GetProperty($page->params[$i]["Name"]);
			$header[$page->params[$i]["Name"]."Path"] = $page->GetProperty($page->params[$i]["Name"]."Path");
		}
		$header["IndexPage"] = 1;
	}
}
else
{
	// Try to find page by path
	$query = "SELECT PageID, StaticPath, Path2Root, Link,
		Title, MetaKeywords, MetaDescription
		FROM `page` WHERE
			WebsiteID=".intval(WEBSITE_ID)."
			AND StaticPath IN (".implode(", ", Connection::GetSQLArray($fixedPath)).")
			AND LanguageCode=".Connection::GetSQLString(DATA_LANGCODE)."
			AND Path2Root<>'#' AND StaticPath IS NOT NULL
		ORDER BY Path2Root";
	$pageList = new PageList();
	$pageList->LoadFromSQL($query);
	$pages = array();
	$currentPageID = null;
	$modulePageID = null;
	foreach ($pageList->GetItems() as $item)
	{
		$p = explode("#", $item["Path2Root"]);
		$c = count($p);
		// First page of the path is found
		if ($item["StaticPath"] == $fixedPath[0] && $c == 3)
		{
			$currentPageID = $item["PageID"];
			$levels++;
			if ($module->ModuleExists($item["Link"]))
			{
				$moduleFound = $item["Link"];
				$modulePageID = $item["PageID"];
			}
			continue;
		}
		// Find other pages
		if (!is_null($currentPageID) && count($fixedPath) > $levels)
		{
			if ($item["StaticPath"] == $fixedPath[$levels] && $p[$c - 2] == $currentPageID)
			{
				$currentPageID = $item["PageID"];
				$levels++;
				if ($module->ModuleExists($item["Link"]))
				{
					$moduleFound = $item["Link"];
					$modulePageID = $item["PageID"];
				}
			}
		}
	}

	if ($moduleFound && $modulePageID != $currentPageID)
		$moduleFound = false;

	if ($levels == count($fixedPath) || $moduleFound != false)
	{
		// Static pages have text/html presentation only
		if ($urlParser->fileExtension != HTML_EXTENSION && $moduleFound == false)
		{
			Send404();
		}

		$pageFound = $page->LoadByID($currentPageID);

		// Redirect code
		if ($page->GetCountChildren() > 0)
		{
			$newURL = $urlParser->GetRedirectURL();
			if ($newURL)
			{
				Send301($newURL);
			}
			else if (($moduleFound == false && count($path) == count($fixedPath)) ||
				($moduleFound != false && $levels == count($path) && $urlParser->fileExtension == HTML_EXTENSION))
			{
				// URL must look like /page/index.htm
				Send301($page->GetPageURL());
			}
		}
		else
		{
			$newURL = $urlParser->GetRedirectURL();
			if ($moduleFound != false)
			{
				// Redirect only in case we are not inside module
				if ($newURL && count($page->GetPathAsArray()) - 1 == count($fixedPath))
				{
					Send301($newURL);
				}
				else if ($levels + 1 == count($path) && count($path) != count($fixedPath))
				{
					// URL must look like /page.htm
					Send301($page->GetPageURL());
				}
			}
			else if (count($path) != count($fixedPath) || $newURL)
			{
				// URL must look like /page.htm
				Send301($page->GetPageURL());
			}
		}

		$header["Title"] = $page->GetProperty("Title");
		$header["Description"] = $page->GetProperty("Description");
		$header["StaticPath"] = $page->GetProperty("StaticPath");
		$header["Template"] = $page->GetProperty("Template");
		$header["Content"] = $page->GetProperty("Content");
		for ($i = 1; $i < $pageDescriptionCount; $i++)
		{
			$header["Description".($i+1)] = $page->GetProperty("Description".($i+1));
		}
		$header["TitleH1"] = $page->GetProperty("TitleH1");
		$header["MetaTitle"] = $page->GetProperty("MetaTitle");
		$header["MetaKeywords"] = $page->GetProperty("MetaKeywords");
		$header["MetaDescription"] = $page->GetProperty("MetaDescription");
		$header["Navigation"] = $page->GetPathAsArray();
		$header["MenuImageCount"] = count($page->params);
		for ($i = 0; $i < count($page->params); $i++)
		{
			$header[$page->params[$i]["Name"]] = $page->GetProperty($page->params[$i]["Name"]);
			$header[$page->params[$i]["Name"]."Path"] = $page->GetProperty($page->params[$i]["Name"]."Path");
		}
	}
}

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

// Load language scripts
$header["JavaScripts"] = array(
	array("JavaScriptFile" => PROJECT_PATH."include/language/language.js.php".($moduleFound != false ? "?Module=".$page->GetProperty('Link') : ""))
	);
$header['CurrentPageURL'] = $page->GetPageURL(false);

if ($moduleFound != false)
{
	$pathToModule = array_slice($fixedPath, 0, $levels);
	$pathInsideModule = array_slice($path, $levels);

	$header['Module'] = $page->GetProperty('Link');
	$header['Content'] = $page->GetProperty('Content');

	if (!$module->LoadForPublic($moduleFound, $page->GetProperty("Template"), $pathToModule, $pathInsideModule, $header, $page->GetProperty("PageID"), $page->GetConfig()))
	{
		Send404();
	}
}
else if ($pageFound)
{
	$publicPage = new PublicPage();
	$content = $publicPage->Load($page->GetProperty("Template"), $header, $page->GetProperty("PageID"));
	$content->SetLoop("Navigation", $header["Navigation"]);
	$content->LoadFromObject($page);

	if ($page->GetProperty("StaticPath") == INDEX_PAGE)
		$content->SetVar("IndexPage", 1);

	$publicPage->Output($content);
}
else
{
	if (count($fixedPath) == 1 && $fixedPath[0] == INDEX_PAGE)
	{
		echo "You have to create page with URL ".GetDirPrefix()."<b>".INDEX_PAGE.HTML_EXTENSION."</b>";
	}
	else
	{
		Send404();
	}
}

?>
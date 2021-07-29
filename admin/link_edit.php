<?php

define("IS_ADMIN", true);
require_once(dirname(__FILE__)."/../include/init.php");
es_include("localpage.php");
es_include("page.php");
es_include("pagelist.php");
es_include("user.php");

$user = new User();
$user->ValidateAccess(array(INTEGRATOR, ADMINISTRATOR, MODERATOR));

$adminPage = new AdminPage();
$title = GetTranslation("title-link-edit");
$styleSheets = array(
	
);
$javaScripts = array(
	array("JavaScriptFile" => ADMIN_PATH."template/js/staticpath.js"),
	array("JavaScriptFile" => CKEDITOR_PATH."ckeditor.js"),
	array("JavaScriptFile" => CKEDITOR_PATH."ajexFileManager/ajex.js"),
	array("JavaScriptFile" => ADMIN_PATH."template/js/link.js")
);
$navigation = array(
	array("Title" => GetTranslation("title-site-structure"), "Link" => "page_tree.php"),
	array("Title" => $title, "Link" => "link_edit.php")
);
$header = array(
	"Title" => $title,
	"Navigation" => $navigation,
	"StyleSheets" => $styleSheets,
	"JavaScripts" => $javaScripts
);
$content = $adminPage->Load("link_edit.html", $header);
$content->SetLoop("Navigation", $navigation);

$request = new LocalObject(array_merge($_GET, $_POST));

$page = new Page();

if ($page->LoadByID($request->GetProperty("PageID")))
{

	// Here we can edit only page with type = 3 (link)
	if ($page->GetProperty("Type") != 3)
	{
		header("location: page_tree.php");
		exit();
	}
	$initialPageID = $page->GetProperty("PageID");
	$initialLanguageCode = $page->GetProperty("LanguageCode");
	$firstLanguage = array();
}
else
{
	$page->SetProperty("LanguageCode", DATA_LANGCODE);
}

if ($request->GetProperty("Save"))
{
	// We have to save current language of the page to not change it during saving
	$languageCode = $page->GetProperty("LanguageCode");
	$page->LoadFromObject($request);
	$page->SetProperty("LanguageCode", $languageCode);

	if ($page->Save())
	{
		$page->OpenParents();
		header("Location: page_tree.php");
		exit();
	}
	else
	{
		$content->LoadErrorsFromObject($page);
	}
}

if (!$page->GetProperty('Path2Root') && $request->GetProperty('Parent'))
{
	$page->SetProperty('Path2Root', '#'.$request->GetProperty('Parent').'#');
	$page->SetProperty('ParentID', $request->GetProperty('Parent'));
}

$content->LoadFromObject($page);

$content->SetLoop("MenuImages", $page->GetMenuImages());

$parentLists = $page->GetParentLists(3);
$content->SetLoop("Parents", $parentLists);
if (isset($parentLists[0]['PageList']))
{
	$menuList = array();
	for ($i = 0; $i < count($parentLists[0]['PageList']); $i++)
	{
		$menuList[$i]['PageID'] = $parentLists[0]['PageList'][$i]['PageID'];
		$menuList[$i]['ImageList'] = array();
		for ($j = 0; $j < count($page->params); $j++)
		{
			$menuList[$i]['ImageList'][] = array('Key' => $j+1, 'Value' => $parentLists[0]['PageList'][$i]['MenuImage'.($j+1)]);
		}
	}
	$content->SetLoop("MenuList", $menuList);
	$content->SetVar("MenuImagesCount", count($page->params));
}

$pageList = new PageList();
$pageList = $pageList->GetPageListForParentSelection();
for($i = 0; $i < count($pageList); $i++)
{
	if($pageList[$i]["PageID"] == $page->GetProperty("ParentID") || $pageList[$i]["PageID"] == $request->GetProperty("ParentID"))
	{
		$pageList[$i]["Selected"] = 1;
		break;
	}
}
$content->SetLoop("ParentPageList", $pageList);

$n = abs(intval(GetFromConfig("PageDescriptionCount")));
$descriptions = array();
if ($n > 0)
{
	for ($i = 0; $i < $n; $i++)
	{
		if ($i > 0) $k = $i + 1; else $k = "";
		$descriptions[] = array("Name" => "Description[".$i."]",
			"Value" => $page->GetProperty("Description".$k),
			"Title" => GetTranslation("page-description".$k));
	}
}
$content->SetLoop("DescriptionList", $descriptions);

if ($content->GetVar('DescriptionList') || $content->GetVar('MenuImages'))
{
	$content->SetVar('Show2Tabs', true);
}

$pageList = new PageList();
$items = $pageList->GetPageListForLink();
$content->SetLoop("PageList", $items);

$content->SetVar("UploadMaxFileSize", GetTranslation("upload-max-file-size", array("UploadMaxFileSize" => GetUploadMaxFileSize())));

$adminPage->Output($content);

?>
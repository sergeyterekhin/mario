<?php

define("IS_ADMIN", true);
require_once(dirname(__FILE__)."/../include/init.php");
es_include("localpage.php");
es_include("page.php");
es_include("user.php");

$user = new User();
$user->ValidateAccess(array(INTEGRATOR, ADMINISTRATOR, MODERATOR));

$adminPage = new AdminPage();
$title = GetTranslation("title-module-edit");
$styleSheets = array(

);
$javaScripts = array(
    array("JavaScriptFile" => ADMIN_PATH."template/js/staticpath.js"),
	array("JavaScriptFile" => CKEDITOR_PATH."ckeditor.js"),
	array("JavaScriptFile" => CKEDITOR_PATH."ajexFileManager/ajex.js")
);
$navigation = array(
	array("Title" => GetTranslation("title-site-structure"), "Link" => "page_tree.php"),
	array("Title" => $title, "Link" => "module_edit.php")
);
$header = array(
	"Title" => $title,
	"Navigation" => $navigation,
	"StyleSheets" => $styleSheets,
	"JavaScripts" => $javaScripts
);
$content = $adminPage->Load("module_edit.html", $header);
$content->SetLoop("Navigation", $navigation);

$request = new LocalObject(array_merge($_GET, $_POST));

if ($request->GetProperty("BackURL"))
	$content->SetVar("BackURL", $request->GetProperty("BackURL"));
else
	$content->SetVar("BackURL", "page_tree.php");

$page = new Page();

if ($page->LoadByID($request->GetProperty("PageID")))
{
	// Here we can edit only page with type = 2 (module)
	if ($page->GetProperty("Type") != 2)
	{
		header("location: page_tree.php");
		exit();
	}
	$initialPageID = $page->GetProperty("PageID");
	$initialLanguageCode = $page->GetProperty("LanguageCode");
	$firstLanguage = array();
	$initialLink = $page->GetProperty("Link");
}
else
{
	$initialLink = $request->GetProperty("Link");
	$page->SetProperty("LanguageCode", DATA_LANGCODE);
}

$found = false;
es_include("module.php");
$module = new Module();
$mList = $module->GetModuleList($initialLink, false, true);
for ($i = 0; $i < count($mList); $i++)
{
	if ($mList[$i]["Folder"] == $initialLink)
	{
		$content->SetVar("ModuleTitle", $mList[$i]["Title"]);
		$content->SetVar("Link", $initialLink);
		$defaultConfig = $mList[$i]["Config"];
		$found = true;
		break;
	}
}

if (!$found)
{
	echo "Module '".$initialLink."' is not found";
	exit();
}

if ($request->GetProperty("Save"))
{
	// We have to save current language of the page to not change it during saving
	$languageCode = $page->GetProperty("LanguageCode");
	$oldConfig = $page->GetProperty("Config");

	$page->LoadFromObject($request);
	$page->SetProperty("LanguageCode", $languageCode);

	// Only integrator can set variable 'Config'
	if ($user->GetProperty('Role') == 'integrator')
	{
		$page->SetConfig($defaultConfig);
	}
	else
	{
		// LoadFromObject() above destroy variable 'Config', we return it back
		$page->SetProperty('Config', $oldConfig);
		// Load default config
		$page->SetConfig($defaultConfig);
	}

	if ($page->Save())
	{
		$page->OpenParents();
		if ($request->GetProperty("BackURL"))
			header("Location: ".$request->GetProperty("BackURL"));
		else
			header("Location: page_tree.php");
		exit();
	}
	else
	{
		$content->LoadErrorsFromObject($page);
	}
}
else
{
	// For new page load default config
	$page->SetProperty("Type", 2);
	$page->SetProperty("Link", $initialLink);
	$page->SetConfig($defaultConfig);
}

if (!$page->GetProperty('Path2Root') && $request->GetProperty('Parent'))
{
	$page->SetProperty('Path2Root', '#'.$request->GetProperty('Parent').'#');
	$page->SetProperty('ParentID', $request->GetProperty('Parent'));
}

$content->LoadFromObject($page);

$content->SetLoop("MenuImages", $page->GetMenuImages());

$parentLists = $page->GetParentLists(2);
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
$content->SetLoop("PageList", $pageList);

$content->SetVar('L_TitleH1', GetTranslation('title-h1'));
$content->SetVar('L_MetaTitle', GetTranslation('meta-title'));
$content->SetVar('L_MetaKeywords', GetTranslation('meta-keywords'));
$content->SetVar('L_MetaDescription', GetTranslation('meta-description'));

$parentURL = "";
if ($page->GetProperty("StaticPath") != INDEX_PAGE)
{
	$path = $page->GetPathAsArray();

	for ($i = 1; $i < count($path) - 1; $i++)
	{
		$parentURL .= $path[$i]["StaticPath"]."/";
	}
	if (!$page->GetProperty("PageID") && isset($path[$i]))
	{
		$parentURL .= $path[$i]["StaticPath"]."/";
	}
}
$content->SetVar("ParentURL", $parentURL);

if ($page->GetCountChildren() > 0 && $page->GetProperty("StaticPath") != INDEX_PAGE)
	$URLClose = "/".INDEX_PAGE.HTML_EXTENSION;
else
	$URLClose = HTML_EXTENSION;
$content->SetVar("URLClose", $URLClose);

$content->LoadModuleTemplateSets($initialLink, $page->GetProperty("Template"));

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
	$content->SetVar('Show3Tabs', true);
}

$content->SetVar("UploadMaxFileSize", GetTranslation("upload-max-file-size", array("UploadMaxFileSize" => GetUploadMaxFileSize())));

$content->SetVar('Integrator', ($user->GetProperty('Role') == 'integrator' ? true : false));

$content->SetVar('ConfigDetails', GetTranslation('config-details', $page->GetProperty("Link")));

$adminPage->Output($content);

?>
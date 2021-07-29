<?php

if (!defined('IS_ADMIN'))
{
	echo "Incorrect call to admin interface";
	exit();
}

require_once(dirname(__FILE__)."/init.php");
require_once(dirname(__FILE__)."/include/category_list.php");
require_once(dirname(__FILE__)."/include/category.php");
require_once(dirname(__FILE__)."/include/media_list.php");
require_once(dirname(__FILE__)."/include/media.php");
es_include("js_calendar/calendar.php");
es_include("urlfilter.php");

$module = $request->GetProperty('load');
$adminPage = new AdminPage($module);
$urlFilter = new URLFilter();

if ($request->IsPropertySet("CategoryID"))
{
	// Edit category
	$page = DefineInitialPage($request);
	if (is_null($page))
	{
		header("Location: ".$moduleURL);
		exit();
	}
	else
	{
		$request->SetProperty("PageID", $page->GetProperty("PageID"));
	}
	$config = $page->GetConfig();

	if ($request->GetIntProperty("CategoryID") > 0)
		$title = GetTranslation("title-category-edit", $module);
	else
		$title = GetTranslation("title-category-add", $module);

	$urlFilter->LoadFromObject($request, array('PageID'));
		
	$javaScripts = array(
		array("JavaScriptFile" => ADMIN_PATH."template/js/staticpath.js"),
		array("JavaScriptFile" => CKEDITOR_PATH."ckeditor.js"),
		array("JavaScriptFile" => CKEDITOR_PATH."ajexFileManager/ajex.js")
	);
	$navigation = array(
		array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
		array("Title" => GetTranslation("title-category-add", $module), "Link" => $moduleURL."&PageID=".$request->GetProperty("PageID")."&CategoryID=".$request->GetProperty('CategoryID'))
	);
	$header = array(
		"Title" => $title,
		"Navigation" => $navigation,
		"JavaScripts" => $javaScripts
	);
	$content = $adminPage->Load("category_edit.html", $header);

	$category = new GalleryCategory($module, $page->GetIntProperty("PageID"), $config);

	if ($request->GetProperty("Save"))
	{
		$category->LoadFromObject($request);
		if ($category->Save())
		{
			header("location: ".$moduleURL."&PageID=".$request->GetProperty("PageID"));
			exit();
		}
		else
		{
			$content->LoadErrorsFromObject($category);
		}
	}
	else
	{
		$category->LoadByID($request->GetProperty("CategoryID"));
	}

	$content->LoadFromObject($category);
	$content->SetLoop("CategoryImageParamList", $category->GetImageParams());

	$content->SetVar('L_TitleH1', GetTranslation('title-h1'));
	$content->SetVar('L_MetaTitle', GetTranslation('meta-title'));
	$content->SetVar('L_MetaKeywords', GetTranslation('meta-keywords'));
	$content->SetVar('L_MetaDescription', GetTranslation('meta-description'));
	
	$content->SetVar("ParamsForURL", $urlFilter->GetForURL());
	$content->SetVar("PageID", $request->GetIntProperty('PageID'));
	$content->SetVar("URLPrefix", $page->GetPagePrefix()."/");
	
	$calendar = new Calendar("Created", $category->GetProperty("Created"));
	$content->SetVar("CalendarField", $calendar->GetHTMLAsField());
}
else if ($request->IsPropertySet("PageID"))
{
	$page = DefineInitialPage($request);
	if (is_null($page))
	{
		header("Location: ".$moduleURL);
		exit();
	}
	else
	{
		$request->SetProperty("PageID", $page->GetProperty("PageID"));
	}
	$config = $page->GetConfig();

	$categoryList = new GalleryCategoryList($module, $config);
	
	if ($request->GetProperty("CategoryIDs") && $request->GetProperty("Do") == "Remove")
	{
		$categoryList->RemoveByCategoryIDs($request->GetProperty("CategoryIDs"));
	}
	
	$categoryList->Load($request);
	
	if($categoryList->GetCountTotalItems() > 0 && !$request->IsPropertySet("ViewCategoryID"))
	{
		//show category list
		$urlFilter->LoadFromObject($request, array('PageID'));
	
		$navigation = array(
			array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
			array("Title" => $page->GetProperty("Title"), "Link" => $moduleURL."&".$urlFilter->GetForURL())
		);
		$javaScripts = array(
			array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js")
		);
		$styleSheets = array(
			
		);
		$header = array(
			"Title" => $page->GetProperty("Title"),
			"Navigation" => $navigation,
			"JavaScripts" => $javaScripts,
			"StyleSheets" => $styleSheets
		);
		$content = $adminPage->Load("category_list.html", $header);
		
		$content->LoadFromObjectList("CategoryList", $categoryList);
		$content->LoadMessagesFromObject($categoryList);
		
		$content->SetVar("ParamsForURL", $urlFilter->GetForURL());
		$content->SetVar("ParamsForForm", $urlFilter->GetForForm());
		$content->SetVar("PageID", $page->GetProperty('PageID'));
	}
	else
	{
		//show item list	
		$urlFilter->LoadFromObject($request, array('PageID', 'ViewCategoryID'));
	
		$category = new GalleryCategory($module, $page->GetIntProperty("PageID"), $config);
		if ($category->LoadByID($request->GetProperty('ViewCategoryID')))
		{
			$navigation = array(
				array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
				array("Title" => $page->GetProperty("Title"), "Link" => $moduleURL."&".$urlFilter->GetForURL(array('ViewCategoryID'))),
				array("Title" => $category->GetProperty("Title"), "Link" => $moduleURL."&".$urlFilter->GetForURL())
			);
		}
		else
		{
			$navigation = array(
				array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
				array("Title" => $page->GetProperty("Title"), "Link" => $moduleURL."&".$urlFilter->GetForURL())
			);
			$request->SetProperty('ViewCategoryID', '0');
			$urlFilter->SetProperty('ViewCategoryID', '0');
		}
		$styleSheets = array(
			array("StyleSheetFile" => ADMIN_PATH."template/plugins/prettyphoto/prettyPhoto.css"),
			array("StyleSheetFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.css")
		);
		$javaScripts = array(
			array("JavaScriptFile" => ADMIN_PATH."template/plugins/prettyphoto/jquery.prettyPhoto.js"),
			array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js")
		);
		
		$header = array(
			"Title" => $page->GetProperty("Title"),
			"Navigation" => $navigation,
			"StyleSheets" => $styleSheets,
			"JavaScripts" => $javaScripts
		);
		$content = $adminPage->Load("media_list.html", $header);
	
		$mediaList = new GalleryMediaList($module, $page->GetProperty("PageID"), $config);
	
		if ($request->GetProperty('Action') == "Upload")
		{
			$media = new GalleryMedia($module, $page->GetIntProperty("PageID"), $config);
			$media->LoadFromObject($request);
			$media->Save();
			$content->LoadErrorsFromObject($media);
			$content->LoadMessagesFromObject($media);
		}
	
		$request->SetProperty("FullList", true);
		$mediaList->Load($request);
		$content->LoadFromObjectList("MediaList", $mediaList);
	
		$request->SetProperty('CategoryID', $request->GetProperty('ViewCategoryID'));
		$categoryList = new GalleryCategoryList($module, $config);
		$categoryList->Load($request);
		$content->LoadFromObjectList("CategoryList", $categoryList);
	
		$content->SetVar("NoCategory", $page->GetProperty('Title'));
	
		$content->SetVar('MTitle', GetTranslation('media-title', $module));
		$content->SetVar('MDescription', GetTranslation('media-description', $module));
		$content->SetVar('MCategory', GetTranslation('media-category', $module));
		$content->SetVar('MSortOrder', GetTranslation('media-sort-order', $module));
	
		$content->SetVar("Paging", $mediaList->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL()));
		$content->SetVar("ListInfo", GetTranslation('list-info1', array('Page' => $mediaList->GetItemsRange(), 'Total' => $mediaList->GetCountTotalItems())));
	
		$content->SetVar("PageID", $page->GetIntProperty('PageID'));
		$content->SetVar("CategoryID", $category->GetProperty('CategoryID'));
	
		$content->SetVar("ParamsForURL", $urlFilter->GetForURL());
		$content->SetVar("ParamsForForm", $urlFilter->GetForForm());
		$content->SetVar("ParamsForForm2", $urlFilter->GetForForm(array("ViewCategoryID")));
	}
}
else
{
	die('PageID param id not defined');
}

$adminPage->Output($content);

?>
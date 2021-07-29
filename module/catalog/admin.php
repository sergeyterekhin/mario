<?php

if (!defined('IS_ADMIN'))
{
	echo "Incorrect call to admin interface";
	exit();
}

require_once(dirname(__FILE__)."/init.php");
require_once(dirname(__FILE__)."/include/category_list.php");
require_once(dirname(__FILE__)."/include/category.php");
require_once(dirname(__FILE__)."/include/item_list.php");
require_once(dirname(__FILE__)."/include/item.php");
require_once(dirname(__FILE__)."/include/media_list.php");
require_once(dirname(__FILE__)."/include/media.php");
es_include("page.php");
es_include("pagelist.php");
es_include("js_calendar/calendar.php");

$module = $request->GetProperty('load');
$adminPage = new AdminPage($module);

if ($request->IsPropertySet("ItemID"))
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

	$category = new CatalogCategory($module, $page->GetIntProperty("PageID"), null, $config);
	if (!$category->LoadByID($request->GetIntProperty("ViewCategoryID")))
	{
		header("Location: ".$moduleURL."&PageID=".$page->GetProperty("PageID"));
		exit();
	}

	if ($request->GetIntProperty("ItemID") > 0)
		$title = GetTranslation("title-item-edit", $module);
	else
		$title = GetTranslation("title-item-add", $module);

	$styleSheets = array(
		array("StyleSheetFile" => PROJECT_PATH."include/js_calendar/skins/calendar-fokcms-2.css"),
		array("StyleSheetFile" => ADMIN_PATH."template/plugins/prettyphoto/prettyPhoto.css"),
	);
	$javaScripts = array(
		array("JavaScriptFile" => ADMIN_PATH."template/js/staticpath.js"),
		array("JavaScriptFile" => CKEDITOR_PATH."ckeditor.js"),
		array("JavaScriptFile" => CKEDITOR_PATH."ajexFileManager/ajex.js"),
		array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js"),
		array("JavaScriptFile" => ADMIN_PATH."template/plugins/prettyphoto/jquery.prettyPhoto.js")
	);
	$navigation = array(
		array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
		array("Title" => $page->GetProperty("Title"), "Link" => $moduleURL."&PageID=".$page->GetProperty("PageID")),
		array("Title" => $category->GetProperty("Title"), "Link" => $moduleURL."&PageID=".$page->GetProperty("PageID")."&ViewCategoryID=".$request->GetProperty("ViewCategoryID")),
		array("Title" => $title, "Link" => $moduleURL."&PageID=".$page->GetProperty("PageID")."&ViewCategoryID=".$request->GetProperty("ViewCategoryID")."&ItemID=".$request->GetProperty("ItemID"))
	);
	$header = array(
		"Title" => $title,
		"Navigation" => $navigation,
		"StyleSheets" => $styleSheets,
		"JavaScripts" => $javaScripts
	);
	$content = $adminPage->Load("item_edit.html", $header);
	$content->SetLoop("Navigation", $navigation);
	
	$item = new CatalogItem($module, $page->GetProperty("PageID"), $config);

	$categoryIDs = array();
	if ($request->GetProperty("Save"))
	{
		$item->LoadFromObject($request);

		if ($item->Save())
		{
			$media = new CatalogMedia($module, $page->GetIntProperty("PageID"), $config);
			$request->SetProperty('ItemID', $item->GetProperty('ItemID'));
			$media->LoadFromObject($request);
			if ($media->Save() || !$media->HasErrors())
			{
				header("location: ".$moduleURL."&PageID=".$page->GetProperty("PageID")."&ViewCategoryID=".$request->GetProperty("ViewCategoryID")."&Page=".$request->GetProperty("Page"));
				exit();
			}
			else
			{
			    $item->_PrepareContentBeforeShow(true);
				$content->LoadErrorsFromObject($media);
				$request->SetProperty("OpenTab", 3);
			}
		}
		else
		{
			$categoryIDs = $item->GetProperty("CategoryIDs");
			$item->RemoveProperty("CategoryIDs");
			$content->LoadErrorsFromObject($item);
		}
	}
	else
	{
		$item->LoadByID($request->GetIntProperty("ItemID"));
	}

	$content->LoadFromObject($item);
	$content->SetLoop("ItemImageParamList", $item->GetImageParams("Item"));
	$content->SetLoop("FeaturedImageParamList", $item->GetImageParams("Featured"));

	// Calendar
	$calendar = new Calendar("ItemDate", $item->GetProperty("ItemDate"), true);
	$content->SetVar("CalendarField", $calendar->GetHTMLAsField());

	// Descriptions
	$n = abs(intval($config["ItemDescriptionCount"]));
	$descriptions = array();
	if ($n > 0)
	{
		for ($i = 0; $i < $n; $i++)
		{
		    $k = (($i > 0) ? ($i + 1) : "");
			$descriptions[] = array("Name" => "Description[".$i."]",
				"Value" => $item->GetProperty("Description".$k),
				"Title" => GetTranslation("item-description".$k, $module));
		}
	}
	$content->SetLoop("DescriptionList", $descriptions);

	$categoryList = new CatalogCategoryList($module);
	$categories = $categoryList->GetCategoryListForLink($page->GetProperty("PageID"), $request->GetProperty("ViewCategoryID"));

	if (count($categoryIDs) > 0)
	{
		for ($i = 0; $i < count($categories); $i++)
		{
			if (in_array($categories[$i]["CategoryID"], $categoryIDs))
			{
				$categories[$i]["Selected"] = 1;
			}
		}
	}
	else if ($itemCategories = $item->GetItemCategoryList($item->GetProperty("ItemID")))
	{
		for ($i = 0; $i < count($categories); $i++)
		{
			if (array_key_exists($categories[$i]["CategoryID"], $itemCategories))
			{
				$categories[$i]["Selected"] = 1;
			}
		}
	}
	else
	{
		for ($i = 0; $i < count($categories); $i++)
		{
			$categories[$i]["Selected"] = $categories[$i]["Current"];
		}
	}

	$content->SetLoop("CategoryList", $categories);

	$mediaList = new CatalogMediaList($module, $config);
	$request->SetProperty('ViewItemID', $request->GetProperty('ItemID'));
	$request->SetProperty('NoPaging', true);
	$mediaList->LoadMediaList($request);
	$content->LoadFromObjectList("MediaList", $mediaList);

	$content->SetVar('L_TitleH1', GetTranslation('title-h1'));
	$content->SetVar('L_MetaTitle', GetTranslation('meta-title'));
	$content->SetVar('L_MetaKeywords', GetTranslation('meta-keywords'));
	$content->SetVar('L_MetaDescription', GetTranslation('meta-description'));

	$content->SetVar('MTitle', GetTranslation('media-title', $module));
	$content->SetVar('MDescription', GetTranslation('media-description', $module));
	$content->SetVar('MSortOrder', GetTranslation('media-sort-order', $module));

	$content->SetVar("URLPrefix", $page->GetPagePrefix()."/".$config["ItemURLPrefix"]."/");
	$content->SetVar("UploadMaxFileSize", GetTranslation("upload-max-file-size", array("UploadMaxFileSize" => GetUploadMaxFileSize())));
	$content->SetVar("PageID", $request->GetProperty("PageID"));
	$content->SetVar("ViewCategoryID", $request->GetProperty("ViewCategoryID"));
	$content->SetVar("Page", $request->GetProperty("Page"));
	$content->SetVar("OpenTab", $request->GetProperty("OpenTab"));
}
else if ($request->IsPropertySet("ViewCategoryID"))
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

	$category = new CatalogCategory($module, $page->GetIntProperty("PageID"), null, $config);
	if (!$category->LoadByID($request->GetIntProperty("ViewCategoryID")))
	{
		header("Location: ".$moduleURL."&PageID=".$page->GetProperty("PageID"));
		exit();
	}

	$url = $moduleURL."&PageID=".$page->GetProperty("PageID")."&ViewCategoryID=".$request->GetProperty("ViewCategoryID");

	$javaScripts = array(
		array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js")
	);
	$navigation = array(
		array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
		array("Title" => $page->GetProperty("Title"), "Link" => $moduleURL."&PageID=".$page->GetProperty("PageID")),
		array("Title" => $category->GetProperty("Title"), "Link" => $url)
	);
	$header = array(
		"Title" => $category->GetProperty("Title"),
		"Navigation" => $navigation,
		"JavaScripts" => $javaScripts
	);
	// $var = new CatalogItemList();
	// $var->LoadItemListByCategory($request);
	// $content->LoadFromObjectList('ItemList', $category);
	$content = $adminPage->Load("item_list.html", $header);
	$content->SetLoop("Navigation", $navigation);


	$itemList = new CatalogItemList($module, $config);

	if ($request->GetProperty('Do') == 'Remove')
	{
		$itemList->Remove($request->GetProperty("ListIDs"));
		$content->LoadMessagesFromObject($itemList);
	}

	$sortOrder = $config["ItemsOrderBy"];
	if ($sortOrder == 'sortorder_asc' || $sortOrder == 'sortorder_desc')
	{
		$content->SetVar("ShowSortOrderColumn", true);
	}

	$itemList->LoadItemListByCategory($request);

	$content->LoadFromObjectList("ItemList", $itemList);

	$content->SetVar("Paging", $itemList->GetPagingAsHTML($url));
	if ($request->GetProperty('SearchString'))
		$content->SetVar("ListInfo", GetTranslation('list-info2', array('Request' => $request->GetProperty('SearchString'), 'Total' => $itemList->GetCountTotalItems())));
	else
		$content->SetVar("ListInfo", GetTranslation('list-info1', array('Page' => $itemList->GetItemsRange(), 'Total' => $itemList->GetCountTotalItems())));

	$content->SetVar("PageID", $page->GetProperty("PageID"));
	$content->SetVar("ViewCategoryID", $request->GetProperty("ViewCategoryID"));
	$content->SetVar("Page", $itemList->GetCurrentPage());
	$content->SetVar("ItemsOnPage", $itemList->GetItemsOnPage());
}
else if ($request->IsPropertySet("CategoryID"))
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

	if ($request->GetIntProperty("CategoryID") > 0)
		$title = GetTranslation("title-category-edit", $module);
	else
		$title = GetTranslation("title-category-add", $module);

	$styleSheets = array();
	$javaScripts = array(
		array("JavaScriptFile" => ADMIN_PATH."template/js/staticpath.js"),
		array("JavaScriptFile" => CKEDITOR_PATH."ckeditor.js"),
		array("JavaScriptFile" => CKEDITOR_PATH."ajexFileManager/ajex.js")
	);
	$navigation = array(
		array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
		array("Title" => $page->GetProperty("Title"), "Link" => $moduleURL."&PageID=".$page->GetProperty("PageID")),
		array("Title" => $title, "Link" => $moduleURL."&PageID=".$page->GetProperty("PageID")."&CategoryID=".$request->GetProperty("CategoryID"))
	);
	$header = array(
		"Title" => $title,
		"Navigation" => $navigation,
		"StyleSheets" => $styleSheets,
		"JavaScripts" => $javaScripts
	);
	$content = $adminPage->Load("category_edit.html", $header);
	$content->SetLoop("Navigation", $navigation);

	$config = $page->GetConfig();

	$category = new CatalogCategory($module, $page->GetIntProperty("PageID"), null, $config);

	if ($request->GetProperty("Save"))
	{
		$category->LoadFromObject($request);

		if ($category->Save())
		{
			$category->OpenParents();
			header("location: ".$moduleURL."&PageID=".$page->GetProperty("PageID"));
			exit();
		}
		else
		{
			$content->LoadErrorsFromObject($category);
		}
	}
	else
	{
		$category->LoadByID($request->GetIntProperty("CategoryID"));
	}

	$parentURL = "";
	$path = $category->GetPathAsArray();

	for ($i = 0; $i < count($path) - 1; $i++)
	{
		$parentURL .= $path[$i]["StaticPath"]."/";
	}
	$content->SetVar("ParentURL", $parentURL);

	$content->SetVar("URLPrefix", $page->GetPagePrefix()."/".$config["CategoryURLPrefix"]."/");
	$content->SetVar("URLSuffix", "/".INDEX_PAGE.HTML_EXTENSION);

	$content->LoadFromObject($category);
	$content->SetLoop("CategoryImageParamList", $category->GetImageParams());

	//$content->SetLoop("Parents", $category->GetParentLists());
	$categoryList = new CatalogCategoryList($module, '', $config);
	$categoryList = $categoryList->GetCategoryListForParentSelection($request->GetProperty("PageID"), $category->GetProperty("CategoryID"), $category->GetParentID());
	$content->SetLoop("CategoryList", $categoryList);
	
	$content->SetVar('L_TitleH1', GetTranslation('title-h1'));
	$content->SetVar('L_MetaTitle', GetTranslation('meta-title'));
	$content->SetVar('L_MetaKeywords', GetTranslation('meta-keywords'));
	$content->SetVar('L_MetaDescription', GetTranslation('meta-description'));

	$content->SetVar("UploadMaxFileSize", GetTranslation("upload-max-file-size", array("UploadMaxFileSize" => GetUploadMaxFileSize())));
	$content->SetVar("PageID", $page->GetProperty("PageID"));
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

	$styleSheets = array(
		array("StyleSheetFile" => ADMIN_PATH."template/plugins/uikit/css/uikit.min.css"),
		array("StyleSheetFile" => ADMIN_PATH."template/plugins/uikit/css/components/nestable.min.css")
	);
	$javaScripts = array(
		array("JavaScriptFile" => PROJECT_PATH."module/".$module."/js/tree.js"),
		array("JavaScriptFile" => ADMIN_PATH."template/plugins/uikit/js/uikit.js"),
		array("JavaScriptFile" => ADMIN_PATH."template/plugins/uikit/js/components/nestable.js")
	);
	$navigation = array(
		array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
		array("Title" => $page->GetProperty("Title"), "Link" => $moduleURL."&PageID=".$page->GetProperty("PageID"))
	);
	$header = array(
		"Title" => $page->GetProperty("Title"),
		"Navigation" => $navigation,
		"StyleSheets" => $styleSheets,
		"JavaScripts" => $javaScripts
	);
	$content = $adminPage->Load("category_tree.html", $header);
	$content->SetLoop("Navigation", $navigation);

	$categoryList = new CatalogCategoryList($module, $page->GetConfig());
	$categoryList->LoadCategoryListForContentTree($page->GetProperty("PageID"));
	$categoryList->PrepareForJS();
	$content->LoadFromObjectList("CategoryList", $categoryList);
	$content->SetVar("CategoryTreeHash", $categoryList->GetCategoryTreeHash($page->GetProperty("PageID")));

	$content->SetVar("PageID", $page->GetProperty("PageID"));
}
else
{
	die("PageID param is not defined");
}

$adminPage->Output($content);

?>
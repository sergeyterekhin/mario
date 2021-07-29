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
es_include("js_calendar/calendar.php");
es_include("urlfilter.php");

$module = $request->GetProperty('load');
$adminPage = new AdminPage($module);
$urlFilter = new URLFilter();

if ($request->IsPropertySet("ItemID"))
{
	// Edit item details
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

	$urlFilter->LoadFromObject($request, array('PageID', 'ViewCategoryID', 'Page'));

	if ($request->GetProperty("ItemID") > 0)
		$title = GetTranslation("title-item-edit", $module);
	else
		$title = GetTranslation("title-item-add", $module);

	$category = new InfoblockCategory($module, $page->GetIntProperty("PageID"), $config);
	if ($category->LoadByID($request->GetProperty('ViewCategoryID')))
	{
		$navigation = array(
			array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
			array("Title" => $page->GetProperty("Title"), "Link" => $moduleURL."&".$urlFilter->GetForURL(array('ViewCategoryID'))),
			array("Title" => $category->GetProperty("Title"), "Link" => $moduleURL."&".$urlFilter->GetForURL()),
			array("Title" => $title, "Link" => $moduleURL."&".$urlFilter->GetForURL()."&ItemID=".$request->GetProperty("ItemID"))
		);
	}
	else
	{
		$navigation = array(
			array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
			array("Title" => $page->GetProperty("Title"), "Link" => $moduleURL."&".$urlFilter->GetForURL()),
			array("Title" => $title, "Link" => $moduleURL."&".$urlFilter->GetForURL()."&ItemID=".$request->GetProperty("ItemID"))
		);
		$request->SetProperty('ViewCategoryID', '0');
		$urlFilter->SetProperty('ViewCategoryID', '0');
	}
	$styleSheets = array(
		array("StyleSheetFile" => PROJECT_PATH."include/js_calendar/skins/calendar-fokcms-2.css")
	);
	$javaScripts = array(
		array("JavaScriptFile" => ADMIN_PATH."template/js/staticpath.js"),
		array("JavaScriptFile" => CKEDITOR_PATH."ckeditor.js"),
		array("JavaScriptFile" => CKEDITOR_PATH."ajexFileManager/ajex.js")
	);
	$header = array(
		"Title" => $title,
		"Navigation" => $navigation,
		"StyleSheets" => $styleSheets,
		"JavaScripts" => $javaScripts
	);
	$content = $adminPage->Load("item_edit.html", $header);

	$item = new InfoblockItem($module, $page->GetIntProperty("PageID"), $config);

	if ($request->GetProperty("Save"))
	{
		$item->LoadFromObject($request);
		if ($item->Save())
		{
			header("location: ".$moduleURL."&".$urlFilter->GetForURL());
			exit();
		}
		else
		{
			$content->LoadErrorsFromObject($item);
		}
	}
	else
	{
		if ($item->LoadByID($request->GetProperty("ItemID")))
			$request->SetProperty('CategoryID', $item->GetProperty('CategoryID'));
		else
			$request->SetProperty('CategoryID', $urlFilter->GetProperty('ViewCategoryID'));
	}

	$content->LoadFromObject($item);
	$content->SetLoop("ItemImageParamList", $item->GetImageParams());
	
	// FieldList
	if($config["FieldList"]){
		$fieldList = explode(",", $config["FieldList"]);
		$fieldArray = array();
		foreach ($fieldList as $field){
			$fieldArray[] = array("Name" => $field, 
				"Value" => $item->GetProperty($field),
				"Title" => GetTranslation("field-".$field, $module));
		}
		$content->SetLoop("FieldList", $fieldArray);
	}

	$content->SetVar('L_TitleH1', GetTranslation('title-h1'));
	$content->SetVar('L_MetaTitle', GetTranslation('meta-title'));
	$content->SetVar('L_MetaKeywords', GetTranslation('meta-keywords'));
	$content->SetVar('L_MetaDescription', GetTranslation('meta-description'));

	$calendar = new Calendar("ItemDate", $item->GetProperty("ItemDate"));
	$content->SetVar("CalendarField", $calendar->GetHTMLAsField());

	$categoryList = new InfoblockCategoryList($module, $config);
	$categoryList->Load($request);
	$content->LoadFromObjectList("CategoryList", $categoryList);

	$content->SetVar("ParamsForURL", $urlFilter->GetForURL());
	$content->SetVar("ParamsForForm", $urlFilter->GetForForm());

	$content->SetVar("URLPrefix", $page->GetPagePrefix()."/");

	// Set correct parent url for selected category id
	if ($categoryList->GetCountTotalItems() > 0)
	{
		$items = $categoryList->GetItems();
		for ($i = 0; $i < count($items); $i++)
		{
			if ($items[$i]['Selected'])
			{
				$content->SetVar("ParentURL", $items[$i]['StaticPath'].'/');
				break;
			}
		}
	}
	$content->SetVar("PageID", $request->GetIntProperty('PageID'));
}
else if ($request->IsPropertySet("CategoryID"))
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

	$category = new InfoblockCategory($module, $page->GetIntProperty("PageID"), $config);

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
	
	$categoryList = new InfoblockCategoryList($module, $config);

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
			array("StyleSheetFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.css")
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
	
		$category = new InfoblockCategory($module, $page->GetIntProperty("PageID"), $config);
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
		$javaScripts = array(
			array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js")
		);
		$styleSheets = array(
			array("StyleSheetFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.css")
		);
		$header = array(
			"Title" => $page->GetProperty("Title"),
			"Navigation" => $navigation,
			"JavaScripts" => $javaScripts,
			"StyleSheets" => $styleSheets
		);
		$content = $adminPage->Load("item_list.html", $header);
	
		$itemList = new InfoblockItemList($module, $config);
	
		if ($request->GetProperty("ListIDs"))
		{
			if ($request->GetProperty('Do') == 'Remove')
				$itemList->RemoveByItemIDs($request->GetProperty("ListIDs"));
			else if ($request->GetProperty('Do') == 'MoveTo')
				$itemList->MoveTo($request->GetProperty("ListIDs"), $page->GetProperty('PageID'), $request->GetProperty("ToCategoryID"));
		}
	
		if ($config['ItemsOrderBy'] == 'Position')
			$content->SetVar('ShowSortOrder', true);
	
		$itemList->Load($request);
		$content->LoadFromObjectList("ItemList", $itemList);
		$content->LoadMessagesFromObject($itemList);
	
		$request->SetProperty('CategoryID', $request->GetProperty('ViewCategoryID'));
		$categoryList = new InfoblockCategoryList($module, $config);
		$categoryList->Load($request);
		$content->LoadFromObjectList("CategoryList", $categoryList);
	
		$content->SetVar("NoCategory", $page->GetProperty('Title'));
	
		$content->SetVar("Paging", $itemList->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL()));
		$content->SetVar("ListInfo", GetTranslation('list-info1', array('Page' => $itemList->GetItemsRange(), 'Total' => $itemList->GetCountTotalItems())));
	
		$urlFilter->SetProperty('Page', $itemList->GetCurrentPage());
		$content->SetVar("CurrentPage", $itemList->GetCurrentPage());
		$content->SetVar("ItemsOnPage", $itemList->GetItemsOnPage());
		$content->SetVar("ParamsForURL", $urlFilter->GetForURL());
		$content->SetVar("ParamsForForm", $urlFilter->GetForForm());
		$content->SetVar("ParamsForForm2", $urlFilter->GetForForm(array('ViewCategoryID')));
		$content->SetVar("PageID", $page->GetProperty('PageID'));
		$content->SetVar("CategoryID", $request->GetProperty("CategoryID"));
	}
}
else
{
	die('PageID param is not defined');
}

$adminPage->Output($content);

?>
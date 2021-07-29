<?php

define("IS_ADMIN", true);
require_once(dirname(__FILE__)."/../../include/init.php");
es_include("page.php");
es_include("user.php");

require_once(dirname(__FILE__)."/init.php");
require_once(dirname(__FILE__)."/include/category.php");
require_once(dirname(__FILE__)."/include/category_list.php");
require_once(dirname(__FILE__)."/include/item.php");
require_once(dirname(__FILE__)."/include/item_list.php");

$module = "infoblock";

$result = array();

$user = new User();
if (!$user->LoadBySession() || !$user->Validate(array(INTEGRATOR, ADMINISTRATOR, MODERATOR)))
{
	$result["SessionExpired"] = GetTranslation("your-session-expired");
	exit();
}
else
{
	$request = new LocalObject(array_merge($_GET, $_POST));
	switch ($request->GetProperty("Action"))
	{
		case "RemoveCategoryImage":
			$page = new Page();
			if ($page->LoadByID($request->GetProperty("PageID")))
			{
				$category = new InfoblockCategory($module, $page->GetProperty("PageID"), $page->GetConfig());
				$category->RemoveCategoryImage($request->GetProperty("ItemID"), $request->GetProperty('SavedImage'));
				$result = true;
			}
			else
			{
				$result = false;
			}
			break;
	
		case "RemoveItemImage":
			$page = new Page();
			if ($page->LoadByID($request->GetProperty("PageID")))
			{
				$item = new InfoblockItem($module, $page->GetProperty("PageID"), $page->GetConfig());
				$item->RemoveItemImage($request->GetProperty("ItemID"), $request->GetProperty('SavedImage'));
				$result = true;
			}
			else
			{
				$result = false;
			}
			break;
	
		case "SwitchItem":
			$item = new InfoblockItem($request->GetProperty('Module'), 0);
			$item->SwitchActive($request->GetProperty('ItemID'), $request->GetProperty('Active'));
			$result = true;
			break;
	
		case "SetItemSortOrder":
			$itemList = new InfoblockItemList($request->GetProperty('Module'));
			$result = $itemList->SetSortOrder($request->GetProperty('ItemID'), $request->GetProperty('CategoryID'), $request->GetProperty('Diff'));
			break;
	
		case "SwitchCategory":
			$category = new InfoblockCategory($request->GetProperty('Module'));
			$category->SwitchActive($request->GetProperty('CategoryID'), $request->GetProperty('Active'));
			$result = true;
			break;
	
		case "SetCategorySortOrder":
			$categoryList = new InfoblockCategoryList($request->GetProperty('Module'));
			$result = $categoryList->SetSortOrder($request->GetProperty('CategoryID'), $request->GetProperty('PageID'), $request->GetProperty('Diff'));
			break;
	}
}

echo json_encode($result);

?>
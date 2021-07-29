<?php

define("IS_ADMIN", true);
require_once(dirname(__FILE__)."/../../include/init.php");
es_include("localpage.php");
es_include("user.php");

require_once(dirname(__FILE__)."/init.php");
require_once(dirname(__FILE__)."/include/category.php");
require_once(dirname(__FILE__)."/include/category_list.php");
require_once(dirname(__FILE__)."/include/media.php");
require_once(dirname(__FILE__)."/include/media_list.php");
require_once(dirname(__FILE__)."/include/item.php");

$module = "catalog";

$request = new LocalObject(array_merge($_GET, $_POST));
$result = array();

$user = new User();
if (!$user->LoadBySession() || !$user->Validate(array(INTEGRATOR, ADMINISTRATOR, MODERATOR)))
{
	$result["SessionExpired"] = GetTranslation("your-session-expired");
}
else
{
	switch ($request->GetProperty("Action"))
	{
		case "RemoveCategory":
		    $page = new Page();
			if ($page->LoadByID($request->GetProperty("PageID")))
			{
				$categoryList = new CatalogCategoryList($module);
				if($request->GetProperty("CategoryTreeHash") == $categoryList->GetCategoryTreeHash($page->GetProperty("PageID")))
				{
					$category = new CatalogCategory($module, $request->GetProperty("PageID"), $page->GetConfig());
					$category->Remove($request->GetIntProperty("CategoryID"));
					$result["CategoryTreeHash"] = $categoryList->GetCategoryTreeHash($page->GetProperty("PageID"));
				}
				else
				{
					$result = false;
				}
			}
			else
			{
				$result = false;
			}
			break;
	
		case "RemoveCategoryImage":
			$page = new Page();
			if ($page->LoadByID($request->GetProperty("PageID")))
			{
				$category = new CatalogCategory($module, $page->GetProperty("PageID"), $page->GetConfig());
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
				$item = new CatalogItem($module, $page->GetProperty("PageID"), $page->GetConfig());
				$item->RemoveItemImage($request->GetProperty("ItemID"), $request->GetProperty('SavedImage'), $request->GetProperty("ImageName"));
				$result = true;
			}
			else
			{
				$result = false;
			}
			break;
	
		case "LoadMedia":
			$page = new Page();
			$page->LoadByID($request->GetProperty("PageID"));
			$config = $page->GetConfig();
			
			$media = new CatalogMedia($module, $request->GetProperty('PageID'), $config);
			$media->LoadByID($request->GetProperty('MediaID'));
			$result = $media->GetProperties();
			$result["MediaFileParamList"] = $media->GetImageParams();
			break;
	
		case "RemoveMedia":
			$mediaList = new CatalogMediaList($module, $request->GetProperty('PageID'));
			$mediaList->RemoveByMediaIDs($request->GetProperty('MediaID'));
			$result = true;
			break;
	
		case "SaveMedia":
			$media = new CatalogMedia($module, $request->GetProperty('PageID'));
			$media->LoadFromObject($request);
	
			if ($media->UpdateMediaInfo())
			{
				$result = true;
			}
			else
			{
				$result['Error'] = $media->GetErrorsAsString();
			}
			break;
	
		case "SetItemSortOrder":
			$itemList = new CatalogItemList($module);
			$result = $itemList->SetSortOrder($request->GetProperty('ItemID'), $request->GetProperty('CategoryID'), $request->GetProperty('Diff'));
			break;
			
		case "SaveCategoryPosition":
			$categoryList = new CatalogCategoryList($module);
			if($request->GetProperty("CategoryTreeHash") == $categoryList->GetCategoryTreeHash($request->GetProperty("PageID")))
			{
				$result["UpdateCategoryList"] = $categoryList->SaveCategoryPosition($request->GetProperty("CategoryID"), $request->GetProperty("ParentID"), $request->GetProperty("SortOrder"));
				$result["CategoryTreeHash"] = $categoryList->GetCategoryTreeHash($request->GetProperty("PageID"));
			}
			else
			{
				$result = false;
			}
			break;
			
		case "SetMediaSortOrder":
			$mediaList = new CatalogMediaList($module);
			$result = $mediaList->SetSortOrder($request->GetProperty('MediaID'), $request->GetProperty('Diff'));
			break;
	
		case "SwitchActive":
			$item = new CatalogItem($module);
			$item->SwitchActive($request->GetProperty('ItemID'), $request->GetProperty('Active'));
			$result = true;
			break;
		case "SwitchFeatured":
			$item = new CatalogItem($module);
			$item->SwitchFeatured($request->GetProperty('ItemID'), $request->GetProperty('Featured'));
			$result = true;
			break;
	}	
}

echo json_encode($result);

?>
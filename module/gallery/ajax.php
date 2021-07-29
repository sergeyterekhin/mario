<?php

define("IS_ADMIN", true);
require_once(dirname(__FILE__)."/../../include/init.php");
es_include("page.php");
es_include("user.php");

require_once(dirname(__FILE__)."/init.php");
require_once(dirname(__FILE__)."/include/category.php");
require_once(dirname(__FILE__)."/include/category_list.php");
require_once(dirname(__FILE__)."/include/media.php");
require_once(dirname(__FILE__)."/include/media_list.php");

$module = "gallery";
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
				$category = new GalleryCategory($module, $page->GetProperty("PageID"), $page->GetConfig());
				$category->RemoveCategoryImage($request->GetProperty("ItemID"), $request->GetProperty('SavedImage'));
				$result = true;
			}
			else
			{
				$result = false;
			}
			break;
	
		case "SwitchCategory":
			$category = new GalleryCategory($request->GetProperty('Module'), 0);
			$category->SwitchActive($request->GetProperty('CategoryID'), $request->GetProperty('Active'));
			$result = true;
			break;
	
		case "SetCategorySortOrder":
			$categoryList = new GalleryCategoryList($request->GetProperty('Module'));
			$result = $categoryList->SetSortOrder($request->GetProperty('CategoryID'), $request->GetProperty('PageID'), $request->GetProperty('Diff'));
			break;
	
		case "LoadMedia":
			$page = new Page();
			$page->LoadByID($request->GetProperty("PageID"));
			$config = $page->GetConfig();
			
			$media = new GalleryMedia($module, $request->GetProperty('PageID'), $config);
			$media->LoadByID($request->GetProperty('MediaID'));
			$result = $media->GetProperties();
			$result["MediaFileParamList"] = $media->GetImageParams();
			break;
	
		case "SaveMedia":
			$media = new GalleryMedia($module, $request->GetIntProperty('PageID'));
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
			
		case "SetMediaSortOrder":
			$mediaList = new GalleryMediaList($request->GetProperty('Module'), $request->GetIntProperty('PageID'));
			$result = $mediaList->SetSortOrder($request->GetProperty('MediaID'), $request->GetProperty('CategoryID'), $request->GetProperty('Diff'));
			break;
	
		case "RemoveMedia":
			$mediaList = new GalleryMediaList($module, $request->GetIntProperty('PageID'));
			$mediaList->RemoveByMediaIDs($request->GetProperty('MediaID'));
			$result = true;
			break;
	}
}

echo json_encode($result);

?>
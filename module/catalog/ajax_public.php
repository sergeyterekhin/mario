<?php

require_once(dirname(__FILE__)."/../../include/init.php");
es_include("js_http_request/JsHttpRequest.php");
es_include("localpage.php");
es_include("user.php");

require_once(dirname(__FILE__)."/init.php");
require_once(dirname(__FILE__)."/include/category.php");
require_once(dirname(__FILE__)."/include/category_list.php");
require_once(dirname(__FILE__)."/include/media.php");
require_once(dirname(__FILE__)."/include/item.php");

$module = "catalog";

$language =& GetLanguage();

$JsHttpRequest =& new JsHttpRequest($language->GetHTMLCharset());
$post = new LocalObject(array_merge($_GET, $_POST));

switch ($post->GetProperty("Action"))
{
	case "LoadItemInfo":
		$item = new Item($module);
		$pageContent = "";
		$images = array();
		if ($item->LoadByID($post->GetProperty("ItemID"), $post, true))
		{
			$publicPage = new PopupPage($module, false);
			// TODO: Надо выбрать для текущей страницы PageID поле Template и брать файл из нужного места
			$content = $publicPage->Load($module."_item_window.html");

			$content->LoadFromObject($item);

			// Item categories
			$content->SetLoop("ItemCategoryList", $item->GetItemCategoryList($item->GetProperty("ItemID"), $post));

			// Item images
			$mediaList = new MediaList($module);
			$mediaList->SetItemsOnPage(0);
			$post->SetProperty("ViewItemID", $item->GetProperty("ItemID"));
			$post->SetProperty("ViewType", "image");
			$mediaList->LoadMediaList($post);
			$content->LoadFromObjectList("ImageList", $mediaList);

			$images = array();
			if (!$images = $mediaList->GetItems())
			{
				$images = array();
			}

			$pageContent = $publicPage->Grab($content);
		}
		$_RESULT["Content"] = $pageContent;
		$_RESULT["Images"] = $images;
		exit();
		break;
}

?>
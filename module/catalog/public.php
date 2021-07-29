<?php

require_once(dirname(__FILE__)."/init.php");
require_once(dirname(__FILE__)."/include/category_list.php");
require_once(dirname(__FILE__)."/include/category.php");
require_once(dirname(__FILE__)."/include/item_list.php");
require_once(dirname(__FILE__)."/include/item.php");
require_once(dirname(__FILE__)."/include/media_list.php");
es_include("modulehandler.php");

class CatalogHandler extends ModuleHandler
{
	function ProcessPublic()
	{
		/*@var request LocalObject */
		$request = $this->ParseRequest();
		$this->header["InsideModule"] = $this->module;
		
		$urlParser =& GetURLParser();

		if ($urlParser->contentType == "text/xml")
		{
			$this->ShowXML($request);
		}
		else
		{
			$this->ShowHTML($request);
		}
	}

	function ShowHTML($request)
	{
		switch ($request->GetProperty('View'))
		{
			case 'Main':
				// Main catalog naviagtion
				
				$this->header['CategoryMainPage'] = true;
				$this->header['Template'] = $this->tmplPrefix."main.html";

				$publicPage = new PublicPage($this->module);
				$content = $publicPage->Load($this->tmplPrefix."main.html", $this->header, $this->pageID);

				$content->SetLoop("Navigation", $this->header["Navigation"]);

				$categoryList = new CatalogCategoryList($this->module, $this->baseURL, $this->config);
				$content->SetLoop("CategoryList", $categoryList->GetCategoryListForTemplate($this->pageID));

				$itemList = new CatalogItemList($this->module, $this->config);

				// List of featured items
				$itemList->LoadFeaturedItemList($request);
				$content->LoadFromObjectList("FeaturedItemList", $itemList);

				// List of featured items groupped by category
				$itemList->LoadFeaturedItemList($request, true);
				$content->LoadFromObjectList("FeaturedItemListByCategory", $itemList);

				// List of all items
				$itemList->LoadItemListByCategory($request);
				$content->LoadFromObjectList("ItemList", $itemList);

				$content->SetLoop("Paging", $itemList->GetPagingAsArray($this->baseURL.HTML_EXTENSION, $this->baseURL.HTML_EXTENSION));
				$content->SetVar("CurrentPage", $itemList->GetCurrentPage());
				$content->SetVar("CurrentCategoryURL", $this->baseURL.HTML_EXTENSION);

				// List of searched items
				if ($request->GetProperty('Search'))
				{
					$searchedItemList = new CatalogItemList($this->module, $this->config);
					$searchedItemList ->LoadItemListBySearchQuery($request);
					$content->LoadFromObjectList("SearchedItemList", $searchedItemList );
					$content->SetLoop("SearchPaging", $searchedItemList ->GetPagingAsArray($this->baseURL.HTML_EXTENSION."?Search=".urlencode($request->GetProperty("Search")), $this->baseURL.HTML_EXTENSION."?Search=".urlencode($request->GetProperty("Search"))));
					$content->SetVar("SearchQuery", strip_tags(trim($request->GetProperty("Search"))));
				}
				break;
			case 'ItemListByCategory':
				$category = new CatalogCategory($this->module, $this->pageID, $this->baseURL, $this->config);
				if ($category->LoadByPath($request->GetProperty("CategoryPath")))
				{
					// Main catalog naviagtion
					$this->CategoryList2Header($request->GetProperty("CategoryURL"), $category->GetProperty("CategoryID"));

					$request->SetProperty("ViewCategoryID", $category->GetProperty("CategoryID"));

					$categoryPath = $category->GetPathAsArray();
					$this->header["Navigation"] = array_merge($this->header["Navigation"], $categoryPath);

					$this->header["Title"] = $category->GetProperty("Title");
					$this->header["TitleH1"] = $category->GetProperty("TitleH1");
					$this->header["Description"] = $category->GetProperty("Description");
					$this->header["MetaTitle"] = $category->GetProperty("MetaTitle");
					$this->header["MetaKeywords"] = $category->GetProperty("MetaKeywords");
					$this->header["MetaDescription"] = $category->GetProperty("MetaDescription");
					$this->header["Template"] = $this->tmplPrefix."item_list.html";
					$this->header["Content"] = $category->GetProperty("Content");
			
					$publicPage = new PublicPage($this->module);
					$content = $publicPage->Load($this->tmplPrefix."item_list.html", $this->header, $this->pageID);

					$content->SetLoop("Navigation", $this->header["Navigation"]);

					$content->LoadFromObject($category);

					$categoryList = new CatalogCategoryList($this->module, $this->baseURL, $this->config);
					$content->SetLoop("CatalogCategoryList", $categoryList->GetCategoryListForTemplate($this->pageID, $category->GetProperty('CategoryID')));

					$itemList = new CatalogItemList($this->module, $this->config);
					$itemList->LoadItemListByCategory($request);

					if (strcmp($request->GetProperty($itemList->GetPageParam()), $itemList->GetCurrentPage()) != 0)
					{
						// Incorrect page parameter is passed, to not duplicate pages show 404
						// TODO: Somehow go to "ItemInfo"
						Send404();
					}

					$content->LoadFromObjectList("ItemList", $itemList);

					$categoryURL = $request->GetProperty("CategoryBaseURL");
					for ($i = 0; $i < count($categoryPath); $i++)
					{
						$categoryURL .= "/".$categoryPath[$i]['StaticPath'];
					}

					$urlFirstPage = $categoryURL.'/';
					$url = $categoryURL."/[[".$itemList->GetPageParam()."]]".HTML_EXTENSION;

					$suffix = "";
					if ($request->IsPropertySet("Year"))
						$suffix = "?Year=".$request->GetProperty("Year");

					//need to fix
					$orderByURL = $request->GetProperty('OrderBy') ? "?OrderBy=".$request->GetProperty('OrderBy') : "";

					$content->SetLoop("Paging", $itemList->GetPagingAsArray($url.$suffix.$orderByURL, $urlFirstPage.$suffix.$orderByURL));
					$content->SetVar("CurrentCategoryURL", $urlFirstPage);
					$content->SetVar("CurrentPage", $itemList->GetCurrentPage());

					$content->SetLoop("YearList", $itemList->GetYearList($request));
				}
				break;
			case 'ItemInfo':
				$item = new CatalogItem($this->module, $this->pageID, $this->config);
				if ($item->LoadByStaticPath($request))
				{
					// Main catalog naviagtion
					$this->CategoryList2Header($request->GetProperty("CategoryURL"));

					// Get category list for the current item
					$itemCategoryList = $item->GetItemCategoryList($item->GetProperty("ItemID"), $request);
					// Create breadcrumb
					$categoryList = new CatalogCategoryList($this->module, $this->config, $request->GetProperty("CategoryURL"));
					$last = end($itemCategoryList);
					$category = new CatalogCategory($this->module, $this->pageID, $this->config, $request->GetProperty("CategoryURL"));
					$category->LoadByID($last['CategoryID']);
					$categoryPath = $category->GetPathAsArray($request->GetProperty('CategoryBaseURL'));
					$this->header["Navigation"] = array_merge($this->header["Navigation"], $categoryPath);

					$this->header["Navigation"][] = array(
						"StaticPath" => $item->GetProperty("StaticPath"),
						"PageURL" => $this->baseURL.'/'.$this->config["ItemURLPrefix"].'/'.$item->GetProperty("StaticPath").HTML_EXTENSION,
						"Title" => $item->GetProperty("Title"),
						"Description" => $item->GetProperty("Description"));

					$this->header["Title"] = $item->GetProperty("Title");
					$this->header["TitleH1"] = $item->GetProperty("TitleH1");
					$this->header["MetaTitle"] = $item->GetProperty("MetaTitle");
					$this->header["MetaKeywords"] = $item->GetProperty("MetaKeywords");
					$this->header["MetaDescription"] = $item->GetProperty("MetaDescription");

					if (is_file(PROJECT_DIR."website/".WEBSITE_FOLDER."/template/".$this->tmplPrefix."item_popup.html"))
					{
						$this->header['Template'] = $this->tmplPrefix."item_popup.html";
						$publicPage = new PopupPage($this->module, false);
						$content = $publicPage->Load($this->tmplPrefix."item_popup.html", $this->header, $this->pageID);
					}
					else
					{
						$this->header['Template'] = $this->tmplPrefix."item_page.html";
						$publicPage = new PublicPage($this->module);
						$content = $publicPage->Load($this->tmplPrefix."item_page.html", $this->header, $this->pageID);
					}

					$content->LoadFromObject($item);

					// Item categories
					$content->SetLoop("ItemCategoryList", $itemCategoryList);

					// Item images
					$mediaList = new CatalogMediaList($this->module, $this->config);
					$mediaList->SetItemsOnPage(0);
					$request->SetProperty("ViewItemID", $item->GetProperty("ItemID"));
					$request->SetProperty("ViewType", "image");
					$mediaList->LoadMediaList($request);
					$content->LoadFromObjectList("ImageList", $mediaList);

					//added catalog_category_list for item page
					$categoryList = new CatalogCategoryList($this->module, $this->baseURL, $this->config);
					$content->SetLoop("CatalogCategoryList", $categoryList->GetCategoryListForTemplate($this->pageID, $category->GetProperty('CategoryID')));
					$content->SetVar("CatalogBaseURL", $this->header["Navigation"][count($this->header["Navigation"]) - 2]['PageURL']);

					$catalogBackURL = $content->GetVar("CatalogBaseURL");
					if (isset($_SERVER["HTTP_REFERER"]))
					{
						$result = parse_url($_SERVER["HTTP_REFERER"]);
						if (isset($result["host"]) && $result["host"] == $_SERVER["HTTP_HOST"])
							$catalogBackURL = $_SERVER["HTTP_REFERER"];
					}
					$content->SetVar("CatalogBackURL", $catalogBackURL);
					$content->SetVar("ItemURL", $this->baseURL.'/'.$this->config["ItemURLPrefix"].'/'.$item->GetProperty("StaticPath").HTML_EXTENSION);
				}
				break;
		}

		if (isset($content) && isset($publicPage))
		{
			for ($i = 1; $i < $this->header['MenuImageCount'] + 1; $i++)
			{
				$content->SetVar('MenuImage'.$i, $this->header['MenuImage'.$i]);
				$content->SetVar('MenuImage'.$i.'Path', $this->header['MenuImage'.$i.'Path']);
			}

			$content->SetVar('PageID', $this->pageID);
			$content->SetVar("PageTitle", $this->header["Title"]);
			$content->SetVar("PageTitleH1", $this->header["TitleH1"]);
			$content->SetVar('PageContent', $this->content);

			$publicPage->Output($content);
		}
		else
		{
			Send404();
		}

	}

	function ShowXML($request)
	{
		switch ($request->GetProperty('View'))
		{
			case 'Main':
				$publicPage = new PopupPage($this->module, false);
				$content = $publicPage->Load($this->tmplPrefix."main.xml", $this->header, $this->pageID);

				$itemList = new CatalogItemList($this->module, $this->config);

				// List of featured items
				$itemList->LoadFeaturedItemList($request);
				$content->LoadFromObjectList("FeaturedItemList", $itemList);

				// List of featured items groupped by category
				$itemList->LoadFeaturedItemList($request, true);
				$content->LoadFromObjectList("FeaturedItemListByCategory", $itemList);

				// List of all items
				$itemList->LoadItemListByCategory($request, true);
				$content->LoadFromObjectList("ItemList", $itemList);

				$content->SetVar('BaseURL', $this->baseURL.HTML_EXTENSION);

				break;
			case 'ItemListByCategory':
				$category = new CatalogCategory($this->module, $this->pageID, $this->config, $request->GetProperty("CategoryURL"));
				if ($category->LoadByPath($request->GetProperty("CategoryPath")))
				{
					$publicPage = new PopupPage($this->module, false);
					$content = $publicPage->Load($this->tmplPrefix."item_list.xml", $this->header, $this->pageID);

					$itemList = new CatalogItemList($this->module, $this->config);

					// List of items for selected category
					$request->SetProperty("ViewCategoryID", $category->GetProperty("CategoryID"));
					$itemList->LoadItemListByCategory($request, true);
					$content->LoadFromObjectList("ItemList", $itemList);

					$categoryPath = $category->GetPathAsArray();
					$content->SetVar('BaseURL', $categoryPath[count($categoryPath) - 1]["PageURL"]);
					$content->SetVar('Title', $category->GetProperty('Title'));
				}
				break;
		}

		if (isset($content) && isset($publicPage))
		{
			$content->SetVar('PageID', $this->pageID);
			$content->SetVar("PageTitle", $this->header["Title"]);
			$content->SetVar('PageContent', $this->content);

			// Output content
			echo '<?xml version="1.0" encoding="UTF-8" ?>';
			$publicPage->Output($content);
		}
		else
		{
			Send404();
		}
	}

	function ParseRequest()
	{
		$request = new LocalObject(array_merge($_GET, $_POST));
		$request->SetProperty('PageID', $this->pageID);
		$request->SetProperty("BaseURL", $this->baseURL);
		$request->SetProperty("CategoryBaseURL", $this->baseURL.'/'.$this->config["CategoryURLPrefix"]);
		$request->SetProperty('ShowActive', 'Y');
		$request->RemoveProperty("View");

		$urlParser =& GetURLParser();

		$itemList = new CatalogItemList($this->module, $this->config);

		$pageMatch = array();

		$chunks = count($this->pathInsideModule);

		// Main, Item, ItemListByCategory
		if ($chunks == 1 && $this->pathInsideModule[0] == "")
		{
			// URL /path/to/module/ - is not available. Only /path/to/module.html is available
			Send403();
		}
		else if($chunks == 0 ||($chunks == 1 && $this->pathInsideModule[0] == INDEX_PAGE.'.html'))
		{
			$request->SetProperty('View', 'Main');
		}
		else if ($chunks > 2 && ($this->pathInsideModule[$chunks - 1] == INDEX_PAGE.'.xml' || $this->pathInsideModule[$chunks - 1] == "" || preg_match("/^([0-9]+)".str_replace(".", "\\.", HTML_EXTENSION)."$/", $this->pathInsideModule[$chunks - 1], $pageMatch)))
		{
			// Try to determine category
			if ($this->pathInsideModule[0] == $this->config["CategoryURLPrefix"])
			{
				$request->SetProperty('View', 'ItemListByCategory');
				$request->SetProperty('CategoryPath', array_slice($this->pathInsideModule, 1, count($this->pathInsideModule) - 2));
			}
			
			if ($request->IsPropertySet('View'))
			{
				if (count($pageMatch) == 2)
				{
					if ($pageMatch[1] == 1)
					{
						// Do not allow duplicate URL for first page of the item list
						Send404();
					}
					else
					{
						$request->SetProperty($itemList->GetPageParam(), $pageMatch[1]);
					}
				}
				else
				{
					// First page of the item list can have only one URL
					$request->SetProperty($itemList->GetPageParam(), 1);
				}
			}
		}
		else if ($chunks > 2 && $this->pathInsideModule[$chunks - 1] == INDEX_PAGE.HTML_EXTENSION)
		{
			unset($this->pathInsideModule[$chunks - 1]);
			Send301($this->baseURL."/".implode("/", $this->pathInsideModule)."/");
		}
		else if ($chunks == 2 && $this->pathInsideModule[0] == $this->config["ItemURLPrefix"] && $urlParser->fileExtension == HTML_EXTENSION)
		{
			$request->SetProperty('View', 'ItemInfo');
			$request->SetProperty('ItemStaticPath', substr($this->pathInsideModule[1], 0, -strlen(HTML_EXTENSION)));
/*
			// Try to determine category
			if ($request->GetProperty('View') != 'ItemInfo')
			{
				if ($this->pathInsideModule[0] == $this->config["CategoryURLPrefix"])
				{
					$request->SetProperty('View', 'ItemInfo');
					$request->SetProperty('CategoryPath', array_slice($this->pathInsideModule, 1, count($this->pathInsideModule) - 2));
					$request->SetProperty('ItemStaticPath', substr($this->pathInsideModule[$chunks - 1], 0, -strlen(HTML_EXTENSION)));
				}
			}
*/
		}

		$_GET[$itemList->GetPageParam()] = $request->GetProperty($itemList->GetPageParam());
		$_POST[$itemList->GetPageParam()] = $request->GetProperty($itemList->GetPageParam());
		$_REQUEST[$itemList->GetPageParam()] = $request->GetProperty($itemList->GetPageParam());

		return $request;
	}

	function CategoryList2Header($categoryBaseURL, $categoryID = null)
	{
		$categoryList = new CatalogCategoryList($this->module, $this->baseURL, $this->config);
		$this->header["CatalogCategoryList"] = $categoryList->GetCategoryListForTemplate($this->pageID, $categoryID);
	}

	function ProcessHeader($module)
	{
		$data = array();

		$pageList = new PageList();
		$pageList->LoadPageListForModule($module);
		$result = $pageList->GetItems();

		$page = new Page();

		for ($i = 0; $i < count($result); $i++)
		{
			$config = $pageList->GetConfig($module, $result[$i]['PageConfig']);
			$page->LoadByID($result[$i]['PageID']);
			$staticPath = $page->GetProperty('StaticPath');

			// Define base URL
			$path = array();
			$pathTemp = $page->GetPathAsArray();
			$pathTemp = array_slice($pathTemp, 1);
			foreach ($pathTemp as $k => $v)
			{
				$path[] = $v["StaticPath"];
			}
			$key = strtoupper($module).'_'.$staticPath.'_URL';
			if ($page->GetCountChildren() > 0)
				$data[$key] = GetDirPrefix().implode('/', $path).'/';
			else
				$data[$key] = GetDirPrefix().implode('/', $path).HTML_EXTENSION;
				
			$baseURL = GetDirPrefix().implode('/', $path);
			$categoryList = new CatalogCategoryList($module, $baseURL, $config);
			$data[strtoupper($module).'_'.$staticPath.'_CategoryList'] = $categoryList->GetCategoryListForTemplate($result[$i]['PageID']);
			
			$itemList = new CatalogItemList($module, $config);
			$itemList->LoadFeaturedItemList(new LocalObject(array('BaseURL' => $baseURL, 'PageID' => $page->GetProperty("PageID"))));
			$data[strtoupper($module).'_'.$staticPath.'_FeaturedItemList'] = $itemList->GetItems();
		}
		return $data;
	}

	function RemoveModuleData()
	{
		$categoryList = new CatalogCategoryList($this->module, $this->config);
		$categoryList->RemoveByPageID($this->pageID);
	}
}
?>
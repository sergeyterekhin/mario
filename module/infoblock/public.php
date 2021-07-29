<?php

require_once(dirname(__FILE__)."/init.php");
require_once(dirname(__FILE__)."/include/category_list.php");
require_once(dirname(__FILE__)."/include/category.php");
require_once(dirname(__FILE__)."/include/item_list.php");
require_once(dirname(__FILE__)."/include/item.php");
es_include("modulehandler.php");

class InfoblockHandler extends ModuleHandler
{
	function ProcessPublic()
	{
		$this->header["InsideModule"] = $this->module;
		$urlParser =& GetURLParser();

		if ($urlParser->IsXML())
			$this->ShowXML();
		else if ($urlParser->IsHTML())
			$this->ShowHTML();
		else
			Send404();
	}

	function ShowHTML()
	{
		/*@var request LocalObject */
		$request = $this->ParseRequest();

		$pageTitleH1 = $this->header["TitleH1"];
		$pageTitle = $this->header["Title"];
		$pageDescription = $this->header["Description"];

		switch ($request->GetProperty('View'))
		{
			case 'CategoryList':
				$categoryList = new InfoblockCategoryList($this->module, $this->config);
				$categoryList->Load($request);

				$this->header["Template"] = $this->tmplPrefix."category_list.html";
				$publicPage = new PublicPage($this->module);
				$content = $publicPage->Load($this->tmplPrefix."category_list.html", $this->header, $this->pageID);

				$content->SetLoop("Navigation", $this->header["Navigation"]);

				$content->LoadFromObjectList('CategoryList', $categoryList);
				
				/*item*/
				$itemList = new InfoblockItemList($this->module, $this->config);
				$itemList->Load($request);
				
				$content->LoadFromObjectList('ItemList', $itemList);
				
				break;
			case 'ItemList':
				$itemList = new InfoblockItemList($this->module, $this->config);
				$itemList->Load($request);

				// Page number is too large
				if (strcmp($request->GetProperty($itemList->GetPageParam()), $itemList->GetCurrentPage()) != 0)
					Send404();

				if ($request->GetIntProperty('ViewCategoryID') > 0)
				{
					$this->header["Title"] = $request->GetProperty("Category_Title");
					$this->header["Description"] = $request->GetProperty("Category_Description");
					$this->header["TitleH1"] = $request->GetProperty("Category_TitleH1");
					$this->header["MetaTitle"] = $request->GetProperty("Category_MetaTitle");
					$this->header["MetaKeywords"] = $request->GetProperty("Category_MetaKeywords");
					$this->header["MetaDescription"] = $request->GetProperty("Category_MetaDescription");
//					$this->header["InsideModule"] = 1;
					array_push($this->header["Navigation"],
						array(
							"StaticPath" => $request->GetProperty("Category_StaticPath"),
							"PageURL" => $request->GetProperty("Category_CategoryURL"),
							"Title" => $request->GetProperty("Category_Title")
							)
						);
				}

				$this->header["Template"] = $this->tmplPrefix."item_list.html";
				$publicPage = new PublicPage($this->module);
				$content = $publicPage->Load($this->tmplPrefix."item_list.html", $this->header, $this->pageID);

				$content->SetLoop("Navigation", $this->header["Navigation"]);

				if ($request->GetIntProperty('ViewCategoryID') > 0)
				{
					$content->SetVar("CategoryTitle", $request->GetProperty("Category_Title"));
					$content->SetVar("TitleH1", $request->GetProperty("Category_TitleH1"));
					$content->SetVar("CategoryDescription", $request->GetProperty("Category_Description"));
					$content->SetVar("CategoryContent", $request->GetProperty("Category_Content"));
					$urlFirstPage = $request->GetProperty('BaseURL')."/";
					$content->SetVar('RSSFeedURL', $request->GetProperty("Category_RSSFeedURL"));
				}
				else
				{
					$urlFirstPage = $this->header['CurrentPageURL'];
					$content->SetVar('RSSFeedURL', $this->baseURL.'.xml');
				}

				$content->LoadFromObjectList('ItemList', $itemList);

				$url = $request->GetProperty('BaseURL')."/[[".$itemList->GetPageParam()."]]".HTML_EXTENSION;
				$content->SetLoop("Paging", $itemList->GetPagingAsArray($url, $urlFirstPage));
				$content->SetVar("CurrentPage", $itemList->GetCurrentPage());

				if ($request->GetIntProperty('ViewCategoryID') > 0)
				{
					// Load categories if they are exists
					$request->SetProperty('BaseURL', $this->baseURL);
					$categoryList = new InfoblockCategoryList($this->module, $this->config);
					$categoryList->Load($request);
					$content->LoadFromObjectList('CategoryList', $categoryList);
				}
				break;
			case 'ItemView':
				$item = new InfoblockItem($this->module, $this->pageID, $this->config);

				if ($item->LoadByStaticPath($request))
				{
					$this->header["Title"] = $item->GetProperty("Title");
					$this->header["TitleH1"] = $item->GetProperty("TitleH1");
					$this->header["Description"] = $item->GetProperty("Description");
					$this->header["MetaTitle"] = $item->GetProperty("MetaTitle");
					$this->header["MetaKeywords"] = $item->GetProperty("MetaKeywords");
					$this->header["MetaDescription"] = $item->GetProperty("MetaDescription");
//					$this->header["InsideModule"] = 1;

					if ($request->GetIntProperty('ViewCategoryID') > 0)
					{
						array_push($this->header["Navigation"],
							array(
								"StaticPath" => $request->GetProperty("Category_StaticPath"),
								"PageURL" => $request->GetProperty("Category_CategoryURL"),
								"Title" => $request->GetProperty("Category_Title")
								)
							);
					}

					array_push($this->header["Navigation"],
						array(
							"StaticPath" => $item->GetProperty("StaticPath"),
							"PageURL" => $item->GetProperty("ItemURL"),
							"Title" => $item->GetProperty("Title")
							)
						);

					$this->header["Template"] = $this->tmplPrefix."item_view.html";
					$publicPage = new PublicPage($this->module);
					$content = $publicPage->Load($this->tmplPrefix."item_view.html", $this->header, $this->pageID);

					$content->SetLoop("Navigation", $this->header["Navigation"]);

					// Item List for this infoblock 
					$itemList = new InfoblockItemList($this->module, $this->config);
					$itemList->Load($request);									
					$content->LoadFromObjectList("ItemList", $itemList);
					
					if ($request->GetIntProperty('ViewCategoryID') > 0)
					{
						$content->SetVar("CategoryTitle", $request->GetProperty("Category_Title"));
						$content->SetVar("CategoryDescription", $request->GetProperty("Category_Description"));
						$content->SetVar("CategoryContent", $request->GetProperty("Category_Content"));
					}

					$backURL = $this->header["Navigation"][count($this->header["Navigation"]) - 2]['PageURL'];
					//It works bad, if you have anouncements in footer on all pages, it's just redirect you on previous page in history
					//Also, if you have announcements on home page, the same missunderstandings will be
/*
					if (isset($_SERVER["HTTP_REFERER"]))
					{
						$result = parse_url($_SERVER["HTTP_REFERER"]);
						if (isset($result["host"]) && $result["host"] == $_SERVER["HTTP_HOST"])
							$backURL = $_SERVER["HTTP_REFERER"];
					}
*/
					$content->SetVar("BackURL", $backURL);

					$content->LoadFromObject($item);

					if ($request->GetIntProperty('ViewCategoryID') > 0)
					{
						// Load categories if they are exists
						$request->SetProperty('BaseURL', $this->baseURL);
						$categoryList = new InfoblockCategoryList($this->module, $this->config);
						$categoryList->Load($request);
						$content->LoadFromObjectList('CategoryList', $categoryList);
					}
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
			$content->SetVar('PageTitleH1', $pageTitleH1);
			$content->SetVar('PageTitle', $pageTitle);
			$content->SetVar('PageDescription', $pageDescription);
			$content->SetVar('PageContent', $this->content);
			$content->SetVar('CurrentPageURL', $this->header['CurrentPageURL']);

			$publicPage->Output($content);
		}
		else
		{
			Send404();
		}
	}

	function ShowXML()
	{
		$request = new LocalObject(array_merge($_GET, $_POST));
		$request->SetProperty('PageID', $this->pageID);
		$request->SetProperty('BaseURL', 'http://'.$_SERVER['HTTP_HOST'].$this->baseURL);
		$request->SetProperty('ShowActive', 'Y');

		$categoryList = new InfoblockCategoryList($this->module, $this->config);
		$categoryList->Load($request);
		$categoryCount = $categoryList->GetCountTotalItems();
		$categories = $categoryList->GetItems();

		$chunks = count($this->pathInsideModule);

		$found = false;
		if ($chunks == 0)
		{
			$found = true;
			$url = $this->header['CurrentPageURL'];
		}
		else if ($chunks == 1 && $categoryCount > 0)
		{
			for ($i = 0; $i < count($categories); $i++)
			{
				if (substr($this->pathInsideModule[0], 0, -4) == $categories[$i]['StaticPath'])
				{
					$request->SetProperty('ViewCategoryID', $categories[$i]['CategoryID']);
					$request->SetProperty('BaseURL', $request->GetProperty('BaseURL').'/'.$categories[$i]['StaticPath']);
					$url = $request->GetProperty('BaseURL').'/'.$categories[$i]['StaticPath'].'/';
					$found = true;
					break;
				}
			}
		}

		if ($found)
		{
			$itemList = new InfoblockItemList($this->module, $this->config);
			$itemList->Load($request);

			$adminPage = new PopupPage($this->module, true);
			$content = $adminPage->Load("rss.xml");

			$content->SetVar("Title", $this->header['Title']);
			$content->SetVar("BaseURL", $url);
			$content->SetVar("Description", $this->header['Description']);
			$content->SetVar("Language", DATA_LANGCODE);
			$content->SetVar("LastModified", $itemList->GetLastModified($this->pageID, $request->GetProperty('ViewCategoryID')));
			$content->SetVar("Generator", $this->config['Generator']);
			$content->SetVar("Webmaster", $this->config['Webmaster']);

			$content->LoadFromObjectList("ItemList", $itemList);

			$language =& GetLanguage();
			echo "<?xml version=\"1.0\" encoding=\"".$language->GetHTMLCharset()."\"?>";
			$adminPage->Output($content);
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
		$request->SetProperty('BaseURL', $this->baseURL);
		$request->SetProperty('ShowActive', 'Y');

		// Is needed to get page parameter name
		$itemList = new InfoblockItemList($this->module, $this->config);

		$categoryList = new InfoblockCategoryList($this->module, $this->config);
		$categoryList->Load($request);
		$categoryCount = $categoryList->GetCountTotalItems();
		$categories = $categoryList->GetItems();

		$chunks = count($this->pathInsideModule);
		if ($chunks == 1 && $this->pathInsideModule[0] == '')
			$path = array(INDEX_PAGE.HTML_EXTENSION);
		else
			$path = $this->pathInsideModule;

		$categoryInfo = null;

		$pageMatch = array();

		if ($categoryCount > 0)
		{
			if ($chunks == 0 || $this->header['CurrentPageURL'] == $this->baseURL.'/'.implode('/', $path))
			{
				$request->SetProperty('View', 'CategoryList');
			}
			else if ($chunks == 1 || ($chunks == 2 && ($this->pathInsideModule[1] == "" || preg_match("/^([0-9]+)".str_replace(".", "\\.", HTML_EXTENSION)."$/", $this->pathInsideModule[1], $pageMatch))))
			{
				for ($i = 0; $i < count($categories); $i++)
				{
					if ($categories[$i]['StaticPath'] == $this->pathInsideModule[0])
					{
						$categoryInfo = $categories[$i];
						$request->SetProperty('View', 'ItemList');

						if (count($pageMatch) == 2)
						{
							if ($pageMatch[1] == 1)
								Send404();
							else
								$request->SetProperty($itemList->GetPageParam(), $pageMatch[1]);
						}
						else
						{
							$request->SetProperty($itemList->GetPageParam(), 1);
						}

						break;
					}
				}

				if ($chunks == 1)
				{
					if ($categoryInfo)
					{
						// Path like /base/url/category is incorrect,
						// Redirect to correct path /base/url/category/
						Send301($this->baseURL.'/'.$categoryInfo['StaticPath'].'/');
					}
					else
					{
						$request->SetProperty('View', 'ItemView');
						$request->SetProperty('StaticPath', substr($this->pathInsideModule[0], 0, -strlen(HTML_EXTENSION)));
					}
				}
			}
			else if ($chunks == 2 && $this->pathInsideModule[1] == INDEX_PAGE.HTML_EXTENSION)
			{
				Send301($this->baseURL."/".$this->pathInsideModule[0]."/");
			}
			else if ($chunks == 2)
			{
				for ($i = 0; $i < count($categories); $i++)
				{
					if ($categories[$i]['StaticPath'] == $this->pathInsideModule[0])
					{
						$categoryInfo = $categories[$i];
						$request->SetProperty('View', 'ItemView');
						$request->SetProperty('StaticPath', substr($this->pathInsideModule[1], 0, -strlen(HTML_EXTENSION)));
					}
				}
			}
		}
		else
		{
			if ($chunks == 0
				||
				($chunks == 1 && preg_match("/^([0-9]+)".str_replace(".", "\\.", HTML_EXTENSION)."$/", $this->pathInsideModule[0], $pageMatch))
				||
				($this->header['CurrentPageURL'] == $this->baseURL.'/'.implode('/', $path))
			)
			{
				$request->SetProperty('View', 'ItemList');
				if (count($pageMatch) == 2)
				{
					if ($pageMatch[1] == 1)
						Send404();
					else
						$request->SetProperty($itemList->GetPageParam(), $pageMatch[1]);
				}
				else
				{
					$request->SetProperty($itemList->GetPageParam(), 1);
				}
			}
			else if ($chunks == 1)
			{
				$request->SetProperty('View', 'ItemView');
				$request->SetProperty('StaticPath', substr($this->pathInsideModule[0], 0, -strlen(HTML_EXTENSION)));
			}
		}

		if (!is_null($categoryInfo))
		{
			$request->SetProperty('ViewCategoryID', $categoryInfo['CategoryID']);

			//Added later, maybe will be better to load by ViewCategoryID in categorylist?
			$request->SetProperty('CategoryID', $categoryInfo['CategoryID']);

			$tempChunks = explode('/', $categoryInfo["CategoryURL"]);
			array_pop($tempChunks);
			$request->SetProperty('BaseURL', implode('/', $tempChunks));
			foreach ($categoryInfo as $k => $v)
			{
				$request->SetProperty('Category_'.$k, $v);
			}
		}

		$_GET[$itemList->GetPageParam()] = $request->GetProperty($itemList->GetPageParam());
		$_POST[$itemList->GetPageParam()] = $request->GetProperty($itemList->GetPageParam());
		$_REQUEST[$itemList->GetPageParam()] = $request->GetProperty($itemList->GetPageParam());

		return $request;
	}

	function LoadMap($level)
	{
		$data = array();

		$categoryList = new InfoblockCategoryList($this->module, $this->config);
		$categoryList->Load(new LocalObject(array('BaseURL' => $this->baseURL, 'PageID' => $this->pageID)));

		$itemList = new InfoblockItemList($this->module, $this->config);

		$priority = GetPriority($level);
		$itemPriority = GetPriority($level + 1);

		if ($result = $categoryList->GetItems())
		{
			for ($i = 0; $i < count($result); $i++)
			{
				$data[] = array('PageURL' => 'http://'.$_SERVER['HTTP_HOST'].$result[$i]['CategoryURL'], 'LastModified' => $result[$i]['LastModified'], 'Priority' => $priority);
				$itemList->Load(new LocalObject(array('ViewCategoryID' => $result[$i]['CategoryID'], 'BaseURL' => $this->baseURL.'/'.$result[$i]['StaticPath'], 'PageID' => $this->pageID, 'ShowActive' => 'Y', 'FullList' => true)));
				if ($nResult = $itemList->GetItems())
				{
					for ($j = 0; $j < count($nResult); $j++)
					{
						$data[] = array('PageURL' => 'http://'.$_SERVER['HTTP_HOST'].$nResult[$j]['ItemURL'], 'LastModified' => $nResult[$j]['ItemDate'], 'Priority' => $itemPriority);
					}
				}
			}
		}
		else
		{
			$itemList->Load(new LocalObject(array('BaseURL' => $this->baseURL, 'PageID' => $this->pageID, 'ShowActive' => 'Y', 'FullList' => true)));
			if ($nResult = $itemList->GetItems())
			{
				for ($j = 0; $j < count($nResult); $j++)
				{
					$data[] = array('PageURL' => 'http://'.$_SERVER['HTTP_HOST'].$nResult[$j]['ItemURL'], 'LastModified' => $nResult[$j]['ItemDate'], 'Priority' => $priority);
				}
			}
		}

		return $data;
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

			if (intval($config['AnnouncementCount']) == 0)
				continue;

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

			// RSS Feed URL
			$data[strtoupper($module).'_'.$staticPath.'_RSSFeedURL'] = GetDirPrefix().implode('/', $path).'.xml';

			$itemList = new InfoblockItemList($module, $config);
			$req = new LocalObject(array('PageID' => $page->GetProperty('PageID'),
				'BaseURL' => GetDirPrefix().implode('/', $path)));

			$categoryList = new InfoblockCategoryList($module, $config);
			$categoryList->Load($req);

			if ($categoryList->GetCountTotalItems() > 0)
			{
				// Load announcement lists by categories
				$categories = $categoryList->GetItems();

				for ($j = 0; $j < count($categories); $j++)
				{
					$req->SetProperty('ViewCategoryID', $categories[$j]['CategoryID']);
					$itemList->LoadAnnouncementList($req);
					$data[strtoupper($module).'_'.$staticPath.'_'.$categories[$j]['StaticPath'].'_ItemList'] = $itemList->GetItems();
					$data[strtoupper($module).'_'.$staticPath.'_'.$categories[$j]['StaticPath'].'_ItemCount'] = $itemList->GetCountTotalItems();
					$categories[$j]['ItemList'] = $itemList->GetItems();
					$categories[$j]['ItemCount'] = $itemList->GetCountTotalItems();
				}
				$data[strtoupper($module).'_'.$staticPath.'_CategoryList'] = $categories;
                                
              	// Load announcement list
                $req->SetProperty('ViewCategoryID', 0);
				$itemList->LoadAnnouncementList($req);
				$data[strtoupper($module).'_'.$staticPath.'_ItemList'] = $itemList->GetItems();
				$data[strtoupper($module).'_'.$staticPath.'_ItemCount'] = $itemList->GetCountTotalItems();
			}
			else
			{
				// Load announcement list
				$itemList->LoadAnnouncementList($req);
				$data[strtoupper($module).'_'.$staticPath.'_ItemList'] = $itemList->GetItems();
				$data[strtoupper($module).'_'.$staticPath.'_ItemCount'] = $itemList->GetCountTotalItems();
			}
		}

		return $data;
	}

	function RemoveModuleData()
	{
		$categoryList = new InfoblockCategoryList($this->module, $this->module);
		$categoryList->RemoveByPageID($this->pageID, $this->config);
	}
}
?>
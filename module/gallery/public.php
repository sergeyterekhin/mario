<?php

require_once(dirname(__FILE__)."/init.php");
require_once(dirname(__FILE__)."/include/category_list.php");
require_once(dirname(__FILE__)."/include/category.php");
require_once(dirname(__FILE__)."/include/media_list.php");
require_once(dirname(__FILE__)."/include/media.php");
es_include("modulehandler.php");

class GalleryHandler extends ModuleHandler
{
	function ProcessPublic()
	{
		$this->header["InsideModule"] = $this->module;
		$urlParser =& GetURLParser();

		if ($urlParser->IsHTML())
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
				$categoryList = new GalleryCategoryList($this->module, $this->config);
				$categoryList->Load($request);

				$publicPage = new PublicPage($this->module);
				$content = $publicPage->Load($this->tmplPrefix."category_list.html", $this->header, $this->pageID);

				$content->SetLoop("Navigation", $this->header["Navigation"]);

				$content->LoadFromObjectList('CategoryList', $categoryList);

				// Load media list which is assigned directly to page
				$mediaList = new GalleryMediaList($this->module, $this->pageID, $this->config);
				$mediaList->Load($request);
				$content->LoadFromObjectList('MediaList', $mediaList);

				break;
			case 'MediaList':
				$mediaList = new GalleryMediaList($this->module, $this->pageID, $this->config);
				$mediaList->Load($request);

				// Page number is too large
				if (strcmp($request->GetProperty($mediaList->GetPageParam()), $mediaList->GetCurrentPage()) != 0)
					Send404();

				if ($request->GetIntProperty('ViewCategoryID') > 0)
				{
					// Replace page data by category data in the header
					$this->header["Title"] = $request->GetProperty("Category_Title");
					$this->header["Description"] = $request->GetProperty("Category_Description");
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

				$publicPage = new PublicPage($this->module);
				$content = $publicPage->Load($this->tmplPrefix."media_list.html", $this->header, $this->pageID);

				$content->SetLoop("Navigation", $this->header["Navigation"]);

				if ($request->GetIntProperty('ViewCategoryID') > 0)
				{
					$content->SetVar("CategoryTitle", $request->GetProperty("Category_Title"));
					$content->SetVar("CategoryDescription", $request->GetProperty("Category_Description"));
					$content->SetVar("CategoryContent", $request->GetProperty("Category_Content"));
					$urlFirstPage = $request->GetProperty('BaseURL')."/".INDEX_PAGE.HTML_EXTENSION;
				}
				else
				{
					$urlFirstPage = $this->header['CurrentPageURL'];
				}

				$content->SetVar("CurrentPage", $mediaList->GetCurrentPage());
				$content->LoadFromObjectList('MediaList', $mediaList);

				$url = $request->GetProperty('BaseURL')."/[[".$mediaList->GetPageParam()."]]".HTML_EXTENSION;
				$content->SetLoop("Paging", $mediaList->GetPagingAsArray($url, $urlFirstPage));

				if ($request->GetIntProperty('ViewCategoryID') > 0)
				{
					// Load categories if they are exists
					$request->SetProperty('BaseURL', $this->baseURL);
					$categoryList = new GalleryCategoryList($this->module, $this->config);
					$request->SetProperty('CategoryID', $request->GetIntProperty('ViewCategoryID'));
					$categoryList->Load($request);
					$content->LoadFromObjectList('CategoryList', $categoryList);
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

	function ParseRequest()
	{
		$request = new LocalObject(array_merge($_GET, $_POST));
		$request->SetProperty('PageID', $this->pageID);
		$request->SetProperty('BaseURL', $this->baseURL);
		$request->SetProperty('ShowActive', 'Y');

		// Is needed to get page parameter name
		$mediaList = new GalleryMediaList($this->module, $this->pageID, $this->config);

		$categoryList = new GalleryCategoryList($this->module, $this->config);
		$categoryList->Load($request);
		$categoryCount = $categoryList->GetCountTotalItems();
		$categories = $categoryList->GetItems();

		$categoryInfo = null;

		$chunks = count($this->pathInsideModule);

		$pageMatch = array();

		if ($categoryCount > 0)
		{
			if ($this->IsMainHTML())
			{
				$request->SetProperty('View', 'CategoryList');
			}
			else if ($chunks == 1 || ($chunks == 2 && ($this->pathInsideModule[1] == "" || $this->pathInsideModule[1] == INDEX_PAGE.HTML_EXTENSION || preg_match("/^([0-9]+)".str_replace(".", "\\.", HTML_EXTENSION)."$/", $this->pathInsideModule[1], $pageMatch))))
			{
				for ($i = 0; $i < count($categories); $i++)
				{
					if ($categories[$i]['StaticPath'] == $this->pathInsideModule[0])
					{
						$categoryInfo = $categories[$i];
						$request->SetProperty('View', 'MediaList');

						if (count($pageMatch) == 2)
						{
							if ($pageMatch[1] == 1)
								Send404();
							else
								$request->SetProperty($mediaList->GetPageParam(), $pageMatch[1]);
						}
						else
						{
							$request->SetProperty($mediaList->GetPageParam(), 1);
						}

						break;
					}
				}

				if ($chunks == 1 && $categoryInfo)
				{
					// Path like /base/url/category.html is incorrect,
					// Redirect to correct path /base/url/category/index.html
					Send301($this->baseURL.'/'.$categoryInfo['StaticPath'].'/'.INDEX_PAGE.HTML_EXTENSION);
				}
			}
		}
		else
		{
			if ($this->IsMainHTML() || ($chunks == 1 && preg_match("/^([0-9]+)".str_replace(".", "\\.", HTML_EXTENSION)."$/", $this->pathInsideModule[0], $pageMatch)))
			{
				$request->SetProperty('View', 'MediaList');
				if (count($pageMatch) == 2)
				{
					if ($pageMatch[1] == 1)
						Send404();
					else
						$request->SetProperty($mediaList->GetPageParam(), $pageMatch[1]);
				}
				else
				{
					$request->SetProperty($mediaList->GetPageParam(), 1);
				}
			}
		}

		if (!is_null($categoryInfo))
		{
			$request->SetProperty('ViewCategoryID', $categoryInfo['CategoryID']);
			$tempChunks = explode('/', $categoryInfo["CategoryURL"]);
			array_pop($tempChunks);
			$request->SetProperty('BaseURL', implode('/', $tempChunks));
			foreach ($categoryInfo as $k => $v)
			{
				$request->SetProperty('Category_'.$k, $v);
			}
		}

		$_GET[$mediaList->GetPageParam()] = $request->GetProperty($mediaList->GetPageParam());
		$_POST[$mediaList->GetPageParam()] = $request->GetProperty($mediaList->GetPageParam());
		$_REQUEST[$mediaList->GetPageParam()] = $request->GetProperty($mediaList->GetPageParam());

		return $request;
	}

	function LoadMap($level)
	{
		$data = array();

		$categoryList = new GalleryCategoryList($this->module, $this->config);
		$categoryList->Load(new LocalObject(array('BaseURL' => $this->baseURL, 'PageID' => $this->pageID)));

		if ($result = $categoryList->GetItems())
		{
			for ($i = 0; $i < count($result); $i++)
			{
				$data[] = array('PageURL' => 'http://'.$_SERVER['HTTP_HOST'].$result[$i]['CategoryURL'], 'LastModified' => $result[$i]['LastModified'], 'Priority' => GetPriority($level));
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
				$data[$key] = GetDirPrefix().implode('/', $path).'/'.INDEX_PAGE.HTML_EXTENSION;
			else
				$data[$key] = GetDirPrefix().implode('/', $path).HTML_EXTENSION;

			if ($config['AnnouncementCount'] == "")
				continue;

			$mediaList = new GalleryMediaList($module, $page->GetProperty('PageID'), $config);
			$req = new LocalObject(array('PageID' => $page->GetProperty('PageID'),
				'BaseURL' => GetDirPrefix().implode('/', $path),
				'AnnouncementList' => true));

			$categoryList = new GalleryCategoryList($module, $config);
			$categoryList->Load($req);

			if ($categoryList->GetCountTotalItems() > 0)
			{
				// Load announcement lists by categories
				$categories = $categoryList->GetItems();
				$data[strtoupper($module).'_'.$staticPath.'_CategoryList'] = $categories;

				for ($j = 0; $j < count($categories); $j++)
				{
					$req->SetProperty('ViewCategoryID', $categories[$j]['CategoryID']);
					$mediaList->Load($req);
					$data[strtoupper($module).'_'.$staticPath.'_'.$categories[$j]['StaticPath'].'_MediaList'] = $mediaList->GetItems();
					$data[strtoupper($module).'_'.$staticPath.'_'.$categories[$j]['StaticPath'].'_MediaCount'] = $mediaList->GetCountTotalItems();
					$categories[$j]['MediaList'] = $mediaList->GetItems();
					$categories[$j]['MediaCount'] = $mediaList->GetCountTotalItems();
				}
			}
			else
			{
				// Load announcement list
				$mediaList->Load($req);
				$data[strtoupper($module).'_'.$staticPath.'_MediaList'] = $mediaList->GetItems();
				$data[strtoupper($module).'_'.$staticPath.'_MediaCount'] = $mediaList->GetCountTotalItems();
			}
		}
		
		return $data;
	}

	function RemoveModuleData()
	{
		$categoryList = new GalleryCategoryList($this->module, $this->config);
		$categoryList->RemoveByPageID($this->pageID);
	}
}
?>
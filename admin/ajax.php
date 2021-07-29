<?php

define("IS_ADMIN", true);
require_once(dirname(__FILE__)."/../include/init.php");
es_include("localpage.php");
es_include("page.php");
es_include("pagelist.php");
es_include("user.php");

$result = array();

$user = new User();
if (!$user->LoadBySession() || !$user->Validate(array(INTEGRATOR, ADMINISTRATOR, MODERATOR)))
{
	$result["SessionExpired"] = GetTranslation("your-session-expired");
}
else 
{
	$request = new LocalObject(array_merge($_GET, $_POST));
	switch ($request->GetProperty("Action"))
	{
		case "LoadSEO":
			$page = new Page();
			$result = $page->GetSEO($request->GetProperty("PageID"));
			break;
	
		case "SaveSEO":
			$page = new Page();
			$page->LoadFromObject($request);
			$result = $page->SaveSEO();
			break;
	
		case "LoadMenu":
			$page = new Page();
			if ($page->LoadByID($request->GetProperty("PageID")))
			{
				$page->SetProperty("MenuImages", $page->GetMenuImages());
				$result = $page->GetProperties();
			}
			else
			{
				$result = array("Title" => "", "Description" => "", "StaticPath" => "", "MenuImages" => $page->GetMenuImages());
			}
			break;
	
		case "SaveMenu":
			$page = new Page();
	
			$fields = array('PageID', 'Title', 'Description', 'StaticPath', 'Active', 'Type', 'LanguageCode');
			for ($i = 0; $i < count($page->params); $i++)
			{
				$fields[] = $page->params[$i]['Name'];
			}
			$request->SetProperty('Type', 0);
			$page->LoadFromObject($request, $fields);
	
			if ($page->Save())
			{
				$result = array('PageID' => $page->GetProperty('PageID'), 'Title' => $page->GetProperty('Title'), 'ShortTitle' => SmallString($page->GetProperty('Title'), 15));
			}
			else
			{
				$result["Error"] = $page->GetErrorsAsString();
			}
			break;
	
		case "Remove":
			$page = new Page();
			$page->Remove($request->GetIntProperty("PageID"));
			$result = "Done";
			break;
	
		case "SwitchActive":
			$page = new Page();
			$page->SwitchActive($request->GetIntProperty("PageID"), $request->GetProperty("Active"));
			$result = "Done";
			break;
	
		case "SaveSort":
			$pageList = new PageList();
			$result = $pageList->SaveSort($request->GetProperty('MenuPageID'), $request->GetProperty('PageList'));
			break;
	
		case "RemoveMenuImage":
			$stmt = GetStatement();
	
			if ($request->GetProperty('SavedImage'))
			{
				@unlink(MENU_IMAGE_DIR.$request->GetProperty('SavedImage'));
			}
	
			if ($request->GetIntProperty('ItemID') > 0 && preg_match("/^MenuImage([0-9]+)$/", $request->GetProperty('ImageName')))
			{
				$imageFile = $stmt->FetchField("SELECT ".$request->GetProperty('ImageName')."
				FROM `page` WHERE PageID=".$request->GetIntProperty('ItemID'));
	
				if ($imageFile) @unlink(MENU_IMAGE_DIR.$imageFile);
	
				$query = "UPDATE `page`
				SET ".$request->GetProperty('ImageName')."=NULL,
				".$request->GetProperty('ImageName')."Config=NULL 
				WHERE WebsiteID=".intval(WEBSITE_ID)." AND PageID=".$request->GetIntProperty('ItemID');
				$stmt->Execute($query);
			}
			$result = "Done";
			break;
	
		case "GetChildren":
			$page = new Page();
			if (!$page->LoadByID($request->GetProperty("PageID")))
			{
				$page->SetProperty("LanguageCode", $request->GetProperty("LanguageCode"));
			}
			$result["StaticPath"] = $page->GetProperty("StaticPath");
			$result["PageList"] = $page->GetChildren($request->GetProperty("Type"));
			break;
	
		case "SaveVariableToXML":
			$request->SetProperty("VariableValue", PrepareContentBeforeSave($request->GetProperty("VariableValue")));
			$language = GetLanguage();
	
			if ($request->GetProperty('Type') != 'php')
				$type = 'template';
			else
				$type = 'php';
	
			$data = $language->GetXML($request->GetProperty('Module'), $type);
			if ($type == 'template')
			{
				if (isset($data[$request->GetProperty('Type')][$request->GetProperty('Template')][$request->GetProperty('TagName')]))
				{
					$data[$request->GetProperty('Type')][$request->GetProperty('Template')][$request->GetProperty('TagName')]["Value"] = $request->GetProperty('VariableValue');
					$language->SaveXML($request->GetProperty('Module'), $type, $data);
				}
			}
			else
			{
				if (isset($data[$request->GetProperty('TagName')]))
				{
					$data[$request->GetProperty('TagName')]["Value"] = $request->GetProperty('VariableValue');
					$language->SaveXML($request->GetProperty('Module'), $type, $data);
				}
			}

			$result = PrepareContentBeforeShow($request->GetProperty('VariableValue'));
			break;
	
		case "ValidateStaticPath":
			$request = new LocalObject($_POST);
			$result["ValidStaticPath"] = GetValidStaticPath($request->GetProperty("StaticPath"), $request->GetProperty("Table"));
			break;
			
		case "RemoveUserImage":
			$user = new User();
			$user->RemoveUserImage($request->GetProperty("ItemID"), $request->GetProperty('SavedImage'));
			$result = "Done";
			break;

	}
}

echo json_encode($result);

?>
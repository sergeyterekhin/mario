<?php

define("IS_ADMIN", true);
require_once(dirname(__FILE__)."/../include/init.php");
es_include("localpage.php");
es_include("urlfilter.php");
es_include("user.php");
es_include("userlist.php");

$auth = new User();
$auth->ValidateAccess(array(INTEGRATOR, ADMINISTRATOR, MODERATOR));
$roleList = $auth->GetAvailableRoles($auth->GetProperty("Role"));

$request = new LocalObject(array_merge($_GET, $_POST));

$adminPage = new AdminPage();

$userList = new UserList();
$user = new User();

$urlFilter = new URLFilter();
$urlFilter->LoadFromObject($request, array($userList->GetPageParam(), $userList->GetOrderByParam(), "SearchString"));
$urlString = $urlFilter->GetForURL();

if ($request->IsPropertySet("UserID"))
{
	if ($auth->GetProperty('UserID') == $request->GetProperty("UserID"))
		$role = null;
	else
		$role = $auth->GetProperty("Role");

	if ($user->LoadByID($request->GetProperty("UserID"), $role, $auth->GetProperty("WebsiteID")))
		$title = GetTranslation("title-user-edit");
	else
		$title = GetTranslation("title-user-add");

	if ($request->GetProperty("UserID") == $auth->GetProperty("UserID"))
	{
		$navigation = array(
			array("Title" => $title, "Link" => "user.php?UserID=".$request->GetProperty("UserID"))
		);
	}
	else
	{
		$navigation = array(
			array("Title" => GetTranslation("title-user-list"), "Link" => "user.php"),
			array("Title" => $title, "Link" => "user.php?UserID=".$request->GetProperty("UserID"))
		);
	}
	$header = array(
		"Title" => $title,
		"Navigation" => $navigation
	);
	$content = $adminPage->Load("user_edit.html", $header);
	$content->SetLoop("Navigation", $navigation);

	if ($request->GetProperty("Do") == "Save")
	{
		// Append instead of Load to avoid lost of Created, LastLogin & LastIP fields data
		$user->AppendFromObject($request);

		if ($user->Save($auth->GetProperty("Role"), $auth->GetProperty("WebsiteID"), $auth->GetProperty("UserID")))
		{
			if ($request->GetProperty("UserID") != $auth->GetProperty("UserID"))
			{
				header("Location: ".ADMIN_PATH."user.php".($urlString ? "?".$urlString : ""));
				exit;
			}
		}
	}

	for ($i = 0; $i < count($roleList); $i++)
	{
		if ($user->GetProperty("Role") == $roleList[$i]["Value"])
		{
			$roleList[$i]["Selected"] = true;
		}
	}

	$content->SetLoop("AvailableWebsiteList", $user->GetAvailableWebsites($auth->GetProperty("Role"), $auth->GetProperty("WebsiteID")));
	$forRoles = array(ADMINISTRATOR, MODERATOR, USER);
	if (!in_array($user->GetProperty("Role"), $forRoles))
	{
		$content->SetVar("HideWebsiteList", true);
	}

	if ($user->GetProperty("Role") == ADMINISTRATOR)
	{
		$content->SetVar("AllWebsites", 1);
	}

	$roles = "";
	for ($i = 0; $i < count($forRoles); $i++)
	{
		$roles .= "roles[roles.length] = \"".$forRoles[$i]."\";\r\n";
	}
	$content->SetVar("Roles", $roles);

	if ($auth->GetProperty("UserID") == $user->GetProperty("UserID"))
	{
		$content->SetVar("MyProfile", true);
	}

	$content->LoadErrorsFromObject($user);
	$content->LoadMessagesFromObject($user);
	$content->LoadFromObject($user);
	$content->SetLoop("UserImageParamList", $user->GetImageParams());
}
else
{
	$title = GetTranslation("title-user-list");

	$navigation = array(
		array("Title" => $title, "Link" => "user.php")
	);
	$header = array(
		"Title" => $title,
		"Navigation" => $navigation
	);
	$content = $adminPage->Load("user_list.html", $header);
	$content->SetLoop("Navigation", $navigation);

	$roles = array();
	for ($i = 0; $i < count($roleList); $i++)
	{
		$roles[$i] = $roleList[$i]["Value"];
		if ($request->GetProperty("ViewRole") == $roleList[$i]["Value"])
		{
			$roleList[$i]["Selected"] = true;
		}
	}
	$request->SetProperty("RoleList", $roles);

	$request->SetProperty("WebsiteID", $auth->GetProperty("WebsiteID"));
	$request->SetProperty("CurrentUserID", $auth->GetProperty("UserID"));

	if ($request->GetProperty("Do") == "Remove")
	{
		$userList->Remove($request);
		$content->LoadMessagesFromObject($userList);
	}

	// TODO: OrderBy
	$userList->LoadUserList($request);

	$content->LoadFromObjectList("UserList", $userList);

	$pagingULRString = $urlFilter->GetForURL(array($userList->GetPageParam()));
	$url = "user.php".($pagingULRString ? "?".$pagingULRString : "");
	$content->SetVar("Paging", $userList->GetPagingAsHTML($url));
	if ($request->GetProperty('SearchString'))
		$content->SetVar("ListInfo", GetTranslation('list-info2', array('Request' => $request->GetProperty('SearchString'), 'Total' => $userList->GetCountTotalItems())));
	else
		$content->SetVar("ListInfo", GetTranslation('list-info1', array('Page' => $userList->GetItemsRange(), 'Total' => $userList->GetCountTotalItems())));
}

if ($urlString)
{
	$content->SetVar("ParamsForURL1", "?".$urlString);
	$content->SetVar("ParamsForURL2", "&".$urlString);
}
$content->SetVar("ParamsForForm", $urlFilter->GetForForm());

$content->LoadFromObject($urlFilter);

$content->SetLoop("RoleList", $roleList);

$adminPage->Output($content);

?>
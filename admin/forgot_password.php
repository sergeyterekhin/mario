<?php

define("IS_ADMIN", true);
require_once(dirname(__FILE__)."/../include/init.php");
es_include("localpage.php");
es_include("user.php");

$user = new User();
$post = new LocalObject($_POST);

$adminPage = new PopupPage();
$content = $adminPage->Load("forgot_password.html");

if ($post->GetProperty('Action'))
{
	$user->SetProperty('Email', $post->GetProperty('Email'));

	if ($user->SendPasswordToEmail())
	{
		$content->LoadMessagesFromObject($user);
	}
	else
	{
		$content->SetVar('Email', $post->GetProperty('Email'));
		$content->LoadErrorsFromObject($user);
	}
}

$adminPage->Output($content);

?>
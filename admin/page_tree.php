<?php

define("IS_ADMIN", true);
require_once(dirname(__FILE__)."/../include/init.php");
es_include("localpage.php");
es_include("page.php");
es_include("pagelist.php");
es_include("user.php");
es_include("filesys.php");

$user = new User();
$user->ValidateAccess(array(INTEGRATOR, ADMINISTRATOR, MODERATOR));

$adminPage = new AdminPage();
$title = GetTranslation("title-site-structure");
$styleSheets = array(
	array("StyleSheetFile" => ADMIN_PATH."template/plugins/uikit/css/uikit.min.css"),
	array("StyleSheetFile" => ADMIN_PATH."template/plugins/uikit/css/components/nestable.min.css"),
);
$fileSys = new FileSys();
$javaScripts = array(
	array("JavaScriptFile" => ADMIN_PATH."template/js/tree.js"),
	array("JavaScriptFile" => ADMIN_PATH."template/plugins/uikit/js/uikit.js"),
	array("JavaScriptFile" => ADMIN_PATH."template/plugins/uikit/js/components/nestable.js") 	
);
$navigation = array(
	array("Title" => $title, "Link" => "page_tree.php")
);
$header = array(
	"Title" => $title,
	"Navigation" => $navigation,
	"StyleSheets" => $styleSheets,
	"JavaScripts" => $javaScripts
);
$content = $adminPage->Load("page_tree.html", $header);
$content->SetLoop("Navigation", $navigation);
$content->SetVar('LanguageCode', DATA_LANGCODE);
$content->SetVar('Integrator', ($user->GetProperty('Role') == 'integrator' ? true : false));

// Load page list
$pageList = new PageList();
$content->SetLoop("PageList", $pageList->GetPageList());

// Load module list for ModuleForm
es_include("module.php");
$module = new Module();
$content->SetLoop("ModuleList", $module->GetModuleList());

$adminPage->Output($content);

?>
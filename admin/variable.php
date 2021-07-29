<?php

define("IS_ADMIN", true);
require_once(dirname(__FILE__)."/../include/init.php");
es_include("localpage.php");
es_include("urlfilter.php");
es_include("user.php");

$auth = new User();
$auth->ValidateAccess(array(INTEGRATOR, ADMINISTRATOR, MODERATOR));

$request = new LocalObject(array_merge($_GET, $_POST));

$adminPage = new AdminPage();

$title = GetTranslation('admin-menu-template-variables');
$javaScripts = array(
	array("JavaScriptFile" => ADMIN_PATH."template/js/variable.js"),
	array("JavaScriptFile" => CKEDITOR_PATH."ckeditor.js"),
	array("JavaScriptFile" => CKEDITOR_PATH."ajexFileManager/ajex.js")
);
$navigation = array(
	array("Title" => $title, "Link" => "variable.php")
);
$header = array(
	"Title" => $title,
	"Navigation" => $navigation,
	"JavaScripts" => $javaScripts
);
$content = $adminPage->Load("variable.html", $header);

$language = GetLanguage();

$k = 0;
$sections = array();
$selectedVariableData = array();

$sections[$k] = array('Name' => GetTranslation('xml-section-general'), 'Sections' => array());

// Load general template data
$data = $language->GetXML('', 'template');

foreach ($data as $k1 => $v1)
{
	foreach ($v1 as $k2 => $v2)
	{
		if (is_array($v2) && count($v2) > 0 && (!(array_keys($v2) == array("Value", "AttributeList")) || !(isset($v2["Value"]) && !is_array($v2["Value"]))))
		{
			$variableData = array();
			foreach ($v2 as $k3 => $v3)
			{
				$variableData[] = array_merge(array('TagName' => $k3), $v3);
			}
			$value = '/'.$k1.'/'.$k2;
			if ($request->GetProperty('section') == $value)
			{
				$selected = true;
				$selectedVariableData = $variableData;
			}
			else
			{
				$selected = false;
			}
			$sections[$k]['Sections'][] = array('Value' => $value, 'Name' => GetTranslation("xml-" . $k2) . ' - ' . $k1, 'Selected' => $selected, 'Data' => $variableData);
		}
	}
}

// Load general php data
$data = $language->GetXML('', 'php');
if (count($data) > 0)
{
	$variableData = array();
	foreach ($data as $k3 => $v3)
	{
		$variableData[] = array_merge(array('TagName' => $k3), $v3);
	}
	$value = '/php';
	if ($request->GetProperty('section') == $value)
	{
		$selected = true;
		$selectedVariableData = $variableData;
	}
	else
	{
		$selected = false;
	}
	$sections[$k]['Sections'][] = array('Value' => $value, 'Name' => GetTranslation('php-messages'), 'Selected' => $selected, 'Data' => $variableData);
}

if (count($sections[$k]['Sections']) == 0)
	unset($sections[$k]);
else
	$k++;

// Load for modules
es_include("module.php");
$module = new Module();
$mList = $module->GetModuleList('', false, true);
for ($j = 0; $j < count($mList); $j++)
{
	$sections[$k] = array('Name' => $mList[$j]["Title"], 'Sections' => array());

	// Load module template data
	$data = $language->GetXML($mList[$j]["Folder"], 'template');
	foreach ($data as $k1 => $v1)
	{
		foreach ($v1 as $k2 => $v2)
		{
			if (is_array($v2) && count($v2) > 0 && (!(array_keys($v2) == array("Value", "AttributeList")) || !(isset($v2["Value"]) && !is_array($v2["Value"]))))
			{
				$variableData = array();
				foreach ($v2 as $k3 => $v3)
				{
					$variableData[] = array_merge(array('TagName' => $k3), $v3);
				}
				$value = $mList[$j]["Folder"].'/'.$k1.'/'.$k2;
				if ($request->GetProperty('section') == $value)
				{
					$selected = true;
					$selectedVariableData = $variableData;
				}
				else
				{
					$selected = false;
				}
				$sections[$k]['Sections'][] = array('Value' => $value, 'Name' => GetTranslation("xml-" . $k2, $mList[$j]["Folder"]) . ' - ' . $k1, 'Selected' => $selected, 'Data' => $variableData);
			}
		}
	}

	// Load module php data
	$data = $language->GetXML($mList[$j]["Folder"], 'php');
	if (count($data) > 0)
	{
		$variableData = array();
		foreach ($data as $k3 => $v3)
		{
			$variableData[] = array_merge(array('TagName' => $k3), $v3);
		}
		$value = $mList[$j]["Folder"].'/php';
		if ($request->GetProperty('section') == $value)
		{
			$selected = true;
			$selectedVariableData = $variableData;
		}
		else
		{
			$selected = false;
		}
		$sections[$k]['Sections'][] = array('Value' => $value, 'Name' => GetTranslation('php-messages'), 'Selected' => $selected, 'Data' => $variableData);
	}

	if (count($sections[$k]['Sections']) == 0)
		unset($sections[$k]);
	else
		$k++;
}

if (count($selectedVariableData) == 0 && count($sections[0]['Sections']) > 0)
{
	$selectedVariableData = $sections[0]['Sections'][0]['Data'];
	$request->SetProperty('section', $sections[0]['Sections'][0]['Value']);
}

$content->SetLoop("SectionList", $sections);
$content->SetLoop("VariableList", $selectedVariableData);
$content->SetVar("SelectedSection", $request->GetProperty('section'));

$content->SetVar('TagName', GetTranslation('tag-name'));
$content->SetVar('VariableValue', GetTranslation('variable-value'));

$adminPage->Output($content);

?>
<?php

define("INFOBLOCK_IMAGE_DIR", PROJECT_DIR."website/".WEBSITE_FOLDER."/var/infoblock/");

define("INFOBLOCK_CATEGORY_IMAGE", "100x100|5|Admin,100x100|0|Full");
define("INFOBLOCK_ITEM_IMAGE", "100x100|5|Admin,100x100|0|Full");

$GLOBALS['moduleConfig']['infoblock'] = array(
	'AdminMenuIcon' => 'fa fa-newspaper-o',
	'ColorA' => '#8639c4', 'ColorI' => '#bcbcbc',
	'Config' => array(
		'CategoryImage' => '100x100|8|Thumb,500x500|8|Main',
		'CategoryImageKeepFileName' => 1,
		'ItemsPerPage' => 20,
		'ItemsOrderBy' => 'ItemDateDesc',
		'ItemImage' => '100x100|8|Thumb,500x500|8|Main',
		'ItemImageKeepFileName' => 1,
		'AnnouncementCount' => 3,
		'AnnouncementOrderBy' => 'ItemDateDesc',
		'Generator' => WEBSITE_NAME,
		'Webmaster' => 'info@'.$_SERVER["HTTP_HOST"],
		'FieldList' => '',
		'AdminMenuIcon' => ''
	)
);

?>
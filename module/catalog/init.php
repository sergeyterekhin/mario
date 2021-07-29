<?php

define("CATALOG_IMAGE_DIR", PROJECT_DIR."website/".WEBSITE_FOLDER."/var/catalog/");

define("CATALOG_CATEGORY_IMAGE", "100x100|5|Admin,100x100|0|Full");
define("CATALOG_ITEM_IMAGE", "100x100|5|Admin,100x100|0|Full");
define("CATALOG_FEATURED_IMAGE", "100x100|5|Admin,100x100|0|Full");
define("CATALOG_MEDIA_FILE", "400x300|5|Admin,900x600|1|AdminBig,100x100|0|Full");

$GLOBALS['moduleConfig']['catalog'] = array(
	'AdminMenuIcon' => 'fa fa-shopping-cart',
	'ColorA' => '#ff7f00', 'ColorI' => '#bcbcbc',
	'Config' => array(
		'CategoryURLPrefix' => 'category',
		'ItemURLPrefix' => 'item',
		'ItemsPerPage' => 20,
		'MediaPerPage' => 0,
		'ItemsOrderBy' => 'sortorder_asc',
		'FeaturedProductStaticPath' => 'catalog',
		'ItemDescriptions' => 'Description',
		'CategoryImage' => '100x100|8|Thumb,500x500|8|Main',
		'CategoryImageKeepFileName' => 1,
		'ItemImage' => '100x100|8|Thumb,500x500|8|Main',
		'ItemImageKeepFileName' => 1,
		'FeaturedImage' => '100x100|8|Thumb,500x500|8|Main',
		'FeaturedImageKeepFileName' => 1,
		'MediaFile' => '100x100|8|Thumb,500x500|8|Main',
		'MediaVideo' => '400x300',
		'MediaKeepFileName' => 1,
		'ItemDescriptionCount' => 1,
		'ffmpeg' => realpath(dirname(__FILE__).'/external/').'/ffmpeg.exe',
		'flvtool2' => realpath(dirname(__FILE__).'/external/').'/flvtool2.exe',
		'mencoder' => realpath(dirname(__FILE__).'/external/').'/mencoder.exe'
)
);

?>
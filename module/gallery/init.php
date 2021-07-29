<?php

define("GALLERY_IMAGE_DIR", PROJECT_DIR."website/".WEBSITE_FOLDER."/var/gallery/");

define("GALLERY_CATEGORY_IMAGE", "100x100|5|Admin,100x100|0|Full");
define("GALLERY_MEDIA_FILE", "400x300|5|Admin,900x600|1|AdminBig,100x100|0|Full");

$GLOBALS['moduleConfig']['gallery'] = array(
	'AdminMenuIcon' => 'fa fa-image',
	'ColorA' => '#73d2e6', 'ColorI' => '#bcbcbc',
	'Config' => array(
		'CategoryImage' => '100x100|8|Thumb,500x500|8|Main',
		'CategoryImageKeepFileName' => 1,
		'MediaFile' => '100x100|8|Thumb,500x500|8|Main',
		'MediaVideo' => '400x300',
		'MediaKeepFileName' => 1,
		'MediaPerPage' => 5,
		'AnnouncementCount' => 3,
		'AnnouncementOrderBy' => 'Position',
		'ffmpeg' => realpath(dirname(__FILE__).'/external/').'/ffmpeg.exe',
		'flvtool2' => realpath(dirname(__FILE__).'/external/').'/flvtool2.exe',
		'mencoder' => realpath(dirname(__FILE__).'/external/').'/mencoder.exe'
	)
);

?>
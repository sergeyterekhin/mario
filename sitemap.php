<?php

require_once(dirname(__FILE__)."/include/init.php");
$request = new LocalObject($_GET);

/*
 * For SEO purposes it is necessary to have possibility to put custom sitemap.xml for the site.
 */

$path = PROJECT_DIR."website/".WEBSITE_FOLDER."/sitemap.xml";
if(file_exists($path))
{
	header("Content-Type: text/xml");
	readfile($path);	
}
else
{
	include 'index.php';
}

?>
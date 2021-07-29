<?php

require_once(dirname(__FILE__)."/include/init.php");

$request = new LocalObject($_GET);
header("Content-Type: text/plain");
readfile(PROJECT_DIR."website/".WEBSITE_FOLDER."/robots.txt");

?>
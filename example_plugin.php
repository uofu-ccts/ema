<?php

/*
 * Output all PHP errors to the browser. Comment out when ready for production.
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/*
 * Builds a complete REDCap UI around the plugin.
 */
$HtmlPage = new HtmlPage();
$HtmlPage->ProjectHeader();

?>
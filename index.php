<?php
/*
 * Base Path of the Application
 */
$basePath = dirname(__FILE__);

/*
 * Application Path
 */
$appPath = $basePath.DIRECTORY_SEPARATOR .'app';

/*
 * Content Path
*/
$contentPath = $basePath.DIRECTORY_SEPARATOR .'content';

/*
 * Components Path
*/
$componentsPath = $basePath.DIRECTORY_SEPARATOR .'components';

/*
 * Cache Path
*/
$cachePath = $basePath.DIRECTORY_SEPARATOR .'cache';

/*
 * load the Application
 */
require_once($appPath.DIRECTORY_SEPARATOR.'app.php');

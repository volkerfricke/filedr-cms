<?php

const CONTENT_FILE_EXTENSION = '.txt';
const TEMPLATE_FILE_EXTENSION = '.html';

// load config
require_once($appPath.DIRECTORY_SEPARATOR.'config.php');

// enhance config with path info
$config['base_path'] 	= $basePath;
$config['app_path'] 	= $appPath;
$config['content_path'] = $contentPath;
$config['components_path'] = $componentsPath;
$config['cache_path'] = $cachePath;

// load page-framework
require_once($appPath.DIRECTORY_SEPARATOR.'page.php');
$page = new Page($config);

if($page->checkUri())
{
	// is use cache, than check for cache
	if($page->config['use_cache'])
	{
		if($cached = $page->getCacheContentForUri())
		{
			echo $cached;
			return;
		}
	}
	
	// load content
	$content = $page->loadContent();
	
	// load layout (index)
	$layoutFile = $page->getLayoutFile();
	
	// load template
	$templateFile = $page->getTemplateFile($content['template']);
	unset($content['template']);
	
	// merge layout and template
	$merged = $page->mergeLayoutAndTemplate($layoutFile, $templateFile);
	
	// recursivly replace partials
	$mergedWithPartialFiles = $page->mergePartials($merged);
	
	// replace components
	$mergedWithComponents = $page->renderComponents($mergedWithPartialFiles);
	
	// replace contents
	$mergedWithContent = $page->mergeContent($mergedWithComponents, $content);
	
	// replace contents
	$finally = $page->mergeSite($mergedWithContent);	
	
	// make a cache file, if use_cache is on
	if($page->config['use_cache'])
	{
		$page->makeCacheFileForUri($finally);
	}	
	
	// spit it out!
	echo $finally;
}
else
{
	header('HTTP/1.1 404 Not Found');
	include $basePath.DIRECTORY_SEPARATOR.'404.php';
	exit;
}

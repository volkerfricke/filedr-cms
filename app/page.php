<?php

class Page {
	
	function __construct($config = array()) 
	{
		$this->uri = trim($_SERVER['REQUEST_URI'],"/");
		$this->config = $config;
		$this->currentPath = '';
		$this->currentLanguage = '';
		
		$GLOBALS['__page'] = $this;
	}

	function checkUri()
	{
		if($this->config['use_language'])
		{
			$this->currentLanguage = $this->config['default_language'];
			
			if(in_array($current_language = array_shift(explode("/", $this->uri)), $this->config['available_languages']))
			{
				$this->currentLanguage = $current_language;
			}
		}
		
		// check home, if no uri, or if only an available lang is set
		if(!$this->uri || $this->uri == $this->currentLanguage)
		{
			if($this->config['use_language'])
			{
				$this->currentPath = $this->config['content_path'].DIRECTORY_SEPARATOR.$this->currentLanguage.DIRECTORY_SEPARATOR.$this->config['home'].CONTENT_FILE_EXTENSION;
			}
			else
			{
				$this->currentPath = $this->config['content_path'].DIRECTORY_SEPARATOR.$this->config['home'].CONTENT_FILE_EXTENSION;
			}
		}
		else
		{
			if($this->config['use_language'])
			{
				$this->currentPath = $this->config['content_path'].DIRECTORY_SEPARATOR.$this->uri.CONTENT_FILE_EXTENSION;
			}
			else
			{
				$this->currentPath = $this->config['content_path'].DIRECTORY_SEPARATOR.$this->uri.CONTENT_FILE_EXTENSION;
			}
		}
		
		return file_exists($this->currentPath);
	}
	
	function loadSiteInformation()
	{
		if($this->config['use_language'])
		{
			return $this->_processContentFile('site.'.$this->currentLanguage, true);
		}
		else
		{
			return $this->_processContentFile('site', true);
		}
	}
	
	function loadContent()
	{
		return $this->_processContentFile($this->currentPath);
	}
	
	function getLayoutFile($filename = 'index')
	{
		$layoutFilePath = $this->config['base_path'].DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.$this->config['theme'].DIRECTORY_SEPARATOR.'layouts'.DIRECTORY_SEPARATOR.$filename.TEMPLATE_FILE_EXTENSION;
		
		if(file_exists($layoutFilePath))
		{
			return file_get_contents($layoutFilePath);
		}
		else 
		{
			die("NO ".$filename." IN THE CHOSEN THEME-LAYOUTS-DIRECTORY. CREATE IT, MAN!");
		}
	}
	
	function getTemplateFile($templateFile)
	{
		$templateFilePath = $this->config['base_path'].DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.$this->config['theme'].DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$templateFile.TEMPLATE_FILE_EXTENSION;
	
		if(file_exists($templateFilePath))
		{
			return file_get_contents($templateFilePath);
		}
		else
		{
			die("NO ".$templateFile." IN THE CHOSEN THEME-TEMPLATES-DIRECTORY. CREATE IT, MAN!");
		}
	}

	function getPartialFile($partialFile)
	{
		$partialFilePath = $this->config['base_path'].DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.$this->config['theme'].DIRECTORY_SEPARATOR.'partials'.DIRECTORY_SEPARATOR.$partialFile.TEMPLATE_FILE_EXTENSION;
	
		if(file_exists($partialFilePath))
		{
			return file_get_contents($partialFilePath);
		}
		else
		{
			die("NO ".$partialFilePath." IN THE CHOSEN THEME-PARTIALS-DIRECTORY. CREATE IT, MAN!");
		}
	}	
	
	function mergeLayoutAndTemplate($layoutFile, $templateFile)
	{
		return str_replace('{{ template }}', $templateFile, $layoutFile);
	}
	
	function mergePartials($merged)
	{
		// scan for partials
		$pattern = '/\{\{ partials\[(.*)\] \}\}/';
		
		preg_match_all($pattern, $merged, $matches);
		
		if(count($matches[0]) && count($matches[1]))
		{
			$partialPlaceholder = $matches[0];
			$partialLayouts = $matches[1];
		
			foreach($partialLayouts as $k => $partial)
			{
				$partialLayout = $this->getPartialFile($partial);
				$merged = str_replace($partialPlaceholder[$k], $partialLayout, $merged);
			}
			
			return $this->mergePartials($merged);
		}
		
		return $merged;
	}
	
	function renderComponents($merged)
	{
		// scan for components
		$pattern = '/\{\{ components\[(.*)\] (\"(.*)\")? \}\}/';
		
		preg_match_all($pattern, $merged, $matches);
		
		if(count($matches[0]) && count($matches[1]) && count($matches[3]))
		{
			$componentPlaceholder = $matches[0];
			$componentName = $matches[1];
			$componentContent = $matches[3];
		
			foreach($componentPlaceholder as $k => $placeholder)
			{
				$componentContentFile = $this->getComponentContentFile($componentName[$k], $componentContent[$k]);
				$componentRendered = $this->loadComponent($componentName[$k], $componentContent[$k], $componentContentFile);
				$merged = str_replace($placeholder, $componentRendered, $merged);
			}
		}		

		return $merged;
	}
	
	function getComponentContentFile($componentName, $componentContentFilename)
	{
		if($this->config['use_language'])
		{
			$componentContentFile = $this->config['content_path'].DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.$componentName.DIRECTORY_SEPARATOR.$this->currentLanguage.DIRECTORY_SEPARATOR.$componentContentFilename.CONTENT_FILE_EXTENSION;
			
		}
		else
		{
			$componentContentFile = $this->config['content_path'].DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.$componentName.DIRECTORY_SEPARATOR.$componentContentFilename.CONTENT_FILE_EXTENSION;
		}		
		
		return $this->_processContentFile($componentContentFile);
	}
	
	function loadComponent($componentName, $componentIdentifier, $componentContent)
	{
		require_once $this->config['components_path'].DIRECTORY_SEPARATOR.$componentName.DIRECTORY_SEPARATOR.$componentName.'.php';
		
		$componentClass =  ucfirst($componentName);
		
		$componentObj = new $componentClass;
		
		$componentObj->initialize($componentContent, $componentIdentifier);
		
		return $componentObj->render();
	}
	
	function mergeContent($merged, $content)
	{
		// scan for partials
		$pattern = '/\{% content\[(.*)\] %\}/';
		
		preg_match_all($pattern, $merged, $matches);
		
		if(count($matches[0]) && count($matches[1]))
		{
			$contentPlaceholder = $matches[0];
			$contentLayouts = $matches[1];
		
			$contentLayout = $content;
			
			foreach($contentLayouts as $k => $content)
			{
				$merged = str_replace($contentPlaceholder[$k], $contentLayout[$content], $merged);
			}
		}
		
		return $merged;		
	}
	
	function mergeSite($merged)
	{
		// scan for partials
		$pattern = '/\{% site\[(.*)\] %\}/';
	
		preg_match_all($pattern, $merged, $matches);
	
		if(count($matches[0]) && count($matches[1]))
		{
			$contentPlaceholder = $matches[0];
			$contentLayouts = $matches[1];
	
			$contentLayout = $this->loadSiteInformation();
				
			foreach($contentLayouts as $k => $content)
			{
				$merged = str_replace($contentPlaceholder[$k], $contentLayout[$content], $merged);
			}
		}
	
		return $merged;
	}	
	
	function getCacheContentForUri()
	{
		$prefix = '_';
		$cacheFile = '';
		
		if(!$this->uri)
		{
			$prefix .= 'root';
		}
		else
		{
			$prefix .= str_replace("/", "_", $this->uri);
		}
		
		$cacheFile = $prefix.$this->config['cache_key'].TEMPLATE_FILE_EXTENSION;
		
		if(file_exists($this->config['cache_path'].DIRECTORY_SEPARATOR.$cacheFile))
		{
			ob_start();
			include($this->config['cache_path'].DIRECTORY_SEPARATOR.$cacheFile);
			return ob_get_clean();			
		}
	}
	
	function makeCacheFileForUri($content)
	{
		$prefix = '_';
		$cacheFile = '';
		
		if(!$this->uri)
		{
			$prefix .= 'root';
		}
		else
		{
			$prefix .= str_replace("/", "_", $this->uri);
		}
		
		$cacheFile = $prefix.$this->config['cache_key'].TEMPLATE_FILE_EXTENSION;		
		
		file_put_contents($this->config['cache_path'].DIRECTORY_SEPARATOR.$cacheFile, $content);
	}
	
	function _processContentFile($file, $fileNameOnly = false)
	{
		$returnContent = array();
		
		$filePath = $fileNameOnly ? $this->config['content_path'].DIRECTORY_SEPARATOR.$file.CONTENT_FILE_EXTENSION : $file;
		
		if(file_exists($filePath))
		{
			$fileContent = file_get_contents($filePath);
			
			$fileContentParts = array_filter(array_map('trim', explode('###', $fileContent)));
			
			foreach($fileContentParts as $k => $fileContentPart)
			{
				$multiple = (substr_count($fileContentPart,':') > 1) ? true : false;
				
				if($multiple)
				{
					$tmpMultiple = array_map('trim', explode(PHP_EOL, $fileContentPart));
					
					foreach($tmpMultiple as $t)
					{
						$t1 = array_map('trim', explode(':', $t));
						$key = $t1[0];
						unset($t1[0]);
					
						$returnContent[$k][$key] = implode(':', $t1); //$t1[1];
					}
				}
				else 
				{
					$tmp = array_map('trim', explode(':', $fileContentPart));
					$returnContent[$tmp[0]] = $tmp[1];
				}
			}
			
			return $returnContent;
		}
	}
	
}

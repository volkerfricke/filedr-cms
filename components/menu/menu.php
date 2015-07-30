<?php

class Menu {
	
	var $_template;
	var $_identifier;
	var $_allowAttributes;
	var $_renderItems;
	
	public function __construct()
	{
		$this->_template = 'menu';
		$this->_identifier = 'menu';
		$this->_allowAttributes = array('title','url','target','alt','class','id');
		$this->_page = $GLOBALS['__page'];
	}
	
	public function initialize($content = array(), $identifier = '')
	{
		// check for template
		if(isset($content['template']))
		{
			$this->_template = $content['template'];
			unset($content['template']);
		}
		
		if(isset($identifier))
		{
			$this->_identifier = $identifier;
		}
		
		if(count($content))
		{
			$renderItems = array();
			
			foreach($content as $key => $item)
			{
				if(is_array($item))
				{
					$usedItem = array_intersect_key($item, array_flip($this->_allowAttributes));
					
					$usedItem['active'] = false;
					
					// if language, than replace the url
					if($this->_page->config['use_language'])
					{
						$usedItem['url'] = $this->_page->currentLanguage."/".$usedItem['url'];
					}

					// check active
					if($usedItem['url'] == $this->_page->uri)
					{
						$usedItem['active'] = true;
					} 
					
					$usedItem['url'] = $this->_page->config['url'].$usedItem['url'];
					
					$renderItems[] = $usedItem;
				}
				else
				{
					echo "Menu Item must have title and url, please provide!";
				}
			}
			
			$this->_renderItems = $renderItems;
		}	
	}
	
	public function render()
	{
		// load the template
		$identifier = $this->_identifier;
		$items = $this->_renderItems;
		
		if($items)
		{
			ob_start();
			include(__DIR__.DIRECTORY_SEPARATOR.'template'.DIRECTORY_SEPARATOR.$this->_template.'.php');
			return ob_get_clean();		
		}
	}
}
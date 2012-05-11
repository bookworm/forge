<?php 

namespace forge\excavate\installer;

use forge\excavate\installer;

class Language extends \forge\excavate\cores\Language
{        
  public function _init()
  {    
    parent::_init();  
          
    $source = $this->getPath('source');

		if(!$source)
		{
			$this->setPath('source', 
  			($this->extension->client_id ? JPATH_ADMINISTRATOR : JPATH_SITE) . '/language/' . $this->extension->element
			);
		}

    $this->manifest = $this->getManifest();   
    $this->root     = $this->manifest->document;   
  }   
  
  public function task_install()
  {  
    if((string) $this->manifest->attributes()->client == 'both')
		{
			\JError::raiseWarning(42, \JText::_('JLIB_INSTALLER_ERROR_DEPRECATED_FORMAT'));
			$element = $this->manifest->site->files;
			if(!$this->_install('site', JPATH_SITE, 0, $element))
				return false;

			$element = $this->manifest->administration->files;
			if(!$this->_install('administrator', JPATH_ADMINISTRATOR, 1, $element))
				return false;

			return true;
		}
		elseif($cname = (string) $this->manifest->attributes()->client)
		{
			$client = \JApplicationHelper::getClientInfo($cname, true);    
			
			if($client === null) {
				$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT', \JText::sprintf('JLIB_INSTALLER_ERROR_UNKNOWN_CLIENT_TYPE', $cname)));
				return false;
			}      
			
			$basePath = $client->path;
			$clientId = $client->id;
			$element  = $this->manifest->files;

			return $this->_install($cname, $basePath, $clientId, $element);
		}
		else
		{
			$cname    = 'site';
			$basePath = JPATH_SITE;
			$clientId = 0;
			$element  = $this->manifest->files;       
			
			return $this->_install($cname, $basePath, $clientId, $element);
		}
		
		return false;     
  } 
}
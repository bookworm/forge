<?php 

namespace forge\excavate\installer;

use forge\excavate\installer;

class Template extends \forge\excavate\cores\Template
{ 
  public function task_setStuff()
  {
		return $this->_taskSetStuff();
  }
  
  public function task_setQuery()
  {      
		return $this->_taskSetQuery();
  }    
  
  public function task_extensionRoot()
  {
		return $this->_taskExtensionRoot();
  }  
  
  public function task_parseFiles()
  {
    if($this->parseFiles($xml->files, -1) === false) {
			$this->abort();
			return false;
		}
		
		return true;
  }   
  
  public function task_parseImages()
  {
    if($this->parseFiles($xml->images, -1) === false) {
			$this->abort();
			return false;
		}
		
		return true;
  } 
  
  public function task_parseCSS()
  {
    if($this->parseFiles($xml->css, -1) === false) {
			$this->abort();
			return false;
		}
		
		return true;
  } 
  
  public function task_parseMedia()
  {
    $this->parseMedia($xml->media);
		
		return true;
  }  
  
  public function task_parseLanguages()
  {
    $this->parseLanguages($xml->languages, $clientId);
		
		return true;
  } 
  
  public function task_setMessageCopyManifest()
  {
    $this->set('message', \JText::_((string) $xml->description));

		if(!$this->copyManifest(-1)) {
			$this->abort(\JText::_('JLIB_INSTALLER_ABORT_TPL_INSTALL_COPY_SETUP'));
			return false;
		}
		
		return true;
  }    
  
  public function task_rowStore()
  {
		return $this->_taskRowStore();
  }
}
<?php 

namespace forge\excavate\updater;

use forge\excavate\updater;

class Module extends \forge\excavate\cores\Module
{ 
  public function _init()
  {    
    $this->setOverwrite(true);
		$this->setUpgrade(true);
		$this->route = 'Update';
  }      
  
  public function task_setupStuff()
  {
    return $this->_taskSetupStuff();
  } 
  
  public function task_getDBID()
  {          
    return $this->_taskGetDBID();
  }  
  
  public function task_setUpdateElement()
  {
    return $this->_taskSetUpdateElement();
  }    
  
  public function task_setManifestScript()
  {
    return $this->_taskSetManifestScript();
  }
  
  public function task_setManifestClass()
  {
    return $this->_taskSetManifestClass();
  }   
  
  public function task_createFolder()
  {
    return $this->_taskCreateFolder();
  } 
  
  public function task_parseManfiestFiles()
  {
    return $this->_taskParseManfiestFiles();
  }  
  
  public function task_copyManifestScript()
  {
    return $this->_taskCopyManifestScript();
  }   
  
  public function task_parseMedia()
  {
    $this->parseMedia($this->manifest->media, $this->clientId);
		
		return true;
  } 
  
  public function task_parseLanguages()
  {
    $this->parseLanguages($this->manifest->languages, $this->clientId);
		
		return true;
  } 
  
  public function task_parseImages()
  {
    $this->parseFiles($this->manifest->images, -1);
		
		return true;
  } 
  
  public function task_insertRowDBStuff()
  {
    return $this->_taskInsertRowDBStuff();
  }   
  
  public function task_parseSQLStuff()
  {
    return $this->_taskParseSQLStuff();
  }  
  
  public function task_runManifestClass()
  {
    return $this->_taskRunManifestClass();
  } 
  
  public function taask_copyManifest()
  {
    if(!$this->copyManifest(-1)) {
			$this->abort(\JText::_('JLIB_INSTALLER_ABORT_MOD_INSTALL_COPY_SETUP'));
			return false;
		}
		
		return true;
  } 
  
  public function task_runManifestClassPostFlight()
  {
    return $this->_taskRunManifestClassPostFlight();
  }
}
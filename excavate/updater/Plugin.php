<?php 

namespace forge\excavate\updater;

use forge\excavate\updater;

class Plugin extends \forge\excavate\cores\Plugin
{        
  public function _init()
  {                             
    $this->setOverwrite(true);
		$this->setUpgrade(true);
		$this->route = 'update';
  }       
   
  public function task_setThings()
  {
    return $this->_taskSetThings();
  }  
  
  public function task_setType()
  {     
    return $this->_taskSetType();
  }
  
  public function task_setGroup()
  {
    return $this->_taskSetGroup();
  } 
  
  public function task_setDBID()
  {          
    return $this->_taskSetDBID();
  } 
  
  public function task_checkExistingFolders()
  {
    return $this->_taskCheckExistingFolders();
  }  
  
  public function task_setManifestScript()
  {
    return $this->_taskSetManifestScript();
  }  
  
  public function task_runManifestClassPreflight()
  {
		return $this->_taskRunManifestClassPreflight(); 
  }
  
  public function task_createFolderExtRoot()
  {
		return $this->_taskCreateFolderExtRoot();
  }
  
  public function task_oldFiles()
  {
    if($this->route == 'update')
		{
			$old_manifest = null;
			$tmpInstaller = new \JInstaller; 

			$tmpInstaller->setPath('source', $this->getPath('extension_root'));
			if($tmpInstaller->findManifest()) {
				$old_manifest   = $tmpInstaller->getManifest();
				$this->oldFiles = $old_manifest->files;
			}
		}
		
		return true; 
  }        
  
  public function task_parseXMLFiles()
  {
 	  if($this->parseFiles($this->xml->files, -1, $this->oldFiles) === false) {
 	  	$this->abort();
 	  	return false;
 	  }  
    
 	  return true;
  }  
  
  public function task_parseMedia()
  {
    $this->parseMedia($this->xml->media, 1);
  
 	 return true;
  }      
  
  public function task_parseLanguages()
  {
    $this->parseLanguages($this->xml->languages, 1);
  
 	 return true;
  }             
  
  public function task_copyManifestScript()
  {
 	 return $this->_taskCopyManifestScript();
  }  
  
  public function task_insertRowDB()
  {
 	 return $this->_taskInsertRowDB();
  }
  
  public function task_parseSQLStuff()
  {
 	 return $this->_taskParseSQLStuff();
  } 
  
  public function task_runManifestClass()
  {
 	 return $this->_taskRunManifestClass();
  }       
  
  public function task_copyManifest()
  {		
 	 return $this->_taskCopyManifest();
  } 
  
  public function task_runManifestClassPostFlight()
  {
 	 return $this->_taskRunManifestClassPostFlight();
  }
}
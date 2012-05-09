<?php 

namespace forge\excavate\installer;

use forge\excavate\installer;

class Plugin extends \forge\excavate\cores\Plugin
{    
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
  
  public function task_parseXMLFiles()
  {
		if($this->parseFiles($xml->files, -1, $this->oldFiles) === false) {
			$this->abort();
			return false;
		}  
		
		return true;
  }  
  
  public function task_parseMedia()
  {
    $this->parseMedia($xml->media, 1);
		
		return true;
  }      
  
  public function task_parseLanguages()
  {
    $this->parseLanguages($xml->languages, 1);
		
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
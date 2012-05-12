<?php 

namespace forge\excavate\updater;

use forge\excavate\updater;

class File extends \forge\excavate\cores\File
{ 
  public function _init()
  {    
    $this->setOverwrite(true);
		$this->setUpgrade(true);
		$this->route = 'update';
  }  
  
  public function task_setPaths()
  {
    return $this->_taskSetPaths();
  } 
  
  public function task_copyManifestScript()
  {
    return $this->_taskCopyManifestScript();
  }   
  
  public function task_setManifestClass()
  {
    return $this->_taskSetManifestClass();
  }   
  
  public function task_populateFilesAndFolderList()
  {
    return $this->_taskPopulateFilesAndFolderList();
  }  
  
  public function task_parseLanguages()
  {
    $this->parseLanguages($this->manifest->languages);
		return true;
  } 
  
  public function task_setDBStuff()
  {
    return $this->_taskSetDBStuff();
  }  
  
  public function task_parseSQL()
  {
    return $this->_taskParseSQL();
  }  
  
  public function task_runManifestClass()
  {
    return $this->_taskRunManifestClass();
  }  
  
  public function task_copyManifestFile()
  {
    return $this->_taskCopyManifestFile();
  }   
  
  public function task_insertUID()
  {
    return $this->_taskInsertUID();
  }  
  
  public function task_runManifestClassPost()
  {
    return $this->_taskRunManifestClassPostFlight();
  }
}
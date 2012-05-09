<?php 

namespace forge\excavate\installer;

use forge\excavate\installer;

class Library extends \forge\excavate\cores\Library
{   
  public function task_setStuff()
  {
    return $this->_taskSetStuff();
  } 
  
  public function task_insertDBStuff()
  {
    return $this->_taskInsertDBStuff();
  } 
  
  public function task_setDescriptionAndLibraryName()
  {
    return $this->_taskSetDescriptionAndLibraryName();
  } 
  
  public function task_parseFiles()
  {
    return $this->_taskParseFiles();
  }   
  
  public function task_parseLanguages()
  {
    $this->parseLanguages($this->manifest->languages);
		
		return true;
  }
  
  public function task_parseMedia()
  {
    $this->parseMedia($this->manifest->media);    
		
		return true;
  } 
  
  public function task_insertRow()
  {
    return $this->_taskInsertRow();
  }     
  
  public function task_copyManifest()
  {
    return $this->_taskCopyManifest();
  }      
}                                              
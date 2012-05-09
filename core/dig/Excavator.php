<?php

namespace forge\core\dig;

use forge\core\dig;

class Excavator extends \forge\core\Object
{     
  public $artifacts = array();    
  public $excavations = array();
  public $failed = array();   
  public $on; 
  public $dig; 
  
  public function __construct($dig, $artifacts)
  {           
    $this->dig = $dig;
    $this->artifacts = $artifacts;
    $this->_addAll(); 
  }      
  
  public function _addAll()
  {
    foreach($this->artifacts as $artifact) {
      $this->addExcavation($artifact);
    }
  }
  
  public function add($artifact)
  {   
    if(!$this->completed($artifact))
    { 
      if($artifact->ext_name == 'JCore') 
        $this->excavations[] = $this->create($artifact, array('shouldRetrievePackage' => false));
      else                                  
        $this->excavations[] = $this->create($artifact);       
    }
  }  
  
  public function create($artifact)
  {                           
    $classPath = $this->classPath($artifact);
    return new $classPath($artifact, $this->dig->tasks->total);
  }     
  
  public function classPath()
  {
    $loader    = Loader::getInstance(); 
    $className = ucwords($artifact->type);  
    $classPath = 'forge\excavate'; 
    $classPath .= '\\';

    if(isset($artifact->update))
      $classPath .= 'updater';     
    elseif(isset($artifact->uninstall))
      $classPath .= 'uninstaller';
    else         
      $classPath .= 'installer';

    $classPath .= "\$className".'_V'.$this->jversionNum;    

    $loader->loadClass($classPath);   
    
    return $classPath;
  }
  
  public function completed($artifact)
  {
    return file_exists($this->tmpPath() . DS . 'Excavation' . '_' . $artifact->ext_name . '_completed');
  }  

  public function append($artifact)
  {   
    $this->add($artifact);
    $this->dig->status->appendedExcavation($artifact);
  }
 
  public function failed($excavation)
  {    
    return $this->status->failedExcavation($excavation);
  }
}
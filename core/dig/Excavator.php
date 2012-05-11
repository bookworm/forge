<?php

namespace forge\core\dig;

use forge\core\dig;      

$loader = \forge\core\Loader::getInstance();      
$loader->loadClasses(array('excavate\ExcavateAbstract', 'excavate\Core', 'excavate\Excavator',
  'excavate\cores\Component', 'excavate\cores\File', 'excavate\cores\Language',    
  'excavate\cores\Library', 'excavate\cores\Module', 'excavate\cores\Plugin', 'excavate\cores\Template'
));

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
      $this->add($artifact);
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
  
  public function classPath($artifact)
  {
    $loader    = \forge\core\Loader::getInstance(); 
    $className = ucwords($artifact->type);  
    $classPath = 'excavate'; 
    $classPath .= '\\';

    if(isset($artifact->update))
      $classPath .= 'updater';     
    elseif(isset($artifact->uninstall))
      $classPath .= 'uninstaller';
    else         
      $classPath .= 'installer';

    $classPath .= "\\$className";                        
    $loader->loadClass($classPath);   
    
    return 'forge\\'.$classPath;
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
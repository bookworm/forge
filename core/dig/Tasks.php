<?php   

namespace forge\core\dig;

use forge\core\dig;

class Tasks extends \forge\core\Object
{
  public $total = 0;  
  public $on    = null;    
  
  public function __construct()
  { 
    $this->log = KLogger::instance($this->tmpPath() . DS . 'log', KLogger::INFO);
  }  
  
  public function _init()
  {
    $this->ex     = Excavator::getInstance();
    $this->status = Status::getInstance();    
    $this->getTasksFromExcavations();
  }
  
  public function getTasksFromExcavations()
  {
    foreach($this->dig->ex->excavations $key => $excavation)
    {   
      $ext_name  = $excavation->artifact->ext_name;   
              
      $artifactTasks                           = $excavation->tasks();
      @$this->ex->artifacts[$ext_name]->tasks  = $artifactTasks;
      
      $this->status->addedExcavation($excavation);
    }
  }
  
  public function increment($resave = true,)
  { 
    $this->on++;  
    
    if($resave == true)
      $this->status->serialize();
  }
  

  public function update($total, $resave = true, $replace = false)
  {
    if($replace == false)
      $this->total = $this->total + $total;
    else          
      $this->total = $total;       
    
    if($resave == true)
      $this->status->serialize();
  }  
  
  public function on()
  {
    return $this->on;
  }
}
<?php   

namespace forge\core\dig;

use forge\core\dig;

class Tasks extends \forge\core\Object
{
  public $total = 0;  
  public $on    = null;   
  public $dig = null; 
  
  public function __construct($dig)
  { 
    $this->log = \KLogger::instance($this->tmpPath() . DS . 'log', \KLogger::INFO);   
    $this->dig = $dig;
  }  
  
  public function _init($dig)
  {
    $this->ex     = $dig->ex;
    $this->status = $dig->status;    
    $this->getTasksFromExcavations();
  }             
  
  public static function &getInstance($dig=null)
  {
    static $instance; 

    if(!is_object($instance))
      $instance = new self($dig);   

    return $instance;
  }
  
  public function getTasksFromExcavations()
  {                   
    foreach($this->ex->excavations as $key => $excavation)
    {   
      $slug  = $excavation->artifact->slug;        
                    
      $artifactTasks                       = $excavation->tasks();
      @$this->ex->artifacts[$slug]->tasks  = $artifactTasks;
       
      $this->status->addedExcavation($excavation);
    }
  }
  
  public function increment($resave = false)
  { 
    $this->on++;  
    
    if($resave == true)
      $this->status->serialize();
  }
  
  public function update($total, $resave = false, $replace = false)
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
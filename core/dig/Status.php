<?php    

namespace forge\core\dig;

use forge\core\dig;

class Status extends \forge\core\Object
{         
  public $finished = false;     
  public $paused = false;
  
  public function __construct()
  {
    $this->log = \KLogger::instance($this->tmpPath() . DS . 'log', \KLogger::INFO);
  }      
  
  public function _init()
  {
    $this->ex    = Excavator::getInstance();
    $this->tasks = Tasks::getInstance();
  }     
  
  public static function &getInstance()
  {
    static $instance; 

    if(!is_object($instance))
      $instance = new self();   

    return $instance;
  }
  
  public function paused()
  {
    $this->serialize();   
    file_put_contents($this->tmpPath() . DS .'dig_restart_needed', "Dig Restart Needed");  
    $this->log->logInfo('Dig Paused');      
    $this->paused = true;
  }      
  
  public function started()
  {
    $this->log->logInfo('The dig has begun');
  }      
  
  public function finished()
  {
    $fileold = \JFile::makeSafe('dig_status'); 
    $now     = strftime("%d-%H-%S", time());       
    $filenew = \JFile::makeSafe('dig' . '_' . 'completed_'.$now);

    renameFile($fileold, $filenew, $this->tmpPath()); 
    $this->serialize();     
    $this->log->logInfo('The dig has finished');  
    $this->finished = true;
      
    return true;
  }
  
  public function restartNeeded()
  {
    return file_exists($this->tmpPath() . DS . 'dig_restart_needed');
  } 
  
  public function status()
  {
    return unserialize(file_get_contents($this->tmpPath() . DS . 'dig_status')); 
  } 
  
  public function serialize()
  {   
    if(file_exists($this->tmpPath() . DS . 'dig_status')) 
      $serialized = unserialize(file_get_contents($this->tmpPath() . DS . 'dig_status'));
    else
      $serialized = array();      
    
    $serialized['onTask']            = $this->tasks->on;   
    $serialized['tasks']             = $this->tasks->total;    
    $serialized['onExcavation']      = $this->ex->on;
    $serialized['onExcavationTask']  = $this->ex->on->onTask;    
    $serialized['excavationTasks']   = $this->ex->on->tasks;
    $serialized['artifacts']         = $this->ex->artifacts;   
    $serialized['excavations']       = $this->ex->excavations;
    
    $serialized = serialize($serialized);   
    file_put_contents(FORGE_TMP_PATH . DS . 'dig_status', $serialized);
    
    return $serialized;
  }      
  
  public function unserialize()
  {              
    if(is_null($this->unserialized))
      $this->unserialized = unserialize(file_get_contents($this->tmpPath() . DS . 'dig_status')); 
    
    return $this->unserialized;     
  }   
  
  public function onTaskOfTotal()
  {
    return $this->unserialized['onTaskOfTotal'];    
  }
  
  public function addedExcavation($artifact)
  {
    $filename        = 'Excavation' . '_' . $ext_name . '_start'; 
    $artifactEncoded = serialize($this->ex->artifacts[$ext_name]);  
    file_put_contents($this->tmpPath() . DS . 'excavations'. DS . $filename, $artifactEncoded);
  }  
  
  public function appendedExcavation($artifact)
  {
    $this->addedExcavation($artifact);
  }  
  
  public function failedExcavation($excavation)
  {
    $this->log->logError("Failed on excavation: ". $excavation->artifact->name);  
    die("Failed on excavation: ". $excavation->artifact->name);
  }   
  
  public function finishedExcavation($excavation)
  {            
    $ext_name = $excavation->artifact->ext_name();
    
    // Rename excavation file.     
    $fileold = \JFile::makeSafe('Excavation' . '_' . $ext_name . '_start');
    $filenew = \JFile::makeSafe('Excavation' . '_' . $ext_name . '_completed');
    $path    = $this->tmpPath() . DS . 'excavations';                                       

    renameFile($fileold, $filenew, $path);    
    $this->log->logInfo('Finished Excavation On: '. $excavation->artifact->name);   

    // Unset array values      
    $this->ex->on = null;
    unset($this->ex->artifacts[$ext_name]);  
    unset($this->ex->excavations[$key]);
  } 
  
  public function canContinue()
  {
    $timer = Timer::getInstance();
    if($timer->getTimeLeft() > 0) return true;
  }  
}
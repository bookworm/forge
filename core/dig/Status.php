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
  
  public function _init($dig)
  {
    $this->ex    = $dig->ex;
    $this->tasks = $dig->tasks;
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
    if($this->ex->on) {
      $serialized['onExcavation']      = $this->ex->on;  
      $serialized['onExcavationTask']  = $this->ex->on->onTask;    
      $serialized['excavationTasks']   = $this->ex->on->tasks;
    }  
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
  
  public function addedExcavation($excavation)
  {            
    $slug = $excavation->artifact->slug;
    $filename        = 'Excavation' . '_' . $slug . '_start'; 
    $artifactEncoded = serialize($this->ex->artifacts[$slug]);  
    
    $folder = $this->tmpPath() . DS . 'excavations';  
      
    if(!\JFolder::exists($folder))  
      \JFolder::create($folder);
		  
    file_put_contents($folder. DS . $filename, $artifactEncoded);
  }  
  
  public function appendedExcavation($artifact)
  {
    $this->addedExcavation($artifact);
  }  
  
  public function failedExcavation($excavation)
  {
    $this->log->logError("Failed on excavation: ". $excavation->artifact->name);  
    die("Failed on excavation: ". $excavation->artifact->name . "\n" . 'Last error: ' . "\n". \JError::getError() 
      . "\n" . 'Message: ' . $excavation->getMessage() . "\n"
    );
  }   
  
  public function finishedExcavation($excavation, $key)
  {            
    $slug = $excavation->artifact->slug;
    
    // Rename excavation file.     
    $fileold = \JFile::makeSafe('Excavation' . '_' . $slug . '_start');
    $filenew = \JFile::makeSafe('Excavation' . '_' . $slug . '_completed');
    $path    = $this->tmpPath() . DS . 'excavations';                                       

    renameFile($fileold, $filenew, $path);  
    $this->log->logInfo('Finished Excavation On: '. $excavation->artifact->name);   

    // Unset array values      
    $this->ex->on = null;
    unset($this->ex->artifacts[$key]);  
    unset($this->ex->excavations[$key]);
  } 
  
  public function canContinue()
  {
    $timer = \forge\core\Timer::getInstance();
    if($timer->getTimeLeft() > 0) return true;
  }  
}
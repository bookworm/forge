<?php 

namespace forge\excavate;

use forge\excavate;

class ExcavateAbstract extends \forge\core\Object
{ 
  public $digTasksCount = 0; 
  public $artifact;    
  public $name;
  public $onTask = 0;    
  public $type;
  public $tasks = array(); 
  public $dig;     
  public $digTasks;
  public $success = true;         
  public $classMethods = array();       
  public $rollbacks = array();      
  public $message  = '';
  public $messages = array();   
  public $errorMessage = '';
    
  public function addMessage($message)
  {
    $this->messages   = $message;    
    $this->messages[] = $message; 
    $this->log->logInfo($message);
    
    return this;
  }        
  
  public function setMessage($message)
  {
    $this->message = $message;     
    $this->log->logInfo($message);
    
    return $this;
  }  

  public function setErrorMessage($message)
  {                                       
    $this->success      = false;
    $this->errorMessage = $message;
    $this->log->logError($message);  
    
    return $this; 
  }    
  
  public function getTasks()
  {
    foreach($this->classMethods as $methodName) 
    {  
      if(strpos($methodName, 'task_') !== false) 
      {       
        $taskName = str_replace(array('task_', '_before', '_after', '_rollback'), '', $methodName);  
        $this->tasks[$taskName] = $taskName; 

        if(strpos_array($methodName, array('_before', '_after', '_rollback')) == false)       
          $this->tasksCount++;          
      }     
    }  

    $this->dig->tasks->update($this->tasksCount);
    return $this->tasksCount;       
  }    

  public function executeTasks()
  {     
    foreach($this->tasks as $key => $task) 
    { 
      $timer = Timer::getInstance();  
      if($timer->getTimeLeft() > 0)
      {
        if(!$this->executeTask($task) == false)
        {
          $this->success = true; 
          unset($this->tasks[$key]); 
          $this->dig->status->serialize();   
        } 
        else { 
          $this->success = false;
          return false;  
        }    
      } 
      else {
        $this->dig->pause();    
        die('Dig Paused. Restart it');
      }
    }             

    $this->success = true;  
    return $this->success;   
  } 
 
  public function executeTask($taskName)
  {
    $type = $this->artifact->type;

    if(arrayFind('task_'.$taskName.'_before', $this->classMethods,  true)) {     
      if(!$this->executeSpecificTask('task_'.$taskName.'_before')) 
        return false;  
    }   

    if(!$this->executeSpecificTask('task_'.$taskName)) 
      return false;       

    $this->updateTaskCount();     

    if(arrayFind('task_'.$taskName.'_after', $this->classMethods, true)) {  
      if(!$this->executeSpecificTask('task_'.$taskName.'_after')) 
        return false;      
    } 

    return true;   
  }  
 
  public function executeSpecificTask($taskName, $arg=null)
  {   
    if($arg === null)
      $arg = $this->artifact;  
      
    $this->log->logInfo("Executing task $taskName. For: ". $this->artifact->name); 
    $result = call_user_func_array(array($this->excavateClass, $taskName), $arg); 

    if(!$result) {
      $this->setErrorMessage($this->excavateClass->errorMSG);
      return false;   
    }  
    else {
      $this->log->logInfo("Completed $taskName. For: ". $this->artifact->name);
      return true;    
    }  
  }    
    
  public function updateTaskCount()
  {        
    $this->onTask++; 
    $this->dig->tasks->increment();   
  } 
  
  public function pushRollback(array $rollback)
  { 
    $this->rollbacks[] = $rollback;  
    return $this;
  }
  
  public function pushStep($step)
  {
    $this->rollbacks[] = $step;        
    return $this;
  } 
}
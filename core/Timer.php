<?php  

// no direct access
defined( '_Forge' ) or die( 'Restricted access' );        
   
class Timer extends \forge\Core\Object
{    
  public $startTime;   
  public $maxExecTime; 

  public function __construct() 
  {
    $this->startTime = $this->microtimeFloat();     
    $bias = 0.75;   

    if(@function_exists('ini_get'))
    {
      $phpMaxExecTime = @ini_get("maximum_execution_time");
      if((!is_numeric($phpMaxExecTime)) || ($phpMaxExecTime == 0)) 
      {
        $phpMaxExecTime = @ini_get("max_execution_time");
        if((!is_numeric($phpMaxExecTime)) || ($phpMaxExecTime == 0))
          $phpMaxExecTime = 14;  
      }
    }
    else
      $phpMaxExecTime = 14;
    
    $phpMaxExecTime--;
    $this->maxExecTime = $phpMaxExecTime * $bias;
  }   

   public function __wakeup()
   {
     $this->startTime = $this->microtimeFloat();
   }
  
  public function getTimeLeft()
  {
    return $this->maxExecTime - $this->getRunningTime();
  } 
   
  public function getRunningTime()
  {
    return $this->microtimeFloat() - $this->startTime;
  }

  private function microtimeFloat()
  {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
  }  

  public function resetTime()
  {
    $this->startTime = $this->microtimeFloat();
  }
}
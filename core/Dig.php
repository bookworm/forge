<?php

namespace forge\core;

use forge\core; 

// no direct access
defined('_Forge') or die('Restricted access'); 
 
// Import Prequisites
jimport('joomla.filesystem.file'); 
jimport('joomla.filesystem.folder');    
$loader = \forge\core\Loader::getInstance();      
$loader->loadClasses(array('core\dig\Status', 'core\dig\Tasks', 'core\dig\Excavator'));

class Dig extends \forge\core\Object
{          
  public $status = null;
  public $ex = null;
  public $tasks = null;       
  
  # Used for refreshManifestCache
  public $extension = null;
  
  public function __construct($artifacts)
  {      
    $this->log    = \KLogger::instance($this->tmpPath() . DS . 'log', \KLogger::INFO); 
    $this->tasks  = new \forge\core\dig\Tasks($this);     
    $this->status = new \forge\core\dig\Status();
    $this->ex     = new \forge\core\dig\Excavator($this, $artifacts);    

    $this->status->_init($this);    
    $this->ex->_init($this);
    $this->tasks->_init($this);       
  } 
  
  public static function &getInstance($artifacts=array())
  {
    static $instance; 

    if(!is_object($instance))
      $instance = new self($artifacts);   

    return $instance;
  }
  
  public function run()
  {
    if($this->status->restartNeeded()) 
      $this->restart();
    
    $this->status->started();  
    $this->go();      
    
    if($this->finish() == true)
      $this->status->finished();
    else  
      $this->restart();
  }    
  
  public function restart()
  {      
    \JFile::delete($this->tmpPath() . DS .'dig_restart_needed');           
    
    $unserialized = $this->status->unserialized();  
    
    $this->tasks->on         = $unserialized['onTask'];   
    $this->ex->on            = $unserialized['onExcavation'];     
    $this->tasks->total      = $unserialized['tasks'];      
    $this->ex->artifacts     = $unserialized['artifacts'];      
    $this->ex->excavations   = $unserialized['excavations'];  
    $this->ex->on->onTask    = $unserialized['onExcavationTask'];
    
    unset($unserialized);  
    
    $this->start();
  }

  public function pause()
  {  
    $this->status->paused();   
  } 
  
  public function go()
  { 
    foreach($this->ex->excavations as $key => $excavation)
    {
      $this->ex->on = $this->ex->excavations[$key];          
      if($this->status->canContinue())
      { 
        $this->status->serialize();
        if($excavation->start() == false) {
          $this->ex->failed($excavation);   
          return false;
          break;
        } 
        else
          $this->status->finishedExcavation($excavation, $key);
      }
      else 
        $this->pause();
    } 
    
    return true;
  }  
  
  public function finish()
  {  
    $files = \JFolder::files($this->tmpPath() . DS .'excavations', '_start');
    return true;
   # if(!empty($files) AND empty($this->ex->artifacts))
   # {       
   #   $this->ex->artifacts = array(); 
   # 
   #   foreach($files as $key => $file) {    
   #     $artifact = unserialize(file_get_contents($this->tmpPath() . DS .'excavations' . $file));       
   #     $this->ex->artifacts[$key] = $artifact;         
   #   }  
   # 
   #   return false;
   # }  
   # elseif(!empty($this->ex->artifacts))     
   #   return false; 
   # else    
   #   return true;        
  }  
  
  public function refreshManifestCache($eid)
 	{
 	 if($eid)
 		{
 			$this->extension = \JTable::getInstance('extension');

      if(!$this->extension->load($eid)) {
        $this->abort(\JText::_('JLIB_INSTALLER_ABORT_LOAD_DETAILS'));
        return false; 
      }

      if($this->extension->state == -1) {
        $this->abort(\JText::_('JLIB_INSTALLER_ABORT_REFRESH_MANIFEST_CACHE'));
        return false; 
      }
      
      foreach($this->ex->excavations as $key => $excavation) {   
        $excavation->refreshManifestCache();
      } 
    }
	}  
	
	# public function __destruct() 
	# { 
	#   JFolder::delete
  # }    
}
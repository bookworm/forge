<?php 

namespace forge\excavate;

use forge\excavate;

class Core extends \forge\excavate\ExcavateAbstract
{   
  public $package;          
  public $_paths;
  public $manifest;   
  public $eid;   
  public $route = ''; 
  public $msg = '';
  public $overwrite = false; 
  public $upgrade = false;    
  public $uninstall = false;
  public $name;
  public $element;
  public $xml; 
  public $lang; 
  public $installer;
  
  /** 
   * For compatibility with amnifest install scripts
   * It basically just relacates the API so if an installation script calls
   * $this->parent they'll actually hit something          
   **/
  public $parent;
  
  public $shouldRetrievePackage = true;  
  
  public function __construct()
  {       
    parent::__construct();
    $this->log = \KLogger::instance($this->tmpPath() . DS . 'log', \KLogger::INFO);   
    $this->parent = $self;         
    $this->installer = new Installer($this);
    var_dump($this->installer);
  }     

  public function _init()
  {        
    if($this->upgrade)
      return $this->setupUpdate();
    else if($this->uninstall)
      return $this->setupUninstall();    
    else
      return $this->setupInstall();
  }    
  
  public function setupUninstall()
  {   
    if(JVERSION_SHORT == '1.7') 
      $this->eid = $this->jinstallerhelper->getExtensionId($this->artifact);   
              
		return true;
  } 

  public function setupInstall()
  {
		if(!$this->installer->findManifest($this->getPath('source')))
			return false;

		return true;
	}            
	
  public function getPath($name, $default=null)
  {
    return (!empty($this->_paths[$name])) ? $this->_paths[$name] : $default;
  }    

  public function setPath($name, $value)
  {
    $this->_paths[$name] = $value;
  }   
 
  public function retrievePackage()
  {  
    $this->package = \forge\installer\Package::retrievePackage($this->artifact); 

    if($this->package == false)
      return false;
    else
      $this->setPath('source', $this->package['dir']);    

    return true;  
  }
   
  public function start()
  { 
    $this->log->logInfo('Starting Excavation On: '. $this->artifact->name);

    if($this->shouldRetrievePackage == true) 
    {    
       if($this->retrievePackage() == false) 
       {  
         $this->success = false;  
         $this->log->logError('Failed to retrieve package for: ' . $this->artifact->name);
         return false;     
       }  
    } 

    $this->_init();  

    $this->executeTasks();  
    return $this->success;    
  }    
  
  public function abort($message='')
  {                                     
    $this->log->logError($message);
    $this->log->logError('Had to abort: ' . $this->artifact->name);  
    foreach($this->rollbacks as $rollback)
    {               
                                     
      if(isset($rollback['task']) AND method_exists("task_$rollback".'_'.'rollback')) 
      {
        if($this->executeSpecificTask("task_$rollback".'_'.'rollback', $arg) == false)
          throw error("Couldn't Properly Abort:" .$this->artifact->name);  
      }
      else
      {
        switch($rollback['type']) 
        {
          case 'file' :
  					$stepval = \JFile::delete($step['path']);
  					break;

  				case 'folder' :
  					$stepval = \JFolder::delete($step['path']);
  					break;

  				case 'query' :
  					# Placeholder in case this is necessary in the future
  					# $stepval is always false because if this step was called it invariably failed
  					$stepval = false;
  					break;

  				case 'extension' :
  					$db = $this->getDbo();

  					$query = 'DELETE' .
  							' FROM `#__extensions`' .
  							' WHERE extension_id = '.(int)$step['id'];
  					$db->setQuery($query);
  					$stepval = $db->Query();

  					break;   
        }
      }
    }
  }    
  
/**
 * Thes are only really here so I have to recode less of the Joomla installers.
 * Lazyness FTW! 
 **/ 
 
  public function getDbo()
  {
    return \JFactory::getDbo();
  }     
         
  public function getOverwrite()
  {
    return $this->overwrite;
  }  
  
  public function setOverwrite($overwrite)
  {               
    $this->overwrite = $overwrite;
    return $this;
  }   
  
  public function getUpgrade()
  {
    return $this->upgrade;
  }  
  
  public function setUpgrade($upgrade)
  {               
    $this->upgrade = $upgrade;
    return $this;
  }
}
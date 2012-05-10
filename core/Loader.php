<?php

namespace forge\core; 

use forge\core;     

require_once 'object.php';

class Loader extends \forge\core\Object
{    
  public $classes = array();
  
  public function __construct()
  {
  }   
  
  public static function &getInstance()
  {
    static $instance; 

    if(!is_object($instance))
      $instance = new self();   

    return $instance;
  }   
  
  public function loadClasses($classes)
  {    
    return $this->loadClass($classes);
  }
  
  public function loadClass($className)
  {      
    if(!is_array($className))
      $classes = array($className);
    else
      $classes = $className;  
      
    foreach($classes as $className)
    {
      $path = '';
      if(strpos($className, "\\")) 
      {  
        $paths = explode("\\", $className);      
        $path  = dirname(dirname(__FILE__));   

       foreach($paths as $p) {
         $path .= DS.$p;
       }
      }
      else
        $path = dirname(dirname(__FILE__)) . DS . $className;
       
      if(file_exists($path . '.php')) 
        require_once $path . '.php';     
      else if(file_exists($path . '_' .substr(JVERSION, 0, 3) . '.php'))     
        require_once $path . '_' . substr(JVERSION, 0, 3) . '.php';       
      else if(file_exists(dirname(dirname(__FILE__)) . DS . substr(JVERSION, 0, 3) . DS . $className . '.php'))   
        require_once dirname(dirname(__FILE__)) . DS . substr(JVERSION, 0, 3) . DS . $className . '.php';   
      else
        throw new \Exception("Class: $className not found");  
    }
  } 
  
  public function loadHelpers($helpers)
  {    
    return $this->loadHelper($helpers);
  }
  
  public function loadHelper($helperName)
  { 
    if(!is_array($helperName))
      $helpers = array($helperName);
    else
      $helpers = $className;
      
    foreach($helpers as $helperName)
    {  
      $path = dirname(dirname(__FILE__)) . DS . 'helpers' . DS . $helperName . '.php';    

      if(file_exists($path)) 
        include_once $path;
      else
        throw new \Exception("Helper: $helperName not found");
    }          
  }       
  
  public function loadLibs($libName)
  {
    return $this->loadLib($libName);
  }
  
  public function loadLib($libName)
  {
    if(!is_array($libName))
      $libs = array($libName);
    else
      $libs = $libName;
      
    foreach($libs as $libName)
    {       
      $path = '';
      if(strpos($libName, "\\")) 
      {  
        $paths = explode("\\", $libName);      
        $path  = dirname(dirname(__FILE__)) . DS . 'lib';   

        foreach($paths as $p) {
          $path .= DS . $p;
        }
      }
      else
        $path = dirname(dirname(__FILE__)) . DS . 'lib' . DS . $libName;

      if(file_exists($path . '.php')) 
        require_once $path . '.php';  
      else
        throw new \Exception("Library: $libName not found");
    }
  }
}
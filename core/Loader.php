<?php

namespace forge\core;

class Loader
{    
  public $classes = array();
  
  public function __construct()
  {
  }      
  
  public function loadClasses($classes)
  {    
    return $this->loadClasses($classes);
  }
  
  public static function loadClass($className)
  {      
    if(!is_array($className))
      $classes = array($className);
    else
      $classes = $className
    foreach($classes as $className)
    {
      $path = '';
      if(strpos($className, "\\")) 
      {  
        $paths = explode("\\");      
        $path  = dirname(dirname(__DIR__));   

        foreach($paths as $p) {
          $path .=  DS . $p;
        }
      }
      else
        $path = dirname(dirname(__DIR__)) . DS . $className;

      if(file_exists($path . '.php')) 
        require_once $path . '.php';     
      else if(file_exists($path . '_' .substr(JVERSION, 0, 3) . '.php'))     
        require_once $path . '_' . substr(JVERSION, 0, 3) . '.php';       
      else if(file_exists(dirname(dirname(__DIR__)) . DS . substr(JVERSION, 0, 3) . DS . $className . '.php'))   
        require_once dirname(dirname(__DIR__)) . DS . substr(JVERSION, 0, 3) . DS . $className . '.php';   
      else
        throw new Exception("Class: $className not found");  
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
      $helpers = $className
    foreach($helpers as $helperName)
    {  
      $path = dirname(dirname(__DIR__)) . DS . 'helpers' . DS . $helperName . '.php';

      if(file_exists($path)) 
        include_once $path;
      else
        throw new Exception("Helper: $helperName not found");
    }          
  } 
  
  public function loadLib($libName)
  {
    if(!is_array($libName))
      $libs = array($libName);
    else
      $libs = $libName
    foreach($libs as $libName)
    {       
      $path = '';
      if(strpos($libName, "\\")) 
      {  
        $paths = explode("\\");      
        $path  = dirname(dirname(__DIR__)) . DS . 'lib';   

        foreach($paths as $p) {
          $path .= DS . $p;
        }
      }
      else
        $path = dirname(dirname(__DIR__)) . DS . 'lib' . DS . $libName;

      if(file_exists($path . '.php')) 
        require_once $path;  
      else
        throw new Exception("Library: $libName not found");
    }
  }
}
<?php      

namespace forge\core;

use forge\core;

class Forge
{ 
  var $artifacts = array();  
  var $config;
  
  public function __construct($config = null)
  { 
    if(!is_null($config)) 
      $this->config = $config;
    else
    { 
      if(file_exists(FORGE_CONFIG_PATH.DS.'config.json'))
      {     
        $config       = file_get_contents(FORGE_PATH.DS.'config'.DS.'config.json');
        $config       = preg_replace( '/\s*(?!<\")\/\*[^\*]+\*\/(?!\")\s*/' , '' , $config);  
        $config       = preg_replace('#/\*[^*]*\*+([^/*][^*]*\*+)*/#', '' , $config); 
        $this->config = json_decode($config);   
        
        unset($config);
      }      
    }          
    
    foreach($this->config->artifacts as $artifact) {
      $this->artifacts[] = strtolower($artifact);
    }
  }

  public function &getInstance($config = null)
  {
    static $instance; 

    if(!is_object($instance))
      $instance = new Forge($config);   

    return $instance;
  }   
}
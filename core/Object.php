<?php

namespace forge\core;

class Object
{              
  public $log;
  
  public function &getInstance()
  {
    static $instance; 

    if(!is_object($instance))
      $instance = new self($config);   

    return $instance;
  }  
  
  public function tmpPath()
  {
    return FORGE_TMP_PATH;
  }   
  
  public function set($property, $value = null)
	{
		$previous        = isset($this->$property) ? $this->$property : null;
		$this->$property = $value;  
		
		return $this;
	}
	
	public function setProperties($properties)
	{
		if(is_array($properties) || is_object($properties))
		{
			foreach((array) $properties as $k => $v) {
				$this->set($k, $v);
			}   
			
			return true;
		}

		return false;
	}    
	
	public function get($property, $default=null)
	{
		if(isset($this->$property))
			return $this->$property;

		return $default;
	}
}
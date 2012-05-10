<?php

/**
 * Calls startFork and Forks off a PHP into another process.      
 *
 * @param string $path Path to file
 * @param string $file Name of the file.  
 * @param string $args Any arguments to pass.
 * @return proc
 */  
if(!function_exists('forkIT'))
{
  function forkIT($path, $file)
  {
    pclose(startFork($path, $file)); 
  }  
}
/**
 * Forks off a PHP into another process.      
 *
 * @param string $path Path to file
 * @param string $file Name of the file.  
 * @param string $args Any arguments to pass.
 * @return proc
 */  
if(!function_exists('startFork'))
{   
  function startFork($path, $file)
  {
    chdir($path);   
    
    if (substr(PHP_OS, 0, 3) == 'WIN')
      $proc = popen('start /b php "' . $path . '\\' . $file);
    else
      $proc = popen('php ' . $path . '/' . $file);

    return $proc;   
  } 
}
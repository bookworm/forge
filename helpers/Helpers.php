<?php

// no direct access
defined( '_Forge' ) or die( 'Restricted access' );    

// Import Prequisites
jimport('joomla.filesystem.file');  
jimport('joomla.filesystem.folder');    

// ------------------------------------------------------------------------         

// Helper functions

/**
 * Searches a array for given string.      
 * 
 * Usage: 
 * {{{
 *    if(array_find('bob', $array))
 *    {
 *      // do something
 *    }  
 * }}}
 *
 * @param   string $needle      String to search for
 * @param   array  $haystack    Array to search in
 * @return  bool   $searchKeys  Whether or not to search the keys.
 * @return  mixed
 */
if(!function_exists('arrayFind'))
{
  function arrayFind($needle, $haystack, $searchKeys = false, $searchNested = false) 
  { 
    $finalCheck = array();
    if(!is_array($haystack)) return false; 
    
    foreach($haystack as $key => $value) 
    { 
      if($searchNested == true AND is_array($value)) {       
       if(arrayFind($needle, $value))
         $finalCheck[$key] = '1';
      } 
      else {      
        $what = ($searchKeys) ? $key : $value;       
        if(strpos($what, $needle) !==false ) return $key;
      }          
    } 
    if($searchNested == true) 
      if(arrayFind('1', $finalCheck)) return true;
    else return false;
  }  
}
  
// ------------------------------------------------------------------------         

/**
 * Renames a file using Joomla! helpers.      
 * 
 * @param string $fileold Old filename 
 * @param string $filenew New filename
 * @param string $path Path to the file.
 */  
if(!function_exists('renameFile'))
{
  function renameFile($fileold, $filenew, $path) 
  {
    $src     = $path . DS . $fileold;
    $dest    = $path . DS . $filenew;
    \JFile::move($src, $dest);     
    #\JFile::delete($fileold);
  }
}   

/**
 * Load php files with require_once in a given dir
 *
 * @param string $path Path in which are the file to load
 * @param string $pattern a regexp pattern that filter files to load
 * @param bool $prevents_output security option that prevents output
 * @return array paths of loaded files
 */   
if(!function_exists('requireOnceDir'))
{    
  function requireOnceDir($path, $pattern = "*.php", $prevents_output = true)
  {
    if($path[strlen($path) - 1] != "/") $path .= "/";
    $filenames = glob($path.$pattern);
    if(!is_array($filenames)) $filenames = array();
    if($prevents_output) ob_start();
    foreach($filenames as $filename) require_once $filename;
    if($prevents_output) ob_end_clean();
  }
}

/**
 * Same as strpos just takes an array as the needle.
 */      
if(!function_exists('strpos_array'))
{
  function strpos_array($haystack, $needle) 
  {
    if(!is_array($needle)) $needle = array($needle);
    foreach($needle as $what) {
      if(($pos = strpos($haystack, $what))!==false) return $pos;
    }
    return false; 
  }    
}
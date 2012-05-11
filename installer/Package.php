<?php 

namespace forge\installer; 

use forge\core;

// no direct access
defined( '_Forge' ) or die( 'Restricted access' );    
 
// Joomla! Imports
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.archive');
jimport('joomla.filesystem.path');

// ------------------------------------------------------------------------

/**
 * Package Handling class.  
 *    
 * @package     Forge
 * @subpackage  core
 * @version     1.0 Beta
 * @author      Ken Erickson AKA Bookworm http://bookwormproductions.net
 * @copyright   Copyright 2009 - 2011 Design BreakDown, LLC.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPLv3      
 */
class Package extends \forge\core\Object
{       
  public static function &getInstance()
  {
    static $instance; 

    if(!is_object($instance))
      $instance = new self();   

    return $instance;
  }

  public function retrievePackage($artifact) 
  {   
    $log      = \KLogger::instance($this->tmpPath().DS.'log', \KLogger::INFO);     
    $filename = $artifact->package_name . '.zip'; 

    if(file_exists(JPATH_SITE.DS.'forge'.DS.'vendor'.DS.'cache'.DS.$filename))    
      $package = $this->extractPackage(JPATH_SITE.DS.'forge'.DS.'vendor'.DS.'cache'.DS.$filename, $artifact);    
    else  
    {      
      if($this->getPackage($artifact) == false) {
        $log->logError('Couldn\'t get the package for: ' . $artifact->slug);      
        return false;
      }
      else
        $package = $this->extractPackage(JPATH_SITE.DS.'forge'.DS.'vendor'.DS.'cache'.DS.$filename, $artifact);       
    }         

    return $package; 
  }                  

  public function getPackage($artifact)
  {    
    $log = \KLogger::instance($this->tmpPath().DS.'log', \KLogger::INFO);        

    $url = $artifact->package_uri;   
    if(empty($url)) {
      $log->logError('Couldn\'t get the package url from API: ' . $artifact->slug);      
      return false;
    }

    return $this->downloadPackage($url, $artifact); 
  }       
    
  public function downloadPackage($url, $artifact)
  { 
    $log = \KLogger::instance($this->tmpPath().DS.'log', \KLogger::INFO);     

    $php_errormsg = 'Error Unknown';
    ini_set('track_errors', true);
    ini_set('user_agent', "Forge DBD Jumpstart");     

    $inputHandle = @fopen($url, "r");
    $error       = strstr($php_errormsg,'failed to open stream:');      

    if(!$inputHandle) { 
      $log->logError("Couldn't download ". $artifact->name . "from $url");      
      return false; 
    }              

    $meta_data = stream_get_meta_data($inputHandle);     
    $target    = JPATH_SITE.DS.'forge'.DS.'vendor'.DS.'cache'.DS.$artifact->package_name . '.zip';    

    $contents = null;        

    while(!feof($inputHandle)) {
      $contents .= fread($inputHandle, 4096);
    }

    \JFile::write($target, $contents);
    fclose($inputHandle);      
    unset($contents);            

    return $target;                      
  } 
  
  
  public function extractPackage($filename, $artifact)
  {     
    $log = \KLogger::instance($this->tmpPath().DS.'log', \KLogger::INFO);

    $archivename = $filename;
    $tmpdir      = uniqid('install_');  

    $extractdir  = \JPath::clean(FORGE_TMP_PATH.DS.'installation'.DS.$artifact->slug);
    $archivename = \JPath::clean($archivename);  

    $result = \JArchive::extract( $archivename, $extractdir);   

    if($result == false) {  
      $log->logError("Failed to extract ". $artifact->name . "from $filename");      
      return false;
    }   

    $retval['extractdir'] = $extractdir;
    $retval['packagefile'] = $archivename;

    $dirList = array_merge(\JFolder::files($extractdir, ''), \JFolder::folders($extractdir, ''));

    if(count($dirList) == 1) {
      if(\JFolder::exists($extractdir.DS.$dirList[0]))
        $extractdir = \JPath::clean($extractdir.DS.$dirList[0]);
    }  

    $retval['dir'] = $extractdir;    

    return $retval;
  } 
}
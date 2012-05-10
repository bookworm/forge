<?php 

use forge\core;     

// no direct access
defined('_Forge') or die( 'Restricted access' );

require dirname(__FILE__) . DS . 'core' . DS . 'loader.php';

$loader = \forge\core\Loader::getInstance();      
$loader->loadHelper('Helpers');       
$loader->loadLibs(array('KLogger', 'forge_api\forge_api'));   
$loader->loadClasses(array('core\Forge', 'core\Timer', 'core\Dig', 'installer\Package'));  
#  
jimport('joomla.installer.install');
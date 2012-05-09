<?php 

use forge\core;     

// no direct access
defined( '_Forge' ) or die( 'Restricted access' );

require 'core' . DS . 'loader.php';

$loader = Loader::getInstance();
$loader->loadHelper('Helpers');   
$loader->loadClasses(array('core\Forge', 'core\dig\Dig', 'core\Excavate', 'installer\Package', 'core\Timer')); 
$loader->loadLibs(array('KLogger', 'forge_api\forge_api'));   

jimport('joomla.installer.install');
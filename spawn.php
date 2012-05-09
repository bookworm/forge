<?php   

use \forge\core;

// Load Stuff.          
require_once 'Forge.php';   

$session = file_get_contents(FORGE_TMP_PATH.DS.'session.txt'); 
$session = unserialize($session);    

$forge = new Forge_API_Glue($session['forgeConfig']['pubKey'], $session['forgeConfig']['privateKey']); 

unset($session);

$dig = Dig::getInstance(); 
$dig->start();
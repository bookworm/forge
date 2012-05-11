<?php      

namespace forge\excavate;

use forge\excavate;
                   
$loader = \forge\core\Loader::getInstance();      
$loader->loadClasses(array('installer\Package', 'excavate\installer'));

class Excavator extends \forge\excavate\Core
{     
  public $installer;      
  public $manifestClass; 
  
  public function __construct($artifact, $dig, $options=array())
  {   
    parent::__construct();
    $this->artifact               = $artifact;
    $this->digTasksCount          = $dig->tasks->total;  
    $this->type                   = $artifact->type;        
    $this->classMethods           = get_class_methods($this);        
    $this->setProperties($options);
    $this->dig = $dig;  
    $this->installer = new Installer($this);  
    $this->parent = $this;  
    if(method_exists($this, '_init')) $this->_init();
  }     
  
  public function __sleep()
  {
    return array('eid', 'route', 'msg', 'overwrite', 'upgrade', 'uninstall', 'name', 
      'shouldRetrievePackage', 'digTasksCount', 'artifact', 'onTask', 'installer',
      'type', 'tasks', 'success', 'rollbacks', 'message', 'messages', 'errorMessage', 'tasksCount'
    );
  }
  
  public function getRedirectURL()
	{
		return $this->redirect_url;
	}

	public function setRedirectURL($newurl)
	{
		$this->redirect_url = $newurl;
	}
	    
  public function copyManifest($cid=1)
  {      
    return $this->installer->copyManifest($cid); 
  }     

  public function getManifest()
  {
    if(!is_object($this->manifest))
      $this->installer->findManifest(); 

    return $this->manifest; 
  } 
  
  public function getManifestPath()
  {
    if(!is_object($this->manifestPath))
      $this->manifestPath = $this->installer->findManifest(); 

    return $this->manifestPath; 
  }        
  
  public function findManifest()
  {
    return $this->installer->findManifest();
  }
  
  public function parseQueries($elem)
  {
    return $this->installer->parseQueries($elem);
  } 
  
  public function isManifest($file)
  {
    return $this->installer->isManifest($file);
  } 

  public function parseSQLFiles($elem)
  {
    return $this->installer->parseSQLFiles($elem, $this->getPath('extension_administrator'));
  } 
  
  public function findDeletedFiles($old_files, $new_files)
  {
    return $this->installer->findDeletedFiles($old_files, $new_files);
  }
 
  public function parseFiles($elem, $cid = 0, $oldFiles = null, $oldMD5 = null)
  {
    return $this->installer->parseFiles($elem, $cid, $oldFiles, $oldMD5);
  }  

  public function parseLanguages($elem, $cid = 0)
  {
    return $this->installer->parseLanguages($elem, $cid);
  }         
    
  public function parseMedia($elem, $cid = 0) 
  {
    return $this->installer->parseMedia($elem, $cid);
  } 
 
  public function copyFiles($files, $overwrite = null) 
  {
    return $this->installer->copyFiles($files, $overwrite);
  }  

  public function removeFiles($elem, $cid=0)
  {
    return $this->installer->removeFiles($elem, $cid);    
  } 

  public function getParams()
  {
    return $this->installer->getParams(); 
  }    
  
  public function setSchemaVersion($schema, $eid)
	{       
	  return $this->installer->setSchemaVersion($schema, $eid);
  } 
  
  public function parseSchemaUpdates($schema, $eid)
	{   
	  return $this->installer->parseSchemaUpdates($schema, $eid);
  }   
  
  public function generateManifestCache()
	{
		return json_encode(\JApplicationHelper::parseXMLInstallFile($this->getPath('manifest')));
	} 
	
	public function loadMD5Sum($filename)
	{  
    return $this->installer->loadMD5Sum($filename);
  }
}
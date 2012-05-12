<?php 

namespace forge\excavate\updater;

use forge\excavate\updater;

class Component extends \forge\excavate\cores\Component
{                      
  public $oldAdminFiles = null;
  
  public function task_setStuff()
  {
    $db = $this->getDbo();
 		$this->setOverwrite(true);
 		$this->manifest = $this->getManifest();

 		$name = strtolower(\JFilterInput::getInstance()->clean((string) $this->manifest->name, 'cmd'));
 		if(substr($name, 0, 4) == "com_")
 			$element = $name;
 		else
 			$element = "com_$name";

 		$this->set('name', $name);
 		$this->set('element', $element);

 		$description = (string) $this->manifest->description;

 		if($description)
 			$this->set('message', \JText::_($description));
 		else
 			$this->set('message', '');

 		$this->setPath('extension_site', \JPath::clean(JPATH_SITE . '/components/' . $this->get('element')));
 		$this->setPath('extension_administrator', \JPath::clean(JPATH_ADMINISTRATOR . '/components/' . $this->get('element')));
 		$this->setPath('extension_root', $this->getPath('extension_administrator'));
 		
 		return true;
  }             
  
  public function task_oldFiles()
  {
    $old_manifest = null;
 		$tmpInstaller = new \JInstaller;
 		$tmpInstaller->setPath('source', $this->getPath('extension_administrator'));

 		if(!$tmpInstaller->findManifest())
 		{
 			$tmpInstaller->setPath('source', $this->getPath('extension_site'));
 			if($tmpInstaller->findManifest())
 				$old_manifest = $tmpInstaller->getManifest();
 		}
 		else
 			$old_manifest = $tmpInstaller->getManifest();

 		if($old_manifest) {
 			$this->oldAdminFiles = $old_manifest->administration->files;
 			$this->oldFiles      = $old_manifest->files;
 		}
 		else {
 			$this->oldAdminFiles = null;
 			$this->oldFiles      = null;
 		}

 		if(!$this->manifest->administration) {
 			\JError::raiseWarning(1, \JText::_('JLIB_INSTALLER_ABORT_COMP_UPDATE_ADMIN_ELEMENT'));
 			return false;
 		}
 		
 		return true;
  } 
  
  public function task_setManifestScript()
  {
    $manifestScript = (string) $this->manifest->scriptfile;

 		if($manifestScript) 
 		{
 			$manifestScriptFile = $this->getPath('source') . '/' . $manifestScript;

 			if(is_file($manifestScriptFile))
 				include_once $manifestScriptFile;

 			$classname = $element . 'InstallerScript';

 			if(class_exists($classname)) {
 				$this->manifestClass = new $classname($this);
 				$this->set('manifest_script', $manifestScript);
 			}
 		}  
 		
 		return true;
  }    
  
  public function task_manifestClassPreflight()
  {
    ob_start();
 		ob_implicit_flush(false);

 		if($this->manifestClass && method_exists($this->manifestClass, 'preflight'))
 		{
 			if($this->manifestClass->preflight('update', $this) === false) {
 				$this->abort(\JText::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_CUSTOM_INSTALL_FAILURE'));
 				return false;
 			}
 		}

 		$this->msg = ob_get_contents();
 		ob_end_clean();
 		
 		return true;
  } 
  
  public function task_createFolders()
  {
    $created = false;
 		if(!file_exists($this->getPath('extension_site')))
 		{
 			if(!$created = \JFolder::create($this->getPath('extension_site')))
 			{
 				\JError::raiseWarning(1,
 					\JText::sprintf('JLIB_INSTALLER_ERROR_COMP_UPDATE_FAILED_TO_CREATE_DIRECTORY_SITE', $this->getPath('extension_site'))
 				);

 				return false;
 			}
 		}

 		if($created)
 			$this->pushStep(array('type' => 'folder', 'path' => $this->getPath('extension_site')));

 		$created = false;
 		if(!file_exists($this->getPath('extension_administrator')))
 		{
 			if(!$created = \JFolder::create($this->getPath('extension_administrator')))
 			{
 				\JError::raiseWarning(
 					1,
 					\JText::sprintf(
 						'JLIB_INSTALLER_ERROR_COMP_UPDATE_FAILED_TO_CREATE_DIRECTORY_ADMIN',
 						$this->getPath('extension_administrator')
 					)
 				);       
 				
 				$this->abort();

 				return false;
 			}
 		}

 		if($created)
 			$this->pushStep(array('type' => 'folder', 'path' => $this->getPath('extension_administrator')));
 		
 		return true;
  } 
  
  public function task_copyManifestFiles()
  {
    if($this->manifest->files)
 		{
 			if($this->parseFiles($this->manifest->files, 0, $this->oldFiles) === false) {
 				$this->abort();
 				return false;
 			}
 		}
 		
 		return true;
  }    
  
  public function task_copyAdminManifestFiles()
  {
    if($this->manifest->administration->files)
 		{
 			if($this->parseFiles($this->manifest->administration->files, 1, $this->oldAdminFiles) === false) {
 				$this->abort();
 				return false;
 			}
 		}
 		
 		return true;
  }   
  
  public function task_parseMedia()
  { 
    $this->parseMedia($this->manifest->media);
    
    return true;
  }
  
  public function task_parseLanguages()
  {
    $this->parseLanguages($this->manifest->languages);
 		$this->parseLanguages($this->manifest->administration->languages, 1);
 		
 		return true;
  }          
  
  public function task_copyInstallScript()
  {
    $installFile = (string) $this->manifest->installfile;
 		if($installFile)
 		{
 			if(!file_exists($this->getPath('extension_administrator') . '/' . $installFile) || $this->getOverwrite())
 			{
 				$path['src']  = $this->getPath('source') . '/' . $installFile;
 				$path['dest'] = $this->getPath('extension_administrator') . '/' . $installFile;

 				if(!$this->copyFiles(array($path))) {
 					$this->abort(\JText::_('JLIB_INSTALLER_ABORT_COMP_UPDATE_PHP_INSTALL'));
 					return false;
 				}
 			}

 			$this->set('install_script', $installFile);
 		}
 		
 		return true;
  }  
  
  public function task_copyUninstallScript()
  {
    $uninstallFile = (string) $this->manifest->uninstallfile;
 		if($uninstallFile)
 		{
 			if(!file_exists($this->getPath('extension_administrator') . '/' . $uninstallFile) || $this->getOverwrite())
 			{
 				$path['src']  = $this->getPath('source') . '/' . $uninstallFile;
 				$path['dest'] = $this->getPath('extension_administrator') . '/' . $uninstallFile;

 				if(!$this->copyFiles(array($path))) {
 					$this->abort(\JText::_('JLIB_INSTALLER_ABORT_COMP_UPDATE_PHP_UNINSTALL'));
 					return false;
 				}
 			}
 		}
 		
 		return true;
  }    
  
  public function task_copyManifestScript()
  {
    if($this->get('manifest_script'))
 		{
 			$path['src']  = $this->getPath('source') . '/' . $this->get('manifest_script');
 			$path['dest'] = $this->getPath('extension_administrator') . '/' . $this->get('manifest_script');

 			if(!file_exists($path['dest']) || $this->getOverwrite())
 			{
 				if(!$this->copyFiles(array($path))) {
 					$this->abort(\JText::_('JLIB_INSTALLER_ABORT_COMP_UPDATE_MANIFEST'));
 					return false;
 				}
 			}
 		}
 		
 		return true;
  }  
  
  public function task_schemaUpdates()
  {
    $row       = \JTable::getInstance('extension');
 		$eid       = $row->find(array('element' => strtolower($this->get('element')), 'type' => 'component'));     
 		$this->eid = $eid;   
 		$db        = $this->getDbo();

 		if($this->manifest->update)
 		{                        
 			$result = $this->parseSchemaUpdates($this->manifest->update->schemas, $eid);
 			if($result === false) {
 				$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_COMP_UPDATE_SQL_ERROR', $db->stderr(true)));
 				return false;
 			}
 		}     
 		
 		$this->row = $row;
 		
 		return true;
  } 
  
  public function task_buildAdminMenus()
  {
    if(!$this->_buildAdminMenus($this->eid))
 			\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ABORT_COMP_BUILDADMINMENUS_FAILED'));
 		
 		return true;
  }     
  
  public function task_runInstallScript()
  {
    if($this->get('install_script'))
 		{
 			if(is_file($this->getPath('extension_administrator') . '/' . $this->get('install_script')) || $this->getOverwrite())
 			{
 				$notdef  = false;
 				$ranwell = false;
 				ob_start();
 				ob_implicit_flush(false);

 				require_once $this->getPath('extension_administrator') . '/' . $this->get('install_script');

 				if(function_exists('com_install'))
 				{
 					if(com_install() === false) {
 						$this->abort(\JText::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_CUSTOM_INSTALL_FAILURE'));
 						return false;
 					}
 				}

 				$this->msg .= ob_get_contents(); // append messages
 				ob_end_clean();
 			}
 		}
 		
 		return true;
  }
  
  public function task_runManifestClass()
  {
    ob_start();
 		ob_implicit_flush(false);

 		if($this->manifestClass && method_exists($this->manifestClass, 'update'))
 		{
 			if($this->manifestClass->update($this) === false) {
 				$this->abort(\JText::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_CUSTOM_INSTALL_FAILURE'));
 				return false;
 			}
 		}

 		$this->msg .= ob_get_contents();
 		ob_end_clean();
 		
 		return true;
  } 
  
  public function task_setUID()
  {
    $update = \JTable::getInstance('update');
 		$uid    = $update->find(array('element' => $this->get('element'), 'type' => 'component', 'client_id' => '', 'folder' => ''));

 		if($uid)
 			$update->delete($uid);
 			
		return true;
  }  
  
  public function task_rowStore()
  {    
    $eid = $this->eid;          
    $db  = $this->getDbo();    
    $row = $this->row;
    
    if($eid)
 			$row->load($eid);
 		else
 		{
 			$row->folder    = '';
 			$row->enabled   = 1;
 			$row->protected = 0;
 			$row->access    = 1;
 			$row->client_id = 1;
 			$row->params    = $this->getParams();
 		}

 		$row->name           = $this->get('name');
 		$row->type           = 'component';
 		$row->element        = $this->get('element');
 		$row->manifest_cache = $this->generateManifestCache();

 		if(!$row->store()) {
 			$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_COMP_UPDATE_ROLLBACK', $db->stderr(true)));
 			return false;
 		}
 		
 		return true;
  }   
  
  public function task_copyManifestFile()
  {
    if(!$this->copyManifest()) {
 			$this->abort(\JText::_('JLIB_INSTALLER_ABORT_COMP_UPDATE_COPY_SETUP'));
 			return false;
 		}
 		
 		return true;
  } 
  
  public function task_runManifestClassPostFlight()
  {
    ob_start();
 		ob_implicit_flush(false);

 		if($this->manifestClass && method_exists($this->manifestClass, 'postflight'))
 			$this->manifestClass->postflight('update', $this);

 		$this->msg .= ob_get_contents();
 		ob_end_clean();

 		if($this->msg != '')
 			$this->set('extension_message', $this->msg);
 			
		return true;
  }
}
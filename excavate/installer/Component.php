<?php 

namespace forge\excavate\installer;

use forge\excavate\installer; 

class Component extends \forge\excavate\cores\Component 
{
  public function task_setPathsBasicChecks()
  { 
    if(!$this->_taskSetPaths())      
      return false;      
    
    if(!$this->manifest->administration) {
			\JError::raiseWarning(1, \JText::_('JLIB_INSTALLER_ERROR_COMP_INSTALL_ADMIN_ELEMENT'));
			return false;
		}
	 
		if(file_exists($this->getPath('extension_site')) || file_exists($this->getPath('extension_administrator')))
		{
			if(!$this->getOverwrite())
			{
				if(file_exists($this->getPath('extension_site')))
					\JError::raiseWarning(1, \JText::sprintf('JLIB_INSTALLER_ERROR_COMP_INSTALL_DIR_SITE', $this->getPath('extension_site')));
				else
				{
					\JError::raiseWarning(1,
						\JText::sprintf('JLIB_INSTALLER_ERROR_COMP_INSTALL_DIR_ADMIN', $this->getPath('extension_administrator'))
					);
				}     
				
				return false;
			}
		}  
		
		return true;
  }    
  
  public function task_manifestScript()
  {
    $manifestScript = (string) $this->manifest->scriptfile;

		if($manifestScript)
		{
			$manifestScriptFile = $this->getPath('source') . '/' . $manifestScript;

			if(is_file($manifestScriptFile))
				include_once $manifestScriptFile;

			$classname = $this->get('element') . 'InstallerScript';

			if(class_exists($classname)) {
				$this->manifestClass = new $classname($this);
				$this->set('manifest_script', $manifestScript);
			}
		}

		ob_start();
		ob_implicit_flush(false);

		if($this->manifestClass && method_exists($this->manifestClass, 'preflight'))
		{
			if($this->manifestClass->preflight('install', $this) === false) {
				$this->abort(\JText::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_CUSTOM_INSTALL_FAILURE'));
				return false;
			}
		}

		$this->msg = ob_get_contents();
		ob_end_clean();  
		
		return true;
  } 
  
  public function task_createSiteFolder()
  {
    $created = false;

		if(!file_exists($this->getPath('extension_site')))
		{
			if(!$created = \JFolder::create($this->getPath('extension_site')))
			{
				\JError::raiseWarning(1,
					\JText::sprintf('JLIB_INSTALLER_ERROR_COMP_INSTALL_FAILED_TO_CREATE_DIRECTORY_SITE', $this->getPath('extension_site'))
				); 
				
				return false;
			}
		}

		if($created)
			$this->pushStep(array('type' => 'folder', 'path' => $this->getPath('extension_site')));    
			
		return true;
  }  
  
  public function task_createAdminFolder()
  {
    $created = false;
		if(!file_exists($this->getPath('extension_administrator')))
		{
			if(!$created = \JFolder::create($this->getPath('extension_administrator')))
			{
				\JError::raiseWarning(
					1,
					\JText::sprintf(
						'JLIB_INSTALLER_ERROR_COMP_INSTALL_FAILED_TO_CREATE_DIRECTORY_ADMIN',
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
  
  public function task_parseFiles()
  {
    if($this->manifest->files)
		{
			if($this->parseFiles($this->manifest->files) === false) {
				$this->abort();
				return false;
			}
		}
		
		return true;
  }   
  
  public function task_parseAdminFiles()
  {
    if($this->manifest->administration->files)
		{
			if($this->parseFiles($this->manifest->administration->files, 1) === false) {
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
  
  public function task_installFile()
  {
    $installFile = (string) $this->manifest->installfile;
		if($installFile)
		{
			if(!file_exists($this->getPath('extension_administrator') . '/' . $installFile) || $this->getOverwrite())
			{
				$path['src']  = $this->getPath('source') . '/' . $installFile;
				$path['dest'] = $this>getPath('extension_administrator') . '/' . $installFile;

				if(!$this->copyFiles(array($path))) {
					$this->abort(\JText::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_PHP_INSTALL'));
					return false;
				}
			}

			$this->set('install_script', $installFile);
		}
		
		return true;
  } 
  
  public function task_uninstallFile()
  {
    $uninstallFile = (string) $this->manifest->uninstallfile;
		if($uninstallFile)
		{
			if(!file_exists($this->getPath('extension_administrator') . '/' . $uninstallFile) || $this->getOverwrite())
			{
				$path['src']  = $this->getPath('source') . '/' . $uninstallFile;
				$path['dest'] = $this->getPath('extension_administrator') . '/' . $uninstallFile;

				if(!$this->copyFiles(array($path))) {
					$this->abort(\JText::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_PHP_UNINSTALL'));
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
					$this->abort(\JText::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_MANIFEST'));
					return false;
				}
			}
		}
		
		return true;
  }  
  
  public function task_parseSQLManifest()
  {
    if(isset($this->manifest->install->sql))
		{
			$utfresult = $this->parseSQLFiles($this->manifest->install->sql);

			if($utfresult === false) {
				$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_COMP_INSTALL_SQL_ERROR', $db->stderr(true)));
				return false;
			}
		} 
		
		return true;
  } 
  
  public function task_installScript()
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

				$this->msg .= ob_get_contents(); 
				ob_end_clean();
			}
		}
		
		return true;    
  }   
  
  public function task_runManifestClass()
  {
    ob_start();
		ob_implicit_flush(false);

		if($this->manifestClass && method_exists($this->manifestClass, 'install'))
		{
			if($this->manifestClass->install($this) === false) {
				$this->abort(\JText::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_CUSTOM_INSTALL_FAILURE'));
				return false;
			}
		}

		$this->msg .= ob_get_contents();
		ob_end_clean(); 
		
		return true;
  }    
  
  public function task_insertIntoDB()
  {
    $row = \JTable::getInstance('extension');
		$row->set('name', $this->get('name'));
		$row->set('type', 'component');
		$row->set('element', $this->get('element'));
		$row->set('folder', '');
		$row->set('enabled', 1);
		$row->set('protected', 0);
		$row->set('access', 0);
		$row->set('client_id', 1);
		$row->set('params', $this->getParams());
		$row->set('manifest_cache', $this->generateManifestCache());

		if(!$row->store()) {
			$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_COMP_INSTALL_ROLLBACK', $db->stderr(true)));
			return false;
		}     
		
		$this->row = $row;
		
		return true;
  }     
  
  public function task_setEID()
  {                
    $db        = $this->db;
    $eid       = $db->insertid();  
    $this->eid = $eid;

		$update = \JTable::getInstance('update');
		$uid    = $update->find(array('element' => $this->get('element'), 'type' => 'component', 'client_id' => '', 'folder' => ''));

		if($uid)
			$update->delete($uid);
	 
	  return true;
  }     
  
  public function task_copyManifest()
  {
		if(!$this->copyManifest()) {
			$this->abort(\JText::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_COPY_SETUP'));
			return false;
		}  
		
		return true;
  }  
  
  public function task_buildAdminMenus()
  {
    if(!$this->_buildAdminMenus($this->row->extension_id))
			\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ABORT_COMP_BUILDADMINMENUS_FAILED'));
			
		return true;
  }
  
  public function task_setSchemaVersion()
  {
    if($this->manifest->update)
			$this->setSchemaVersion($this->manifest->update->schemas, $eid);
			
		return true;
  }   
  
  public function task_assetDB()
  {      
    $row              = $this->row;
    $asset            = \JTable::getInstance('Asset');
		$asset->name      = $row->element;
		$asset->parent_id = 1;
		$asset->rules     = '{}';
		$asset->title     = $row->name;      
		
		$asset->setLocation(1, 'last-child');   
		
		if(!$asset->store()) {
			$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_COMP_INSTALL_ROLLBACK', $db->stderr(true)));
			return false;
		}
		
		return true;
  } 
  
  public function task_manifestClassPostFlight()
  {
		ob_start();
		ob_implicit_flush(false);

		if($this->manifestClass && method_exists($this->manifestClass, 'postflight'))
			$this->manifestClass->postflight('install', $this);

		$this->msg .= ob_get_contents();
		ob_end_clean();

		if($this->msg != '')
			$this->set('extension_message', $this->msg);
			
		return true;
  }
}                   
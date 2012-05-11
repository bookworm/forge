<?php 

namespace forge\excavate\cores;

use forge\excavate\cores;

class File extends \forge\excavate\Excavator  
{
  public function loadLanguage($path)
	{
		$this->manifest = $this->getManifest();
		$extension      = 'files_' . str_replace('files_', '', strtolower(\JFilterInput::getInstance()->clean((string) $this->manifest->name, 'cmd')));
		$lang           = \JFactory::getLanguage();
		$source         = $path;
		
		$lang->load($extension . '.sys', $source, null, false, false)
			|| $lang->load($extension . '.sys', JPATH_SITE, null, false, false)
			|| $lang->load($extension . '.sys', $source, $lang->getDefault(), false, false)
			|| $lang->load($extension . '.sys', JPATH_SITE, $lang->getDefault(), false, false);
	} 
	
 	protected function extensionExistsInSystem($extension = null)
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true);
		$query->select($query->qn('extension_id'))
			->from($query->qn('#__extensions'));
		$query->where($query->qn('type') . ' = ' . $query->q('file'))
			->where($query->qn('element') . ' = ' . $query->q($extension));
		$db->setQuery($query);

		try {
			$db->Query();
		}
		catch(JException $e) {
			$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_FILE_ROLLBACK', $db->stderr(true)));
			return false;
		}
		$id = $db->loadResult();

		if(empty($id))
			return false;

		return true;
	}
	
	protected function populateFilesAndFolderList()
	{
		$this->folderList = array();
		$this->fileList   = array();

		$eFileset = $this->manifest->fileset->files;

		$packagePath = $this->getPath('source');
		$jRootPath   = \JPath::clean(JPATH_ROOT);

		foreach($this->manifest->fileset->files as $eFiles)
		{
			$folder = (string) $eFiles->attributes()->folder;
			$target = (string) $eFiles->attributes()->target;

			$arrList = preg_split("#/|\\/#", $target);

			$folderName = $jRootPath;
			foreach($arrList as $dir)
			{
				if(empty($dir))
					continue;

				$folderName .= '/' . $dir;
				if(!JFolder::exists($folderName))
					array_push($this->folderList, $folderName);
			}

			$sourceFolder = empty($folder) ? $packagePath : $packagePath . '/' . $folder;
			$targetFolder = empty($target) ? $jRootPath : $jRootPath . '/' . $target;

			if(!JFolder::exists($sourceFolder)) {
				\JError::raiseWarning(1, \JText::sprintf('JLIB_INSTALLER_ABORT_FILE_INSTALL_FAIL_SOURCE_DIRECTORY', $sourceFolder));
				$this->abort();
				return false;
			}

			if(count($eFiles->children()))
			{
				foreach($eFiles->children() as $eFileName)
				{
					$path['src']  = $sourceFolder . '/' . $eFileName;
					$path['dest'] = $targetFolder . '/' . $eFileName;
					$path['type'] = 'file';
					if($eFileName->getName() == 'folder')
					{
						$folderName = $targetFolder . '/' . $eFileName;
						array_push($this->folderList, $folderName);
						$path['type'] = 'folder';
					}

					array_push($this->fileList, $path);
				}
			}
			else
			{
				$files = JFolder::files($sourceFolder);
				foreach($files as $file)
				{
					$path['src']  = $sourceFolder . '/' . $file;
					$path['dest'] = $targetFolder . '/' . $file;
					array_push($this->fileList, $path);
				}
			}
		}
	} 
	
	public function refreshManifestCache()
	{
		$manifestPath = JPATH_MANIFESTS . '/files/' . $this->extension->element . '.xml';
		$this->manifest = $this->isManifest($manifestPath);
		$this->setPath('manifest', $manifestPath);

		$manifest_details = \JApplicationHelper::parseXMLInstallFile($this->getPath('manifest'));
		$this->extension->manifest_cache = json_encode($manifest_details);
		$this->extension->name = $manifest_details['name'];

		try {
			return $this->extension->store();
		}
		catch(JException $e) {
			\JError::raiseWarning(101, \JText::_('JLIB_INSTALLER_ERROR_PACK_REFRESH_MANIFEST_CACHE'));
			return false;
		}
	}  
	
	public function _taskSetPaths()
	{
	  $this->manifest = $this->getManifest();

		$name = \JFilterInput::getInstance()->clean((string) $this->manifest->name, 'string');
		$this->set('name', $name);

		$manifestPath = \JPath::clean($this->getPath('manifest'));
		$element      = preg_replace('/\.xml/', '', basename($manifestPath));
		$this->set('element', $element);

		$description = (string) $this->manifest->description;
		if($description)
			$this->set('message', \JText::_($description));
		else
			$this->set('message', '');

		if($this->extensionExistsInSystem($element))
		{
			if(!$this->getOverwrite()) {
				$this->abort(\JText::_('JLIB_INSTALLER_ABORT_FILE_SAME_NAME'));
				return false;
			}
			else
				$this->route = 'update';
		}

		$this->setPath('extension_root', JPATH_ROOT);
		
		return true;
	}     
	
	public function _taskCopyManifestScript()
  {
    $this->scriptElement = $this->manifest->scriptfile;
		$manifestScript      = (string) $this->manifest->scriptfile;

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
  
  public function _taskSetManifestClass()
  {
    ob_start();
		ob_implicit_flush(false);

		if($this->manifestClass && method_exists($this->manifestClass, 'preflight'))
		{
			if($this->manifestClass->preflight($this->route, $this) === false) {
				$this->abort(\JText::_('JLIB_INSTALLER_ABORT_FILE_INSTALL_CUSTOM_INSTALL_FAILURE'));
				return false;
			}
		}

		$this->msg = ob_get_contents(); 
		ob_end_clean();
		
		return true;
  }
  
  public function _taskPopulateFilesAndFolderList()
  {
    $this->populateFilesAndFolderList();

		foreach($this->folderList as $folder)
		{
			if(!JFolder::exists($folder))
			{
				if(!$created = JFolder::create($folder))
				{
					\JError::raiseWarning(1, \JText::sprintf('JLIB_INSTALLER_ABORT_FILE_INSTALL_FAIL_SOURCE_DIRECTORY', $folder));
					$this->abort();
					return false;
				}
  
				if($created)
					$this->pushStep(array('type' => 'folder', 'path' => $folder));
			}
		}

		$this->copyFiles($this->fileList);
		
		return true;
  }  
  
  public function _taskSetDBStuff()
  {
    $db = $this->getDbo();
   
    $query = $db->getQuery(true);
    $query->select($query->qn('extension_id'))
    	->from($query->qn('#__extensions'));
    $query->where($query->qn('type') . ' = ' . $query->q('file'))
    	->where($query->qn('element') . ' = ' . $query->q($element));
    $db->setQuery($query);
    try {
    	$db->Query();
    }
    catch(JException $e) {
    	$this->abort(
    		\JText::sprintf('JLIB_INSTALLER_ABORT_FILE_ROLLBACK', \JText::_('JLIB_INSTALLER_' . $this->route), $db->stderr(true))
    	);  
   
    	return false;
    }
    $id  = $db->loadResult();
    $row = \JTable::getInstance('extension');
   
    if($id)
    {
    	$row->load($id);
    	$row->set('name', $this->get('name'));
    	$row->manifest_cache = $this->generateManifestCache();
    	if(!$row->store())
    	{
    		$this->abort(
    			\JText::sprintf('JLIB_INSTALLER_ABORT_FILE_ROLLBACK', \JText::_('JLIB_INSTALLER_' . $this->route), $db->stderr(true))
    		);    
   
    		return false;
    	}
    }
    else
    {
    	$row->set('name', $this->get('name'));
    	$row->set('type', 'file');
    	$row->set('element', $this->get('element'));
    	$row->set('folder', '');
    	$row->set('enabled', 1);
    	$row->set('protected', 0);
    	$row->set('access', 0);
    	$row->set('client_id', 0);
    	$row->set('params', '');
    	$row->set('system_data', '');
    	$row->set('manifest_cache', $this->generateManifestCache());
   
    	if(!$row->store()) {
    		$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_FILE_INSTALL_ROLLBACK', $db->stderr(true)));
    		return false;
    	}
   
    	$row->set('extension_id', $db->insertid());
   
    	$this->pushStep(array('type' => 'extension', 'extension_id' => $row->extension_id));
    }
        
    $this->row = $row;
    
    return true; 
  }
  
  public function _taskParseSQL()
  {          
    $row = $this->row;
    
    if(strtolower($this->route) == 'install')
		{
			$utfresult = $this->parseSQLFiles($this->manifest->install->sql);

			if($utfresult === false)
			{
				$this->abort(
					\JText::sprintf('JLIB_INSTALLER_ABORT_FILE_INSTALL_SQL_ERROR', \JText::_('JLIB_INSTALLER_' . $this->route), $db->stderr(true))
				);

				return false;
			}

			if($this->manifest->update)
				$this->setSchemaVersion($this->manifest->update->schemas, $row->extension_id);
		}
		elseif(strtolower($this->route) == 'update')
		{
			if($this->manifest->update)
			{
				$result = $this->parseSchemaUpdates($this->manifest->update->schemas, $row->extension_id);    
				
				if($result === false) {
					$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_FILE_UPDATE_SQL_ERROR', $db->stderr(true)));
					return false;
				}
			}
		}
		
		return true;
  }  
  
  public function _taskRunManifestClass()
  {
    ob_start();
		ob_implicit_flush(false);

		if($this->manifestClass && method_exists($this->manifestClass, $this->route))
		{
			if($this->manifestClass->{$this->route}($this) === false) {
				$this->abort(\JText::_('JLIB_INSTALLER_ABORT_FILE_INSTALL_CUSTOM_INSTALL_FAILURE'));
				return false;
			}
		}

		$this->msg .= ob_get_contents(); 
		ob_end_clean();
		
		return true;
  } 
  
  public function _taskCopyManifestFile()
  {
    $manifest = array();
		$manifest['src']  = $this->getPath('manifest');
		$manifest['dest'] = JPATH_MANIFESTS . '/files/' . basename($this->getPath('manifest'));     
		
		if(!$this->copyFiles(array($manifest), true)) {
			$this->abort(\JText::_('JLIB_INSTALLER_ABORT_FILE_INSTALL_COPY_SETUP'));
			return false;
		}
		
		return true;
  }    
  
  public function _taskInsertUID()
  {
    $update = \JTable::getInstance('update');
		$uid    = $update->find(
			array('element' => $this->get('element'), 'type' => 'file', 'client_id' => '', 'folder' => '')
		);

		if($uid)
			$update->delete($uid);
			
		return true;
  } 
  
  public function _taskRunManifestClassPostFlight()
  {
    ob_start();
		ob_implicit_flush(false);

		if($this->manifestClass && method_exists($this->manifestClass, 'postflight'))
			$this->manifestClass->postflight($this->route, $this);

		$this->msg .= ob_get_contents();
		ob_end_clean();

		if($this->msg != '')
			$this->set('extension_message', $this->msg); 
			
		return true;
  }
}      
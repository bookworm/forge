<?php 

namespace forge\excavate\cores;

use forge\excavate\cores;

class Library extends \forge\excavate\Excavator  
{
  public function loadLanguage($path = null)
	{
	  $source = $this->getPath('source');
		if(!$source)
			$this->setPath('source', JPATH_PLATFORM . '/' . $this->extension->element);

		$this->manifest = $this->getManifest();      
		
		$extension = 'lib_' . strtolower(\JFilterInput::getInstance()->clean((string) $this->manifest->name, 'cmd'));
		$name = strtolower((string) $this->manifest->libraryname);
		$lang = \JFactory::getLanguage();  
		
		$source = $path ? $path : JPATH_PLATFORM . "/$name";
		$lang->load($extension . '.sys', $source, null, false, false)
			|| $lang->load($extension . '.sys', JPATH_SITE, null, false, false)
			|| $lang->load($extension . '.sys', $source, $lang->getDefault(), false, false)
			|| $lang->load($extension . '.sys', JPATH_SITE, $lang->getDefault(), false, false); 
	}   
	     
	public function discover()
	{
		$results = array();
		$file_list = \JFolder::files(JPATH_MANIFESTS . '/libraries', '\.xml$');  
		
		foreach($file_list as $file)
		{
			$manifest_details = \JApplicationHelper::parseXMLInstallFile(JPATH_MANIFESTS . '/libraries/' . $file);
			$file = \JFile::stripExt($file);
			$extension = \JTable::getInstance('extension');
			$extension->set('type', 'library');
			$extension->set('client_id', 0);
			$extension->set('element', $file);
			$extension->set('name', $file);
			$extension->set('state', -1);
			$extension->set('manifest_cache', json_encode($manifest_details));
			$results[] = $extension;
		} 
		
		return $results;
	}  
	
	public function discover_install()
	{
		$manifestPath = JPATH_MANIFESTS . '/libraries/' . $this->extension->element . '.xml';
		$this->manifest = $this->isManifest($manifestPath);
		$this->setPath('manifest', $manifestPath);
		$manifest_details = \JApplicationHelper::parseXMLInstallFile($this->getPath('manifest'));
		$this->extension->manifest_cache = json_encode($manifest_details);
		$this->extension->state   = 0;
		$this->extension->name    = $manifest_details['name'];
		$this->extension->enabled = 1;
		$this->extension->params  = $this->getParams();       
		
		if($this->extension->store())
			return true;
		else {
			\JError::raiseWarning(101, \JText::_('JLIB_INSTALLER_ERROR_LIB_DISCOVER_STORE_DETAILS'));
			return false;
		}
	}     
	
	public function refreshManifestCache()
	{
		$manifestPath   = JPATH_MANIFESTS . '/libraries/' . $this->extension->element . '.xml';
		$this->manifest = $this->isManifest($manifestPath);
		$this->setPath('manifest', $manifestPath);

		$manifest_details = \JApplicationHelper::parseXMLInstallFile($this->getPath('manifest'));
		$this->extension->manifest_cache = json_encode($manifest_details);
		$this->extension->name = $manifest_details['name'];

		try {
			return $this->extension->store();
		}
		catch(JException $e) {
			\JError::raiseWarning(101, \JText::_('JLIB_INSTALLER_ERROR_LIB_REFRESH_MANIFEST_CACHE'));
			return false;
		}
	}   
	
	public function _taskSetStuff()
  {
    $this->manifest = $this->getManifest();

		$name    = \JFilterInput::getInstance()->clean((string) $this->manifest->name, 'string');
		$element = str_replace('.xml', '', basename($this->getPath('manifest')));
		$this->set('name', $name);
		$this->set('element', $element);          
		
		return true;
  } 
  
  public function _taskInsertDBStuff()
  {
    $db = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('extension_id'));
		$query->from($db->quoteName('#__extensions'));
		$query->where($db->quoteName('type') . ' = ' . $db->quote('library'));
		$query->where($db->quoteName('element') . ' = ' . $db->quote($element));
		$db->setQuery($query);
		$result = $db->loadResult();        
		
		if($result)
		{
			if($this->getOverwrite() || $this->getUpgrade()) {   
				$installer = new \JInstaller; 
				$installer->uninstall('library', $result);
			}
			else {
				$this->abort(\JText::_('JLIB_INSTALLER_ABORT_LIB_INSTALL_ALREADY_INSTALLED'));
				return false;
			}
		}
		
		return true;
  } 
  
  public function _taskSetDescriptionAndLibraryName()
  {
    $description = (string) $this->manifest->description;
		if($description)
			$this->set('message', \JText::_($description));
		else
			$this->set('message', '');  
			
		$group = (string) $this->manifest->libraryname;
		if(!$group) {
			$this->abort(\JText::_('JLIB_INSTALLER_ABORT_LIB_INSTALL_NOFILE'));
			return false;
		}
		else
			$this->setPath('extension_root', JPATH_PLATFORM . '/' . implode(DIRECTORY_SEPARATOR, explode('/', $group)));
			
		return true;
  } 
  
  public function _taskParseFiles()
  {
    $created = false;
 		if(!file_exists($this->getPath('extension_root')))
 		{
 			if(!$created = \JFolder::create($this->getPath('extension_root')))
 			{
 				$this->abort(
 					\JText::sprintf('JLIB_INSTALLER_ABORT_LIB_INSTALL_FAILED_TO_CREATE_DIRECTORY', $this->getPath('extension_root'))
 				);        

 				return false;
 			}
 		}	

 		if($created)
 			$this->pushStep(array('type' => 'folder', 'path' => $this->getPath('extension_root')));    

 		if($this->parseFiles($this->manifest->files, -1) === false) {
 			$this->abort();
 			return false;
 		}

 		return true;
  }  
  
  public function _taskInsertRow()
  {
    $row = \JTable::getInstance('extension');
		$row->name           = $this->get('name');
		$row->type           = 'library';
		$row->element        = $this->get('element');
		$row->folder         = ''; 
		$row->enabled        = 1;
		$row->protected      = 0;
		$row->access         = 1;
		$row->client_id      = 0;
		$row->params         = $this->getParams();
		$row->custom_data    = '';
		$row->manifest_cache = $this->generateManifestCache();
		if(!$row->store()) {
			$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_LIB_INSTALL_ROLLBACK', $db->stderr(true)));
			return false;
		}      
		
		$this->row = $row;
		
		return true;
  }   
  
  public function _taskCopyManifest()
  {
    $manifest = array();
		$manifest['src']  = $this->getPath('manifest');
		$manifest['dest'] = JPATH_MANIFESTS . '/libraries/' . basename($this->getPath('manifest'));    
		
		if(!$this->copyFiles(array($manifest), true)) {
			$this->abort(\JText::_('JLIB_INSTALLER_ABORT_LIB_INSTALL_COPY_SETUP'));
			return false;
		}
		
		return true;
  }
}
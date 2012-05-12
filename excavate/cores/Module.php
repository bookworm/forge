<?php 

namespace forge\excavate\cores;

use forge\excavate\cores;

class Module extends \forge\excavate\Excavator  
{                     
  public $basePath;
  public $clientId;  
  public $cname;
  
  public function loadLanguage($path = null)
	{
		$source = $this->getPath('source');

		if(!$source)
		{
			$this->setPath('source',
				($this->extension->client_id ? JPATH_ADMINISTRATOR : JPATH_SITE) . '/modules/' . $this->extension->element
			);
		}

		$this->manifest = $this->getManifest();

		if($this->manifest->files)
		{
			$element   = $this->manifest->files;
			$extension = '';

			if(count($element->children()))
			{
				foreach($element->children() as $file)
				{
					if((string) $file->attributes()->module) {
						$extension = strtolower((string) $file->attributes()->module);
						break;
					}
				}
			}

			if($extension)
			{
				$lang   = \JFactory::getLanguage();
				$source = $path ? $path : ($this->extension->client_id ? JPATH_ADMINISTRATOR : JPATH_SITE) . '/modules/' . $extension;
				$folder = (string) $element->attributes()->folder;

				if($folder && file_exists("$path/$folder"))
					$source = "$path/$folder";

				$client = (string) $this->manifest->attributes()->client;
				$lang->load($extension . '.sys', $source, null, false, false)
					|| @$lang->load($extension . '.sys', constant('JPATH_' . strtoupper($client)), null, false, false)
					|| $lang->load($extension . '.sys', $source, $lang->getDefault(), false, false)
					|| @$lang->load($extension . '.sys', constant('JPATH_' . strtoupper($client)), $lang->getDefault(), false, false);
			}
		}
	}       

	public function discover()
	{
		$results    = array();
		$site_list  = \JFolder::folders(JPATH_SITE . '/modules');
		$admin_list = \JFolder::folders(JPATH_ADMINISTRATOR . '/modules');
		$site_info  = \JApplicationHelper::getClientInfo('site', true);
		$admin_info = \JApplicationHelper::getClientInfo('administrator', true);

		foreach($site_list as $module)
		{
			$manifest_details = \JApplicationHelper::parseXMLInstallFile(JPATH_SITE . "/modules/$module/$module.xml");
			$extension = \JTable::getInstance('extension');
			$extension->set('type', 'module');
			$extension->set('client_id', $site_info->id);
			$extension->set('element', $module);
			$extension->set('name', $module);
			$extension->set('state', -1);
			$extension->set('manifest_cache', json_encode($manifest_details));
			$results[] = clone $extension;
		}

		foreach($admin_list as $module)
		{
			$manifest_details = \JApplicationHelper::parseXMLInstallFile(JPATH_ADMINISTRATOR . "/modules/$module/$module.xml");
			$extension = \JTable::getInstance('extension');
			$extension->set('type', 'module');
			$extension->set('client_id', $admin_info->id);
			$extension->set('element', $module);
			$extension->set('name', $module);
			$extension->set('state', -1);
			$extension->set('manifest_cache', json_encode($manifest_details));
			$results[] = clone $extension;
		}

		return $results;
	}    
	
	public function discover_install()
	{
		$client       = \JApplicationHelper::getClientInfo($this->extension->client_id);
		$manifestPath = $client->path . '/modules/' . $this->extension->element . '/' . $this->extension->element . '.xml';
		$this->manifest = $this->isManifest($manifestPath);
		$description = (string) $this->manifest->description;

		if($description)
			$this->set('message', \JText::_($description));
		else
			$this->set('message', '');

		$this->setPath('manifest', $manifestPath);
		$manifest_details = \JApplicationHelper::parseXMLInstallFile($this->getPath('manifest'));

		$this->extension->manifest_cache = json_encode($manifest_details);
		$this->extension->state          = 0;
		$this->extension->name           = $manifest_details['name'];
		$this->extension->enabled        = 1;
		$this->extension->params         = $this->getParams();   
		
		if($this->extension->store())
			return $this->extension->get('extension_id');
		else {
			\JError::raiseWarning(101, \JText::_('JLIB_INSTALLER_ERROR_MOD_DISCOVER_STORE_DETAILS'));
			return false;
		}
	}  
	
	public function refreshManifestCache()
	{
		$client         = \JApplicationHelper::getClientInfo($this->extension->client_id);
		$manifestPath   = $client->path . '/modules/' . $this->extension->element . '/' . $this->extension->element . '.xml';
		$this->manifest = $this->isManifest($manifestPath); 
		
		$this->setPath('manifest', $manifestPath);
		$manifest_details                = \JApplicationHelper::parseXMLInstallFile($this->getPath('manifest'));
		$this->extension->manifest_cache = json_encode($manifest_details);
		$this->extension->name           = $manifest_details['name'];

		if($this->extension->store())
			return true;
		else {
			\JError::raiseWarning(101, \JText::_('JLIB_INSTALLER_ERROR_MOD_REFRESH_MANIFEST_CACHE'));
			return false;
		}
	}   

	protected function _rollback_menu($arg)
	{
		$db = $this->getDbo();

		$query = 'DELETE' . ' FROM #__modules_menu' . ' WHERE moduleid=' . (int) $arg['id'];
		$db->setQuery($query);

		try {
			return $db->query();
		}
		catch(JException $e) {
			return false;
		}
	}

	protected function _rollback_module($arg)
	{
		$db = $this->getDbo();

		$query = 'DELETE' . ' FROM #__modules' . ' WHERE id=' . (int) $arg['id'];
		$db->setQuery($query);
		try {
			return $db->query();
		}
		catch(JException $e) {
			return false;
		}
	}  
	
	public function _taskSetupStuff()
  {
    $this->db       = $this->getDbo();
		$this->manifest = $this->getManifest();

		$name = (string) $this->manifest->name;
		$name = \JFilterInput::getInstance()->clean($name, 'string');
		$this->set('name', $name);

		$description = (string) $this->manifest->description;
		if($description)
			$this->set('message', \JText::_($description));
		else
			$this->set('message', '');

		if($cname = (string) $this->manifest->attributes()->client)
		{
			$client = \JApplicationHelper::getClientInfo($cname, true);

			if($client === false) {
				$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_MOD_UNKNOWN_CLIENT', \JText::_('JLIB_INSTALLER_' . $this->route), $client->name));
				return false;
			}

			$basePath = $client->path;
			$clientId = $client->id;
		}
		else
		{
			$cname    = 'site';
			$basePath = JPATH_SITE;
			$clientId = 0;
		}

		$element = '';
		if(count($this->manifest->files->children()))
		{
			foreach($this->manifest->files->children() as $file)
			{
				if((string) $file->attributes()->module)
				{
					$element = (string) $file->attributes()->module;
					$this->set('element', $element);
					break;
				}
			}
		}      
		
		if(!empty($element))
			$this->setPath('extension_root', $basePath . '/modules/' . $element);
		else {
			$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_MOD_INSTALL_NOFILE', \JText::_('JLIB_INSTALLER_' . $this->route)));
			return false;
		}
		
		$this->basePath = $basePath; 
		$this->clientId = $clientId;
		$this->cname    = $cname;
		
		return true;
  }
  
  public function _taskGetDBID()
  {       
    $element  = $this->element; 
    $clientId = $this->clientId;
       
    $db    = $this->db;
    $query = $db->getQuery(true);
		$query->select($query->qn('extension_id'))->from($query->qn('#__extensions'));
		$query->where($query->qn('element') . ' = ' . $query->q($element))->where($query->qn('client_id') . ' = ' . (int) $clientId);
		$db->setQuery($query);

		try {
			$db->Query();
		}
		catch(JException $e) {
			$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_MOD_ROLLBACK', \JText::_('JLIB_INSTALLER_' . $this->route), $db->stderr(true)));
			return false;
		}

		$this->eid = $db->loadResult();
		
		return true;
  }  
  
  public function _taskSetUpdateElement()
  {
    if(file_exists($this->getPath('extension_root')) && (!$this->getOverwrite() || $this->getUpgrade()))
		{
			$updateElement = $this->manifest->update;

			if($this->getUpgrade() || ($this->manifestClass && method_exists($this->manifestClass, 'update'))
				|| $updateElement)
			{
				$this->setOverwrite(true);
				$this->setUpgrade(true);

				if($this->eid)
					$this->route = 'Update';
			}
			elseif(!$this->getOverwrite())
			{
				$this->abort(
					\JText::sprintf(
						'JLIB_INSTALLER_ABORT_MOD_INSTALL_DIRECTORY', \JText::_('JLIB_INSTALLER_' . $this->route),
						$this->getPath('extension_root')
					)
				);

				return false;
			}
		}
		
		return true;
  } 
  
  public function _taskSetManifestScript()
  {
    $this->scriptElement = $this->manifest->scriptfile;
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
  
  public function _taskSetManifestClass()
  {
    ob_start();
		ob_implicit_flush(false);

		if($this->manifestClass && method_exists($this->manifestClass, 'preflight'))
		{
			if($this->manifestClass->preflight($this->route, $this) === false) {
				$this->abort(\JText::_('JLIB_INSTALLER_ABORT_MOD_INSTALL_CUSTOM_INSTALL_FAILURE'));
				return false;
			}
		}

		$this->msg = ob_get_contents();
		ob_end_clean();
		
		return true;
  }    
  
  public function _taskCreateFolder()
  {
    $created = false;
		if(!file_exists($this->getPath('extension_root')))
		{
			if(!$created = \JFolder::create($this->getPath('extension_root')))
			{
				$this->abort(
					\JText::sprintf(
						'JLIB_INSTALLER_ABORT_MOD_INSTALL_CREATE_DIRECTORY', \JText::_('JLIB_INSTALLER_' . $this->route),
						$this->getPath('extension_root')
					)
				);

				return false;
			}
		}

		if($created)
			$this->pushStep(array('type' => 'folder', 'path' => $this->getPath('extension_root')));
		
		return true;
  }  
  
  public function _taskParseManfiestFiles()
  {
    if($this->parseFiles($this->manifest->files, -1) === false) {
			$this->abort();
			return false;
		}
		
		return true;
  }   
  
  public function _taskCopyManifestScript()
  {
    if($this->get('manifest_script'))
		{
			$path['src']  = $this->getPath('source') . '/' . $this->get('manifest_script');
			$path['dest'] = $this->getPath('extension_root') . '/' . $this->get('manifest_script');

			if(!file_exists($path['dest']) || $this->getOverwrite())
			{
				if(!$this->copyFiles(array($path))) {
					$this->abort(\JText::_('JLIB_INSTALLER_ABORT_MOD_INSTALL_MANIFEST'));
					return false;
				}
			}
		}
		
		return true;
  } 
  
  public function _taskInsertRowDBStuff()
  {
    $row      = \JTable::getInstance('extension');    
    $id       = $this->eid;             
    $clientId = $this->clientId;
    $db       = $this->getDbo();
    
		if($id)
		{
			$row->load($id);
			$row->name           = $this->get('name'); 
			$row->manifest_cache = $this->generateManifestCache(); 

			if(!$row->store()) {
				$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_MOD_ROLLBACK', \JText::_('JLIB_INSTALLER_' . $this->route), $db->stderr(true)));
				return false;
			}
		}
		else
		{
			$row->set('name', $this->get('name'));
			$row->set('type', 'module');
			$row->set('element', $this->get('element'));
			$row->set('folder', ''); 
			$row->set('enabled', 1);
			$row->set('protected', 0);
			$row->set('access', $clientId == 1 ? 2 : 0);
			$row->set('client_id', $clientId);
			$row->set('params', $this->getParams());
			$row->set('custom_data', '');
			$row->set('manifest_cache', $this->generateManifestCache());

			if(!$row->store()) {
				$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_MOD_ROLLBACK', \JText::_('JLIB_INSTALLER_' . $this->route), $db->stderr(true)));
				return false;
			}

			$row->extension_id = $db->insertid();

			$this->pushStep(array('type' => 'extension', 'extension_id' => $row->extension_id));

			$name = preg_replace('#[\*?]#', '', \JText::_($this->get('name')));
			$module = \JTable::getInstance('module');
			$module->set('title', $name);
			$module->set('module', $this->get('element'));
			$module->set('access', '1');
			$module->set('showtitle', '1');
			$module->set('client_id', $clientId);
			$module->set('language', '*');

			$module->store();
		}  
		
		$this->row = $row;
		
		return true;
  }   
  
  public function _taskParseSQLStuff()
  {   
    $row = $this->row;
    
    if(strtolower($this->route) == 'install')
		{
			$utfresult = $this->parseSQLFiles($this->manifest->install->sql);

			if($utfresult === false)
			{
				$this->abort(
					\JText::sprintf('JLIB_INSTALLER_ABORT_MOD_INSTALL_SQL_ERROR', \JText::_('JLIB_INSTALLER_' . $this->route), $db->stderr(true))
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
					$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_MOD_UPDATE_SQL_ERROR', $db->stderr(true)));
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
				$this->abort(\JText::_('JLIB_INSTALLER_ABORT_MOD_INSTALL_CUSTOM_INSTALL_FAILURE'));
				return false;
			}
		}

		$this->msg .= ob_get_contents();
		ob_end_clean();
		
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
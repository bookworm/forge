<?php 

namespace forge\excavate\cores;

use forge\excavate\cores;

class Plugin extends \forge\excavate\Excavator  
{
  public function loadLanguage($path = null)
	{
		$source = $this->getPath('source');
		if(!$source)
			$this->setPath('source', JPATH_PLUGINS . '/' . $this->extension->folder . '/' . $this->extension->element);
		
		$this->manifest = $this->getManifest(); 
		
		$element = $this->manifest->files;                
		if($element)
		{
			$group = strtolower((string) $this->manifest->attributes()->group);
			$name  = '';         
			
			if(count($element->children()))
			{
				foreach($element->children() as $file)
				{
					if((string) $file->attributes()->plugin) {
						$name = strtolower((string) $file->attributes()->plugin);
						break;
					}
				}
			}    
			
			if($name)
			{
				$extension = "plg_${group}_${name}";
				$lang      = \JFactory::getLanguage();
				$source    = $path ? $path : JPATH_PLUGINS . "/$group/$name";
				$folder    = (string) $element->attributes()->folder;    
				
				if($folder && file_exists("$path/$folder"))
					$source = "$path/$folder";

				$lang->load($extension . '.sys', $source, null, false, false)
					|| $lang->load($extension . '.sys', JPATH_ADMINISTRATOR, null, false, false)
					|| $lang->load($extension . '.sys', $source, $lang->getDefault(), false, false)
					|| $lang->load($extension . '.sys', JPATH_ADMINISTRATOR, $lang->getDefault(), false, false);
			}
		}
	}   

	public function discover()
	{
		$results = array();
		$folder_list = \JFolder::folders(JPATH_SITE . '/plugins');

		foreach($folder_list as $folder)
		{
			$file_list = \JFolder::files(JPATH_SITE . '/plugins/' . $folder, '\.xml$');   
			
			foreach($file_list as $file)
			{
				$manifest_details = \JApplicationHelper::parseXMLInstallFile(JPATH_SITE . '/plugins/' . $folder . '/' . $file);
				$file = \JFile::stripExt($file);

				if($file == 'example')
					continue;

				$extension = \JTable::getInstance('extension');
				$extension->set('type', 'plugin');
				$extension->set('client_id', 0);
				$extension->set('element', $file);
				$extension->set('folder', $folder);
				$extension->set('name', $file);
				$extension->set('state', -1);
				$extension->set('manifest_cache', json_encode($manifest_details));
				$results[] = $extension;
			}            
			
			$folder_list = \JFolder::folders(JPATH_SITE . '/plugins/' . $folder);
			foreach($folder_list as $plugin_folder)
			{
				$file_list = \JFolder::files(JPATH_SITE . '/plugins/' . $folder . '/' . $plugin_folder, '\.xml$');
				foreach($file_list as $file)
				{
					$manifest_details = \JApplicationHelper::parseXMLInstallFile(
						JPATH_SITE . '/plugins/' . $folder . '/' . $plugin_folder . '/' . $file
					);
					$file = \JFile::stripExt($file);

					if($file == 'example')
						continue;

					$extension = \JTable::getInstance('extension');
					$extension->set('type', 'plugin');
					$extension->set('client_id', 0);
					$extension->set('element', $file);
					$extension->set('folder', $folder);
					$extension->set('name', $file);
					$extension->set('state', -1);
					$extension->set('manifest_cache', json_encode($manifest_details));
					$results[] = $extension;
				}
			}
		}   
		
		return $results;
	}   
	
	public function discover_install()
	{
		$client = \JApplicationHelper::getClientInfo($this->extension->client_id);
		if(is_dir($client->path . '/plugins/' . $this->extension->folder . '/' . $this->extension->element))
			$manifestPath = $client->path . '/plugins/' . $this->extension->folder . '/' . $this->extension->element . '/'. $this->extension->element . '.xml';
		else
			$manifestPath = $client->path . '/plugins/' . $this->extension->folder . '/' . $this->extension->element . '.xml';

		$this->manifest = $this->isManifest($manifestPath);
		$description = (string) $this->manifest->description;
		if($description)
			$this->set('message', \JText::_($description));
		else
			$this->set('message', '');

		$this->setPath('manifest', $manifestPath);
		$manifest_details = \JApplicationHelper::parseXMLInstallFile($manifestPath);
		$this->extension->manifest_cache = json_encode($manifest_details);
		$this->extension->state = 0;
		$this->extension->name = $manifest_details['name'];
		$this->extension->enabled = ('editors' == $this->extension->folder) ? 1 : 0;
		$this->extension->params = $this->getParams();      
		
		if($this->extension->store())
			return $this->extension->get('extension_id');
		else {
			\JError::raiseWarning(101, \JText::_('JLIB_INSTALLER_ERROR_PLG_DISCOVER_STORE_DETAILS'));
			return false;
		}
	}   
	
	public function refreshManifestCache()
 	{
 		$client = \JApplicationHelper::getClientInfo($this->extension->client_id);
 		$manifestPath = $client->path . '/plugins/' . $this->extension->folder . '/' . $this->extension->element . '/'
 			. $this->extension->element . '.xml';        
 			
 		$this->manifest = $this->isManifest($manifestPath);
 		$this->setPath('manifest', $manifestPath);
 		$manifest_details = \JApplicationHelper::parseXMLInstallFile($this->getPath('manifest'));
 		$this->extension->manifest_cache = json_encode($manifest_details);

 		$this->extension->name = $manifest_details['name'];
 		if($this->extension->store())
 			return true;
 		else {
 			\JError::raiseWarning(101, \JText::_('JLIB_INSTALLER_ERROR_PLG_REFRESH_MANIFEST_CACHE'));
 			return false;
 		}
 	}  
 	
 	public function _taskSetThings()
  {
    $this->db = $this->getDbo();
		$this->manifest = $this->getManifest();
		$xml            = $this->manifest;

		$name = (string) $xml->name;
		$name = \JFilterInput::getInstance()->clean($name, 'string');
		$this->set('name', $name);

		$description = (string) $xml->description;
		if($description)
			$this->set('message', \JText::_($description));
		else
			$this->set('message', '');
		
		$this->xml = $xml;	
		return true;
  } 
  
  public function _taskSetType()
  {        
    $xml  = $this->xml;
    $type = (string) $xml->attributes()->type;

		if(count($xml->files->children()))
		{
			foreach($xml->files->children() as $file)
			{
				if((string) $file->attributes()->$type) {
					$element = (string) $file->attributes()->$type;
					break;
				}
			}
		}  
		
		$this->element = $element;
		
		return true;
  }  
  
  public function _taskSetGroup()
  {        
    $xml     = $this->xml;    
    $element = $this->element;
    
    $group = (string) $xml->attributes()->group;
		if(!empty($element) && !empty($group))
			$this->setPath('extension_root', JPATH_PLUGINS . '/' . $group . '/' . $element);
		else {
			$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_PLG_INSTALL_NO_FILE', \JText::_('JLIB_INSTALLER_' . $this->route)));
			return false;
		}     
		
		$this->group = $group;
		
		return true;
  }   
  
  public function _taskSetDBID()
  {       
    $element = $this->element;  
    $group   = $this->group;
    $db      = $this->db;
    $query   = $db->getQuery(true);  
    
		$query->select($query->qn('extension_id'))->from($query->qn('#__extensions'));
		$query->where($query->qn('folder') . ' = ' . $query->q($group));
		$query->where($query->qn('element') . ' = ' . $query->q($element));
		$db->setQuery($query);
		try {
			$db->Query();
		}
		catch(JException $e) {
			$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_PLG_INSTALL_ROLLBACK', \JText::_('JLIB_INSTALLER_' . $this->route), $db->stderr(true)));
			return false;
		}
		$this->eid = $db->loadResult();
		
		return true;
  }
  
  public function _taskCheckExistingFolders()
  {   
    $xml = $this->xml;    
    
    if(file_exists($this->getPath('extension_root')) && (!$this->getOverwrite() || $this->getUpgrade()))
		{
			$updateElement = $xml->update;
			if ($this->getUpgrade() || ($this->manifestClass && method_exists($this->manifestClass, 'update'))
				|| $updateElement)
			{
				$this->setOverwrite(true);
				$this->setUpgrade(true);
				if($this->eid)
					$this->route = 'update';
			}
			elseif(!$this->getOverwrite())
			{
				$this->abort(
					\JText::sprintf(
						'JLIB_INSTALLER_ABORT_PLG_INSTALL_DIRECTORY', \JText::_('JLIB_INSTALLER_' . $this->route),
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
    $xml     = $this->xml;    
    $group   = $this->group;
    $element = $this->element;  
    
    if((string) $xml->scriptfile)
		{
			$manifestScript = (string) $xml->scriptfile;
			$manifestScriptFile = $this->getPath('source') . '/' . $manifestScript;
			if(is_file($manifestScriptFile))
				include_once $manifestScriptFile;

			$groupClass = str_replace('-', '', $group);
			$classname = 'plg' . $groupClass . $element . 'InstallerScript';
			if(class_exists($classname)) {
				$this->manifestClass = new $classname($this);
				$this->set('manifest_script', $manifestScript);
			}
		}
		
		return true;
  }   
  
  public function _taskRunManifestClassPreflight()
  {
    ob_start();
		ob_implicit_flush(false);
		if($this->manifestClass && method_exists($this->manifestClass, 'preflight'))
		{
			if($this->manifestClass->preflight($this->route, $this) === false) {
				$this->abort(\JText::_('JLIB_INSTALLER_ABORT_PLG_INSTALL_CUSTOM_INSTALL_FAILURE'));
				return false;
			}
		}
		$this->msg = ob_get_contents();
		ob_end_clean();  
		
		return true;
  }
  
  public function _taskCreateFolderExtRoot()
  {
    $created = false;
 		if(!file_exists($this->getPath('extension_root')))
 		{
 			if(!$created = \JFolder::create($this->getPath('extension_root')))
 			{
 				$this->abort(
 					\JText::sprintf(
 						'JLIB_INSTALLER_ABORT_PLG_INSTALL_CREATE_DIRECTORY', \JText::_('JLIB_INSTALLER_' . $this->route),
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

  public function _taskCopyManifestScript()
  {
    if($this->get('manifest_script'))
 		{
 			$path['src']  = $this->getPath('source') . '/' . $this->get('manifest_script');
 			$path['dest'] = $this->getPath('extension_root') . '/' . $this->get('manifest_script');

 			if(!file_exists($path['dest']))
 			{
 				if(!$this->copyFiles(array($path))) {
 					$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_PLG_INSTALL_MANIFEST', \JText::_('JLIB_INSTALLER_' . $this->route)));
 					return false;
 				}
 			}
 		}

 		return true;
  }  

  public function _taskInsertRowDB()
  {
    $row     = \JTable::getInstance('extension');  
    $id      = $this->eid;    
    $element = $this->element;
    $group   = $this->group;
    
 		if($id)
 		{
 			if(!$this->getOverwrite())
 			{
 				$this->abort(
 					\JText::sprintf(
 						'JLIB_INSTALLER_ABORT_PLG_INSTALL_ALLREADY_EXISTS', \JText::_('JLIB_INSTALLER_' . $this->route),
 						$this->get('name')
 					)
 				);  

 				return false;
 			}      

 			$row->load($id);
 			$row->name = $this->get('name');
 			$row->manifest_cache = $this->generateManifestCache();
 			$row->store();
 		}
 		else
 		{                      
 			$row->name           = $this->get('name');
 			$row->type           = 'plugin';
 			$row->ordering       = 0;
 			$row->element        = $element;
 			$row->folder         = $group;
 			$row->enabled        = 0;
 			$row->protected      = 0;
 			$row->access         = 1;
 			$row->client_id      = 0;
 			$row->params         = $this->getParams();
 			$row->custom_data    = '';
 			$row->system_data    = '';
 			$row->manifest_cache = $this->generateManifestCache();

 			if($group == 'editors')
 				$row->enabled = 1;

 			if(!$row->store())
 			{
 				$this->abort(
 					\JText::sprintf('JLIB_INSTALLER_ABORT_PLG_INSTALL_ROLLBACK', \JText::_('JLIB_INSTALLER_' . $this->route), $db->stderr(true))
 				);

 				return false;
 			}

 			$this->pushStep(array('type' => 'extension', 'id' => $row->extension_id));
 			$id = $row->extension_id;
 		} 
 		
 		$this->row = $row;
 		$this->eid = $id;

 		return true;
  }

  public function _taskParseSQLStuff()
  {        
    $row = $this->row;
    $db  = $this->db;
    
    if(strtolower($this->route) == 'install')
 		{
 			$utfresult = $this->parseSQLFiles($this->manifest->install->sql);
 			if($utfresult === false)
 			{
 				$this->abort(
 					\JText::sprintf('JLIB_INSTALLER_ABORT_PLG_INSTALL_SQL_ERROR', \JText::_('JLIB_INSTALLER_' . $this->route), $db->stderr(true))
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
 					$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_PLG_UPDATE_SQL_ERROR', $db->stderr(true)));  
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
 				$this->abort(\JText::_('JLIB_INSTALLER_ABORT_PLG_INSTALL_CUSTOM_INSTALL_FAILURE'));     
 				return false;
 			}
 		}

 		$this->msg .= ob_get_contents();
 		ob_end_clean();

 		return true;
  }       

  public function _taskCopyManifest()
  {
    if(!$this->copyManifest(-1)) {
 			$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_PLG_INSTALL_COPY_SETUP', \JText::_('JLIB_INSTALLER_' . $this->route))); 
 			return false;
 		}

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
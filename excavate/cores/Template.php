<?php 

namespace forge\excavate\cores;

use forge\excavate\cores;

class Template extends \forge\excavate\Excavator 
{
  public function loadLanguage($path = null)
	{
		$source = $this->getPath('source');

		if(!$source)
		{
			$this->setPath('source',
				($this->extension->client_id ? JPATH_ADMINISTRATOR : JPATH_SITE) . '/templates/' . $this->extension->element
			);
		}

		$clientId = isset($this->extension) ? $this->extension->client_id : 0;
		$this->manifest = $this->getManifest();
		$name   = strtolower(\JFilterInput::getInstance()->clean((string) $this->manifest->name, 'cmd'));
		$client = (string) $this->manifest->attributes()->client;

		if(!$client)
			$client = 'ADMINISTRATOR';

		$extension = "tpl_$name";
		$lang   = \JFactory::getLanguage();
		$source = $path ? $path : ($this->extension->client_id ? JPATH_ADMINISTRATOR : JPATH_SITE) . '/templates/' . $name;
		$lang->load($extension . '.sys', $source, null, false, false)
			|| $lang->load($extension . '.sys', constant('JPATH_' . strtoupper($client)), null, false, false)
			|| $lang->load($extension . '.sys', $source, $lang->getDefault(), false, false)
			|| $lang->load($extension . '.sys', constant('JPATH_' . strtoupper($client)), $lang->getDefault(), false, false);
	}  
	
	public function discover()
	{
		$results    = array();
		$site_list  = \JFolder::folders(JPATH_SITE . '/templates');
		$admin_list = \JFolder::folders(JPATH_ADMINISTRATOR . '/templates');
		$site_info  = \JApplicationHelper::getClientInfo('site', true);
		$admin_info = \JApplicationHelper::getClientInfo('administrator', true);

		foreach($site_list as $template)
		{
			if($template == 'system')
				continue;

			$manifest_details = \JApplicationHelper::parseXMLInstallFile(JPATH_SITE . "/templates/$template/templateDetails.xml");
			$extension = \JTable::getInstance('extension');
			$extension->set('type', 'template');
			$extension->set('client_id', $site_info->id);
			$extension->set('element', $template);
			$extension->set('name', $template);
			$extension->set('state', -1);
			$extension->set('manifest_cache', json_encode($manifest_details));
			$results[] = $extension;
		}

		foreach($admin_list as $template)
		{
			if($template == 'system')
				continue;

			$manifest_details = \JApplicationHelper::parseXMLInstallFile(JPATH_ADMINISTRATOR . "/templates/$template/templateDetails.xml");
			$extension = \JTable::getInstance('extension');
			$extension->set('type', 'template');
			$extension->set('client_id', $admin_info->id);
			$extension->set('element', $template);
			$extension->set('name', $template);
			$extension->set('state', -1);
			$extension->set('manifest_cache', json_encode($manifest_details));
			$results[] = $extension;
		}

		return $results;
	}  
	
	public function discover_install()
 	{
 		$client         = \JApplicationHelper::getClientInfo($this->extension->client_id);
 		$manifestPath   = $client->path . '/templates/' . $this->extension->element . '/templateDetails.xml';
 		$this->manifest = $this->isManifest($manifestPath);
 		$description    = (string) $this->manifest->description;

 		if($description)
 			$this->set('message', \JText::_($description));
 		else
 			$this->set('message', '');

 		$this->setPath('manifest', $manifestPath);
 		$manifest_details = \JApplicationHelper::parseXMLInstallFile($this->getPath('manifest'));
 		$this->extension->manifest_cache = json_encode($manifest_details);
 		$this->extension->state = 0;
 		$this->extension->name = $manifest_details['name'];
 		$this->extension->enabled = 1;

 		$data = new \JObject;

 		foreach($manifest_details as $key => $value) {
 			$data->set($key, $value);
 		}

 		$this->extension->params = $this->getParams();

 		if($this->extension->store())
 		{
 			$db = $this->getDbo();
 			$query = $db->getQuery(true);
 			$query->insert($db->quoteName('#__template_styles'));
 			$debug   = $lang->setDebug(false);
 			$columns = array($db->quoteName('template'),
 				$db->quoteName('client_id'),
 				$db->quoteName('home'),
 				$db->quoteName('title'),
 				$db->quoteName('params')
 			);
 			$query->columns($columns);
 			$query->values(
 				$db->Quote($this->extension->element)
 				. ',' . $db->Quote($this->extension->client_id)
 				. ',' . $db->Quote(0)
 				. ',' . $db->Quote(\JText::sprintf('JLIB_INSTALLER_DEFAULT_STYLE', $this->extension->name))
 				. ',' . $db->Quote($this->extension->params)
 			);
 			$lang->setDebug($debug);
 			$db->setQuery($query);
 			$db->query();

 			return $this->extension->get('extension_id');
 		}
 		else {
 			\JError::raiseWarning(101, \JText::_('JLIB_INSTALLER_ERROR_TPL_DISCOVER_STORE_DETAILS'));
 			return false;
 		}
 	}    
 	
 	public function refreshManifestCache()
 	{
 		$client = \JApplicationHelper::getClientInfo($this->extension->client_id);
 		$manifestPath = $client->path . '/templates/' . $this->extension->element . '/templateDetails.xml';
 		$this->manifest = $this->isManifest($manifestPath);
 		$this->setPath('manifest', $manifestPath);

 		$manifest_details = \JApplicationHelper::parseXMLInstallFile($this->getPath('manifest'));
 		$this->extension->manifest_cache = json_encode($manifest_details);
 		$this->extension->name = $manifest_details['name'];

 		try {
 			return $this->extension->store();
 		}
 		catch(JException $e) {
 			\JError::raiseWarning(101, \JText::_('JLIB_INSTALLER_ERROR_TPL_REFRESH_MANIFEST_CACHE'));
 			return false;
 		}
 	} 
 	
 	public function _taskSetStuff()
  {
    $db   = $this->getDbo();
		$lang = \JFactory::getLanguage();
		$xml  = $this->getManifest();

		if($cname = (string) $xml->attributes()->client)
		{
			$client = \JApplicationHelper::getClientInfo($cname, true);
			if($client === false) {
				$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_TPL_INSTALL_UNKNOWN_CLIENT', $cname));
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

		$name = \JFilterInput::getInstance()->clean((string) $xml->name, 'cmd');

		$element = strtolower(str_replace(" ", "_", $name));
		$this->set('name', $name);
		$this->set('element', $element);
		$this->db       = $db;
		$this->lang     = $lang;
		$this->xml      = $lang;   
		$this->basePath = $basePath;
		$this->clientId = $clientId;

		return true;
  }
  
  public function _taskSetQuery()
  {      
    $db      = $this->db;
    $query   = $db->getQuery(true); 
    $element = $this->element; 
    
		$query->select($query->qn('extension_id'))->from($query->qn('#__extensions'));
		$query->where($query->qn('type') . ' = ' . $query->q('template'));
		$query->where($query->qn('element') . ' = ' . $query->q($element));
		$db->setQuery($query);

		try {
			$this->eid = $db->loadResult();
		}
		catch(JDatabaseException $e) {
			$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_TPL_INSTALL_ROLLBACK'), $e->getMessage());
			return false;
		}

		if(\JError::$legacy && $db->getErrorNum()) {
			$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_TPL_INSTALL_ROLLBACK', $db->stderr(true)));
			return false;
		}
		
		return true;
  }    
  
  public function _taskExtensionRoot()
  {
    $element = $this->element; 
    $xml     = $this->xml; 
    
    $this->setPath('extension_root', $basePath . '/templates/' . $element);

		if(file_exists($this->getPath('extension_root')) && (!$this->getOverwrite() || $this->getUpgrade()))
		{
			$updateElement = $xml->update;
	
			if($this->getUpgrade() || ($this->manifestClass && method_exists($this->manifestClass, 'update'))
				|| $updateElement)
			{
				$this->setOverwrite(true);
				$this->setUpgrade(true);
				if($this->eid)
					$this->route = 'update';
			}     
			elseif (!$this->getOverwrite())
			{
				$this->abort(
					\JText::sprintf(
						'JLIB_INSTALLER_ABORT_TPL_INSTALL_ANOTHER_TEMPLATE_USING_DIRECTORY', \JText::_('JLIB_INSTALLER_' . $this->route),
						$this->getPath('extension_root')
					)
				);    
				
				return false;
			}
		}

		if(file_exists($this->getPath('extension_root')) && !$this->getOverwrite())
		{
			\JError::raiseWarning(
				100,
				\JText::sprintf('JLIB_INSTALLER_ABORT_TPL_INSTALL_ANOTHER_TEMPLATE_USING_DIRECTORY', $this->getPath('extension_root'))
			);    
			
			return false;
		}

		$created = false;
		if(!file_exists($this->getPath('extension_root')))
		{
			if(!$created = \JFolder::create($this->getPath('extension_root'))) {
				$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_TPL_INSTALL_FAILED_CREATE_DIRECTORY', $this->getPath('extension_root')));
				return false;
			}
		}

		if($created)
			$this->pushStep(array('type' => 'folder', 'path' => $this->getPath('extension_root')));
		
		return true;
  }  
     
  public function _taskRowStore()
  {
    $row = \JTable::getInstance('extension'); 
    $id  = $this->eid;

		if($this->route == 'update' && $id)
			$row->load($id);
		else
		{
			$row->type        = 'template';
			$row->element     = $this->get('element');
			$row->folder      = '';
			$row->enabled     = 1;
			$row->protected   = 0;
			$row->access      = 1;
			$row->client_id   = $clientId;
			$row->params      = $this->getParams();
			$row->custom_data = ''; 
		}
		$row->name = $this->get('name'); // name might change in an update
		$row->manifest_cache = $this->generateManifestCache();

		if(!$row->store()) {
			$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_TPL_INSTALL_ROLLBACK', $db->stderr(true)));
			return false;
		}

		if($this->route == 'install')
		{
			$query = $db->getQuery(true);
			$query->insert($db->quoteName('#__template_styles'));
			$debug = $lang->setDebug(false);
			$columns = array($db->quoteName('template'),
				$db->quoteName('client_id'),
				$db->quoteName('home'),
				$db->quoteName('title'),
				$db->quoteName('params')
			);
			$query->columns($columns);
			$query->values(
				$db->Quote($row->element)
				. ',' . $db->Quote($clientId)
				. ',' . $db->Quote(0)
				. ',' . $db->Quote(\JText::sprintf('JLIB_INSTALLER_DEFAULT_STYLE', \JText::_($this->get('name'))))
				. ',' . $db->Quote($row->params)
			);
			$lang->setDebug($debug);
			$db->setQuery($query);
			$db->query();
		}
		
		$this->row = $row;
		
		return true;
  }
}
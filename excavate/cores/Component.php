<?php 

namespace forge\excavate\cores;

use forge\excavate\cores;    

class Component extends \forge\excavate\Excavator
{     
  public function loadLanguage($path = null)
 	{
 		$source = $this->getPath('source');

 		if(!$source)
 		{
 			$this->setPath('source',
 				($this->extension->client_id ? JPATH_ADMINISTRATOR : JPATH_SITE) .
 				'/components/' . $this->extension->element
 			);
 		}

 		$this->manifest = $this->getManifest();
 		$name = strtolower(\JFilterInput::getInstance()->clean((string) $this->manifest->name, 'cmd'));

 		if(substr($name, 0, 4) == "com_")
 			$extension = $name;
 		else
 			$extension = "com_$name";

 		$lang   = \JFactory::getLanguage();
 		$source = $path ? $path : ($this->extension->client_id ? JPATH_ADMINISTRATOR : JPATH_SITE) . '/components/' . $extension;

 		if($this->manifest->administration->files)
 			$element = $this->manifest->administration->files;
 		elseif($this->manifest->files)
 			$element = $this->manifest->files;
 		else
 			$element = null;

 		if($element)
 		{
 			$folder = (string) $element->attributes()->folder;

 			if($folder && file_exists("$path/$folder"))
 				$source = "$path/$folder";
 		}  
 		
 		$lang->load($extension . '.sys', $source, null, false, false) || $lang->load($extension . '.sys', JPATH_ADMINISTRATOR, null, false, false)
 			|| $lang->load($extension . '.sys', $source, $lang->getDefault(), false, false)
 			|| $lang->load($extension . '.sys', JPATH_ADMINISTRATOR, $lang->getDefault(), false, false);
 	}  
 	
 	public function _taskSetPaths()
 	{
 	  $db             = $this->getDbo();
		$this->manifest = $this->getManifest();

		$name = strtolower(\JFilterInput::getInstance()->clean((string) $this->manifest->name, 'cmd'));
		if(substr($name, 0, 4) == "com_")
			$element = $name;
		else
			$element = "com_$name";

		$this->set('name', $name);
		$this->set('element', $element);

		$this->set('message', \JText::_((string) $this->manifest->description));

		$this->setPath('extension_site', \JPath::clean(JPATH_SITE . '/components/' . $this->get('element')));
		$this->setPath('extension_administrator', \JPath::clean(JPATH_ADMINISTRATOR . '/components/' . $this->get('element')));

		$this->setPath('extension_root', $this->getPath('extension_administrator'));

		if(!$this->manifest->administration) {
			\JError::raiseWarning(1, \JText::_('JLIB_INSTALLER_ERROR_COMP_INSTALL_ADMIN_ELEMENT'));
			return false;
		}
 	}
 	
 	protected function _buildAdminMenus()
 	{
 		$db     = $this->getDbo();
 		$table  = \JTable::getInstance('menu');
 		$option = $this->get('element');

 		$query = $db->getQuery(true);
 		$query->select('m.id, e.extension_id');
 		$query->from('#__menu AS m');
 		$query->leftJoin('#__extensions AS e ON m.component_id = e.extension_id');
 		$query->where('m.parent_id = 1');
 		$query->where("m.client_id = 1");
 		$query->where('e.element = ' . $db->quote($option));

 		$db->setQuery($query);

 		$componentrow = $db->loadObject();

 		if($componentrow)
 		{
 			if(!$this->getOverwrite())
 				return true;

 			if($option)
 				$this->_removeAdminMenus($componentrow); // If something goes wrong, theres no way to rollback TODO: Search for better solution

 			$component_id = $componentrow->extension_id;
 		}
 		else
 		{
 			$query->clear();
 			$query->select('e.extension_id');
 			$query->from('#__extensions AS e');
 			$query->where('e.element = ' . $db->quote($option));

 			$db->setQuery($query);

 			$component_id = $db->loadResult(); // TODO Find Some better way to discover the component_id
 		}

 		$menuElement = $this->manifest->administration->menu;

 		if($menuElement)
 		{
 			$data = array();
 			$data['menutype']     = 'main';
 			$data['client_id']    = 1;
 			$data['title']        = (string) $menuElement;
 			$data['alias']        = (string) $menuElement;
 			$data['link']         = 'index.php?option=' . $option;
 			$data['type']         = 'component';
 			$data['published']    = 0;
 			$data['parent_id']    = 1;
 			$data['component_id'] = $component_id;
 			$data['img']          = ((string) $menuElement->attributes()->img) ? (string) $menuElement->attributes()->img : 'class:component';
 			$data['home']         = 0;

 			if(!$table->setLocation(1, 'last-child') || !$table->bind($data) || !$table->check() || !$table->store()) {
 				\JError::raiseWarning(1, $table->getError());
 				return false;
 			}

 			$this->pushStep(array('type' => 'menu', 'id' => $component_id));
 		}
 		else
 		{
 			$data = array();
 			$data['menutype']     = 'main';
 			$data['client_id']    = 1;
 			$data['title']        = $option;
 			$data['alias']        = $option;
 			$data['link']         = 'index.php?option=' . $option;
 			$data['type']         = 'component';
 			$data['published']    = 0;
 			$data['parent_id']    = 1;
 			$data['component_id'] = $component_id;
 			$data['img']          = 'class:component';
 			$data['home']         = 0;

 			if(!$table->setLocation(1, 'last-child') || !$table->bind($data) || !$table->check() || !$table->store()) {
 				\JError::raiseWarning(1, $table->getError());
 				return false;
 			}

 			$this->pushStep(array('type' => 'menu', 'id' => $component_id));
 		}

 		$parent_id = $table->id;

 		if(!$this->manifest->administration->submenu)
 			return true;

 		$parent_id = $table->id;

 		foreach($this->manifest->administration->submenu->menu as $child)
 		{
 			$data = array();
 			$data['menutype']     = 'main';
 			$data['client_id']    = 1;
 			$data['title']        = (string) $child;
 			$data['alias']        = (string) $child;
 			$data['type']         = 'component';
 			$data['published']    = 0;
 			$data['parent_id']    = $parent_id;
 			$data['component_id'] = $component_id;
 			$data['img']          = ((string) $child->attributes()->img) ? (string) $child->attributes()->img : 'class:component';
 			$data['home']         = 0;

 			if((string) $child->attributes()->link)
 				$data['link'] = 'index.php?' . $child->attributes()->link;
 			else
 			{
 				$request = array();

 				if((string) $child->attributes()->act)
 					$request[] = 'act=' . $child->attributes()->act;

 				if((string) $child->attributes()->task)
 					$request[] = 'task=' . $child->attributes()->task;

 				if((string) $child->attributes()->controller)
 					$request[] = 'controller=' . $child->attributes()->controller;

 				if((string) $child->attributes()->view)
 					$request[] = 'view=' . $child->attributes()->view;

 				if((string) $child->attributes()->layout)
 					$request[] = 'layout=' . $child->attributes()->layout;

 				if((string) $child->attributes()->sub)
 					$request[] = 'sub=' . $child->attributes()->sub;

 				$qstring      = (count($request)) ? '&' . implode('&', $request) : '';
 				$data['link'] = 'index.php?option=' . $option . $qstring;
 			}

 			$table = \JTable::getInstance('menu');

 			if(!$table->setLocation($parent_id, 'last-child') || !$table->bind($data) || !$table->check() || !$table->store())
 				return false;

 			$this->pushStep(array('type' => 'menu', 'id' => $component_id));
 		}

 		return true;
 	}
 	
 	protected function _removeAdminMenus(&$row)
	{
		$db    = $this->getDbo();
		$table = \JTable::getInstance('menu');
		$id    = $row->extension_id;

		$query = $db->getQuery(true);
		$query->select('id');
		$query->from('#__menu');
		$query->where($query->qn('client_id') . ' = 1');
		$query->where($query->qn('component_id') . ' = ' . (int) $id);

		$db->setQuery($query);

		$ids = $db->loadColumn();

		if($error = $db->getErrorMsg())
		{
			\JError::raiseWarning('', \JText::_('JLIB_INSTALLER_ERROR_COMP_REMOVING_ADMIN_MENUS_FAILED'));

			if($error && $error != 1)
				\JError::raiseWarning(100, $error);

			return false;
		}
		elseif(!empty($ids))
		{
			foreach($ids as $menuid)
			{
				if(!$table->delete((int) $menuid)) {
					$this->setError($table->getError());
					return false;
				}
			}

			$table->rebuild();
		}     
		
		return true;
	} 
	
	protected function _rollback_menu($step)
	{
		return $this->_removeAdminMenus((object) array('extension_id' => $step['id']));
	}
	
	public function discover()
 	{
 		$results          = array();
 		$site_components  = \JFolder::folders(JPATH_SITE . '/components');
 		$admin_components = \JFolder::folders(JPATH_ADMINISTRATOR . '/components');

 		foreach($site_components as $component)
 		{
 			if(file_exists(JPATH_SITE . '/components/' . $component . '/' . str_replace('com_', '', $component) . '.xml'))
 			{
 				$manifest_details = \JApplicationHelper::parseXMLInstallFile(
 					JPATH_SITE . '/components/' . $component . '/' . str_replace('com_', '', $component) . '.xml'
 				);    
 				
 				$extension = \JTable::getInstance('extension');
 				$extension->set('type', 'component');
 				$extension->set('client_id', 0);
 				$extension->set('element', $component);
 				$extension->set('name', $component);
 				$extension->set('state', -1);
 				$extension->set('manifest_cache', json_encode($manifest_details));
 				$results[] = $extension;
 			}
 		}

 		foreach($admin_components as $component)
 		{
 			if(file_exists(JPATH_ADMINISTRATOR . '/components/' . $component . '/' . str_replace('com_', '', $component) . '.xml'))
 			{
 				$manifest_details = \JApplicationHelper::parseXMLInstallFile(
 					JPATH_ADMINISTRATOR . '/components/' . $component . '/' . str_replace('com_', '', $component) . '.xml'
 				);       
 				
 				$extension = \JTable::getInstance('extension');
 				$extension->set('type', 'component');
 				$extension->set('client_id', 1);
 				$extension->set('element', $component);
 				$extension->set('name', $component);
 				$extension->set('state', -1);
 				$extension->set('manifest_cache', json_encode($manifest_details));
 				$results[] = $extension;
 			}
 		}   
 		
 		return $results;
 	}  
 	
 	public function discover_install()
	{
		$client        = \JApplicationHelper::getClientInfo($this->extension->client_id);
		$short_element = str_replace('com_', '', $this->extension->element);
		$manifestPath  = $client->path . '/components/' . $this->extension->element . '/' . $short_element . '.xml'; 
		
		$this->manifest = $this->isManifest($manifestPath);
		$this->setPath('manifest', $manifestPath);
		$this->setPath('source', $client->path . '/components/' . $this->extension->element);
		$this->setPath('extension_root', $this->getPath('source'));

		$manifest_details = \JApplicationHelper::parseXMLInstallFile($this->getPath('manifest'));
		$this->extension->manifest_cache = json_encode($manifest_details);
		$this->extension->state   = 0;
		$this->extension->name    = $manifest_details['name'];
		$this->extension->enabled = 1;
		$this->extension->params  = $this->getParams();

		try {
			$this->extension->store();
		}
		catch(JException $e) {
			\JError::raiseWarning(101, \JText::_('JLIB_INSTALLER_ERROR_COMP_DISCOVER_STORE_DETAILS'));
			return false;
		}

		$db = $this->getDbo();
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
			$this->set('message', \JText::_((string) $description));
		else
			$this->set('message', '');

		$this->setPath('extension_site', \JPath::clean(JPATH_SITE . '/components/' . $this->get('element')));
		$this->setPath('extension_administrator', \JPath::clean(JPATH_ADMINISTRATOR . '/components/' . $this->get('element')));
		$this->setPath('extension_root', $this->getPath('extension_administrator')); // copy this as its used as a common base

		if(!$this->manifest->administration) {
			\JError::raiseWarning(1, \JText::_('JLIB_INSTALLER_ERROR_COMP_INSTALL_ADMIN_ELEMENT'));
			return false;
		}

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

		ob_start();
		ob_implicit_flush(false);

		if($this->manifestClass && method_exists($this->manifestClass, 'preflight'))
		{
			if($this->manifestClass->preflight('discover_install', $this) === false) {
				$this->abort(\JText::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_CUSTOM_INSTALL_FAILURE'));
				return false;
			}
		}

		$this->msg = ob_get_contents(); 
		ob_end_clean();

		if(isset($this->manifest->install->sql))
		{
			$utfresult = $this->parseSQLFiles($this->manifest->install->sql);

			if($utfresult === false) {
				$this->abort(\\JText::sprintf('JLIB_INSTALLER_ABORT_COMP_INSTALL_SQL_ERROR', $db->stderr(true)));
				return false;
			}
		}

		if(!$this->_buildAdminMenus($this->extension->extension_id))
			\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ABORT_COMP_BUILDADMINMENUS_FAILED'));

		if($this->get('install_script'))
		{

			if(is_file($this->getPath('extension_administrator') . '/' . $this->get('install_script')))
			{
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

		ob_start();
		ob_implicit_flush(false);

		if($this->manifestClass && method_exists($this->manifestClass, 'discover_install'))
		{
			if($this->manifestClass->install($this) === false) {
				$this->abort(\JText::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_CUSTOM_INSTALL_FAILURE'));
				return false;
			}
		}

		$this->msg .= ob_get_contents(); // append messages
		ob_end_clean();

		$update = \JTable::getInstance('update');
		$uid    = $update->find(array('element' => $this->get('element'), 'type' => 'component', 'client_id' => '', 'folder' => ''));

		if($uid)
			$update->delete($uid);

		ob_start();
		ob_implicit_flush(false);

		if($this->manifestClass && method_exists($this->manifestClass, 'postflight'))
			$this->manifestClass->postflight('discover_install', $this);

		$this->msg .= ob_get_contents(); // append messages
		ob_end_clean();

		if($this->msg != '')
			$this->set('extension_message', $this->msg);

		return $this->extension->extension_id;
	} 
	
	public function refreshManifestCache()
	{
		$client        = \JApplicationHelper::getClientInfo($this->extension->client_id);
		$short_element = str_replace('com_', '', $this->extension->element);
		$manifestPath  = $client->path . '/components/' . $this->extension->element . '/' . $short_element . '.xml';
		$this->manifest = $this->isManifest($manifestPath);
		$this->setPath('manifest', $manifestPath);

		$manifest_details = \JApplicationHelper::parseXMLInstallFile($this->getPath('manifest'));
		$this->extension->manifest_cache = json_encode($manifest_details);
		$this->extension->name = $manifest_details['name'];

		try {
			return $this->extension->store();
		}
		catch(JException $e) {
			\JError::raiseWarning(101, \JText::_('JLIB_INSTALLER_ERROR_COMP_REFRESH_MANIFEST_CACHE'));
			return false;
		}
	}
}
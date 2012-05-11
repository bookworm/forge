<?php 

namespace forge\excavate\uninstaller;

use forge\excavate\uninstaller;

class Component extends \forge\excavate\cores\Component 
{   
  public function task_uninstall()
 	{        
 	  $id     = $this->eid;
 		$db     = $this->getDbo();
 		$row    = null;
 		$retval = true;

 		$row = \JTable::getInstance('extension');
 		if(!$row->load((int) $id)) {
 			\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_COMP_UNINSTALL_ERRORUNKOWNEXTENSION'));
 			return false;
 		}

 		if($row->protected) {
 			\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_COMP_UNINSTALL_WARNCORECOMPONENT'));
 			return false;
 		}

 		$this->setPath('extension_administrator', \JPath::clean(JPATH_ADMINISTRATOR . '/components/' . $row->element));
 		$this->setPath('extension_site', \JPath::clean(JPATH_SITE . '/components/' . $row->element));
 		$this->setPath('extension_root', $this->getPath('extension_administrator')); 

 		$this->setPath('source', $this->getPath('extension_administrator'));

 		$this->findManifest();
 		$this->manifest = $this->getManifest();

 		if(!$this->manifest)
 		{
 			\JFolder::delete($this->getPath('extension_administrator'));
 			\JFolder::delete($this->getPath('extension_site'));

 			$this->_removeAdminMenus($row);
 			\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_COMP_UNINSTALL_ERRORREMOVEMANUALLY'));

 			return false;
 		}

 		$name = strtolower(\JFilterInput::getInstance()->clean((string) $this->manifest->name, 'cmd'));
 		if(substr($name, 0, 4) == "com_")
 			$element = $name;
 		else
 			$element = "com_$name";

 		$this->set('name', $name);
 		$this->set('element', $element);

 		$this->loadLanguage(JPATH_ADMINISTRATOR . '/components/' . $element);

 		$scriptFile = (string) $this->manifest->scriptfile;

 		if($scriptFile)
 		{
 			$manifestScriptFile = $this->getPath('source') . '/' . $scriptFile;

 			if(is_file($manifestScriptFile))
 				include_once $manifestScriptFile;

 			$classname = $row->element . 'InstallerScript';
 			if(class_exists($classname)) {
 				$this->manifestClass = new $classname($this);
 				$this->set('manifest_script', $scriptFile);
 			}
 		}

 		ob_start();
 		ob_implicit_flush(false);

 		if($this->manifestClass && method_exists($this->manifestClass, 'uninstall'))
 			$this->manifestClass->uninstall($this);

 		$this->msg = ob_get_contents();
 		ob_end_clean();

 		$uninstallFile = (string) $this->manifest->uninstallfile;
 		if($uninstallFile)
 		{
 			if(is_file($this->getPath('extension_administrator') . '/' . $uninstallFile))
 			{
 				ob_start();
 				ob_implicit_flush(false);

 				require_once $this->getPath('extension_administrator') . '/' . $uninstallFile;

 				if(function_exists('com_uninstall'))
 				{
 					if(com_uninstall() === false) {
 						\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_COMP_UNINSTALL_CUSTOM'));
 						$retval = false;
 					}
 				}

 				$this->msg .= ob_get_contents();
 				ob_end_clean();
 			}
 		}

 		if($this->msg != '')
 			$this->set('extension_message', $this->msg);

 		if(isset($this->manifest->uninstall->sql))
 		{
 			$utfresult = $this->parseSQLFiles($this->manifest->uninstall->sql);

 			if($utfresult === false) {
 				\JError::raiseWarning(100, \JText::sprintf('JLIB_INSTALLER_ERROR_COMP_UNINSTALL_SQL_ERROR', $db->stderr(true)));
 				$retval = false;
 			}
 		}

 		$this->_removeAdminMenus($row);

 		$this->removeFiles($this->manifest->media);
 		$this->removeFiles($this->manifest->languages);
 		$this->removeFiles($this->manifest->administration->languages, 1);

 		$query = $db->getQuery(true);
 		$query->delete()->from('#__schemas')->where('extension_id = ' . $id);
 		$db->setQuery($query);
 		$db->query();

 		$asset = \JTable::getInstance('Asset');
 		if($asset->loadByName($element))
 			$asset->delete();

 		$query = $db->getQuery(true);
 		$query->delete()->from('#__categories')->where('extension=' . $db->quote($element), 'OR')
 			->where('extension LIKE ' . $db->quote($element . '.%'));
 		$db->setQuery($query);
 		$db->query();

 		if($db->getErrorNum())
 		{
 			\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_COMP_UNINSTALL_FAILED_DELETE_CATEGORIES'));
 			$this->setError($db->getErrorMsg());
 			$retval = false;
 		}

 		$update = \JTable::getInstance('update');
 		$uid    = $update->find(array('element' => $row->element, 'type' => 'component', 'client_id' => '', 'folder' => ''));

 		if($uid)
 			$update->delete($uid);

 		if(trim($row->element))
 		{
 			if(is_dir($this-getPath('extension_site')))
 			{
 				if(!\JFolder::delete($this->getPath('extension_site'))) {
 					\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_COMP_UNINSTALL_FAILED_REMOVE_DIRECTORY_SITE'));
 					$retval = false;
 				}
 			}

 			if(is_dir($this->getPath('extension_administrator')))
 			{
 				if(!\JFolder::delete($this->getPath('extension_administrator'))) {
 					\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_COMP_UNINSTALL_FAILED_REMOVE_DIRECTORY_ADMIN'));
 					$retval = false;
 				}
 			}

 			$row->delete($row->extension_id);
 			unset($row);

 			return $retval;
 		}
 		else {
 			\JError::raiseWarning(100, 'JLIB_INSTALLER_ERROR_COMP_UNINSTALL_NO_OPTION');
 			return false;
 		}
 	}
}
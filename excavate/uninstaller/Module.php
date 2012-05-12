<?php 

namespace forge\excavate\uninstaller;

use forge\excavate\uninstaller;

class Module extends \forge\excavate\cores\Module
{ 
  public function _init()
  {        
    $artifact = $this->artifact;
       
    if(isset($artifact->client)) 
  	  $client = $artifact->client;
	  else  
  	  $client = 'site';
  	  
  	if(isset($artifact->group))
    	$group = $artifact->group;
  	else 
    	$group = null;
    	
    $this->eid = \forge\excavate\Installer::getExtensionID($artifact->type, $artifact->db_name, $client, $group);
  }
   
  public function task_uninstall()
	{          
	  $id     = $this->eid;
		$row    = null;
		$retval = true;
		$db     = $this->getDbo();

		$row = \JTable::getInstance('extension');

		if(!$row->load((int) $id) || !strlen($row->element)) {
			\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_MOD_UNINSTALL_ERRORUNKOWNEXTENSION'));
			return false;
		}

		if($row->protected) {
			\JError::raiseWarning(100, \JText::sprintf('JLIB_INSTALLER_ERROR_MOD_UNINSTALL_WARNCOREMODULE', $row->name));
			return false;
		} 
		
		$element = $row->element;
		$client  = \JApplicationHelper::getClientInfo($row->client_id);

		if($client === false) {
			$this->abort(\JText::sprintf('JLIB_INSTALLER_ERROR_MOD_UNINSTALL_UNKNOWN_CLIENT', $row->client_id));
			return false;
		}
		$this->setPath('extension_root', $client->path . '/modules/' . $element);

		$this->setPath('source', $this->getPath('extension_root'));
         
      echo 'gug?';
                         
		$this->manifest = $this->getManifest();
    echo 'gug?';

		$this->loadLanguage(($row->client_id ? JPATH_ADMINISTRATOR : JPATH_SITE) . '/modules/' . $element);
      
    echo 'gug?';
		$this->scriptElement = $this->manifest->scriptfile;
		$manifestScript      = (string) $this->manifest->scriptfile;

		if($manifestScript)
		{
			$manifestScriptFile = $this->getPath('extension_root') . '/' . $manifestScript;

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

		if($this->manifestClass && method_exists($this->manifestClass, 'uninstall'))
			$this->manifestClass->uninstall($this);

		$this->msg = ob_get_contents();
		ob_end_clean();

		if(!($this->manifest instanceof \JXMLElement)) 
		{
			\JFolder::delete($this->getPath('extension_root'));
			\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_MOD_UNINSTALL_INVALID_NOTFOUND_MANIFEST'));

			return false;
		}

		$utfresult = $this->parseSQLFiles($this->manifest->uninstall->sql);

		if($utfresult === false) {
			\JError::raiseWarning(100, \JText::sprintf('JLIB_INSTALLER_ERROR_MOD_UNINSTALL_SQL_ERROR', $db->stderr(true)));
			$retval = false;
		}

		$query = $db->getQuery(true);
		$query->delete()->from('#__schemas')->where('extension_id = ' . $row->extension_id);
		$db->setQuery($query);
		$db->Query();

		$this->removeFiles($this->manifest->media);
		$this->removeFiles($this->manifest->languages, $row->client_id);

		$query = $db->getQuery(true);
		$query->select($query->qn('id'))->from($query->qn('#__modules'));
		$query->where($query->qn('module') . ' = ' . $query->q($row->element));
		$query->where($query->qn('client_id') . ' = ' . (int) $row->client_id);
		$db->setQuery($query);

		try {
			$modules = $db->loadColumn();
		}
		catch(JException $e) {
			$modules = array();
		}

		if(count($modules))
		{
			\JArrayHelper::toInteger($modules);
			$modID = implode(',', $modules);

			$query = 'DELETE' . ' FROM #__modules_menu' . ' WHERE moduleid IN (' . $modID . ')';
			$db->setQuery($query);
			try {
				$db->query();
			}
			catch(JException $e) {
				\JError::raiseWarning(100, \JText::sprintf('JLIB_INSTALLER_ERROR_MOD_UNINSTALL_EXCEPTION', $db->stderr(true)));
				$retval = false;
			}

			$query = 'DELETE' . ' FROM #__modules' . ' WHERE id IN (' . $modID . ')';
			$db->setQuery($query);

			try {
				$db->query();
			}
			catch(JException $e) {
				\JError::raiseWarning(100, \JText::sprintf('JLIB_INSTALLER_ERROR_MOD_UNINSTALL_EXCEPTION', $db->stderr(true)));
				$retval = false;
			}
		}

		$row->delete($row->extension_id);
		$query = 'DELETE FROM #__modules WHERE module = ' . $db->Quote($row->element) . ' AND client_id = ' . $row->client_id;
		$db->setQuery($query);

		try {
			$db->Query();
		}
		catch(JException $e) {
		}

		unset($row);

		if(!\JFolder::delete($this->getPath('extension_root')))
			$retval = false;

		return $retval;
	}
}
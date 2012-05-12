<?php 

namespace forge\excavate\uninstaller;

use forge\excavate\uninstaller;

class Plugin extends \forge\excavate\cores\Plugin
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
 		$this->route = 'uninstall';
       
    $id     = $this->eid;
 		$row    = null;
 		$retval = true;
 		$db     = $this->getDbo();

 		$row = \JTable::getInstance('extension');
 		if(!$row->load((int) $id)) {
 			\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_PLG_UNINSTALL_ERRORUNKOWNEXTENSION'));
 			return false;
 		}

 		if($row->protected) {
 			\JError::raiseWarning(100, \JText::sprintf('JLIB_INSTALLER_ERROR_PLG_UNINSTALL_WARNCOREPLUGIN', $row->name));
 			return false;
 		}

 		if(trim($row->folder) == '') {
 			\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_PLG_UNINSTALL_FOLDER_FIELD_EMPTY'));
 			return false;
 		}

 		if(is_dir(JPATH_PLUGINS . '/' . $row->folder . '/' . $row->element))
 			$this->setPath('extension_root', JPATH_PLUGINS . '/' . $row->folder . '/' . $row->element);
 		else
 			$this->setPath('extension_root', JPATH_PLUGINS . '/' . $row->folder);

 		$manifestFile = $this->getPath('extension_root') . '/' . $row->element . '.xml';

 		if(!file_exists($manifestFile)) {
 			\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_PLG_UNINSTALL_INVALID_NOTFOUND_MANIFEST'));
 			return false;
 		}

 		$xml = \JFactory::getXML($manifestFile);

 		$this->manifest = $xml;

 		if(!$xml) {
 			\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_PLG_UNINSTALL_LOAD_MANIFEST'));
 			return false;
 		}

 		if($xml->getName() != 'install' && $xml->getName() != 'extension') {
 			\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_PLG_UNINSTALL_INVALID_MANIFEST'));
 			return false;
 		}

 		$this->setPath('source', JPATH_PLUGINS . '/' . $row->folder . '/' . $row->element);
 		$this->loadLanguage(JPATH_PLUGINS . '/' . $row->folder . '/' . $row->element);

 		$manifestScript = (string) $xml->scriptfile;
 		if($manifestScript)
 		{
 			$manifestScriptFile = $this->getPath('source') . '/' . $manifestScript;
 			if(is_file($manifestScriptFile))
 				include_once $manifestScriptFile;

 			$folderClass = str_replace('-', '', $row->folder);
 			$classname = 'plg' . $folderClass . $row->element . 'InstallerScript';     

 			if(class_exists($classname)) {
 				$this->manifestClass = new $classname($this);
 				$this->set('manifest_script', $manifestScript);
 			}
 		}

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

 		$utfresult = $this->parseSQLFiles($xml->{strtolower($this->route)}->sql);
 		if($utfresult === false) {
 			$this->abort(\JText::sprintf('JLIB_INSTALLER_ABORT_PLG_UNINSTALL_SQL_ERROR', $db->stderr(true)));
 			return false;
 		}

 		ob_start();
 		ob_implicit_flush(false);
 		if($this->manifestClass && method_exists($this->manifestClass, 'uninstall'))
 			$this->manifestClass->uninstall($this);

 		$this->msg = ob_get_contents();
 		ob_end_clean();

 		$this->removeFiles($xml->images, -1);
 		$this->removeFiles($xml->files, -1);
 		\JFile::delete($manifestFile);

 		$this->removeFiles($xml->media);
 		$this->removeFiles($xml->languages, 1);

 		$query = $db->getQuery(true);
 		$query->delete()->from('#__schemas')->where('extension_id = ' . $row->extension_id);
 		$db->setQuery($query);
 		$db->Query();

 		$row->delete($row->extension_id);
 		unset($row);

 		$files = \JFolder::files($this->getPath('extension_root'));

 		\JFolder::delete($this->getPath('extension_root'));

 		if($this->msg)
 			$this->set('extension_message', $this->msg);

 		return $retval;
 	}   
}
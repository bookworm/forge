<?php 

namespace forge\excavate\uninstaller;

use forge\excavate\uninstaller;

class File extends \forge\excavate\cores\File
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
 	  $id = $this->eid;
 	  
 		$row = \JTable::getInstance('extension');
 		if(!$row->load($id)) {
 			\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_FILE_UNINSTALL_LOAD_ENTRY'));
 			return false;
 		}

 		if($row->protected) {
 			\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_FILE_UNINSTALL_WARNCOREFILE'));
 			return false;
 		}

 		$retval       = true;
 		$manifestFile = JPATH_MANIFESTS . '/files/' . $row->element . '.xml';

 		if(file_exists($manifestFile))
 		{
 			$this->setPath('extension_root', JPATH_ROOT); // . '/files/' . $manifest->filename);

 			$xml = \JFactory::getXML($manifestFile);

 			if(!$xml) {
 				\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_FILE_UNINSTALL_LOAD_MANIFEST'));
 				return false;
 			}

 			if($xml->getName() != 'extension') {
 				\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_FILE_UNINSTALL_INVALID_MANIFEST'));
 				return false;
 			}

 			$this->manifest = $xml;

 			$this->scriptElement = $this->manifest->scriptfile;  
 			
 			$manifestScript = (string) $this->manifest->scriptfile;
 			if($manifestScript)
 			{
 				$manifestScriptFile = $this->getPath('extension_root') . '/' . $manifestScript;

 				if(is_file($manifestScriptFile))
 					include_once $manifestScriptFile;

 				$classname = $row->element . 'InstallerScript';

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

 			$utfresult = $this->parseSQLFiles($this->manifest->uninstall->sql);
 			if($utfresult === false) {
 				\JError::raiseWarning(100, \JText::sprintf('JLIB_INSTALLER_ERROR_FILE_UNINSTALL_SQL_ERROR', $db->stderr(true)));
 				$retval = false;
 			}

 			$db = \JFactory::getDbo();
 			$query = $db->getQuery(true);
 			$query->delete()
 				->from('#__schemas')
 				->where('extension_id = ' . $row->extension_id);
 			$db->setQuery($query);
 			$db->Query();

 			$packagePath = $this->getPath('source');
 			$jRootPath   = \JPath::clean(JPATH_ROOT);

 			foreach($xml->fileset->files as $eFiles)
 			{
 				$folder = (string) $eFiles->attributes()->folder;
 				$target = (string) $eFiles->attributes()->target;       
 				
 				if(empty($target))
 					$targetFolder = JPATH_ROOT;
 				else
 					$targetFolder = JPATH_ROOT . '/' . $target;

 				$folderList = array();
 				if(count($eFiles->children()) > 0)
 				{
 					foreach($eFiles->children() as $eFileName)
 					{
 						if($eFileName->getName() == 'folder')
 							$folderList[] = $targetFolder . '/' . $eFileName;
 						else {
 							$fileName = $targetFolder . '/' . $eFileName;
 							\JFile::delete($fileName);
 						}
 					}
 				}

 				foreach($folderList as $folder)
 				{
 					$files = \JFolder::files($folder);
 					if(!count($files))
 						\JFolder::delete($folder);
 				}
 			}

 			\JFile::delete($manifestFile);

 		}
 		else
 		{
 			\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_FILE_UNINSTALL_INVALID_NOTFOUND_MANIFEST'));
 			$row->delete();
 			return false;
 		}

 		$this->removeFiles($xml->languages);

 		$row->delete();

 		return $retval;
 	}                                
}
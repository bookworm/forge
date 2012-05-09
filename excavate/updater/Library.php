<?php 

namespace forge\excavate\updater;

use forge\excavate\updater;

class Library extends \forge\excavate\cores\Library
{
  public function task_uninstall()
  {
    $this->manifest = $this->getManifest();

		$name    = (string) $this->manifest->name;
		$name    = JFilterInput::getInstance()->clean($name, 'string');
		$element = str_replace('.xml', '', basename($this->getPath('manifest')));
		$this->set('name', $name);
		$this->set('element', $element);
		
		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('extension_id'));
		$query->from($db->quoteName('#__extensions'));
		$query->where($db->quoteName('type') . ' = ' . $db->quote('library'));
		$query->where($db->quoteName('element') . ' = ' . $db->quote($element));
		$db->setQuery($query);
		$result = $db->loadResult(); 
		
		if($result) {
		  $installer = new JInstaller; 
			$installer->uninstall('library', $result);
		}
			
		return true;
  }
  
  public function task_setStuff()
  {
    return $this->_taskSetStuff();
  } 
  
  public function task_insertDBStuff()
  {
    return $this->_taskInsertDBStuff();
  } 
  
  public function task_setDescriptionAndLibraryName()
  {
    return $this->_taskSetDescriptionAndLibraryName();
  } 
  
  public function task_parseFiles()
  {
    return $this->_taskParseFiles();
  }   
  
  public function task_parseLanguages()
  {
    $this->parseLanguages($this->manifest->languages);
		
		return true;
  }
  
  public function task_parseMedia()
  {
    $this->parseMedia($this->manifest->media);    
		
		return true;
  } 
  
  public function task_insertRow()
  {
    return $this->_taskInsertRow();
  }     
  
  public function task_copyManifest()
  {
    return $this->_taskCopyManifest();
  }     
}
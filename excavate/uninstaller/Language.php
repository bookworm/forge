<?php 

namespace forge\excavate\uninstaller;

use forge\excavate\uninstaller;

class Language extends \forge\excavate\cores\Language
{                    
  public function _init($eid)
  {      
    parent::_init();      
    $this->eid = $eid;
  }     
  
  public function task_uninstall()
  {
    $eid = $this->eid;  
    
	  $extension = \JTable::getInstance('extension');
		$extension->load($eid);
		$client = \JApplicationHelper::getClientInfo($extension->get('client_id'));    
		
		$element = $extension->get('element');
		if(empty($element)) {
			\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_LANG_UNINSTALL_ELEMENT_EMPTY'));
			return false;
		}   
		
		$protected = $extension->get('protected');
		if($protected == 1) {
			\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_LANG_UNINSTALL_PROTECTED'));
			return false;
		}      
		
		$params = \JComponentHelper::getParams('com_languages');
		if($params->get($client->name) == $element) {
			\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_LANG_UNINSTALL_DEFAULT'));
			return false;
		}
		
		$path = $client->path . '/language/' . $element;
		$this->setPath('source', $path);
		$this->findManifest();
		$this->manifest = $this->getManifest();
		$this->removeFiles($this->manifest->media);
		
		
		if(!\JFolder::exists($path)) {
			$extension->delete();
			\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_LANG_UNINSTALL_PATH_EMPTY'));
			return false;
		}

		if(!\JFolder::delete($path)) {
			\JError::raiseWarning(100, \JText::_('JLIB_INSTALLER_ERROR_LANG_UNINSTALL_DIRECTORY'));
			return false;
		}        
		
		$extension->delete();
		
		
		$db = \JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->from('#__users');
		$query->select('*');
		$db->setQuery($query);
		$users = $db->loadObjectList();
		if($client->name == 'administrator')
			$param_name = 'admin_language';
		else
			$param_name = 'language';     
			
		$count = 0;
		foreach ($users as $user)
		{
			$registry = new \JRegistry;
			$registry->loadString($user->params);
			if($registry->get($param_name) == $element)
			{
				$registry->set($param_name, '');
				$query = $db->getQuery(true);
				$query->update('#__users');
				$query->set('params=' . $db->quote($registry));
				$query->where('id=' . (int) $user->id);
				$db->setQuery($query);
				$db->query();
				$count = $count + 1;
			}
		}     
		
		if(!empty($count))
			\JError::raiseNotice(500, \JText::plural('JLIB_INSTALLER_NOTICE_LANG_RESET_USERS', $count));

		return true;
  }      
}
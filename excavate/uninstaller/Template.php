<?php 

namespace forge\excavate\uninstaller;

use forge\excavate\uninstaller;

class Template extends \forge\excavate\cores\Template
{             
  public function _init($eid)
  {
    parent::_init();
    $this->eid = $eid;
  }    
  
  public function task_uninstall()
	{            
	  $id = $this->eid;
		$retval = true;
		$row = JTable::getInstance('extension');

		if(!$row->load((int) $id) || !strlen($row->element)) {
			JError::raiseWarning(100, JText::_('JLIB_INSTALLER_ERROR_TPL_UNINSTALL_ERRORUNKOWNEXTENSION'));
			return false;
		}

		if($row->protected) {
			JError::raiseWarning(100, JText::sprintf('JLIB_INSTALLER_ERROR_TPL_UNINSTALL_WARNCORETEMPLATE', $row->name));
			return false;
		}

		$name     = $row->element;
		$clientId = $row->client_id;

		if(!$name) {
			JError::raiseWarning(100, JText::_('JLIB_INSTALLER_ERROR_TPL_UNINSTALL_TEMPLATE_ID_EMPTY'));
			return false;
		}

		$db    = $this->getDbo();
		$query = 'SELECT COUNT(*) FROM #__template_styles' . ' WHERE home = 1 AND template = ' . $db->Quote($name);
		$db->setQuery($query);

		if($db->loadResult() != 0) {
			JError::raiseWarning(100, JText::_('JLIB_INSTALLER_ERROR_TPL_UNINSTALL_TEMPLATE_DEFAULT'));
			return false;
		}

		$client = JApplicationHelper::getClientInfo($clientId);

		if(!$client) {
			JError::raiseWarning(100, JText::_('JLIB_INSTALLER_ERROR_TPL_UNINSTALL_INVALID_CLIENT'));
			return false;
		}

		$this->setPath('extension_root', $client->path . '/templates/' . strtolower($name));
		$this->setPath('source', $this->getPath('extension_root'));

		$this->findManifest();
		$manifest = $this->getManifest();
		if(!($manifest instanceof JXMLElement))
		{
			$row->delete($row->extension_id);
			unset($row);

			JFolder::delete($this->getPath('extension_root'));
			JError::raiseWarning(100, JText::_('JLIB_INSTALLER_ERROR_TPL_UNINSTALL_INVALID_NOTFOUND_MANIFEST'));
			return false;
		}

		$this->removeFiles($manifest->media);
		$this->removeFiles($manifest->languages, $clientId);

		if(JFolder::exists($this->getPath('extension_root')))
			$retval = JFolder::delete($this->getPath('extension_root'));
		else {
			JError::raiseWarning(100, JText::_('JLIB_INSTALLER_ERROR_TPL_UNINSTALL_TEMPLATE_DIRECTORY'));
			$retval = false;
		}

		$query = 'UPDATE #__menu INNER JOIN #__template_styles' . ' ON #__template_styles.id = #__menu.template_style_id'
			. ' SET #__menu.template_style_id = 0' . ' WHERE #__template_styles.template = ' . $db->Quote(strtolower($name))
			. ' AND #__template_styles.client_id = ' . $db->Quote($clientId);
		$db->setQuery($query);
		$db->Query();

		$query = 'DELETE FROM #__template_styles' . ' WHERE template = ' . $db->Quote($name) . ' AND client_id = ' . $db->Quote($clientId);
		$db->setQuery($query);
		$db->Query();

		$row->delete($row->extension_id);
		unset($row);

		return $retval;
	}
}
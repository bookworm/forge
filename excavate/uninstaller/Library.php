<?php 

namespace forge\excavate\uninstaller;

use forge\excavate\uninstaller;

class Library extends \forge\excavate\cores\Library
{   
  public function task_uninstall()
	{         
	  $id     = $this->eid;
		$retval = true;

		$row = JTable::getInstance('extension');
		if(!$row->load((int) $id) || !strlen($row->element)) {
			JError::raiseWarning(100, JText::_('ERRORUNKOWNEXTENSION'));
			return false;
		}

		if($row->protected) {
			JError::raiseWarning(100, JText::_('JLIB_INSTALLER_ERROR_LIB_UNINSTALL_WARNCORELIBRARY'));
			return false;
		}

		$manifestFile = JPATH_MANIFESTS . '/libraries/' . $row->element . '.xml';

		if(file_exists($manifestFile)) {
			$manifest = new JLibraryManifest($manifestFile);
			$this->setPath('extension_root', JPATH_PLATFORM . '/' . $manifest->libraryname);

			$xml = JFactory::getXML($manifestFile);

			if(!$xml) {
				JError::raiseWarning(100, JText::_('JLIB_INSTALLER_ERROR_LIB_UNINSTALL_LOAD_MANIFEST'));
				return false;
			}

			if($xml->getName() != 'install' && $xml->getName() != 'extension') {
				JError::raiseWarning(100, JText::_('JLIB_INSTALLER_ERROR_LIB_UNINSTALL_INVALID_MANIFEST'));
				return false;
			}

			$this->removeFiles($xml->files, -1);
			JFile::delete($manifestFile);
		}
		else
		{
			$row->delete($row->extension_id);
			unset($row);
			JError::raiseWarning(100, JText::_('JLIB_INSTALLER_ERROR_LIB_UNINSTALL_INVALID_NOTFOUND_MANIFEST'));
			return false;
		}

		if(JFolder::exists($this->getPath('extension_root')))
		{
			if(is_dir($this->getPath('extension_root')))
			{
				$files = JFolder::files($this->getPath('extension_root'));
				if(!count($files))
					JFolder::delete($this->getPath('extension_root'));
			}
		}

		$this->removeFiles($xml->media);
		$this->removeFiles($xml->languages);

		$row->delete($row->extension_id);
		unset($row);

		return $retval;
	}       
}
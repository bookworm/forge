<?php 

namespace forge\excavate\cores;

use forge\excavate\cores;

class Language extends \forge\excavate\Excavator  
{                                                             
  public $_core = false;  
  
  public function _init()
  {
    parent::_init();          
  }      
  
  public function _install($cname, $basePath, $clientId, &$element)
  {       
		$name = JFilterInput::getInstance()->clean((string) $this->manifest->name, 'cmd');
		$this->set('name', $name);    
		
		$tag = (string) $this->manifest->tag;   
		
		if(!$tag) {
			$this->abort(JText::sprintf('JLIB_INSTALLER_ABORT', JText::_('JLIB_INSTALLER_ERROR_NO_LANGUAGE_TAG')));
			return false;
		}  
		
		$this->set('tag', $tag);    
		
		$this->setPath('extension_site', $basePath . '/language/' . $tag);          
		
		if($element && count($element->children()))
		{
			$files = $element->children();
			foreach($files as $file)
			{
				if((string) $file->attributes()->file == 'meta') {
					$this->_core = true;
					break;
				}
			}
		} 
		
		if(!$this->_core)
		{
			if(!JFile::exists($this->getPath('extension_site') . '/' . $this->get('tag') . '.xml')) {
				$this->abort(JText::sprintf('JLIB_INSTALLER_ABORT', JText::sprintf('JLIB_INSTALLER_ERROR_NO_CORE_LANGUAGE', $this->get('tag'))));
				return false;
			}
		} 
		
		$created = false;
		if(!file_exists($this->getPath('extension_site')))
		{
			if(!$created = JFolder::create($this->getPath('extension_site')))
			{
				$this->abort(
					JText::sprintf(
						'JLIB_INSTALLER_ABORT',
						JText::sprintf('JLIB_INSTALLER_ERROR_CREATE_FOLDER_FAILED', $this->getPath('extension_site'))
					)
				);     
				
				return false;
			}
		}
		else
		{
			$updateElement = $this->update;

			if($this->getUpgrade() || ($this->manifestClass && method_exists($this->manifestClass, 'update')) || $updateElement) {
				return $this->_update();
			}
			elseif(!$this->getOverwrite())
			{
				if(file_exists($this->getPath('extension_site')))
				{
					JError::raiseWarning(1,
						JText::sprintf(
							'JLIB_INSTALLER_ABORT',
							JText::sprintf('JLIB_INSTALLER_ERROR_FOLDER_IN_USE', $this->getPath('extension_site'))
						)
					);
				}
				else
				{
					JError::raiseWarning(1,
						JText::sprintf(
							'JLIB_INSTALLER_ABORT',
							JText::sprintf('JLIB_INSTALLER_ERROR_FOLDER_IN_USE', $this->getPath('extension_administrator'))
						)
					);
				}    
				
				return false;
			}
		}   
		
		if($created)
			$this->pushStep(array('type' => 'folder', 'path' => $this->getPath('extension_site')));

		if($this->parseFiles($element) === false) {
			$this->abort();
			return false;
		}   
		
	  $this->parseMedia($this->manifest->media);    
	  
	  $this->setPath('extension_site', $basePath . '/language/pdf_fonts');
		$overwrite = $this->setOverwrite(true);
		if($this->parseFiles($this->manifest->fonts) === false) {
			$this->abort();
			return false;
		}      
		
		$this->setOverwrite($overwrite);   
		
		$description = (string) $this->description;
		if($description)
			$this->set('message', JText::_($description));
		else
			$this->set('message', '');      
			
		$row = JTable::getInstance('extension');
		$row->set('name', $this->get('name'));
		$row->set('type', 'language');
		$row->set('element', $this->get('tag'));

		$row->set('folder', '');
		$row->set('enabled', 1);
		$row->set('protected', 0);
		$row->set('access', 0);
		$row->set('client_id', $clientId);
		$row->set('params', $this->getParams());
		$row->set('manifest_cache', $this->generateManifestCache());

		if(!$row->store()) {
			$this->abort(JText::sprintf('JLIB_INSTALLER_ABORT', $row->getError()));
			return false;
		}	    
		
		$update = JTable::getInstance('update');
		$uid    = $update->find(array('element' => $this->get('tag'), 'type' => 'language', 'client_id' => '', 'folder' => ''));
		if($uid)
			$update->delete($uid);

		return $row->get('extension_id');
  } 
  
  public function update()
  {
    $xml = $this->getManifest();
		$this->manifest = $xml;
		$cname = $xml->attributes()->client;

		$client = JApplicationHelper::getClientInfo($cname, true);
		if($client === null || (empty($cname) && $cname !== 0)) {
			$this->abort(JText::sprintf('JLIB_INSTALLER_ABORT', JText::sprintf('JLIB_INSTALLER_ERROR_UNKNOWN_CLIENT_TYPE', $cname)));
			return false;
		}
		$basePath = $client->path;
		$clientId = $client->id;
		
		
		$name = (string) $this->manifest->name;
		$name = JFilterInput::getInstance()->clean($name, 'cmd');
		$this->set('name', $name);        
		
		$tag = (string) $xml->tag;   
		
		if(!$tag) {
			$this->abort(JText::sprintf('JLIB_INSTALLER_ABORT', JText::_('JLIB_INSTALLER_ERROR_NO_LANGUAGE_TAG')));
			return false;
		}     
		
		$this->set('tag', $tag);
		$folder = $tag;
		
		$this->setPath('extension_site', $basePath . '/language/' . $this->get('tag'));  
		
		if(count($xml->files->children()))
		{
			foreach($xml->files->children() as $file)
			{
				if((string) $file->attributes()->file == 'meta') {
					$this->_core = true;
					break;
				}
			}
		}  
		
		if(!$this->_core)
		{
			if(!JFile::exists($this->getPath('extension_site') . '/' . $this->get('tag') . '.xml')) {
				$this->abort(JText::sprintf('JLIB_INSTALLER_ABORT', JText::sprintf('JLIB_INSTALLER_ERROR_NO_CORE_LANGUAGE', $this->get('tag'))));
				return false;
			}
		}
		
		if($this->parseFiles($xml->files) === false) {
			$this->abort();
			return false;
		}
		
		$this->parseMedia($xml->media);

		$this->setPath('extension_site', $basePath . '/language/pdf_fonts');
		$overwrite = $this->setOverwrite(true);
		if($this->parseFiles($xml->fonts) === false) {
			$this->abort();
			return false;
		}    
		
		$this->setOverwrite($overwrite);       
		
		$this->set('message', (string) $xml->description);      
		
		$update = JTable::getInstance('update');
		$uid = $update->find(array('element' => $this->get('tag'), 'type' => 'language', 'client_id' => $clientId));
		if($uid)
			$update->delete($uid);
		
		$row = JTable::getInstance('extension');
		$eid = $row->find(array('element' => strtolower($this->get('tag')), 'type' => 'language', 'client_id' => $clientId));
		if($eid) {
			$row->load($eid);
		}
		else
		{
			$row->set('folder', ''); // There is no folder for language
			$row->set('enabled', 1);
			$row->set('protected', 0);
			$row->set('access', 0);
			$row->set('client_id', $clientId);
			$row->set('params', $this->getParams());
		}
		$row->set('name', $this->get('name'));
		$row->set('type', 'language');
		$row->set('element', $this->get('tag'));
		$row->set('manifest_cache', $this->generateManifestCache());    
		
		if(!$row->store()) {
			$this-->abort(JText::sprintf('JLIB_INSTALLER_ABORT', $row->getError()));
			return false;
		}

		// And now we run the postflight
		ob_start();
		ob_implicit_flush(false);
		if($this->manifestClass && method_exists($this->manifestClass, 'postflight'))
			$this->manifestClass->postflight('update', $this);

		$this->msg .= ob_get_contents(); 
		ob_end_clean();   
		
		if($this->msg != '')
			$this->set('extension_message', $this->msg);

		return $row->get('extension_id');
  }   
  
  public function discover()
  {
    $results = array();
		$site_languages = JFolder::folders(JPATH_SITE . '/language');
		$admin_languages = JFolder::folders(JPATH_ADMINISTRATOR . '/language');  
		
		foreach($site_languages as $language)
		{
			if(file_exists(JPATH_SITE . '/language/' . $language . '/' . $language . '.xml'))
			{
				$manifest_details = JApplicationHelper::parseXMLInstallFile(JPATH_SITE . '/language/' . $language . '/' . $language . '.xml');
				$extension = JTable::getInstance('extension');
				$extension->set('type', 'language');
				$extension->set('client_id', 0);
				$extension->set('element', $language);
				$extension->set('name', $language);
				$extension->set('state', -1);
				$extension->set('manifest_cache', json_encode($manifest_details));
				$results[] = $extension;
			}
		}     
		
		foreach($admin_languages as $language)
		{
			if(file_exists(JPATH_ADMINISTRATOR . '/language/' . $language . '/' . $language . '.xml'))
			{
				$manifest_details = JApplicationHelper::parseXMLInstallFile(JPATH_ADMINISTRATOR . '/language/' . $language . '/' . $language . '.xml');
				$extension = JTable::getInstance('extension');
				$extension->set('type', 'language');
				$extension->set('client_id', 1);
				$extension->set('element', $language);
				$extension->set('name', $language);
				$extension->set('state', -1);
				$extension->set('manifest_cache', json_encode($manifest_details));
				$results[] = $extension;
			}
		}  
		
		return $results;
  } 
  
  public function discover_install()
  {
		$client = JApplicationHelper::getClientInfo($this->extension->client_id);    
		
		$short_element  = $this->extension->element;
		$manifestPath   = $client->path . '/language/' . $short_element . '/' . $short_element . '.xml';
		$this->manifest = $this->isManifest($manifestPath);         
		
		$this->setPath('manifest', $manifestPath);
		$this->setPath('source', $client->path . '/language/' . $short_element);
		$this->setPath('extension_root', $this->getPath('source'));      
		
		$manifest_details                = JApplicationHelper::parseXMLInstallFile($this->getPath('manifest'));
		$this->extension->manifest_cache = json_encode($manifest_details);
		$this->extension->state          = 0;
		$this->extension->name           = $manifest_details['name'];
		$this->extension->enabled        = 1;        
		
		try {
			$this->extension->store();
		}
		catch(JException $e) {
			JError::raiseWarning(101, JText::_('JLIB_INSTALLER_ERROR_LANG_DISCOVER_STORE_DETAILS'));
			return false;
		}           
		
		return $this->extension->get('extension_id');
  } 
  
  public function refreshManifestCache()
	{
		$client         = JApplicationHelper::getClientInfo($this->extension->client_id);
		$manifestPath   = $client->path . '/language/' . $this->extension->element . '/' . $this->extension->element . '.xml';
		$this->manifest = $this->isManifest($manifestPath);
		$this->setPath('manifest', $manifestPath);      
		
		$manifest_details = JApplicationHelper::parseXMLInstallFile($this->getPath('manifest'));
		$this->extension->manifest_cache = json_encode($manifest_details);
		$this->extension->name = $manifest_details['name'];

		if($this->extension->store())
			return true;
		else {
			JError::raiseWarning(101, JText::_('JLIB_INSTALLER_ERROR_MOD_REFRESH_MANIFEST_CACHE'));
			return false;
		}
	}
}
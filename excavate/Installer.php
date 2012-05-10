<?php 

namespace forge\excavate;

use forge\excavate;

defined('JPATH_PLATFORM') or die;

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.archive');
jimport('joomla.filesystem.path');
jimport('joomla.base.adapter');

class Installer extends \forge\core\Object
{
	public $extension = null;
	protected $extension_message = null;
	protected $redirect_url = null;    
	public $excavator;     
	protected $_db; 
	public $manfiest;
	
	public function __construct($excavator)
	{         
	  $this->excavator = $excavator;     
	  $this->_db       = $this->excavator->getDbo(); 
	}    
	
	public static function &getInstance($excavator)
  {
    static $instance; 

    if(!is_object($instance))
      $instance = new self($excavator);   

    return $instance;
  }

	public function getOverwrite()
	{
		return $this->excavator->getOverwrite();
	}    
	
	public function setOverwrite($overwrite)
	{
		return $this->excavator->setOverwrite($overwrite);
	}       
	
	public function getUpgrade()
	{    
	  return $this->excavator->getUpgrade();
	}
	
	public function setUpgrade($upgrade)
	{
	 return $this->excavator->setUpgrade($upgrade);
	}

	public function isOverwrite()
	{
	  return $this->excavator->getOverwrite();
	}

	public function isUpgrade()
	{
		return $this->excavator->getUpgrade();
	}

	public function getManifest()
	{ 
	  return $this->excavator->getManifest();
	}

	public function getPath($name, $default = null)
	{
	  return $this->excavator->getPath($name, $default);
	}           
	
	public function abort($msg = null, $type = null)
	{  
	  return $this->excavator->abort($msg, $type); 
	}     
	
	public function pushStep($step)
	{
	  return $this->excavator->pushStep($step); 
	}
	
	public function parseQueries($element)
	{
		$db = $this->_db;

		if(!$element || !count($element->children()))
			return 0;

		$queries = $element->children();

		if(count($queries) == 0)
			return 0;

		foreach($queries as $query)
		{
			$db->setQuery($query->data());

			if(!$db->query()) {
				JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true)));
				return false;
			}
		}

		return (int) count($queries);
	}

	public function parseSQLFiles($element)
	{
		if(!$element || !count($element->children()))
			return 0;

		$queries  = array();
		$db       = $this->_db;
		$dbDriver = strtolower($db->name);

		if($dbDriver == 'mysqli')
			$dbDriver = 'mysql';
		elseif($dbDriver == 'sqlsrv')
			$dbDriver = 'sqlazure';

		$sqlfile = '';
		foreach ($element->children() as $file)
		{
			$fCharset = (strtolower($file->attributes()->charset) == 'utf8') ? 'utf8' : '';
			$fDriver  = strtolower($file->attributes()->driver);

			if($fDriver == 'mysqli')
				$fDriver = 'mysql';
			elseif($fDriver == 'sqlsrv')
				$fDriver = 'sqlazure';

			if($fCharset == 'utf8' && $fDriver == $dbDriver)
			{
				$sqlfile = $this->getPath('extension_root') . '/' . $file;

				if(!file_exists($sqlfile)) {
					JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_SQL_FILENOTFOUND', $sqlfile));
					return false;
				}

				$buffer = file_get_contents($sqlfile);

				if($buffer === false) {
					JError::raiseWarning(1, JText::_('JLIB_INSTALLER_ERROR_SQL_READBUFFER'));
					return false;
				}

				$queries = JInstallerHelper::splitSql($buffer);

				if(count($queries) == 0)
					return 0;

				foreach($queries as $query)
				{
					$query = trim($query);

					if($query != '' && $query{0} != '#')
					{
						$db->setQuery($query);

						if(!$db->query()) {
							JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true)));
							return false;
						}
					}
				}
			}
		}

		return (int) count($queries);
	}

	public function setSchemaVersion($schema, $eid)
	{
		if($eid && $schema)
		{
			$db          = JFactory::getDBO();
			$schemapaths = $schema->children();

			if(!$schemapaths)
				return;

			if(count($schemapaths))
			{
				$dbDriver = strtolower($db->name);
				if($dbDriver == 'mysqli')
					$dbDriver = 'mysql';
				elseif($dbDriver == 'sqlsrv')
					$dbDriver = 'sqlazure';

				$schemapath = '';

				foreach($schemapaths as $entry)
				{
					$attrs = $entry->attributes();
					if($attrs['type'] == $dbDriver) {
						$schemapath = $entry;
						break;
					}
				}

				if(strlen($schemapath))
				{
					$files = str_replace('.sql', '', JFolder::files($this->getPath('extension_root') . '/' . $schemapath, '\.sql$'));
					usort($files, 'version_compare');

					$query = $db->getQuery(true);
					$query->delete()
						->from('#__schemas')
						->where('extension_id = ' . $eid);
					$db->setQuery($query);

					if($db->query())
					{
						$query->clear();
						$query->insert($db->quoteName('#__schemas'));
						$query->columns(array($db->quoteName('extension_id'), $db->quoteName('version_id')));
						$query->values($eid . ', ' . $db->quote(end($files)));
						$db->setQuery($query);
						$db->query();
					}
				}
			}
		}   
		
		return true;
	}

	public function parseSchemaUpdates($schema, $eid)
	{
		$files        = array();
		$update_count = 0;

		if($eid && $schema)
		{
			$db          = JFactory::getDBO();
			$schemapaths = $schema->children();

			if(count($schemapaths))
			{
				$dbDriver = strtolower($db->name);

				if($dbDriver == 'mysqli')
					$dbDriver = 'mysql';
				elseif($dbDriver == 'sqlsrv')
					$dbDriver = 'sqlazure';

				$schemapath = '';
				foreach($schemapaths as $entry)
				{
					$attrs = $entry->attributes();
					if($attrs['type'] == $dbDriver) {
						$schemapath = $entry;
						break;
					}
				}

				if(strlen($schemapath))
				{
					$files = str_replace('.sql', '', JFolder::files($this->getPath('extension_root') . '/' . $schemapath, '\.sql$'));
					usort($files, 'version_compare');

					if(!count($files))
						return false;

					$query = $db->getQuery(true);
					$query->select('version_id')
						->from('#__schemas')
						->where('extension_id = ' . $eid);
					$db->setQuery($query);
					$version = $db->loadResult();

					if($version)
					{
						foreach($files as $file)
						{
							if(version_compare($file, $version) > 0)
							{
								$buffer = file_get_contents($this->getPath('extension_root') . '/' . $schemapath . '/' . $file . '.sql');

								if($buffer === false) {
									JError::raiseWarning(1, JText::_('JLIB_INSTALLER_ERROR_SQL_READBUFFER'));
									return false;
								}

								$queries = JInstallerHelper::splitSql($buffer);

								if(count($queries) == 0)
									continue;

								foreach($queries as $query)
								{
									$query = trim($query);
									if($query != '' && $query{0} != '#')
									{
										$db->setQuery($query);

										if(!$db->query()) {
											JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true)));
											return false;
										}

										$update_count++;
									}
								}
							}
						}
					}

					$query = $db->getQuery(true);
					$query->delete()
						->from('#__schemas')
						->where('extension_id = ' . $eid);
					$db->setQuery($query);

					if($db->Query())
					{
						$query->clear();
						$query->insert($db->quoteName('#__schemas'));
						$query->columns(array($db->quoteName('extension_id'), $db->quoteName('version_id')));
						$query->values($eid . ', ' . $db->quote(end($files)));
						$db->setQuery($query);
						$db->Query();
					}
				}
			}
		}

		return $update_count;
	}

	public function parseFiles($element, $cid = 0, $oldFiles = null, $oldMD5 = null)
	{
		if(!$element || !count($element->children()))
			return 0;

		$copyfiles = array();
		$client = JApplicationHelper::getClientInfo($cid);

		if($client) {
			$pathname    = 'extension_' . $client->name;
			$destination = $this->getPath($pathname);
		}
		else {
			$pathname    = 'extension_root';
			$destination = $this->getPath($pathname);
		}

		$folder = (string) $element->attributes()->folder;
      
		if($folder && file_exists($this->getPath('source') . '/' . $folder))
			$source = $this->getPath('source') . '/' . $folder;
		else
			$source = $this->getPath('source');

		if($oldFiles && ($oldFiles instanceof JXMLElement))
		{
			$oldEntries = $oldFiles->children();

			if(count($oldEntries))
			{
				$deletions = $this->findDeletedFiles($oldEntries, $element->children());

				foreach($deletions['folders'] as $deleted_folder) {
					JFolder::delete($destination . '/' . $deleted_folder);
				}

				foreach($deletions['files'] as $deleted_file) {
					JFile::delete($destination . '/' . $deleted_file);
				}
			}
		}

		if(file_exists($source . '/MD5SUMS'))
		{
			$path['src']  = $source . '/MD5SUMS';
			$path['dest'] = $destination . '/MD5SUMS';
			$path['type'] = 'file';
			$copyfiles[]  = $path;
		}

		foreach ($element->children() as $file)
		{
			$path['src']  = $source . '/' . $file;
			$path['dest'] = $destination . '/' . $file;

			$path['type'] = ($file->getName() == 'folder') ? 'folder' : 'file';

			if(basename($path['dest']) != $path['dest'])
			{
				$newdir = dirname($path['dest']);

				if(!JFolder::create($newdir)) {
					JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_CREATE_DIRECTORY', $newdir));
					return false;
				}
			}

			$copyfiles[] = $path;
		}

		return $this->copyFiles($copyfiles);
	}

	public function parseLanguages($element, $cid = 0)
	{
		if(!$element || !count($element->children()))
			return 0;

		$copyfiles   = array();
		$client      = JApplicationHelper::getClientInfo($cid);
		$destination = $client->path . '/language';

		$folder = (string) $element->attributes()->folder;

		if($folder && file_exists($this->getPath('source') . '/' . $folder))
			$source = $this->getPath('source') . '/' . $folder;
		else
			$source = $this->getPath('source');

		foreach($element->children() as $file)
		{
			if((string) $file->attributes()->tag != '')
			{
				$path['src'] = $source . '/' . $file;

				if((string) $file->attributes()->client != '') {
					$langclient   = JApplicationHelper::getClientInfo((string) $file->attributes()->client, true);
					$path['dest'] = $langclient->path . '/language/' . $file->attributes()->tag . '/' . basename((string) $file);
				}
				else
					$path['dest'] = $destination . '/' . $file->attributes()->tag . '/' . basename((string) $file);

				if(!JFolder::exists(dirname($path['dest'])))
					continue;
			}
			else {
				$path['src']  = $source . '/' . $file;
				$path['dest'] = $destination . '/' . $file;
			}

			if(basename($path['dest']) != $path['dest'])
			{
				$newdir = dirname($path['dest']);

				if(!JFolder::create($newdir)) {
					JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_CREATE_DIRECTORY', $newdir));
					return false;
				}
			}

			$copyfiles[] = $path;
		}

		return $this->copyFiles($copyfiles);
	}

	public function parseMedia($element, $cid = 0)
	{
		if(!$element || !count($element->children()))
			return 0;

		$copyfiles = array();
		$client = JApplicationHelper::getClientInfo($cid);

		$folder     = ((string) $element->attributes()->destination) ? '/' . $element->attributes()->destination : null;
		$destination = JPath::clean(JPATH_ROOT . '/media' . $folder);

		$folder = (string) $element->attributes()->folder;

		if($folder && file_exists($this->getPath('source') . '/' . $folder))
			$source = $this->getPath('source') . '/' . $folder;
		else
			$source = $this->getPath('source');

		foreach($element->children() as $file)
		{
			$path['src']  = $source . '/' . $file;
			$path['dest'] = $destination . '/' . $file;
			$path['type'] = ($file->getName() == 'folder') ? 'folder' : 'file';

			if(basename($path['dest']) != $path['dest'])
			{
				$newdir = dirname($path['dest']);

				if(!JFolder::create($newdir)) {
					JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_CREATE_DIRECTORY', $newdir));
					return false;
				}
			}

			$copyfiles[] = $path;
		}

		return $this->copyFiles($copyfiles);
	}

	public function getParams()
	{
		if(!isset($this->getManifest()->config->fields->fieldset))
			return '{}';

		$fieldsets = $this->getManifest()->config->fields->fieldset;

		$ini = array();

		foreach($fieldsets as $fieldset)
		{
			if(!count($fieldset->children()))
				return null;

			foreach($fieldset as $field)
			{
				if(($name = $field->attributes()->name) === null)
					continue;

				if(($value = $field->attributes()->default) === null)
					continue;

				$ini[(string) $name] = (string) $value;
			}
		}

		return json_encode($ini);
	}

	public function copyFiles($files, $overwrite = null)
	{
		if(is_null($overwrite) || !is_bool($overwrite))
			$overwrite = $this->getOverwrite();

		if(is_array($files) && count($files) > 0)
		{
			foreach($files as $file)
			{
				$filesource = JPath::clean($file['src']);
				$filedest   = JPath::clean($file['dest']);
				$filetype   = array_key_exists('type', $file) ? $file['type'] : 'file';

				if(!file_exists($filesource)) {
					JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_NO_FILE', $filesource));
					return false;
				}
				elseif(($exists = file_exists($filedest)) && !$overwrite)
				{
					if($this->getPath('manifest') == $filesource)
						continue;
 
					JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_FILE_EXISTS', $filedest));

					return false;
				}
				else
				{
					if($filetype == 'folder')
					{
						if(!(JFolder::copy($filesource, $filedest, null, $overwrite))) {
							JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_FAIL_COPY_FOLDER', $filesource, $filedest));
							return false;
						}

						$step = array('type' => 'folder', 'path' => $filedest);
					}
					else
					{
						if(!(JFile::copy($filesource, $filedest, null))) {
							JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_FAIL_COPY_FILE', $filesource, $filedest));
							return false;
						}

						$step = array('type' => 'file', 'path' => $filedest);
					}

					if(!$exists)
						$this->pushStep($step);
				}
			}
		}
		else
			return false;

		return count($files);
	}

	public function removeFiles($element, $cid = 0)
	{
		if(!$element || !count($element->children()))
			return true;

		$removefiles = array();
		$retval      = true;

		$debug = false;
		if(isset($GLOBALS['installerdebug']) && $GLOBALS['installerdebug'])
			$debug = true;

		if($cid > -1)
			$client = JApplicationHelper::getClientInfo($cid);
		else
			$client = null;

		$files = $element->children();

		if(count($files) == 0)
			return true;

		$folder = '';

		switch($element->getName())
		{
			case 'media':
				if((string) $element->attributes()->destination)
					$folder = (string) $element->attributes()->destination;
				else
					$folder = '';

				$source = $client->path . '/media/' . $folder;

				break;

			case 'languages':
				$lang_client = (string) $element->attributes()->client;

				if($lang_client) {
					$client = JApplicationHelper::getClientInfo($lang_client, true);
					$source = $client->path . '/language';
				}
				else
				{
					if($client)
						$source = $client->path . '/language';
					else
						$source = '';
				}

				break;

			default:
				if($client) {
					$pathname = 'extension_' . $client->name;
					$source   = $this->getPath($pathname);
				}
				else {
					$pathname = 'extension_root';
					$source   = $this->getPath($pathname);
				}

				break;
		}

		foreach($files as $file)
		{
			if($file->getName() == 'language' && (string) $file->attributes()->tag != '')
			{
				if($source)
					$path = $source . '/' . $file->attributes()->tag . '/' . basename((string) $file);
				else {
					$target_client = JApplicationHelper::getClientInfo((string) $file->attributes()->client, true);
					$path          = $target_client->path . '/language/' . $file->attributes()->tag . '/' . basename((string) $file);
				}

				if(!JFolder::exists(dirname($path)))
					continue;
			}
			else
				$path = $source . '/' . $file;


			if(is_dir($path))
				$val = JFolder::delete($path);
			else
				$val = JFile::delete($path);

			if($val === false) {
				JError::raiseWarning(43, 'Failed to delete ' . $path);
				$retval = false;
			}
		}

		if(!empty($folder))
			$val = JFolder::delete($source);

		return $retval;
	}

	public function copyManifest($cid = 1)
	{
		$client = JApplicationHelper::getClientInfo($cid);

		$path['src'] = $this->getPath('manifest');

		if($client) {
			$pathname     = 'extension_' . $client->name;
			$path['dest'] = $this->getPath($pathname) . '/' . basename($this->getPath('manifest'));
		}
		else {
			$pathname     = 'extension_root';
			$path['dest'] = $this->getPath($pathname) . '/' . basename($this->getPath('manifest'));
		}

		return $this->copyFiles(array($path), true);
	}

	public function findManifest()
	{
		$xmlfiles = JFolder::files($this->getPath('source'), '.xml$', 1, true);

		if(!empty($xmlfiles))
		{
			foreach($xmlfiles as $file)
			{
				$manifest = $this->isManifest($file);

				if(!is_null($manifest))
				{   
					if((string) $manifest->attributes()->method == 'upgrade') {
						$this->setUpgrade(true);
						$this->setOverwrite(true);
					}

					if((string) $manifest->attributes()->overwrite == 'true')
						$this->setOverwrite(true);

					$this->excavator->manifest = $manifest;
					$this->setPath('manifest', $file);

					$this->setPath('source', dirname($file));

					return true;
				}
			}

			JError::raiseWarning(1, JText::_('JLIB_INSTALLER_ERROR_NOTFINDJOOMLAXMLSETUPFILE'));

			return false;
		}
		else {
			JError::raiseWarning(1, JText::_('JLIB_INSTALLER_ERROR_NOTFINDXMLSETUPFILE'));
			return false;
		}    
		
		return true;
	}

	public function isManifest($file)
	{   
    $xml = JFactory::getXML($file);

    if(!$xml)
      return null;
    if($xml->getName() != 'install' && $xml->getName() != 'extension')
      return null;

    return $xml;	
	}

	public function generateManifestCache()
	{
		return json_encode(JApplicationHelper::parseXMLInstallFile($this->getPath('manifest')));
	}

	public function findDeletedFiles($old_files, $new_files)
	{
		$files           = array();
		$folders         = array();
		$containers      = array();
		$files_deleted   = array();
		$folders_deleted = array();

		foreach($new_files as $file)
		{
			switch($file->getName())
			{
				case 'folder':
					$folders[] = (string) $file;
					break;

				case 'file':
				default:
					$files[]         = (string) $file;
					$container_parts = explode('/', dirname((string) $file));
					$container       = '';

					foreach($container_parts as $part)
					{
						if(!empty($container))
							$container .= '/';       
							
						$container .= $part; 
						if(!in_array($container, $containers))
							$containers[] = $container; // add the container if it doesn't already exist
					}    
					
					break;
			}
		}

		foreach($old_files as $file)
		{
			switch($file->getName())
			{
				case 'folder':
					if(!in_array((string) $file, $folders)) {
						if(!in_array((string) $file, $containers))
							$folders_deleted[] = (string) $file;
					}
					break;

				case 'file':
				default:
					if(!in_array((string) $file, $files)) {
						if(!in_array(dirname((string) $file), $folders))
							$files_deleted[] = (string) $file; 
					}
					break;
			}
		}

		return array('files' => $files_deleted, 'folders' => $folders_deleted);
	}

	public function loadMD5Sum($filename)
	{
		if(!file_exists($filename))
			return false;

		$data   = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$retval = array();

		foreach($data as $row)
		{
			$results             = explode('  ', $row); 
			$results[1]          = str_replace('./', '', $results[1]); 
			$retval[$results[1]] = $results[0]; 
		}

		return $retval;
	}  
	
	public function installedArtifact($artifact)
	{
    if($ext->load(array($name => $artifact->ext_name)));    
      return true;  
            
    return false;
	} 
	
	public static function installedArtifacts($artifacts)
	{
	  $ext = JTable::getInstance('extension');
		
	  foreach($artifacts as $artifact) {      
	    if(!$ext->load(array($name => $artifact->ext_name)))
  	    return false;
	  }
	  
	  return true;
	}
}
<?php 

namespace forge\excavate\updater;

use forge\excavate\updater;

class Language extends \forge\excavate\cores\Language
{        
  public function _init()
  {    
    parent::_init();  
          
    $source = $this->getPath('source');

		if(!$source)
		{
			$this->setPath('source', 
  			($this->extension->client_id ? JPATH_ADMINISTRATOR : JPATH_SITE) . '/language/' . $this->extension->element
			);
		}

    $this->manifest = $this->getManifest();   
    $this->root     = $this->manifest->document;   
  }  
  
  public function task_update()
  {
    return $this->update();
  }
}
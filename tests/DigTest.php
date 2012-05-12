<?php     

require_once dirname(__FILE__) . DS . 'mocks' . DS . 'Component.php';
require_once dirname(__FILE__) . DS . 'mocks' . DS . 'Library.php';
require_once dirname(__FILE__) . DS . 'mocks' . DS . 'Module1.php';
require_once dirname(__FILE__) . DS . 'mocks' . DS . 'Module2.php';
require_once dirname(__FILE__) . DS . 'mocks' . DS . 'Plugin.php';
require_once dirname(__FILE__) . DS . 'mocks' . DS . 'Template.php';      

require_once dirname(__FILE__) . DS . 'mocks' . DS . 'ComponentUpdate.php';
require_once dirname(__FILE__) . DS . 'mocks' . DS . 'LibraryUpdate.php';
require_once dirname(__FILE__) . DS . 'mocks' . DS . 'Module1Update.php';
require_once dirname(__FILE__) . DS . 'mocks' . DS . 'PluginUpdate.php';
require_once dirname(__FILE__) . DS . 'mocks' . DS . 'TemplateUpdate.php';      

require_once dirname(__FILE__) . DS . 'mocks' . DS . 'ComponentUninstall.php';
require_once dirname(__FILE__) . DS . 'mocks' . DS . 'LibraryUninstall.php';
require_once dirname(__FILE__) . DS . 'mocks' . DS . 'Module1Uninstall.php';
require_once dirname(__FILE__) . DS . 'mocks' . DS . 'PluginUninstall.php';
require_once dirname(__FILE__) . DS . 'mocks' . DS . 'TemplateUninstall.php';

define('_Forge', 'magic');
require_once 'forge.php';

class DigTest extends PHPUnit_Framework_TestCase  
{    
  public $componentMock;
  public $libraryMock;  
  public $moduleMock1;  
  public $moduleMock2;  
  public $pluginMock;   
  public $templateMock;    
   
  public $componentMockUpdate;
  public $libraryMockUpdate;  
  public $moduleMockUpdate1;  
  public $pluginMockUpdate;   
  public $templateMockUpdate;   
  
  public $componentMockUninstall;
  public $libraryMockUninstall;  
  public $moduleMockUninstall1;  
  public $pluginMockUninstall;   
  public $templateMockUninstall;
  
  public $artifacts_install;
  public $artifacts_update;
  public $dig_install;    
  public $dig_update; 
  public $dig_uninstall;
  
  public function setup()
  {
    $this->componentMock  = new ComponentMock();  
    $this->libraryMock    = new LibraryMock(); 
    $this->moduleMock1    = new ModuleMock1(); 
    $this->moduleMock2    = new ModuleMock2(); 
    $this->pluginMock     = new PluginMock(); 
    $this->templateMock   = new TemplateMock();    
    
    $this->componentMockUpdate  = new ComponentMockUpdate();  
    $this->libraryMockUpdate    = new LibraryMockUpdate(); 
    $this->moduleMockUpdate1    = new ModuleMockUpdate1(); 
    $this->pluginMockUpdate     = new PluginMockUpdate(); 
    $this->templateMockUpdate   = new TemplateMockUpdate();   
    
    $this->componentMockUninstall  = new ComponentMockUninstall();  
    $this->libraryMockUninstall    = new LibraryMockUninstall(); 
    $this->moduleMockUninstall1    = new ModuleMockUninstall1(); 
    $this->pluginMockUninstall     = new PluginMockUninstall(); 
    $this->templateMockUninstall   = new TemplateMockUninstall();
    
    $this->artifacts_install = array($this->componentMock, $this->libraryMock, $this->moduleMock1,
      $this->pluginMock, $this->templateMock 
    );    
    
    $this->artifacts_update = array($this->componentMockUpdate, $this->moduleMockUpdate1,
      $this->pluginMockUpdate, $this->templateMockUpdate 
    );     
    
    $this->artifacts_uninstall = array($this->componentMockUninstall, $this->moduleMockUninstall1,
      $this->pluginMockUninstall, $this->templateMockUninstall 
    );    
    
    $this->dig_install = new \forge\core\Dig($this->artifacts_install);      
  }
  
  public function testCreateInstance()
  {
    $dig = new \forge\core\Dig($this->artifacts_install);
    $this->assertInstanceOf('\forge\core\Dig', $dig);          
    unset($dig);
    \JFolder::delete(FORGE_TMP_PATH);   
  }
  
  public function testGetTasks()
  {       
    $dig = new \forge\core\Dig($this->artifacts_install);  
    $dig->tasks->getTasksFromExcavations();
    $this->assertGreaterThan(1, $dig->tasks->total);   
    unset($dig);
    \JFolder::delete(FORGE_TMP_PATH);   
  }  
  public function testStatusInstance()
  {             
    $dig = new \forge\core\Dig($this->artifacts_install);
    $this->assertInstanceOf('forge\core\dig\Status', $dig->status); 
    unset($dig);  
   \JFolder::delete(FORGE_TMP_PATH);        
  }  
  
  public function testSerialize()
  {
    $dig = new \forge\core\Dig($this->artifacts_install);    
    $dig->status->serialize();       
    $this->assertTrue(file_exists(FORGE_TMP_PATH . DS . 'dig_status'));  
    unset($dig);  
    \JFolder::delete(FORGE_TMP_PATH);   
  } 
  
  public function testHasExcavations()
  {  
    $dig = new \forge\core\Dig($this->artifacts_install);
    $this->assertGreaterThan(0, count($dig->ex->excavations));    
    unset($dig);          
    \JFolder::delete(FORGE_TMP_PATH);   
  } 
  
  public function testPause()
  {         
    $dig = new \forge\core\Dig($this->artifacts_install);
    $dig->pause();
    $this->assertTrue(file_exists(FORGE_TMP_PATH . DS .'dig_restart_needed'));    
    unlink(FORGE_TMP_PATH . DS .'dig_restart_needed');    
    unset($dig);    
    \JFolder::delete(FORGE_TMP_PATH);   
  }  
           
  
  public function testInstallArtifacts()
  {   
    $this->dig_install->run();
    $this->assertTrue($this->dig_install->status->finished);
    $this->assertTrue(\forge\excavate\Installer::installedArtifacts($this->artifacts_install));       
    unset($this->dig_install);
    \JFolder::delete(FORGE_TMP_PATH);
    
    $this->dig_update = new \forge\core\Dig($this->artifacts_update);   
    $this->dig_update->run();
    $this->assertTrue($this->dig_update->status->finished);
    $this->assertTrue(\forge\excavate\Installer::installedArtifacts($this->artifacts_update));    
    unset($this->dig_update);     
    \JFolder::delete(FORGE_TMP_PATH);

    $this->dig_uninstall = new \forge\core\Dig($this->artifacts_uninstall);
    $this->dig_uninstall->run();
    $this->assertTrue($this->dig_uninstall->status->finished);
    $this->assertFalse(\forge\excavate\Installer::installedArtifacts($this->artifacts_uninstall));   
    unset($this->dig_uninstall); 
    \JFolder::delete(FORGE_TMP_PATH);
  }           
}
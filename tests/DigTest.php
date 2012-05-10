<?php     

require_once dirname(__FILE__) . DS . 'mocks' . DS . 'Component.php';
require_once dirname(__FILE__) . DS . 'mocks' . DS . 'Library.php';
require_once dirname(__FILE__) . DS . 'mocks' . DS . 'Module1.php';
require_once dirname(__FILE__) . DS . 'mocks' . DS . 'Module2.php';
require_once dirname(__FILE__) . DS . 'mocks' . DS . 'Plugin.php';
require_once dirname(__FILE__) . DS . 'mocks' . DS . 'Template.php';   
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
  public $artifacts;
  public $dig;
  
  public function setup()
  {
    $this->componentMock  = new ComponentMock();  
    $this->libraryMock    = new LibraryMock(); 
    $this->moduleMock1    = new ModuleMock1(); 
    $this->moduleMock2    = new ModuleMock2(); 
    $this->pluginMock     = new PluginMock(); 
    $this->templateMock   = new TemplateMock();     
    
    $this->artifacts = array($this->componentMock, $this->libraryMock, $this->moduleMock1,
      $this->pluginMock, $this->templateMock 
    );       
    
    $this->dig = new \forge\core\Dig($this->artifacts);
  }
  
  public function testCreateInstance()
  {
    $dig = new \forge\dig\Dig($this->artifacts);
    $this->assertInstanceOf('Dig', $dig);  
    unset($dig);       
  }
  
  # public function testGetTasks()
  # {       
  #   $dig = new \forge\dig\Dig($this->artifacts);  
  #   $dig->ex->addAll();         
  #   $dig->tasks->getTasksFromExcavations();
  #   $this->assertGreaterThan(1, $dig->tasks->total);
  # }  
  # 
  # public function testStatusInstance()
  # {             
  #   $dig = new \forge\dig\Dig($this->artifacts);
  #   $this->assertInstanceOf('Status', $dig->status); 
  #   unset($dig);       
  # }  
  # 
  # public function testSerialize()
  # {
  #   $this->dig->serialize();
  #   $this->assertTrue(file_exists(FORGE_TMP_PATH . DS . 'dig_status'));
  # } 
  # 
  # public function testHasExcavations()
  # {  
  #   $dig = new \forge\dig\Dig($this->artifacts);
  #   $dig->ex->addAll();    
  #   $this->assertGreaterThan(0, count($dig->ex->excavations));    
  #   unset($dig);
  # }   
  
  # public function testRunDig()
  # {
  #   $this->dig->run();
  #   $this->assertTrue($this->dig->status->finished);
  #   $this->assertTrue(\forge\excavator\Installer::installedArtifacts($this->artifacts));
  # }    
  # 
  # public function testPause()
  # {
  #   $this->dig->pause();
  #   $this->assertTrue(file_exists(FORGE_TMP_PATH . DS .'dig_restart_needed'));
  # }
  # 
  # public function testInstallAndUninstallArtifact()
  # { 
  #   $dig = new \forge\dig\Dig(array($this->moduleMock2)); 
  #   $dig->run();    
  #   $this->assertTrue($dig->status->finished);
  #   $this->assertTrue(\forge\excavator\Installer::installedArtifact($this->moduleMock2));
  # }
} # 
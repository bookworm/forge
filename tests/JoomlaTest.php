<?php   

jimport('joomla.database.database');  

class JoomlaTest extends PHPUnit_Framework_TestCase
{ 
  public function testJoomlaDBInstance()
  {
    $db = JFactory::getDbo();   
    $this->assertInstanceOf('JDatabaseMySQLi', $db);
  }
}
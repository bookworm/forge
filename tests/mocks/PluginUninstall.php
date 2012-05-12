<?php

class PluginMockUninstall
{
  public $name              = 'Demo Plugin';
  public $ext_name          = 'Demo Plugin';        
  public $db_name           = 'demo'; 
  public $slug              = 'plugin_demo';  
  public $group             = 'system';
  public $type              = 'plugin';
  public $desc              = 'Awesome demo plugin desc';
  public $intro             = 'Awesome demo plugin intro';
  public $version           = '0.0.1';
  public $package_uri       = 'File//';  
  public $package_name      = 'plugin_demo-0.0.1';
  public $homepage          = 'designbreakdown.com'; 
  public $vulnerabilities   = array();
  public $incompatibilities = array();
  public $compatibilities   = array();
  public $dependencies      = array();
  public $integrations      = array();  
  public $uninstall         = true;
}
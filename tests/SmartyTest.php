<?php

require dirname(__FILE__).'/../vendor/autoload.php';

class Configuration {
	public static function get($key)
	{
		return $key;
	}
}

class SmartyTest extends PHPUnit_Framework_TestCase
{
	public function testAdminTemplate()
	{
		$s = new Smarty();
		$s->assign('module_dir', '');
		$s->assign('pw_signup_url', '');
		$s->setTemplateDir(dirname(__FILE__).'/../views/templates/');
		$output = $s->fetch('admin/configure.tpl');
		$this->assertNotEmpty($output);
	}

	public function testButtonTemplate()
	{
		$s = new Smarty();
		$s->assign('pw_api_key', '');
		$s->setTemplateDir(dirname(__FILE__).'/../views/templates/');
		$output = $s->fetch('hook/widget.tpl');
		$this->assertNotEmpty($output);
	}
}

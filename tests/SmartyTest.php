<?php
/**
* 2014-2015 PriceWaiter LLC
*
* The MIT License (MIT)
*
* Copyright (c) <year> <copyright holders>
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in
* all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
* THE SOFTWARE.
*
*  @author    PriceWaiter LLC <extensions@pricewaiter.com>
*  @copyright 2014-2015 PriceWaiter LLC
*  @license   http://opensource.org/licenses/MIT
*/

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
		$s->assign('pw_button_config_url', '');
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

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

include(dirname(__FILE__).'/pwconfig.php');

if (!defined('_PS_VERSION_'))
exit;

/* protect against PS require our class twice */
if (!class_exists('PriceWaiter'))
{

class PriceWaiter extends PaymentModule
{
	public function __construct()
	{
		$this->name = 'pricewaiter';
		$this->tab = 'pricing_promotion';
		$this->version = '0.9.3';
		$this->author = 'PriceWaiter';
		$this->module_key = '5b42774d38970868478f8b56732f2641';
		$this->bootstrap = true;
		$this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6');
		$this->dependencies = array('blockcart');
		$this->ws_key_query = 'SELECT id_webservice_account FROM '._DB_PREFIX_.
			'webservice_account WHERE description = \'PriceWaiter API callback access\'';

		parent::__construct();

		$this->displayName = $this->l('PriceWaiter - Name Your Price Button');
		$this->description =
			$this->l('PriceWaiter provides a suite of conversion tools such as Name Your Price or Make an Offer buttons.');

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall the PriceWaiter bundle?');

		if (!Configuration::get('PRICEWAITER_API_KEY'))
			$this->warning = $this->l('No PriceWaiter API key!');
	}

	public function install()
	{
		if (Shop::isFeatureActive())
			Shop::setContext(Shop::CONTEXT_ALL);

		$db = Db::getInstance();

		return parent::install() &&
		$this->registerHook('displayProductButtons') &&
		$this->grantWSPermissions(self::generateToken(), $db) &&
		$this->sendPWStoreInfo() &&
		$this->createPWDisableTable($db) &&
		Configuration::updateValue('PRICEWAITER_CUSTOM_CSS', 'text-align:center;padding:10px;');
	}

	public function uninstall()
	{
		$db = Db::getInstance();

		return parent::uninstall()
		&& Configuration::deleteByName('PRICEWAITER_API_KEY')
		&& Configuration::deleteByName('PRICEWAITER_API_ACCESS_TOKEN')
		&& Configuration::deleteByName('PRICEWAITER_SIGNUP_TOKEN')
		&& Configuration::deleteByName('PRICEWAITER_BUTTON_DISABLED')
		&& Configuration::deleteByName('PRICEWAITER_CONVERSION_DISABLED')
		&& Configuration::deleteByName('PRICEWAITER_CUSTOM_CSS')
		&& $this->deleteWSKey($db)
		&& $this->deletePWDisableTable($db);
	}

	public function hookDisplayProductButtons()
	{
		if (!$this->context)
			$this->context = Context::getContext();

		$product = new Product((int)Tools::getValue('id_product'));
		$raw_attributes = Product::getAttributesInformationsByProduct($product->id);
		$attributes = array();
		foreach ($raw_attributes as $attr)
		{
			$attributes[$attr['id_attribute']] = array(
				'label' => $attr['group'],
				'value' => $attr['attribute'],
			);
		}

		$raw_combinations = $this->context->smarty->getTemplateVars('combinations');
		$combinations = array();
		foreach ($raw_combinations as $combo_id => $raw_combo)
		{
			$combo = array(
				'attributes' => $raw_combo['attributes'],
				'specific_price' => $raw_combo['specific_price'],
				'quantity' => $raw_combo['quantity'],
			);
			if ($raw_combo['id_image'] > 0)
			{
				$img_id = (Configuration::get('PS_LEGACY_IMAGES') ? ($product->id.'-'.$raw_combo['id_image']) : $raw_combo['id_image']);
				$image_type = $product->link_rewrite[$this->context->cart->id_lang];
				$combo['img_link'] = $this->context->link->getImageLink($image_type, $img_id);
			}
			$combinations[$combo_id] = $combo;
		}

		$images = $product->getImages($this->context->cart->id_lang);
		$cover_id = (Configuration::get('PS_LEGACY_IMAGES') ? ($product->id.'-'.$images[0]['id_image']) : $images[0]['id_image']);
		foreach ($images as $img)
		{
			if ($img['cover'] == 1)
			{
				$cover_id = (Configuration::get('PS_LEGACY_IMAGES') ? ($product->id.'-'.$img['id_image']) : $img['id_image']);
				break;
			}
		}

		$product_name = $product->name;
		// PS 1.5 compat
		if (is_array($product_name))
			$product_name = array_shift($product_name);

		$category = $this->context->smarty->getTemplateVars('category');

		$sql = 'SELECT * FROM '._DB_PREFIX_.'pw_disable
			WHERE (`id_object` = '.$product->id.' AND `object_type` = \'product\')
			OR (`id_object` = '.$category->id.' AND `object_type` = \'category\');';

		$disable_rules = Db::getInstance()->executeS($sql);

		$enable_button = 1;
		$enable_conversion_tools = 1;
		foreach ($disable_rules as $rule)
		{
			if ($rule['pw_feature'] === 'button')
				$enable_button = 0;
			if ($rule['pw_feature'] === 'conversion_tools')
				$enable_conversion_tools = 0;
		}
		if (Configuration::get('PRICEWAITER_BUTTON_DISABLED'))
			$enable_button = 0;
		if (Configuration::get('PRICEWAITER_CONVERSION_DISABLED'))
			$enable_conversion_tools = 0;

		$image_type = $product->link_rewrite[$this->context->cart->id_lang];

		# ensure JS sees these as falsy if empty
		if (count($attributes) == 0)
			$attributes = false;

		if (count($combinations) == 0)
			$combinations = false;

		$smarty_properties = array(
			'pw_api_key' => Configuration::get('PRICEWAITER_API_KEY'),
			'pw_product_name' => $product_name,
			'pw_product' => $product,
			'pw_combinations' => strip_tags(Tools::jsonEncode($combinations)),
			'pw_attributes' => strip_tags(Tools::jsonEncode($attributes)),
			'pw_widget_host' => _PW_WIDGET_SERVER_,
			'pw_default_image' => $this->context->link->getImageLink($image_type, $cover_id),
			'pw_enable_button' => $enable_button,
			'pw_enable_conversion_tools' => $enable_conversion_tools,
			'pw_ps_version' => _PS_VERSION_,
		);

		if (Configuration::get('PRICEWAITER_CUSTOM_CSS')) {
			$custom_css = Configuration::get('PRICEWAITER_CUSTOM_CSS');
			$custom_css = str_replace(array('\r\n', '\n', '\r'), '', htmlspecialchars(strip_tags($custom_css)));
			$custom_css = htmlspecialchars($custom_css);
			$this->context->smarty->assign(array('pw_custom_css' => Configuration::get('PRICEWAITER_CUSTOM_CSS')));
		}

		$this->context->smarty->assign($smarty_properties);

		return $this->display(__FILE__, 'views/templates/hook/widget.tpl');
	}

	private function grantWSPermissions($key, $db)
	{
		if (!$this->context)
			$this->context = Context::getContext();

		$id = -1;
		if (false === $this->findOrCreateWSKey($key, $db, $id))
			return false;

		Configuration::updateValue('PRICEWAITER_API_ACCESS_TOKEN', $key);

		Configuration::updateValue('PS_WEBSERVICE', 1);

		$args = array(
			'id_shop' => $this->context->shop->id,
			'id_webservice_account' => $id,
		);

		$db->autoExecute(_DB_PREFIX_.'webservice_account_shop', $args, 'INSERT');

		$query = 'SELECT resource, method FROM '._DB_PREFIX_.'webservice_permission
			WHERE id_webservice_account = '.$db->escape($id, false);

		$existing_permissions = $db->query($query);

		$data = array();
		foreach (self::$permissions as $object => $perms)
		{
			foreach (array_keys($perms) as $perm)
			{
				$row = array(
					'id_webservice_account' => $id,
					'resource' => $object,
					'method' => Tools::strtoupper($perm)
				);

				if (!self::resultContains($row, $existing_permissions, $db))
					array_push($data, $row);
			}
		}

		if (count($data) === 0)
			return true;
		elseif (false !== $db->autoExecute(_DB_PREFIX_.'webservice_permission', $data, 'INSERT'))
			return true;

		return false;
	}

	private function findOrCreateWSKey($key, $db, &$id)
	{
		$id = $db->getValue($this->ws_key_query);

		if (false !== $id)
		{
			$db->autoExecute(_DB_PREFIX_.'webservice_account', array('key' => $key), 'UPDATE', "id_webservice_account = $id");
			return true;
		}

		$data = array(
			'description' => 'PriceWaiter API callback access',
			'key' => $db->escape($key, false),
			'active' => true
		);

		if (false === $db->autoExecute(_DB_PREFIX_.'webservice_account', $data, 'INSERT'))
			return false;

		$id = $db->getValue($this->ws_key_query);

		return true;
	}

	private function deleteWSKey($db)
	{
		$id = $db->getValue($this->ws_key_query);
		if (false === $id)
			return true; // if no id found, nothing to be done

		$where = 'id_webservice_account = '.$db->escape($id, false);
		if ($db->delete(_DB_PREFIX_.'webservice_permission', $where) && $db->delete(_DB_PREFIX_.'webservice_account', $where))
			return true;

		return false;
	}

	/**
	 * Send store info to PriceWaiter store signup API.
	 * Can silently fail and still proceed with module install.
	 */
	private function sendPWStoreInfo()
	{
		if (!$this->context)
			$this->context = Context::getContext();

		if (!function_exists('curl_init'))
			return true;

		$http = 'http://';
		if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')
			$http = 'https://';

		$ch = curl_init(_PW_API_SERVER_.'/store-signups');

		$store_info = array(
			'platform' => 'prestashop',
			'admin_email' => $this->context->employee->email,
			'admin_first_name' => $this->context->employee->firstname,
			'admin_last_name' => $this->context->employee->lastname,
			'store_name' => $this->context->shop->name,
			'store_url' => $this->context->shop->domain,
			'customer_service_email' => Configuration::get('PS_SHOP_EMAIL'),
			'customer_service_phone' => Configuration::get('PS_SHOP_PHONE'),
			'twitter_handle' => Configuration::get('BLOCKSOCIAL_TWITTER'),
			'store_country' => $this->context->country->iso_code,
			'store_currency' => $this->context->currency->iso_code,
			'prestashop_store_url' => $http.$this->context->shop->domain,
			'prestashop_access_token' => Configuration::get('PRICEWAITER_API_ACCESS_TOKEN'),
		);

		curl_setopt_array($ch, array(
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $store_info,
			CURLOPT_RETURNTRANSFER => true,
		));

		$body = curl_exec($ch);

		if (curl_error($ch))
		{
			curl_close($ch);
			return true;
		}

		$body = Tools::jsonDecode($body);
		if (!isset($body->body->token))
		{
			curl_close($ch);
			return true;
		}

		curl_close($ch);

		Configuration::updateValue('PRICEWAITER_SIGNUP_TOKEN', $body->body->token);

		return true;
	}

	private function createPWDisableTable($db)
	{
		$sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'pw_disable`
			(`id_object` INT UNSIGNED,
			`object_type` VARCHAR(200),
			`pw_feature` VARCHAR(200))';

		$db->query($sql);

		return true;
	}

	private function deletePWDisableTable($db)
	{
		$sql = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'pw_disable`';
		$db->query($sql);

		return true;
	}

	private function createCartRule($cart, $amount_paid)
	{
		$product_list = $cart->getProducts();
		$total = (float)$cart->getOrderTotal(true, Cart::ONLY_PRODUCTS, $product_list, $cart->id_carrier);
		$discount = $total - $amount_paid;

		// reuse existing cart rule
		$cart_rule_id = CartRule::getIdByCode($cart->gift_message);
		if ($cart_rule_id > 0)
			return $cart_rule_id;

		$cart_rule = new CartRule();

		$cart_rule->id_customer = $cart->id_customer;
		$cart_rule->date_from = date('Y-m-d H:i:s', time() - 60);
		$cart_rule->date_to = date('Y-m-d H:i:s', time() + 60);
		$cart_rule->description = 'Discount negotiated on PriceWaiter.';
		$cart_rule->quantity = $product_list[0]['cart_quantity'];
		$cart_rule->quantity_per_user = $product_list[0]['cart_quantity'];
		$cart_rule->reduction_amount = $discount;
		$cart_rule->reduction_currency = $cart->id_currency;
		$cart_rule->reduction_product = $product_list[0]['id_product'];
		$cart_rule->active = true;
		$cart_rule->name = array(Configuration::get('PS_LANG_DEFAULT') => $cart->gift_message);
		$cart_rule->add();

		return (int)$cart_rule->id;
	}

	public function validateOrder($id_cart, $id_order_state, $amount_paid, $payment_method = 'Unknown',
		$message = null, $extra_vars = array(), $currency_special = null, $dont_touch_amount = false,
		$secure_key = false, Shop $shop = null)
	{
		// manually create link between cart_rule and cart
		$cart = new Cart($id_cart);

		$id_cart_rule = $this->createCartRule($cart, $amount_paid);
		$cart->addCartRule($id_cart_rule);

		// 1.5 seems to have trouble in Carrier code with a missing cart in context
		$context = Context::getContext();
		if (!$context->cart)
			$context->cart = $cart;

		if (!$context->currency)
			$context->currency = new Currency($cart->id_currency);

		if (!$context->link)
		{
			$protocol_link = (Configuration::get('PS_SSL_ENABLED') || Tools::usingSecureMode()) ? 'https://' : 'http://';
			$use_ssl = ((isset($this->ssl) && $this->ssl && Configuration::get('PS_SSL_ENABLED')) || Tools::usingSecureMode()) ? true : false;
			$protocol_content = ($use_ssl) ? 'https://' : 'http://';
			$context->link = new Link($protocol_link, $protocol_content);
		}

		// unset gift message used for internal PW id.
		$cart->gift_message = '';
		$cart->save();

		return parent::validateOrder($id_cart, $id_order_state, $amount_paid, $payment_method,
		$message, $extra_vars, $currency_special, $dont_touch_amount,
		$secure_key, $shop);
	}

	public function getContent()
	{
		if (!$this->context) $this->context = Context::getContext();

		$db = Db::getInstance();

		$pw_signup_url = _PW_MANAGE_SERVER_.'/sign-up';
		if (Configuration::get('PRICEWAITER_SIGNUP_TOKEN') !== '')
			$pw_signup_url .= '?token='.Configuration::get('PRICEWAITER_SIGNUP_TOKEN');

		$this->context->smarty->assign(array(
			'pw_signup_url' => $pw_signup_url,
			'pw_manage_url' => _PW_MANAGE_SERVER_,
		));

		$pw_api_key = Configuration::get('PRICEWAITER_API_KEY');
		$output = '';
		$success = true;
		$change = false;

		if (Tools::isSubmit('submit'.$this->name))
		{
			$new_pw_api_key = (string)Tools::getValue('pw_api_key');
			if ($new_pw_api_key !== $pw_api_key)
				$change = true;

			if (empty($new_pw_api_key) || !preg_match('/^[0-9a-zA-Z_=-]+$/', $new_pw_api_key))
			{
				$output .= $this->displayError($this->l('Invalid API key.'));
				$success = false;
			}

			$button_cats = Tools::getValue('pw_button_disabled_cats');
			$button_products = Tools::getValue('pw_button_disabled_products');
			$exit_cats = Tools::getValue('pw_conversion_disabled_cats');
			$exit_products = Tools::getValue('pw_conversion_disabled_products');
			Configuration::updateValue('PRICEWAITER_BUTTON_DISABLED', Tools::getValue('pw_button_disabled'));
			Configuration::updateValue('PRICEWAITER_CONVERSION_DISABLED', Tools::getValue('pw_conversion_disabled'));
			Configuration::updateValue('PRICEWAITER_CUSTOM_CSS', Tools::getValue('pw_custom_css'));

			$pw_disabled_objects = $this->getSelectedObjectsToDisable($button_cats, $button_products, $exit_cats, $exit_products);

			if (count($pw_disabled_objects) > 0)
				$change = true;
		}

		if ($success && $change)
		{
			Configuration::updateValue('PRICEWAITER_API_KEY', $new_pw_api_key);
			$db->query('TRUNCATE TABLE '._DB_PREFIX_.'pw_disable;');
			$db->autoExecute(_DB_PREFIX_.'pw_disable', $pw_disabled_objects, 'INSERT');
			$output .= $this->displayConfirmation($this->l('Settings updated'));
		}

		return $this->display(__FILE__, 'views/templates/admin/configure.tpl').$output.$this->displayForm();
	}

	public function displayForm()
	{
		// Get default Language
		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
		$db = Db::getInstance();

		$product_options = array();
		foreach (Product::getProducts($default_lang, 0, 0, 'id_product', 'asc', false, true, $this->context) as $prod)
		{
			$product_options[] = array(
				'id' => $prod['id_product'],
				'name' => $prod['name'],
			);
		}

		$category_options = array();

		$sql = 'SELECT * FROM `'._DB_PREFIX_.'category` c
			LEFT OUTER JOIN `'._DB_PREFIX_.'category_lang` cl
			ON c.`id_category` = cl.`id_category`
			WHERE cl.`id_lang` = '.$default_lang.'
			ORDER BY c.`id_parent` ASC';
		$categories = $db->executeS($sql);
		foreach ($categories as $cat)
		{
			if ($cat['name'] === 'Root')
				continue;
			$category_options[] = array(
				'id' => $cat['id_category'],
				'name' => $cat['name'],
			);
		}

		// Init Fields form array
		$fields_form = array(
			array('form' => array())
		);
		$switch_type = 'switch';
		if (_PS_VERSION_ < 1.6)
			$switch_type = 'radio';
		$fields_form[0]['form'] = array(
			'legend' => array(
				'title' => $this->l('Settings'),
				'icon' => 'icon-cogs'
			),
			'input' => array(
				array(
					'type' => 'text',
					'label' => $this->l('PriceWaiter API key'),
					'name' => 'pw_api_key',
					'description' => 'Copy the API Key from your PriceWaiter account (on the Store Settings page) to connect.',
					'size' => 40,
					'required' => true
				),
				array(
					'type' => 'select',
					'label' => $this->l('Disable PriceWaiter on these products'),
					'name' => 'pw_button_disabled_products[]',
					'multiple' => true,
					'description' => $this->l('Don\'t display the PriceWaiter button for products in this list.'),
					'options' => array(
						'query' => $product_options,
						'id' => 'id',
						'name' => 'name',
					),
				),
				array(
					'type' => 'select',
					'label' => $this->l('Disable PriceWaiter on these categories'),
					'name' => 'pw_button_disabled_cats[]',
					'multiple' => true,
					'description' => $this->l('Don\'t display the PriceWaiter button for categories in this list.'),
					'options' => array(
						'query' => $category_options,
						'id' => 'id',
						'name' => 'name',
					),
				),
				array(
					'type' => 'select',
					'label' => $this->l('Disable Conversion Tools on these products'),
					'name' => 'pw_conversion_disabled_products[]',
					'multiple' => true,
					'description' => $this->l('Don\'t display PriceWaiter\'s conversion tools for products in this list.'),
					'options' => array(
						'query' => $product_options,
						'id' => 'id',
						'name' => 'name',
					),
				),
				array(
					'type' => 'select',
					'label' => $this->l('Disable Conversion Tools on these categories'),
					'name' => 'pw_conversion_disabled_cats[]',
					'multiple' => true,
					'description' => $this->l('Don\'t display PriceWaiter\'s conversion tools for categories in this list.'),
					'options' => array(
						'query' => $category_options,
						'id' => 'id',
						'name' => 'name',
					),
				),
				array(
					'type' => $switch_type,
					'label' => $this->l('Disable PriceWaiter'),
					'name' => 'pw_button_disabled',
					'description' => 'Disable the PriceWaiter button for all products in your store.',
					'class' => 't',
					'values' => array(
						array(
							'id' => 'button_off',
							'value' => 1,
							'label' => $this->l('Yes')
						),
						array(
							'id' => 'button_on',
							'value' => 0,
							'label' => $this->l('No')
						)
					),
				),
				array(
					'type' => $switch_type,
					'label' => $this->l('Disable Conversion Tools'),
					'name' => 'pw_conversion_disabled',
					'description' => 'Disable PriceWaiter conversion tools for all products in your store.',
					'class' => 't',
					'values' => array(
						array(
							'id' => 'conversion_off',
							'value' => 1,
							'label' => $this->l('Yes'),
						),
						array(
							'id' => 'conversion_on',
							'value' => 0,
							'label' => $this->l('No'),
						),
					),
				),
				array(
					'type' => 'textarea',
					'label' => 'PriceWaiter button styling',
					'name' => 'pw_custom_css',
					'description' => 'Enter CSS to style the wrapper around the PriceWaiter button.',
				)
			),
			'submit' => array(
				'title' => $this->l('Save'),
				'class' => 'button'
			),
		);

		$helper = new HelperForm();

		// Module, token and currentIndex
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

		// Language
		$helper->default_form_language = $default_lang;
		$helper->allow_employee_form_lang = $default_lang;

		// Title and toolbar
		$helper->title = $this->displayName;
		$helper->show_toolbar = true;// false -> remove toolbar
		$helper->toolbar_scroll = true;// yes - > Toolbar is always visible on the top of the screen.
		$helper->submit_action = 'submit'.$this->name;
		$helper->toolbar_btn = array(
			'save' => array(
				'desc' => $this->l('Save'),
				'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
				'&token='.Tools::getAdminTokenLite('AdminModules'),
			),
			'back' => array(
				'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
				'desc' => $this->l('Back to list')
			)
		);

		$helper->fields_value['pw_button_disabled_products[]'] = $this->getDisabledObjects('product', 'button');
		$helper->fields_value['pw_button_disabled_cats[]'] = $this->getDisabledObjects('category', 'button');
		$helper->fields_value['pw_conversion_disabled_products[]'] = $this->getDisabledObjects('product', 'conversion_tools');
		$helper->fields_value['pw_conversion_disabled_cats[]'] = $this->getDisabledObjects('category', 'conversion_tools');
		$helper->fields_value['pw_button_disabled'] = Configuration::get('PRICEWAITER_BUTTON_DISABLED');
		$helper->fields_value['pw_conversion_disabled'] = Configuration::get('PRICEWAITER_CONVERSION_DISABLED');
		$helper->fields_value['pw_custom_css'] = Configuration::get('PRICEWAITER_CUSTOM_CSS');

		// Load current value
		$helper->fields_value['pw_api_key'] = Configuration::get('PRICEWAITER_API_KEY');

		return $helper->generateForm($fields_form);
	}

	private function getDisabledObjects($object_type, $pw_feature)
	{
		$db = Db::getInstance();

		$sql = 'SELECT * FROM `'._DB_PREFIX_.'pw_disable`
			WHERE `object_type` = \''.$object_type.'\' AND `pw_feature` = \''.$pw_feature.'\';';

		$result = $db->executeS($sql);

		if (!$result)
			return array();

		$ids = array();
		foreach ($result as $row)
			$ids[] = $row['id_object'];

		return $ids;
	}

	private static function getSelectedObjectsToDisable($button_disabled_cats, $button_disabled_products,
		$conversion_disabled_cats, $conversion_disabled_products)
	{
		$pw_disabled_objects = array();

		if (is_array($button_disabled_cats))
		{
			foreach ($button_disabled_cats as $cat)
			{
				$pw_disabled_objects[] = array(
					'id_object' => $cat,
					'object_type' => 'category',
					'pw_feature' => 'button',
				);
			}
		}
		if (is_array($button_disabled_products))
		{
			foreach ($button_disabled_products as $prod)
			{
				$pw_disabled_objects[] = array(
					'id_object' => $prod,
					'object_type' => 'product',
					'pw_feature' => 'button',
				);
			}
		}
		if (is_array($conversion_disabled_cats))
		{
			foreach ($conversion_disabled_cats as $cat)
			{
				$pw_disabled_objects[] = array(
					'id_object' => $cat,
					'object_type' => 'category',
					'pw_feature' => 'conversion_tools',
				);
			}
		}
		if (is_array($conversion_disabled_products))
		{
			foreach ($conversion_disabled_products as $prod)
			{
				$pw_disabled_objects[] = array(
					'id_object' => $prod,
					'object_type' => 'product',
					'pw_feature' => 'conversion_tools',
				);
			}
		}

		return $pw_disabled_objects;
	}

	private static function generateToken()
	{
		$token = '';
		$chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$count = 0;
		do {
			$token .= $chars[mt_rand(0, 35)];
			$count++;
		} while ($count < 32);

		return $token;
	}

	private static function resultContains($row, $result, $db)
	{
		while ($result_row = $db->nextRow($result))
		{
			if ($row['resource'] == $result_row['resource'] && $row['method'] == $result_row['method'])
				return true;
		}

		return false;
	}

	private static $permissions = array(
		'addresses' => array(
			'get' => true,
			'put' => true,
			'post' => true,
			'head' => true
		),
		'carriers' => array(
			'get' => true,
			'head' => true
		),
		'carts' => array(
			'get' => true,
			'post' => true,
			'head' => true
		),
		'cart_rules' => array(
			'get' => true,
			'post' => true,
			'head' => true
		),
		'combinations' => array(
			'get' => true,
			'head' => true
		),
		'configurations' => array(
			'post' => true,
			'head' => true
		),
		'countries' => array(
			'get' => true,
			'head' => true
		),
		'currencies' => array(
			'get' => true,
			'head' => true
		),
		'customers' => array(
			'get' => true,
			'put' => true,
			'post' => true,
			'head' => true
		),
		'languages' => array(
			'get' => true,
			'head' => true
		),
		'order_carriers' => array(
			'get' => true,
			'put' => true,
			'post' => true,
			'head' => true
		),
		'order_details' => array(
			'get' => true,
			'put' => true,
			'post' => true,
			'head' => true
		),
		'order_discounts' => array(
			'get' => true,
			'put' => true,
			'post' => true,
			'head' => true
		),
		'order_histories' => array(
			'get' => true,
			'put' => true,
			'post' => true,
			'head' => true
		),
		'order_invoices' => array(
			'get' => true,
			'put' => true,
			'post' => true,
			'head' => true
		),
		'order_payments' => array(
			'get' => true,
			'put' => true,
			'post' => true,
			'head' => true
		),
		'order_slip' => array(
			'get' => true,
			'put' => true,
			'post' => true,
			'head' => true
		),
		'order_states' => array(
			'get' => true,
			'put' => true,
			'post' => true,
			'head' => true
		),
		'orders' => array(
			'get' => true,
			'put' => true,
			'post' => true,
			'head' => true
		),
		'product_feature_values' => array(
			'get' => true,
			'head' => true
		),
		'product_features' => array(
			'get' => true,
			'head' => true
		),
		'product_option_values' => array(
			'get' => true,
			'head' => true
		),
		'product_options' => array(
			'get' => true,
			'head' => true
		),
		'products' => array(
			'get' => true,
			'head' => true
		),
		'specific_price_rules' => array(
			'get' => true,
			'head' => true
		),
		'specific_prices' => array(
			'get' => true,
			'head' => true
		),
		'states' => array(
			'get' => true,
			'head' => true
		),
		'stock_availables' => array(
			'get' => true,
			'put' => true,
			'post' => true,
			'head' => true
		),
		'stocks' => array(
			'get' => true,
			'head' => true
		),
		'tax_rule_groups' => array(
			'get' => true,
			'head' => true
		),
		'tax_rules' => array(
			'get' => true,
			'head' => true
		),
		'taxes' => array(
			'get' => true,
			'head' => true
		)
	);
}

}

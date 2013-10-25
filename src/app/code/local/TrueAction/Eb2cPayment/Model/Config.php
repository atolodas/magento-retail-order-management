<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cPayment_Model_Config extends TrueAction_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'is_payment_enabled' => 'eb2ccore/eb2cpayment/enabled',
		'stored_value_balance_api_uri' => 'eb2c/payment/stored_value_balance_api_uri',
		'stored_value_redeem_api_uri' => 'eb2c/payment/stored_value_redeem_api_uri',
		'stored_value_redeem_void_api_uri' => 'eb2c/payment/stored_value_redeem_void_api_uri',
		'paypal_set_express_checkout_api_uri' => 'eb2c/payment/paypal_set_express_checkout_api_uri',
		'paypal_get_express_checkout_api_uri' => 'eb2c/payment/paypal_get_express_checkout_api_uri',
		'paypal_do_express_checkout_api_uri' => 'eb2c/payment/paypal_do_express_checkout_api_uri',
		'paypal_do_authorization_api_uri' => 'eb2c/payment/paypal_do_authorization_api_uri',
		'paypal_do_void_api_uri' => 'eb2c/payment/paypal_do_void_api_uri',
		'developer_mode' => 'eb2c/payment/developer_mode',
		'enabled_eb2c_debug' => 'eb2c/payment/enabled_eb2c_debug',
		'api_xml_ns' => 'eb2ccore/api/xml_namespace',
		'api_payment_xml_ns' => 'eb2c/payment/api_payment_xml_ns',
		'api_service' => 'eb2c/payment/api_service',
		'api_opt_stored_value_balance' => 'eb2c/payment/api_opt_stored_value_balance',
		'api_opt_stored_value_redeem' => 'eb2c/payment/api_opt_stored_value_redeem',
		'api_opt_stored_value_redeem_void' => 'eb2c/payment/api_opt_stored_value_redeem_void',
		'api_opt_paypal_set_express_checkout' => 'eb2c/payment/api_opt_paypal_set_express_checkout',
		'api_opt_paypal_get_express_checkout' => 'eb2c/payment/api_opt_paypal_get_express_checkout',
		'api_opt_paypal_do_express_checkout' => 'eb2c/payment/api_opt_paypal_do_express_checkout',
		'api_opt_paypal_do_authorization' => 'eb2c/payment/api_opt_paypal_do_authorization',
		'api_opt_paypal_do_void' => 'eb2c/payment/api_opt_paypal_do_void',
		'api_return_format' => 'eb2c/payment/api_return_format',
		'xsd_file_paypal_do_express'        => 'eb2c/payment/xsd/paypal_express_do_file',
		'xsd_file_paypal_get_express'       => 'eb2c/payment/xsd/paypal_express_get_file',
		'xsd_file_paypal_set_express'       => 'eb2c/payment/xsd/paypal_express_set_file',
		'xsd_file_paypal_do_auth'           => 'eb2c/payment/xsd/paypal_do_auth_file',
		'xsd_file_paypal_void_auth'         => 'eb2c/payment/xsd/paypal_void_auth_file',
		'xsd_file_stored_value_balance'     => 'eb2c/payment/xsd/stored_value_balance_file',
		'xsd_file_stored_value_void_redeem' => 'eb2c/payment/xsd/stored_value_redeem_void_file',
		'xsd_file_stored_value_redeem'      => 'eb2c/payment/xsd/stored_value_redeem_file',
	);
}

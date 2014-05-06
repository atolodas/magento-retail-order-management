<?php
require_once(Mage::getModuleDir('controllers', 'EbayEnterprise_Eb2cCore') . DS . 'Exchange/System/Config/ValidateController.php');

class EbayEnterprise_Eb2cCore_Test_Controller_Exchange_System_Config_ValidateControllerTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * The Mage::app instance when starting the tests, stored so that when it is
	 * swapped out during a test it can be put back in place after tests have run.
	 * @var Mage_Core_Model_App
	 */
	protected $_origApp;
	/**
	 * Store the Mage::app before tests have run.
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_origApp = EcomDev_Utils_Reflection::getRestrictedPropertyValue('Mage', '_app');
	}
	/**
	 * Restore Mage::app to the initial value
	 */
	public function tearDown()
	{
		EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_app', $this->_origApp);
		parent::tearDown();
	}
	/**
	 * Provide the request params, whether the "default" value is expected and
	 * the type of the config source expected.
	 * @return array
	 */
	public function provideRequestParams()
	{
		return array(
			array(array('website' => 'default'), true, 'Mage_Core_Model_Store'),
			array(array('website' => 'default'), false, 'Mage_Core_Model_Website'),
			array(array('store' => 'main'), true, 'Mage_Core_Model_Website'),
			array(array('store' => 'main'), false, 'Mage_Core_Model_Store'),
			array(array(), true, 'Mage_Core_Model_Store'),
			array(array(), false, 'Mage_Core_Model_Store'),
		);
	}
	/**
	 * Test getting the configuration source based on request params. Should result
	 * in either a Mage_Core_Model_Store or Mage_Core_Model_Website depending
	 * on the request params.
	 * @param  array $requestParams
	 * @param  string $sourceType Model class that should provide the config data
	 * @test
	 * @dataProvider provideRequestParams
	 */
	public function testGetConfigSource($requestParams, $useDefault, $sourceType)
	{
		$requestMock = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
		$requestMock->setParams($requestParams);
		$responseMock = $this->getMockForAbstractClass('Zend_Controller_Response_Abstract');

		$store = $this->getModelMockBuilder('core/store')
			->setMethods(array('getWebsite'))
			->disableOriginalConstructor()
			->getMock();
		$website = $this->getModelMockBuilder('core/website')
			->disableOriginalConstructor()
			->getMock();

		$store->expects($this->any())
			->method('getWebsite')
			->will($this->returnValue($website));
		$app = $this->getModelMockBuilder('core/app')
			->setMethods(array('getWebsite', 'getStore'))
			->disableOriginalConstructor()
			->getMock();
		$app->expects($this->any())
			->method('getStore')
			->will($this->returnValue($store));
		$app->expects($this->any())
			->method('getWebsite')
			->will($this->returnValue($website));
		EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_app', $app);

		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cCore_Exchange_System_Config_ValidateController')
			->disableOriginalConstructor()
			->setMethods(null)
			->setConstructorArgs(array($requestMock, $responseMock))
			->getMock();

		$this->assertInstanceOf(
			$sourceType,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$controller, '_getConfigSource', array($requestMock, $useDefault)
			)
		);
	}
	public function provideParamsForFallbackTest()
	{
		$paramValue = 'param value';
		$configValue = 'config value';

		return array(
			array(array('param' => $paramValue, 'param_use_default' => '0'), $configValue, $paramValue),
			array(array('param' => $paramValue, 'param_use_default' => '1'), $configValue, $configValue),
			array(array('param' => '', 'param_use_default' => '0'), $configValue, ''),
			array(array('not_the_param' => $paramValue, 'not_the_use_default' => '0'), $configValue, $configValue),
		);
	}
	/**
	 * Test getting the value from the param or config depending on the params
	 * available in the request.
	 * @param array $params Request params
	 * @param string $configValue Value stored in config
	 * @param string $expectedValue Expected output based on given request params
	 * @test
	 * @dataProvider provideParamsForFallbackTest
	 */
	public function testGetParamOrFallbackValue($params, $configValue, $expectedValue)
	{
		$configPath = 'path/to/config/value';
		$paramName = 'param';
		$useDefaultName = 'param_use_default';

		$requestMock = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
		$requestMock->setParams($params);

		$configSource = $this->getModelMockBuilder('core/store')
			->setMethods(array('getConfig'))
			->disableOriginalConstructor()
			->getMock();

		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cCore_Exchange_System_Config_ValidateController')
			->disableOriginalConstructor()
			->setMethods(array('_getConfigSource'))
			->getMock();
		$controller->expects($this->any())
			->method('_getConfigSource')
			->will($this->returnValue($configSource));
		$configSource->expects($this->any())
			->method('getConfig')
			->with($this->identicalTo($configPath))
			->will($this->returnValue($configValue));

		$this->assertSame(
			$expectedValue,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$controller,
				'_getParamOrFallbackValue',
				array($requestMock, $paramName, $useDefaultName, $configPath)
			)
		);
	}
	public function provideApiKeyParams()
	{
		return array(
			array(array('api_key' => 'abcd1234', 'api_key_use_default' => ''), null, 'abcd1234'),
			array(array('api_key' => 'abcd1234', 'api_key_use_default' => '1'), 'core/store', '4321dcba'),
			array(array('api_key' => '******', 'api_key_use_default' => '0'), 'core/website', '4321dcba'),
			array(array('api_key' => '******', 'api_key_use_default' => '1'), 'core/store', '4321dcba'),
			array(array('api_key' => '', 'api_key_use_default' => '1'), 'core/website', ''),
			array(array('api_key' => '', 'api_key_use_default' => '0'), '', ''),
			array(array('api_key_use_default' => '1'), 'core/website', '4321dcba'),
		);
	}
	/**
	 * Test getting the API key to use for the request. It is expected to either
	 * come from the request or configuration. When included in the request, it
	 * should only be used if the use default param is not true and the value is
	 * not the obscured value used by Magento for the encrypted config. When the
	 * key is not in the request, is in the request as the obscured value or the
	 * use default flag is true, the api key should come from config. Additionally
	 * when the config source is a website, should also decrypt the value before
	 * returning it.
	 * @param  array $requestParams
	 * @param  string|null $sourceType Factory alias for the source of config values, either core/store or core/website
	 * @param  string $apiKey Expected API key to be returned
	 * @test
	 * @dataProvider provideApiKeyParams
	 */
	public function testGetApiKey($requestParams, $sourceType, $apiKey)
	{
		$requestMock = $this->getMockForAbstractClass('Zend_Controller_Request_Abstract');
		$requestMock->setParams($requestParams);

		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cCore_Exchange_System_Config_ValidateController')
			->disableOriginalConstructor()
			->setMethods(array('_getConfigSource'))
			->getMock();

		// if there is a source type, expect the value to come from config, so
		// need to set up the model it will be coming from
		if ($sourceType) {
			$configSource = $this->getModelMockBuilder($sourceType)
				->disableOriginalConstructor()
				->setMethods(array('getConfig'))
				->getMock();
			$configSource->expects($this->any())
				->method('getConfig')
				->with($this->identicalTo('eb2ccore/api/key'))
				->will($this->returnValue($apiKey));
			$controller->expects($this->once())
				->method('_getConfigSource')
				->will($this->returnValue($configSource));
		}
		// when coming from a website, the config value needs to be decrypted,
		// this is handled automatically by the store model but not the website
		if ($sourceType === 'core/website') {
			$coreHelper = $this->getHelperMock('core/data', array('decrypt'));
			$coreHelper->expects($this->once())
				->method('decrypt')
				->with($this->identicalTo($apiKey))
				->will($this->returnArgument(0));
			$this->replaceByMock('helper', 'core', $coreHelper);
		}

		$this->assertSame(
			$apiKey,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$controller,
				'_getApiKey',
				array($requestMock)
			)
		);
	}
	/**
	 * Test doign simple validations on API settings. When all are valid, should
	 * simply return self
	 * @test
	 */
	public function testValidateSettings()
	{
		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cCore_Exchange_System_Config_ValidateController')
			->disableOriginalConstructor()
			->getMock();
		$this->assertSame(
			$controller,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$controller,
				'_validateSettings',
				array('STORE_ID', 'API_KEY', 'example.com')
			)
		);
	}
	public function provideSettingsAndExceptions()
	{
		return array(
			array('', '', '', 'Store Id, API Key, API Hostname'),
			array('', '', 'example.com', 'Store Id, API Key'),
			array('', 'apikey-123', 'example.com', 'Store Id'),
		);
	}
	/**
	 * Test doing simple validations on the settings - basically ensure that none
	 * are empty. If any are, an exception should be thrown which includes the
	 * settings that are invalid.
	 * @param  string $storeId
	 * @param  string $apiKey
	 * @param  string $hostname
	 * @param  string $exceptionMessage
	 * @test
	 * @dataProvider provideSettingsAndExceptions
	 */
	public function testValidateInvalidSettings($storeId, $apiKey, $hostname, $exceptionMessage)
	{
		$this->setExpectedException(
			'EbayEnterprise_Eb2cCore_Exception_Api_Configuration',
			$exceptionMessage
		);
		$translationHelper = $this->getHelperMock('eb2ccore/data', array('__'));
		$translationHelper->expects($this->once())
			->method('__')
			->will($this->returnArgument(1));
		$this->replaceByMock('helper', 'eb2ccore', $translationHelper);
		$controller = $this->getMockBuilder('EbayEnterprise_Eb2cCore_Exchange_System_Config_ValidateController')
			->disableOriginalConstructor()
			->getMock();
		EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$controller,
			'_validateSettings',
			array($storeId, $apiKey, $hostname)
		);
	}
}

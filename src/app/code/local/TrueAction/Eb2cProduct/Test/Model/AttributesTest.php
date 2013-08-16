<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cProduct_Test_Model_AttributesTest extends TrueAction_Eb2cCore_Test_Base
{
	public static $modelClass = 'TrueAction_Eb2cProduct_Model_Attributes';

	public function testGetAttributeSetCollection()
	{
		$mock  = $this->getResourceModelMockBuilder('eav/entity_attribute_set_collection')
			->disableOriginalConstructor()
			->getMock();
		$this->replaceByMock('resource_model' ,'eav/entity_attribute_set_collection', $mock);
		$model = Mage::getModel('eb2cproduct/attributes');
		$val   = $this->_reflectMethod($model, '_getAttributeSetCollection')->invoke($model);
		$this->assertInstanceOf('Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection', $val);
	}

	/**
	 * verify a group is returned when successful and null when unsuccessful.
	 * @loadExpectation
	 * @dataProvider dataProvider
	 */
	public function testGetAttributeGroup($groupFound)
	{
		$groupFieldName = 'attribute_group_name';
		$groupName      = 'group';
		$attributeSetId = 1;
		$groupId       = 2;
		$e = $this->expected('%s-%s-%s', $groupName, $attributeSetId, (int)$groupFound);
		$mockCollection = $this->getResourceModelMockBuilder('eav/entity_attribute_group_collection')
			->disableOriginalConstructor()
			->setMethods(array('setAttributeSetFilter', 'load', 'addFieldToFilter', 'getFirstItem'))
			->getMock();
		$mock          = $this->getModelMockBuilder('eav/entity_attribute_group')
			->disableOriginalConstructor()
			->setMethods(array('getResourceCollection', 'getId'))
			->getMock();

		// mock out the collection methods
		$mockCollection->expects($this->once())->method('setAttributeSetFilter')
			->with($this->identicalTo($attributeSetId))
			->will($this->returnSelf());
		$mockCollection->expects($this->once())->method('addFieldToFilter')
			->with($this->identicalTo($groupFieldName), $this->equalTo($e->getGroupNameFilter()))
			->will($this->returnSelf());
		$mockCollection->expects($this->once())->method('load')
			->will($this->returnSelf());
		$mockCollection->expects($this->once())->method('getFirstItem')
			->will($this->returnValue($mock));

		// mock out the model methods
		$mock->expects($this->once())->method('getResourceCollection')
			->will($this->returnValue($mockCollection));
		$mock->expects($this->once())->method('getId')
			->will($this->returnValue($groupFound ? $groupId : null));

		$this->replaceByMock('model' ,'eav/entity_attribute_group', $mock);
		$model = Mage::getModel('eb2cproduct/attributes');
		$val   = $this->_reflectMethod($model, '_getAttributeGroup')->invoke($model, $groupName, $attributeSetId);
		if ($groupFound) {
			$this->assertInstanceOf('Mage_Eav_Model_Entity_Attribute_Group', $val);
		} else {
			$this->assertNull($val);
		}
	}

	public function _testGetModelPrototype()
	{
		$config = Mage::getModel('core/config');
		$config->loadString(self::$configXml);
	}

	/**
	 * verify a the model field name is returned when it is defined in the map
	 * and the input field name is returned if not in the map.
	 * @dataProvider dataProvider
	 */
	public function testGetMappedFieldName($fieldName, $expected)
	{
		$map   = array('field_in_map' => 'model_field_name');
		$model = Mage::getModel('eb2cproduct/attributes');
		$this->_reflectProperty($model, '_fieldNameMap')->setValue($model, $map);
		$modelFieldName = $this->_reflectMethod($model, '_getMappedFieldName')
			->invoke($model, $fieldName);
		$this->assertSame($expected, $modelFieldName);
	}

	/**
	 * verify a the function returns a value in the correct format for the field as
	 * per the mapping
	 * @dataProvider dataProvider
	 */
	public function testGetMappedFieldValue($fieldName, $data, $expected)
	{
		$xml      = "<?xml version='1.0'?>\n<{$fieldName}>{$data}</{$fieldName}>";
		$dataNode = new Varien_SimpleXml_Element($xml);
		$model    = Mage::getModel('eb2cproduct/attributes');
		$value    = $this->_reflectMethod($model, '_getMappedFieldValue')
			->invoke($model, $fieldName, $dataNode);
		$this->assertSame($expected, $value);
	}

	/**
	 * verify a the correct field name for the frontend type is returned.
	 * @dataProvider dataProvider
	 */
	public function testGetDefaultValueFieldName($frontendType, $expected)
	{
		$model    = Mage::getModel('eb2cproduct/attributes');
		$value    = $this->_reflectMethod($model, '_getDefaultValueFieldName')
			->invoke($model, $frontendType);
		$this->assertSame($expected, $value);
	}

	/**
	 * ensure the _getOrCreateFunction returns a model we expect.
	 */
	public function testGetOrCreateAttribute()
	{
		$this->markTestIncomplete();
		$code = 'tax_code';
		$model = Mage::getModel('eb2cproduct/attributes');
		$getOrCreateAttribute = $this->_reflectMethod($model, '_getOrCreateAttribute');
		$attrModel = $getOrCreateAttribute->invoke($model, $code);
		$this->assertInstanceOf(self::$modelClass, $attrModel);
	}

	public function callbackGetModuleDir($dir, $module)
	{
		$vfs = $this->getFixture()->getVfs();
		$url = $vfs->url('app/code/local/TrueAction');
		return $url . DS . $module . DS . 'etc';
	}

	/**
	 * verify the default config in the config.xml can be overridden by another xml file.
	 * @loadExpectation attributesConfig.yaml
	 * @dataProvider provideOverrideXmlVfsStructure
	 */
	public function testLoadDefaultAttributesConfig($expectation, $vfsStructure)
	{
		$model  = Mage::getModel('eb2cproduct/attributes');
		$config = $this->_reflectMethod($model, '_loadDefaultAttributesConfig')->invoke($model);
		$this->assertInstanceOf('Mage_Core_Model_Config', $config);
		$e           = $this->expected($expectation);
		$configArray = $config->getNode('default')->asArray();
		$this->assertSame($e->getData('tax_code'), $configArray['tax_code']);
	}

	/**
	 * verify a list of default codes is generated from the config.
	 * @loadExpectation testGetDefaultAttributesCodeList.yaml
	 */
	public function testGetDefaultAttributesCodeList()
	{
		$model  = Mage::getModel('eb2cproduct/attributes');
		$fn     = $this->_reflectMethod($model, 'getDefaultAttributesCodeList');
		$result	= $fn->invoke($model);
		$e      = $this->expected('default');
		$this->assertSame($e->getData(), $result);
	}

	/**
	 * @loadExpectation testGetDefaultAttributesCodeList.yaml
	 */
	public function testGetDefaultAttributesCodeListFilterByGroup()
	{
		$model  = Mage::getModel('eb2cproduct/attributes');
		$fn     = $this->_reflectMethod($model, 'getDefaultAttributesCodeList');
		$result	= $fn->invoke($model, 'Prices');
		$e      = $this->expected('prices');
		$this->assertSame($e->getData(), $result);
	}

	public function provideOverrideXmlVfsStructure()
	{
		return array(
			array('base_config', $this->_getOverrideXmlVfsStructure()),
		);
	}

	protected function _getOverrideXmlVfsStructure(array $etcContents = array())
	{
		return array(
			'app' => array(
				'code' => array(
					'local' => array(
						'TrueAction' => array(
							'Eb2cProduct' => array(
								'etc' => $etcContents
			))))));
	}

	public static $configXml  = '
		<eb2cproduct_attributes>
			<default>
				<tax_code>
					<scope>Store</scope>
					<label>Tax Code2</label>
					<group>Prices</group>
					<frontend_type>boolean</frontend_type>
					<unique>Y</unique>
					<product_types><![CDATA[simple,configurable,virtual,bundle,downloadable]]></product_types>
					<default><![CDATA[refridgerator]]></default>
				</tax_code>
			</default>
		</eb2cproduct_attributes>';
}

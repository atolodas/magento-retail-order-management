<?php
class EbayEnterprise_Eb2cProduct_Test_Model_AttributesTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	public static $modelClass = 'EbayEnterprise_Eb2cProduct_Model_Attributes';

	/**
	 * ensure the tax code is readable
	 * @loadFixture
	 * @large
	 * NOTE: ticket EB2C-14
	 * NOTE: marked large because this is an integration test that invokes several
	 * queries to/from the database.
	 */
	public function testReadingAttributeValue()
	{
		$taxCode = 'thecode';
		$product = Mage::getModel('catalog/product')
			->getCollection()
			->addAttributeToSelect('*')
			->addFieldToFilter('entity_id', array('eq' => 1))
			->getFirstItem();
		$this->assertNotNull($product->getId());
		$this->assertArrayHasKey('tax_code', $product->getData());
		$this->assertSame($taxCode, $product->getTaxCode());
		$data = array('tax_code' => 'thecode2');
		Mage::getSingleton('catalog/product_action')
			->updateAttributes(array($product->getId()), $data, 0);
		unset($product);
		$product = Mage::getModel('catalog/product')
			->getCollection()
			->addAttributeToSelect('*')
			->addFieldToFilter('entity_id', array('eq' => 1))
			->getFirstItem();
		$this->assertSame('thecode2', $product->getTaxCode());
	}

	/**
	 * verify the _getMappedFieldValue function throws an exception when
	 * the function mapped to the fieldname does not exist.
	 * @dataProvider dataProvider
	 */
	public function testGetMappedFieldValueException($funcName, $message)
	{
		$exceptionName = 'Mage_Core_Exception';
		$this->setExpectedException($exceptionName, $message);
		$element = new Varien_SimpleXml_Element('<scope>Website</scope>');
		$model = Mage::getModel('eb2cproduct/attributes');
		$this->_reflectProperty($model, '_valueFunctionMap')
			->setValue($model, array('is_global' => $funcName));
		$fn = $this->_reflectMethod($model, '_getMappedFieldValue');
		$fn->invoke($model, 'is_global', $element);
	}

	/**
	 * verify the _formatScope function throws an exception when
	 * an invalid valid is passed in.
	 * @dataProvider dataProvider
	 */
	public function testFormatScopeException($value, $message)
	{
		$exceptionName = 'Mage_Core_Exception';
		$this->setExpectedException($exceptionName, $message);
		$model = Mage::getModel('eb2cproduct/attributes');
		$fn = $this->_reflectMethod($model, '_formatScope');
		$fn->invoke($model, $value);
	}

	/**
	 * the return value is an array.
	 * the function loops through all attributes in the default config
	 */
	public function testGetAttributesData()
	{
		$attrNode = Mage::getModel('core/config');
		$attrNode->loadString(self::$configXml);
		$attrNode =	$attrNode->getNode('default/tax_code');
		$defaultNode = $this->getMock('Varien_Object', array('children'));
		$defaultNode->expects($this->once())
			->method('children')
			->will($this->returnValue(array('tax_code' => $attrNode)));
		$config = $this->getModelMock('core/config', array('getNode'));
		$config->expects($this->once())
			->method('getNode')
			->with($this->identicalTo('default'))
			->will($this->returnValue($defaultNode));
		$model = $this->getModelMock('eb2cproduct/attributes', array(
			'_loadDefaultAttributesConfig',
			'_getPrototypeData'
		));
		$model->expects($this->once())
			->method('_loadDefaultAttributesConfig')
			->will($this->returnValue($config));
		$model->expects($this->once())
			->method('_getPrototypeData')
			->with($this->identicalTo($attrNode))
			->will($this->returnSelf());
		$result = $model->getAttributesData();
		$this->assertEquals(array(), $result);
	}

	/**
	 * the function shouldn't die if an exception occurs.
	 */
	public function testGetAttributesDataException()
	{
		$attrNode = Mage::getModel('core/config');
		$attrNode->loadString(self::$configXml);
		$attrNode =	$attrNode->getNode('default/tax_code');
		$defaultNode = $this->getMock('Varien_Object', array('children'));
		$defaultNode->expects($this->once())
			->method('children')
			->will($this->returnValue(array('tax_code' => $attrNode)));
		$config = $this->getModelMock('core/config', array('getNode'));
		$config->expects($this->once())
			->method('getNode')
			->with($this->identicalTo('default'))
			->will($this->returnValue($defaultNode));
		$model = $this->getModelMock('eb2cproduct/attributes', array(
			'_loadDefaultAttributesConfig',
			'_getPrototypeData'
		));
		$model->expects($this->once())
			->method('_loadDefaultAttributesConfig')
			->will($this->returnValue($config));
		$model->expects($this->once())
			->method('_getPrototypeData')
			->with($this->identicalTo($attrNode))
			->will($this->throwException(new Mage_Core_Exception()));
		$result = $model->getAttributesData();
		$this->assertEquals(array(), $result);
	}

	/**
	 * verify we get an array with the entity type id for products.
	 * @loadExpectation
	 */
	public function testGetTargetEntityTypeIds()
	{
		$e          = $this->expected('product_only');
		$entityType = 'catalog/product';
		$entity     = $this->getModelMock($entityType);
		$this->assertInstanceOf('Mage_Catalog_Model_Product', $entity);
		$entity->expects($this->once())
			->method('getResource')
			->will($this->returnSelf());
		$entity->expects($this->once())
			->method('getTypeId')
			->will($this->returnValue($e->getEntityTypeId()));
		$this->replaceByMock('model', $entityType, $entity);
		$model = Mage::getModel('eb2cproduct/attributes');
		$ids   = $model->getTargetEntityTypeIds();
		$this->assertEquals($e->getIds(), $ids);
	}

	/**
	 * verify the function returns true if the attribute set's entity id
	 * is a valid entity id.
	 * @param  int $eid
	 * @param  bool $expect
	 * @dataProvider dataProvider
	 */
	public function testIsValidEntityType($eid, $expect)
	{
		$model = $this->getModelMock('eb2cproduct/attributes', array('_getTargetEntityTypeIds'));
		$model->expects($this->any())
			->method('_getTargetEntityTypeIds')
			->will($this->returnValue(array(10)));
		$val = $this->_reflectMethod($model, '_isValidEntityType')->invoke($model, $eid);
		$this->assertSame($expect, $val);
	}

	/**
	 * verify a the model field name is returned when it is defined in the map
	 * and the input field name is returned if not in the map.
	 * @dataProvider dataProvider
	 */
	public function testGetMappedFieldName($fieldName, $expected)
	{
		$map = array('field_in_map' => 'model_field_name');
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
	 * verify a new model is returned and contains the correct data for each field
	 * @loadExpectation
	 */
	public function testGetPrototypeData()
	{
		$dataNode = new Varien_SimpleXml_Element(self::$configXml);
		$result   = $dataNode->xpath('/eb2cproduct_attributes/default/tax_code');
		// start precondition checks
		$this->assertSame(1, count($result));
		list($taxCodeNode) = $result;
		$this->assertInstanceOf('Varien_SimpleXml_Element', $taxCodeNode);
		$this->assertSame('tax_code', $taxCodeNode->getName());
		// end preconditions checks

		$model = Mage::getModel('eb2cproduct/attributes');
		$attrData = $this->_reflectMethod($model, '_getPrototypeData')
			->invoke($model, $taxCodeNode);
		$this->assertNotEmpty($attrData);
		$e = $this->expected('tax_code');
		$this->assertEquals($e->getData(), $attrData);
	}

	public function testGetPrototypeDataCache()
	{
		// setup input data
		$dataNode = new Varien_SimpleXml_Element(self::$configXml);
		$result = $dataNode->xpath('/eb2cproduct_attributes/default/tax_code');
		$this->assertSame(1, count($result));
		list($taxCodeNode) = $result;
		$this->assertInstanceOf('Varien_SimpleXml_Element', $taxCodeNode);
		$this->assertSame('tax_code', $taxCodeNode->getName());

		// mock functions to make sure they're not called
		$model = $this->getModelMock('eb2cproduct/attributes', array('_getDefaultValueFieldName', '_getMappedFieldName', '_getMappedFieldValue'));
		// mock up the cache
		$dummyObject = new Varien_Object();
		$this->_reflectProperty($model, '_prototypeCache')
			->setValue($model, array('tax_code' => $dummyObject));
		$attrData = $this->_reflectMethod($model, '_getPrototypeData')
			->invoke($model, $taxCodeNode);
		$this->assertNotEmpty($attrData);
		$this->assertInstanceOf('Varien_Object', $dummyObject);
		$this->assertSame($dummyObject, $attrData);
	}

	public function provideOverrideXmlVfsStructure()
	{
		return array(
			array('base_config', $this->_getOverrideXmlVfsStructure()),
		);
	}

	protected function _getOverrideXmlVfsStructure(array $etcContents=array())
	{
		return array(
			'app' => array(
				'code' => array(
					'local' => array(
						'EbayEnterprise' => array(
							'Eb2cProduct' => array(
								'etc' => $etcContents
							)
						)
					)
				)
			)
		);
	}

	public static $configXml = '
		<eb2cproduct_attributes>
			<default>
				<tax_code>
					<scope>Store</scope>
					<label>Tax Code2</label>
					<group>Prices</group>
					<input_type>boolean</input_type>
					<unique>Y</unique>
					<product_types><![CDATA[simple,configurable,virtual,bundle,downloadable]]></product_types>
					<default><![CDATA[N]]></default>
				</tax_code>
			</default>
		</eb2cproduct_attributes>';
}
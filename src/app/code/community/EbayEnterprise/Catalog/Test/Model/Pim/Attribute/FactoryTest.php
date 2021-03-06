<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class EbayEnterprise_Catalog_Test_Model_Pim_Attribute_FactoryTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * Constructor should load and store the PIM feed mappings from the config
     */
    public function testConstructor()
    {
        // mock mapping from the config.xml
        $mappingConfig = array('sku' => array('xml_dest' => 'Some/Xpath',));
        $defaultPimConfig = array('xml_dest' => 'ConfigurableAttributes/Attribute[@name="%s"]');

        $configRegistryMock = $this->getModelMock('eb2ccore/config_registry', array('getConfigData'));
        $configRegistryMock->expects($this->exactly(2))
            ->method('getConfigData')
            ->will($this->returnValueMap(array(
                array('ebayenterprise_catalog/feed_pim_mapping', $mappingConfig),
                array('ebayenterprise_catalog/default_pim_mapping', $defaultPimConfig),
            )));
        $this->replaceByMock('model', 'eb2ccore/config_registry', $configRegistryMock);

        $factory = Mage::getModel('ebayenterprise_catalog/pim_attribute_factory');
        $this->assertSame(
            $mappingConfig,
            EcomDev_Utils_Reflection::getRestrictedPropertyValue($factory, '_attributeMappings')
        );
        $this->assertSame(
            $defaultPimConfig,
            EcomDev_Utils_Reflection::getRestrictedPropertyValue($factory, '_defaultMapping')
        );
    }
    /**
     * Test creating a PIM Attribute Model for a given product and attribute.
     */
    public function testGetPimAttribute()
    {
        $doc = Mage::helper('eb2ccore')->getNewDomDocument();
        $attributeMapping = array('xml_dest' => 'Some/XPath', 'class' => 'ebayenterprise_catalog/pim', 'type' => 'helper');
        $pimAttrConstructorArgs = array(
            'destination_xpath' => 'Some/XPath',
            'sku' => 'SomeSku',
            'value' => $this->getMockBuilder('DOMDocumentFragment')->disableOriginalConstructor()->getMock()
        );
        $config = array('mappings' => $attributeMapping);

        $product = $this->getModelMock('catalog/product');
        $attribute = $this->getModelMock('catalog/entity_attribute');
        $pimAttribute = $this->getModelMockBuilder('ebayenterprise_catalog/pim_attribute')
            ->disableOriginalConstructor()
            ->getMock();

        $factory = $this->getModelMockBuilder('ebayenterprise_catalog/pim_attribute_factory')
            ->disableOriginalConstructor()
            ->setMethods(array('_getAttributeMapping', '_resolveMappedCallback'))
            ->getMock();
        $factory->expects($this->once())
            ->method('_getAttributeMapping')
            ->with($this->identicalTo($attribute))
            ->will($this->returnValue($attributeMapping), $this->identicalTo($config));
        $factory->expects($this->once())
            ->method('_resolveMappedCallback')
            ->with(
                $this->identicalTo($attributeMapping),
                $this->identicalTo($attribute),
                $this->identicalTo($product),
                $this->identicalTo($doc)
            )
            ->will($this->returnValue($pimAttrConstructorArgs));
        $this->replaceByMock('model', 'ebayenterprise_catalog/pim_attribute', $pimAttribute);

        $this->assertSame($pimAttribute, $factory->getPimAttribute($attribute, $product, $doc, $config));
    }
    /**
     * When a resolved mapping callback returns null due to a mapping being
     * disabled, this method should return null instead of a PIM attribute model.
     */
    public function testGetPimAttributeDisabledMapping()
    {
        $attribute = 'sku';
        $attributeMapping = array('type' => 'disabled', 'xml_dest' => 'Some/XPath');
        $config = array('mappings' => array('sku' => $attributeMapping));
        $doc = Mage::helper('eb2ccore')->getNewDomDocument();
        $product = $this->getModelMock('catalog/product');
        $pimAttribute = $this->getModelMockBuilder('ebayenterprise_catalog/pim_attribute')
            ->disableOriginalConstructor()
            ->getMock();
        $factory = $this->getModelMockBuilder('ebayenterprise_catalog/pim_attribute_factory')
            ->disableOriginalConstructor()
            ->setMethods(array('_getAttributeMapping', '_resolveMappedCallback'))
            ->getMock();

        $this->replaceByMock('model', 'ebayenterprise_catalog/pim_attribute', $pimAttribute);

        $factory->expects($this->once())
            ->method('_getAttributeMapping')
            ->with($this->identicalTo($attribute), $this->identicalTo($config))
            ->will($this->returnValue($attributeMapping));
        $factory->expects($this->once())
            ->method('_resolveMappedCallback')
            ->with(
                $this->identicalTo($attributeMapping),
                $this->identicalTo($attribute),
                $this->identicalTo($product),
                $this->identicalTo($doc)
            )
            ->will($this->returnValue(null));

        $this->assertSame(null, $factory->getPimAttribute($attribute, $product, $doc, $config));
    }
    /**
     * Test getting an attribute mapping
     */
    public function testGetAttributeMapping()
    {
        $attribute = 'sku';
        $skuMapping = array('xml_dest' => 'Some/XPath');
        $config = array('mappings' => array($attribute => $skuMapping));

        $factory = $this->getModelMockBuilder('ebayenterprise_catalog/pim_attribute_factory')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->assertSame(
            $skuMapping,
            EcomDev_Utils_Reflection::invokeRestrictedMethod($factory, '_getAttributeMapping', array($attribute, $config))
        );
    }
    /**
     * Should invoke the configured callback using the eb2ccore/feed helper.
     * Helper method must be called with the callback configuration including
     * a 'parameters' key including the attribute value, attribute and product.
     * The method should return an array of arguments to be passed to the PIM
     * Attribute model's constructor.
     */
    public function testResolveMappedCallback()
    {
        $doc = Mage::helper('eb2ccore')->getNewDomDocument();
        $attribute = 'some_attribute_code';
        $product = $this->getModelMock('catalog/product', array('getDataUsingMethod'));
        $callbackValue = $this->getMockBuilder('DOMDocumentFragment')
            ->disableOriginalConstructor()
            ->getMock();
        $coreHelper = $this->getHelperMock('eb2ccore/data', array('invokeCallback'));
        $factory = $this->getModelMockBuilder('ebayenterprise_catalog/pim_attribute_factory')
            ->setMethods(array('_createPimAttributeArgs'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->replaceByMock('helper', 'eb2ccore', $coreHelper);

        $attributeMapping = array(
            'xml_dest' => 'Some/XPath',
            'class' => 'ebayenterprise_catalog/pim',
            'type' => 'helper',
            'method' => 'doSomeThing',
            'translate' => 1
        );
        $languageCode = 'en-us';
        $sku = '45-12345';
        $attributeValue = 'some attribute value';
        $callbackConfig = array_merge(
            $attributeMapping,
            array('parameters' => array($attributeValue, $attribute, $product, $doc))
        );
        $pimAttrModelConstructorArgs = array(
            'destination_xpath' => $attributeMapping['xml_dest'],
            'sku' => $sku,
            'value' => $callbackValue,
            'language' => $languageCode,
        );

        $product->expects($this->once())
            ->method('getDataUsingMethod')
            ->with($this->identicalTo($attribute))
            ->will($this->returnValue($attributeValue));
        $coreHelper->expects($this->once())
            ->method('invokeCallback')
            ->with($this->identicalTo($callbackConfig))
            ->will($this->returnValue($callbackValue));
        $factory->expects($this->once())
            ->method('_createPimAttributeArgs')
            ->with(
                $this->identicalTo($callbackConfig),
                $this->identicalTo($callbackValue),
                $this->identicalTo($product)
            )
            ->will($this->returnValue($pimAttrModelConstructorArgs));
        $this->assertSame(
            $pimAttrModelConstructorArgs,
            EcomDev_Utils_Reflection::invokeRestrictedMethod(
                $factory,
                '_resolveMappedCallback',
                array($attributeMapping, $attribute, $product, $doc)
            )
        );
    }
    /**
     * When an attribute configuration is set to the "disabled" type, this method
     * should simply return null.
     */
    public function testResolveMappedCallbackDisabledMapping()
    {
        $doc = Mage::helper('eb2ccore')->getNewDomDocument();
        $attribute = $this->getModelMock('catalog/entity_attribute', array('getAttributeCode'));
        $product = $this->getModelMock('catalog/product', array('getDataUsingMethod'));
        $coreHelper = $this->getHelperMock('ebayenterprise_catalog/feed', array('invokeCallback'));

        $attributeMapping = array(
            'xml_dest' => 'Some/XPath',
            'class' => 'ebayenterprise_catalog/pim',
            'type' => 'disabled',
            'method' => 'doSomeThing',
            'translate' => 1
        );

        // When the mapping is "disabled", no attempt to do anything with the
        // attribute or product should be made.
        $attribute->expects($this->never())
            ->method('getAttributeCode');
        $product->expects($this->never())
            ->method('getDataUsingMethod');
        $coreHelper->expects($this->never())
            ->method('invokeCallback');

        $factory = $this->getModelMockBuilder('ebayenterprise_catalog/pim_attribute_factory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertNull(
            EcomDev_Utils_Reflection::invokeRestrictedMethod(
                $factory,
                '_resolveMappedCallback',
                array($attributeMapping, $attribute, $product, $doc)
            )
        );
    }
    /**
     * Create the array of args to pass to the PIM Attribute model constructor
     * based on a given attribute mapping, value and product.
     */
    public function testCreatingPimAttributeArgsWithTranslation()
    {
        $sku = '45-12345';
        $languageCode = 'en-US';

        $product = $this->getModelMock('catalog/product', array('getSku', 'getPimLanguageCode'));
        $callbackValue = $this->getMockBuilder('DOMDocumentFragment')
            ->disableOriginalConstructor()
            ->getMock();
        $factory = $this->getModelMockBuilder('ebayenterprise_catalog/pim_attribute_factory')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $attributeMapping = array(
            'xml_dest' => 'Some/XPath',
            'class' => 'ebayenterprise_catalog/pim',
            'type' => 'helper',
            'method' => 'doSomeThing',
            'translate' => 1
        );
        $pimAttrModelConstructorArgs = array(
            'destination_xpath' => $attributeMapping['xml_dest'],
            'sku' => $sku,
            'language' => $languageCode,
            'value' => $callbackValue,
        );

        $product->expects($this->any())
            ->method('getSku')
            ->will($this->returnValue($sku));
        $product->expects($this->once())
            ->method('getPimLanguageCode')
            ->will($this->returnValue($languageCode));
        $this->assertSame(
            $pimAttrModelConstructorArgs,
            EcomDev_Utils_Reflection::invokeRestrictedMethod(
                $factory,
                '_createPimAttributeArgs',
                array($attributeMapping, $callbackValue, $product)
            )
        );
    }
    /**
     * When the translate key in the config is set to false/0, the language
     * key in the arg array should not be set.
     */
    public function testCreatingPimAttributeArgsNoTranslation()
    {
        $product = $this->getModelMock('catalog/product', array('getSku', 'getLanguageCode'));
        $callbackValue = $this->getMockBuilder('DOMDocumentFragment')
            ->disableOriginalConstructor()
            ->getMock();
        $attributeMapping = array(
            'xml_dest' => 'Some/XPath',
            'class' => 'ebayenterprise_catalog/pim',
            'type' => 'helper',
            'method' => 'doSomeThing',
            'translate' => 0
        );
        $sku = '45-12345';
        $pimAttrModelConstructorArgs = array(
            'destination_xpath' => $attributeMapping['xml_dest'],
            'sku' => $sku,
            'language' => null,
            'value' => $callbackValue,
        );
        $factory = $this->getModelMockBuilder('ebayenterprise_catalog/pim_attribute_factory')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $product->expects($this->any())
            ->method('getSku')
            ->will($this->returnValue($sku));
        $product->expects($this->never())
            ->method('getLanguageCode');

        $this->assertSame(
            $pimAttrModelConstructorArgs,
            EcomDev_Utils_Reflection::invokeRestrictedMethod(
                $factory,
                '_createPimAttributeArgs',
                array($attributeMapping, $callbackValue, $product)
            )
        );
    }
}

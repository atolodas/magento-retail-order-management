<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cInventory_Test_Model_Feed_Item_InventoriesTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_inventories;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_inventories = Mage::getModel('eb2cinventory/feed_item_inventories');
	}

	public function providerProcessFeeds()
	{
		return array(
			array(array(Mage::getBaseDir('var') . DS . Mage::getStoreConfig('eb2c/inventory/feed_local_received_path') . 'sample-feed.xml'))
		);
	}

	/**
	 * testing processFeeds method
	 *
	 * @test
	 * @medium
	 * @dataProvider providerProcessFeeds
	 * @loadFixture loadConfig.yaml
	 */
	public function testProcessFeeds($feeds=array())
	{
		$inventoriesReflector = new ReflectionObject($this->_inventories);

		$localPath = Mage::getBaseDir('var') . DS . Mage::getStoreConfig('eb2c/inventory/feed_local_received_path');
		if (is_dir($localPath)) {
			foreach(glob($localPath . '*') as $file) {
				unlink($file);
			}
			rmdir($localPath);
		}
		$this->assertEmpty(
			$this->_inventories->processFeeds($feeds)
		);

		// Adding xml to feed directory in other to get the feed
		$sampleFeed = dirname(__FILE__) . '/InventoriesTest/fixtures/sample-feed.xml';
		$destination = $localPath . 'sample-feed.xml';
		if (!is_dir($localPath)) {
			umask(0);
			@mkdir($localPath, 0777, true);
		}

		copy($sampleFeed, $destination);

		$fileTransferHelperMock = $this->getMock(
			'TrueAction_FileTransfer_Helper_Data',
			array('getFile')
		);
		$fileTransferHelperMock->expects($this->any())
			->method('getFile')
			->will($this->returnValue(true));

		$inventoryHelperMock = $this->getMock(
			'TrueAction_Eb2cInventory_Helper_Data',
			array('getFileTransferHelper')
		);
		$inventoryHelperMock->expects($this->any())
			->method('getFileTransferHelper')
			->will($this->returnValue($fileTransferHelperMock));

		$helper = $inventoriesReflector->getProperty('_helper');
		$helper->setAccessible(true);
		$helper->setValue($this->_inventories, $inventoryHelperMock);

		$this->assertNull(
			$this->_inventories->processFeeds($feeds)
		);

		copy($sampleFeed, $destination);
		$this->assertNull(
			$this->_inventories->processFeeds($feeds)
		);

		// test with mock product and stock item
		$productMock = $this->getMock(
			'Mage_Catalog_Model_Product',
			array('loadByAttribute', 'getId')
		);
		$productMock->expects($this->any())
			->method('loadByAttribute')
			->will($this->returnValue(true));
		$productMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));

		$stockItemMock = $this->getMock(
			'Mage_CatalogInventory_Model_Stock_Item',
			array('loadByProduct', 'setQty', 'save')
		);
		$stockItemMock->expects($this->any())
			->method('loadByProduct')
			->will($this->returnSelf());
		$stockItemMock->expects($this->any())
			->method('setQty')
			->will($this->returnSelf());
		$stockItemMock->expects($this->any())
			->method('save')
			->will($this->returnSelf());

		$productProperty = $inventoriesReflector->getProperty('_product');
		$productProperty->setAccessible(true);
		$productProperty->setValue($this->_inventories, $productMock);

		$stockItemProperty = $inventoriesReflector->getProperty('_stockItem');
		$stockItemProperty->setAccessible(true);
		$stockItemProperty->setValue($this->_inventories, $stockItemMock);

		copy($sampleFeed, $destination);
		$this->assertNull(
			$this->_inventories->processFeeds($feeds)
		);
	}

	/**
	 * testing _clean method, to cover exception catch section
	 *
	 * @test
	 */
	public function testCleanWithException()
	{
		$stockStatusMock = $this->getMock(
			'Mage_CatalogInventory_Model_Stock_Status',
			array('rebuild')
		);
		$stockStatusMock->expects($this->any())
			->method('rebuild')
			->will($this->throwException(new Exception));

		$inventoriesReflector = new ReflectionObject($this->_inventories);
		$stockStatusProperty = $inventoriesReflector->getProperty('_stockStatus');
		$stockStatusProperty->setAccessible(true);
		$stockStatusProperty->setValue($this->_inventories, $stockStatusMock);

		$cleanMethod = $inventoriesReflector->getMethod('_clean');
		$cleanMethod->setAccessible(true);
		$this->assertNull(
			$cleanMethod->invoke($this->_inventories)
		);
	}
}
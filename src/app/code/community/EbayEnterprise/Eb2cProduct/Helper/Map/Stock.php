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

class EbayEnterprise_Eb2cProduct_Helper_Map_Stock extends Mage_Core_Helper_Abstract
{
	const STOCK_CONFIG_PATH = 'eb2cproduct/feed/stock_map';

	/**
	 * @var array holding eb2c manage stock value type
	 */
	protected $_stockMap = array();

	/**
	 * @see self::$_stockMap
	 * @return array
	 */
	protected function _getStockMap()
	{
		if (empty($this->_stockMap)) {
			$this->_stockMap = Mage::helper('eb2ccore/feed')
				->getConfigData(self::STOCK_CONFIG_PATH);
		}
		return $this->_stockMap;
	}

	/**
	 * extract the salesClass from DOMNOdeList object
	 * then get a configuration array of SalesClass possible values map to a known manage_stock value
	 * check if the extracted salesClass value is a key in the array of configuration map
	 * then return an array with key mage_stock with value of extracted salesclass parsed as bool
	 * if the key is not found in the map simply return null
	 * @param DOMNodeList $nodes
	 * @param Mage_Catalog_Model_Product $product
	 * @return array an array with key manage_stock with value otherwise an empty array
	 */
	public function extractStockData(DOMNodeList $nodes, Mage_Catalog_Model_Product $product)
	{
		$coreHelper = Mage::helper('eb2ccore');
		$value = $coreHelper->extractNodeVal($nodes);
		$mapData = $this->_getStockMap();
		$id = (int) $product->getId();
		if (isset($mapData[$value]) && $id) {
			$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($id);
			if ($stockItem) {
				$stockItem->addData(array(
					'manage_stock' => $this->_boolToInt($coreHelper->parseBool($mapData[$value])),
					'use_config_manage_stock' => false,
					'product_id' => $id,
					'stock_id' => Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID,
				))
				->save();
			}
		}
		return null;
	}

	/**
	 * return int value of a given boolean value
	 * @param bool $value
	 * @return int
	 */
	protected function _boolToInt($value)
	{
		return $value? 1 : 0;
	}
}

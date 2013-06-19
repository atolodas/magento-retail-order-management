<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Model_Observer
{
	protected $_quantity;
	protected $_details;
	protected $_allocation;

	/**
	 * Get Quantity instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Model_Quantity
	 */
	protected function _getQuantity()
	{
		if (!$this->_quantity) {
			$this->_quantity = Mage::getModel('eb2cinventory/quantity');
		}
		return $this->_quantity;
	}

	/**
	 * Get Details instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Model_Details
	 */
	protected function _getDetails()
	{
		if (!$this->_details) {
			$this->_details = Mage::getModel('eb2cinventory/details');
		}
		return $this->_details;
	}

	/**
	 * Get Allocation instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Model_Allocation
	 */
	protected function _getAllocation()
	{
		if (!$this->_allocation) {
			$this->_allocation = Mage::getModel('eb2cinventory/allocation');
		}
		return $this->_allocation;
	}

	/**
	 * Retrieve shopping cart model object
	 *
	 * @return Mage_Checkout_Model_Cart
	 */
	protected function _getCart()
	{
		return Mage::getSingleton('checkout/cart');
	}

	/**
	 * Check e2bc quantity, triggering sales_quote_item_qty_set_after event will run this method.
	 *
	 * @param Varien_Event_Observer $observer
	 *
	 * @return void
	 */
	public function checkEb2cInventoryQuantity($observer)
	{
		$quoteItem = $observer->getEvent()->getItem();
		$itemId = (int) $quoteItem->getId();

		$requestedQty = $quoteItem->getQty();
		$productId = $quoteItem->getProductId();
		$productSku = $quoteItem->getSku();

		if ($productId) {
			// We have a valid product, let's check Eb2c Quantity
			$availableStock = $this->_getQuantity()->requestQuantity($requestedQty, $itemId, $productSku);
			if ($availableStock < $requestedQty && $availableStock > 0) {
				// Inventory Quantity is less in eb2c than what user requested from magento front-end
				// then, remove item from cart, and then alert customers of the available stock number of this inventory
				// set cart item to eb2c available qty
				$quoteItem->setQty($availableStock);

				// get quote from quote item
				$quote = $quoteItem->getQuote();

				// recalc totals
				$quote->collectTotals();

				// save the quote
				$quote->save();

				$this->_getCart()->getCheckoutSession()->addNotice(
					'Sorry for the inconvenience, however, the requested quantity ' .
					$requestedQty . ' is greater than what we currently have in stock ' .
					$availableStock . '.'
				);

			} elseif ($availableStock <= 0) {
				// Inventory Quantity is out of stock in eb2c
				// then, remove item from cart, and then alert customer the inventory is out of stock.
				$quoteItem->getQuote()->deleteItem($quoteItem);
				$this->_getCart()->getCheckoutSession()->addNotice(
					'Sorry for the inconvenience, however, this product is out of stock.'
				);
				// throwing an error to prevent the successful add to cart message
				Mage::throwException('Cannot add the item to shopping cart.');
			}
		}
	}

	/**
	 * Check e2bc inventoryDetails, triggering checkout_controller_onepage_save_shipping_method event will run this method.
	 *
	 * @param Varien_Event_Observer $observer
	 *
	 * @return void
	 */
	public function processInventoryDetails($observer)
	{
		// get the quote from the event observer
		$quote = $observer->getEvent()->getQuote();

		// generate request and send request to eb2c inventory details
		if ($inventoryDetailsResponseMessage = $this->_getDetails()->getInventoryDetails($quote)) {
			// got a valid response from eb2c, then go ahead and update the quote with the eb2c information
			$this->_getDetails()->processInventoryDetails($quote, $inventoryDetailsResponseMessage);
		}
	}

	/**
	 * Allocating e2bc inventory for placed order, triggering sales_order_place_after event will run this method.
	 *
	 * @param Varien_Event_Observer $observer
	 *
	 * @return void
	 */
	public function processAllocation($observer)
	{
		// get the order from the event observer
		$order = $observer->getEvent()->getOrder();

		// generate request and send request to eb2c allocation
		if ($allocationResponseMessage = $this->_getAllocation()->allocateOrderItems($order)) {
			// got a valid response from eb2c, then go ahead and update the order with the eb2c information
			$this->_getAllocation()->processAllocation($order, $allocationResponseMessage);
		}
	}
}
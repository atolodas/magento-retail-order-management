<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Tax
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Calculate items and address amounts including/excluding tax
 */
class TrueAction_Eb2c_Tax_Overrides_Model_Sales_Total_Quote_Subtotal extends Mage_Tax_Model_Sales_Total_Quote_Subtotal
{
	/**
	 * Calculate item price including/excluding tax, row total including/excluding tax
	 * and subtotal including/excluding tax.
	 * Determine discount price if needed
	 *
	 * @param   Mage_Sales_Model_Quote_Address $address
	 * @return  Mage_Tax_Model_Sales_Total_Quote_Subtotal
	 */
	public function collect(Mage_Sales_Model_Quote_Address $address)
	{
		$this->_store   = $address->getQuote()->getStore();
		$this->_address = $address;

		$this->_subtotalInclTax     = 0;
		$this->_baseSubtotalInclTax = 0;
		$this->_subtotal            = 0;
		$this->_baseSubtotal        = 0;
		$this->_roundingDeltas      = array();

		$address->setSubtotalInclTax(0);
		$address->setBaseSubtotalInclTax(0);
		$address->setTotalAmount('subtotal', 0);
		$address->setBaseTotalAmount('subtotal', 0);

		$items = $this->_getAddressItems($address);
		if (!$items) {
			return $this;
		}
		foreach ($items as $item) {
			if ($item->getParentItem()) {
				continue;
			}
			if ($item->getHasChildren() && $item->isChildrenCalculated()) {
				foreach ($item->getChildren() as $child) {
					$this->_applyTaxes($child);
				}
				$this->_recalculateParent($item);
			} else {
				$this->_applyTaxes($item);
			}
			$this->_addSubtotalAmount($address, $item);
		}
		$address->setRoundingDeltas($this->_roundingDeltas);
		return $this;
	}

	/**
	 * Calculate item price and row total including/excluding tax based on unit price rounding level
	 *
	 * @param Mage_Sales_Model_Quote_Item_Abstract $item
	 * @param TrueAction_Eb2c_Tax_Model_Response_OrderItem $itemResponse
	 * @return Mage_Tax_Model_Sales_Total_Quote_Subtotal
	 */
	protected function _applyTaxes($item)
	{
		$rate   = 0; // this is no longer useful.
		$qty    = $item->getTotalQty();

		$price          = $taxPrice         = $this->_calculator->round($item->getCalculationPriceOriginal());
		$basePrice      = $baseTaxPrice     = $this->_calculator->round($item->getBaseCalculationPriceOriginal());
		$subtotal       = $taxSubtotal      = $item->getRowTotal();
		$baseSubtotal   = $baseTaxSubtotal  = $item->getBaseRowTotal();
		$taxOnOrigPrice = !$this->_helper->applyTaxOnCustomPrice($this->_store) && $item->hasCustomPrice();
		if ($taxOnOrigPrice) {
			$origPrice       = $item->getOriginalPrice();
			$baseOrigPrice   = $item->getBaseOriginalPrice();
		}
		$item->setTaxPercent($rate);
		$tax             = $this->_calculator->getTaxForItem($item);
		$baseTax         = $this->_calculator->getTaxForItemAmount($basePrice, $item);
		$taxPrice        = $price + $tax;
		$baseTaxPrice    = $basePrice + $baseTax;
		$taxSubtotal     = $taxPrice * $qty;
		$baseTaxSubtotal = $baseTaxPrice * $qty;
		if ($this->_config->priceIncludesTax($this->_store)) {
			$taxSubtotal     = $subtotal;
			$baseTaxSubtotal = $baseSubtotal;
			$price           = $price - $tax;
			$basePrice       = $basePrice - $baseTax;
			$subtotal        = $price * $qty;
			$baseSubtotal    = $basePrice * $qty;
			if ($taxOnOrigPrice) {
				$taxable        = $origPrice;
				$baseTaxable    = $baseOrigPrice;
			} else {
				$taxable        = $taxPrice;
				$baseTaxable    = $baseTaxPrice;
			}
			$isPriceInclTax = true;
		} else {
			if ($taxOnOrigPrice) {
				$taxable        = $origPrice;
				$baseTaxable    = $baseOrigPrice;
			} else {
				$taxable        = $price;
				$baseTaxable    = $basePrice;
			}
			$isPriceInclTax = false;
		}
		if ($item->hasCustomPrice()) {
			/**
			 * Initialize item original price before declaring custom price
			 */
			$item->getOriginalPrice();
			$item->setCustomPrice($price);
			$item->setBaseCustomPrice($basePrice);
		}
		$item->setPrice($price);
		$item->setBasePrice($basePrice);
		$item->setRowTotal($subtotal);
		$item->setBaseRowTotal($baseSubtotal);
		$item->setPriceInclTax($taxPrice);
		$item->setBasePriceInclTax($baseTaxPrice);
		$item->setRowTotalInclTax($taxSubtotal);
		$item->setBaseRowTotalInclTax($baseTaxSubtotal);
		$item->setTaxableAmount($taxable);
		$item->setBaseTaxableAmount($baseTaxable);
		$item->setIsPriceInclTax($isPriceInclTax);
		if ($this->_config->discountTax($this->_store)) {
			$item->setDiscountCalculationPrice($taxPrice);
			$item->setBaseDiscountCalculationPrice($baseTaxPrice);
		}
		return $this;
	}

}
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

class EbayEnterprise_Eb2cPayment_Overrides_Model_Giftcardaccount extends Enterprise_GiftCardAccount_Model_Giftcardaccount
{
	const EBAY_ENTERPRISE_EB2CPAYMENT_GIFTCARD_ACCOUNT_EXISTS = 'EbayEnterprise_Eb2cPayment_GiftCard_Account_Exists';
	/**
	 * Giftcard pan that was requested for load
	 * @var bool|string
	 */
	protected $_requestedPan = false;
	/**
	 * Giftcard pin that was requested for load
	 * @var bool|string
	 */
	protected $_requestedPin = false;
	/**
	 * filter gift card by by pan and pin
	 * @return EbayEnterprise_Eb2cPayment_Overrides_Model_Giftcardaccount
	 */
	protected function _filterGiftCardByPanPin()
	{
		return Mage::getResourceModel('enterprise_giftcardaccount/giftcardaccount_collection')
			->addFieldToFilter('eb2c_pan', array('eq' => $this->_requestedPan)) // add filter by pan.
			->addFieldToFilter('eb2c_pin', array('eq' => $this->_requestedPin)) // add filter by pan.
			->load();
	}
	/**
	 * get pin by pan from giftcardaccount
	 * @param string $pan, the payment account number from eb2c that's stored in magento
	 * @return string $pin
	 */
	public function giftCardPinByPan($pan)
	{
		return (string) Mage::getResourceModel('enterprise_giftcardaccount/giftcardaccount_collection')
			->addFieldToFilter('eb2c_pan', array('eq' => $pan)) // add filter by pan.
			->getFirstItem()
			->getEb2cPin();
	}
	/**
	 * overriding the loadByCode method to update magento gift card with actual eb2c records
	 * Load gift card account model using specified code
	 * @param string $code
	 * @return Enterprise_GiftCardAccount_Model_Giftcardaccount
	 */
	public function loadByCode($code)
	{
		return $this->loadByPanPin($code, $this->giftCardPinByPan($code));
	}
	/**
	 * custom Load gift card account model using specified eb2c stored value data
	 * @param string $pan, payment account number
	 * @param string $pin, personal identification number
	 * @return Enterprise_GiftCardAccount_Model_Giftcardaccount
	 */
	public function loadByPanPin($pan, $pin)
	{
		$this->_requestedCode = $pan;
		$this->_requestedPan = $pan;
		$this->_requestedPin = $pin;
		// Check eb2c stored value first
		if (trim($pan) !== '' && trim($pin) !== '') {
			// only fetch eb2c stored value balance when both pan and pin is valid
			$storeValueBalanceReply = Mage::getModel('eb2cpayment/storedvalue_balance')->getBalance($pan, $pin);
			if ($storeValueBalanceReply) {
				$balanceData = Mage::getModel('eb2cpayment/storedvalue_balance')->parseResponse($storeValueBalanceReply);
				if ($balanceData) {
					$balanceData['pin'] = $pin;
					$balanceData['paymentAccountUniqueId'] = $pan; // the return pan might be tokenized.
					// making sure we have the right data
					$mgGiftCard = $this->_filterGiftCardByPanPin();
					if ($mgGiftCard->count()) {
						$this->_updateGiftCardWithEb2cData($mgGiftCard->getFirstItem(), $balanceData);
					} else {
						$this->_addGiftCardWithEb2cData($balanceData);
					}
				}
			}
		}
		return $this->load($this->_requestedCode, 'code');
	}

	/**
	 * Update enterprise giftcard account with data from eb2c
	 *
	 * @param Enterprise_GiftCardAccount_Model_Giftcardaccount $giftCard the gift card object
	 * @param array $balanceData the eb2c stored value balance data
	 * @return void
	 */
	protected function _updateGiftCardWithEb2cData(Enterprise_GiftCardAccount_Model_Giftcardaccount $giftCard, array $balanceData)
	{
		if ($giftCard->getGiftcardaccountId()) {
			$giftCard->setCode($balanceData['paymentAccountUniqueId'])
				->setEb2cPan($balanceData['paymentAccountUniqueId'])
				->setEb2cPin($balanceData['pin'])
				->setStatus(1)
				->setState(1)
				->setBalance((float) $balanceData['balanceAmount'])
				->setIsRedeemable(1)
				->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
				->unsDateExpires()
				->save();
		}
	}
	/**
	 * add eb2c storedvalue gift card data to magento enterprise giftcard account
	 * @param array $balanceData, the eb2c stored value balance data
	 * @return void
	 */
	protected function _addGiftCardWithEb2cData(array $balanceData)
	{
		$giftCard = Mage::getModel('enterprise_giftcardaccount/giftcardaccount')->load(null);
		$giftCard->unsGiftcardaccountId()
			->setCode($balanceData['paymentAccountUniqueId'])
			->setEb2cPan($balanceData['paymentAccountUniqueId'])
			->setEb2cPin($balanceData['pin'])
			->setStatus(1)
			->setState(1)
			->setBalance((float) $balanceData['balanceAmount'])
			->setIsRedeemable(1)
			->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
			->unsDateExpires()
			->setDateCreated(Mage::getModel('core/date')->date('Y-m-d'))
			->save();
	}

	/**
	 * overriding addToCart method in order to save the eb2c pan and pin field in the quote
	 * Add gift card to quote gift card storage
	 *
	 * @param bool $saveQuote
	 * @param null $quote
	 * @return Enterprise_GiftCardAccount_Model_Giftcardaccount
	 */
	public function addToCart($saveQuote=true, $quote=null)
	{
		if (is_null($quote)) {
			$quote = $this->_getCheckoutSession()->getQuote();
		}
		$website = Mage::app()->getStore($quote->getStoreId())->getWebsite();
		if ($this->isValid(true, true, $website)) {
			$cards = Mage::helper('enterprise_giftcardaccount')->getCards($quote);
			if (!$cards) {
				$cards = array();
			} else {
				foreach ($cards as $one) {
					if ($one['i'] == $this->getId()) {
						Mage::throwException(
							Mage::helper('enterprise_giftcardaccount')->__(self::EBAY_ENTERPRISE_EB2CPAYMENT_GIFTCARD_ACCOUNT_EXISTS)
						);
						// @codeCoverageIgnoreStart
					}
				}
				// @codeCoverageIgnoreEnd
			}
			$cards[] = array(
				'i' => $this->getId(),
				'c' => $this->getCode(),
				'a' => $this->getBalance(), // amount
				'ba' => $this->getBalance(), // base amount
				'pan' => $this->getEb2cPan(),
				'pin' => $this->getEb2cPin(),
			);
			Mage::helper('enterprise_giftcardaccount')->setCards($quote, $cards);
			if ($saveQuote) {
				$quote->save();
			}
		}
		return $this;
	}
}

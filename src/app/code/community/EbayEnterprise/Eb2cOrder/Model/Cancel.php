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

/**
 * Generates an OrderCancel
 *
 * Some events I *may* need to care about. I need to investigate whether 'order_cancel_after' ... it's my
 *	first candidate for where I should hook the cancel request in.
 *
 *  order_cancel_after
 */
class EbayEnterprise_Eb2cOrder_Model_Cancel
{
	const ORDER_CANCEL_FAILURE_MESSAGE = 'EbayEnterprise_Eb2cOrder_Cancel_Fail_Message';
	/**
	 * @var EbayEnterprise_Dom_Document, DOM Object
	 */
	private $_domRequest = null;

	/**
	 * @var EbayEnterprise_Dom_Document, DOM Object
	 */
	private $_domResponse = null;

	/**
	 * @var EbayEnterprise_Eb2cOrder_Helper_Data, helper Object
	 */
	private $_helper;

	/**
	 * @var EbayEnterprise_Eb2cCore_Model_Config_Registry, config Object
	 */
	private $_config;

	/**
	 * @var string, order id
	 */
	private $_orderId;

	public function __construct()
	{
		$this->_helper = Mage::helper('eb2corder');
		$this->_config = $this->_helper->getConfig();
	}

	/**
	 * cancel builds, sends Cancel Order Request; returns true or false if we got an answer.
	 * @param string $orderType, the order type
	 * @param string $orderId, the order id
	 * @param string $reasonCode, the reason code
	 * @param string $reason, the reason
	 * @return self
	 */
	public function buildRequest($orderType, $orderId, $reasonCode, $reason)
	{
		$this->_orderId = $orderId;
		$this->_domRequest = Mage::helper('eb2ccore')->getNewDomDocument();
		$cancelRequest = $this->_domRequest->addElement($this->_config->apiCancelDomRootNodeName, null, $this->_config->apiXmlNs)->firstChild;
		$cancelRequest->addAttribute('orderType', $orderType);
		$cancelRequest->addChild('CustomerOrderId', $this->_orderId)
			->addChild('ReasonCode', $reasonCode)
			->addChild('Reason', $reason);

		$this->_domRequest->formatOutput = true;
		return $this;
	}

	/**
	 * Send request to cancel order to EB2C.
	 *
	 * @return self
	 */
	public function sendRequest()
	{
		$response = Mage::getModel('eb2ccore/api')->request(
			$this->_domRequest,
			$this->_config->xsdFileCancel,
			$this->_helper->getOperationUri($this->_config->apiCancelOperation),
			$this->_helper->getConfig()->serviceOrderTimeout
		);
		if (trim($response) !== '') {
			// load load response with actual content
			$this->_domResponse = Mage::helper('eb2ccore')->getNewDomDocument();
			$this->_domResponse->loadXML($response);
		}
		return $this;
	}

	/**
	 * processing the request response from eb2c, throw exception if reponse status is not cancelled
	 *
	 * @throws EbayEnterprise_Eb2cOrder_Model_Cancel_Exception
	 * @return self
	 */
	public function processResponse()
	{
		if (trim($this->_domResponse->saveXML()) !== '') {
			$status = $this->_domResponse->getElementsByTagName('ResponseStatus')->item(0)->nodeValue;
			if (strtoupper(trim($status)) === 'CANCELLED') {
				Mage::dispatchEvent('eb2c_order_cancel_succeeded', array('order_id' => $this->_orderId));
			} else {
				Mage::dispatchEvent('eb2c_order_cancel_failed', array('order_id' => $this->_orderId));
				throw new EbayEnterprise_Eb2cOrder_Model_Cancel_Exception(
					Mage::helper('eb2corder')->__(self::ORDER_CANCEL_FAILURE_MESSAGE)
				);
			}
		}

		return $this;
	}
}

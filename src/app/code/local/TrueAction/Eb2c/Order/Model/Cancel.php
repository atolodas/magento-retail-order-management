<?php
/**
 * Generates an OrderCancel
 * @package Eb2c\Order
 * @author westm@trueaction.com
 *
 * Some events I *may* need to care about. I need to investigate whether 'order_cancel_after' ... it's my 
 *	first candidate for where I should hook the cancel request in.
 * 
 *  order_cancel_after
 */
class TrueAction_Eb2c_Order_Model_Cancel extends Mage_Core_Model_Abstract
{
	private $_domRequest = null;
	private $_domResponse = null;
	private $_helper;
	private $_config;

	public function _construct()
	{
		$this->_helper = Mage::helper('eb2corder');
		$this->_config = $this->_helper->getConfig();
	}

	/**
	 * cancel builds, sends Cancel Order Request; returns true or false if we got an answer. Throws exception
	 *	if something went wrong along the way.
	 *
	 * @param args array of arguments keyed as: 'order_type', 'order_id', 'reason_code', 'reason'
	 */
	public function cancel(array $args)
	{
		$consts = $this->_helper->getConstHelper();
		$this->_domRequest = $this->_helper->getDomDocument();
		$cancelRequest = $this->_domRequest->addElement($consts::CANCEL_DOM_ROOT_NODE_NAME, null, $consts::DOM_ROOT_NS)->firstChild;
		$cancelRequest->addAttribute('orderType', $args['order_type']);

		$cancelRequest->createChild('CustomerOrderId', $args['order_id']);
		$cancelRequest->createChild('ReasonCode', $args['reason_code']);
		$cancelRequest->createChild('Reason', $args['reason']);;

		$this->_domRequest->formatOutput = true;
		return $this->_transmit();
	}


	/**
	 * Handles communication with service endpoint. Either returns true or false when successfully receiving a valid
	 *	response or throws an exception if we can't get a valid response.
	 *
	 */
	private function _transmit()
	{
		$consts = $this->_helper->getConstHelper();
		$uri = $this->_helper->getOperationUri($consts::CANCEL_OPERATION);

		if( $this->_config->developerMode ) {
			$uri = $this->_config->developerCancelUri;
		}

		try {
			$response = $this->_helper->getCoreHelper()->callApi(
							$this->_domRequest,
							$uri,
							$this->_config->serviceOrderTimeout
						);

			$status='';
			$this->_domResponse = $this->_helper->getDomDocument();
			$this->_domResponse->loadXML($response);
			$elementSet = $this->_domResponse->getElementsByTagName('ResponseStatus');
			foreach( $elementSet as $element ) {
				$status = $element->nodeValue;
			}
		}
		catch(Exception $e) {
			Mage::throwException('Cancel request failed: ' . $e->getMessage());
		}
		
		return strcmp($status,'CANCELLED') ? false : true;
	}
}
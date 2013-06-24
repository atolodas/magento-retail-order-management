<?php
/**
 * Responsible for handling the AddressValidationResponse message from EB2C.
 */
class TrueAction_Eb2c_Address_Model_Validation_Response
	extends Mage_Core_Model_Abstract
{

	/**
	 * @var array
	 */
	protected static $_paths = array(
		'request_address'  => 'eb2c:AddressValidationResponse/eb2c:RequestAddress',
		'result_code'      => 'eb2c:AddressValidationResponse/eb2c:Result/eb2c:ResultCode',
		'result_errors'    => 'eb2c:AddressValidationResponse/eb2c:Result/eb2c:ErrorLocations/eb2c:ErrorLocation',
		'suggestion_count' => 'eb2c:AddressValidationResponse/eb2c:Result/eb2c:ResultSuggestionCount',
		'suggestions'      => 'eb2c:AddressValidationResponse/eb2c:Result/eb2c:SuggestedAddresses/eb2c:SuggestedAddress',
		'provider_error'  => 'eb2c:AddressValidationResponse/eb2c:Result/eb2c:ProviderErrorText'
	);

	/**
	 * @var TrueAction_Eb2c_Address_Helper_Data
	 */
	protected $_helper;

	/**
	 * @var TrueAction_Dom_Document
	 */
	protected $_doc;

	protected function _construct()
	{
		$this->_helper = Mage::helper('eb2caddress');
		$this->_doc = new TrueAction_Dom_Document();
	}

	/**
	 * Load the response message into the dom document.
	 */
	public function setMessage($message)
	{
		$this->_doc->loadXML($message);
		return $this;
	}

	/**
	 * Pass through to the TrueAction_Eb2c_Address_Helper_Data::getTextValueByXPath method.
	 * @param string $path
	 * @param string|array
	 */
	protected function _lookupPath($pathKey, DOMNode $context = null)
	{
		return $this->_helper->
			getTextValueByXPath(self::$_paths[$pathKey], $context ?: $this->_doc);
	}

	/**
	 * Gets the original address submitted to the service.
	 * @return Mage_Customer_Model_Address
	 */
	public function getOriginalAddress()
	{
		$xpath = new DOMXPath($this->_doc);
		$physicalAddressElement = $xpath->query(self::$_paths['request_address'])->item(0);
		return $this->_helper->physicalAddressXmlToAddress($physicalAddressElement);
	}

	/**
	 * Get the list of suggested addresses returned by the service.
	 * @return Mage_Customer_Model_Address[]
	 */
	public function getAddressSuggestions()
	{
		$xpath = new DOMXPath($this->_doc);
		$physicalAddressElements = $xpath->query(self::$_paths['suggestions']);
		$suggestionAddresses = array();
		foreach ($physicalAddressElements as $physicalAddress) {
			$suggestionAddresses[] = $this->_helper->physicalAddressXmlToAddress($physicalAddress);
		}
		return $suggestionAddresses;
	}

	/**
	 * Indicates if the address should be considered valid.
	 */
	public function isAddressValid()
	{
		switch ($this->_lookupPath('result_code')) {
			case 'V':
				return true;
			case 'C':
				if ((int) $this->_lookupPath('suggestion_count') <= 1) {
					return true;
				} else {
					return false;
				}
			case 'K':
				return false;
			case 'N':
				return true;
			case 'U':
				Mage::log('', Zend_Log::WARN);
				return true;
			case 'T':
				Mage::log('', Zend_Log::WARN);
				return true;
			case 'P':
				Mage::log('', Zend_Log::WARN);
				Mage::log($this->_lookupPath('provider_error'), Zend_Log::DEBUG);
				return true;
			case 'M':
				Mage::log('', Zend_Log::WARN);
				return true;
			default:
				Mage::log('', Zend_Log::WARN);
				return true;
		}
	}

}
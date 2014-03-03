<?php
/**
 * represent a single attribute value for a store.
 */
class TrueAction_Eb2cProduct_Model_Pim_Attribute
{
	/**
	 * Weightins of various nodes off of the Item node. Used for sorting the
	 * attributes in the order they need to appear in the feed DOM
	 * @var array
	 */
	protected $_pathBaseWeights = array(
		'ItemId' => 0,
		'StyleID' => 1,
		'ProductLinks' => 1,
		'CategoryLinks' => 2,
		'BaseAttributes' => 3,
		'ExtendedAttributes' => 4,
		'CustomAttributes' => 5,
		'PriceEvent' => 6,
		'HTSCode' => 7,
		'Images' => 8,
	);
	/**
	 * constructor compatible with magento factory initilialization.
	 * @param array $initParams associative array used for initialization.
	 */
	public function __construct(array $initParams=array())
	{
		if ($missingArgs = array_diff(array('destination_xpath', 'sku', 'value'), array_keys($initParams))) {
			trigger_error(
				sprintf('%s missing arguments: %s are required', __METHOD__, implode(', ', $missingArgs)),
				E_USER_ERROR
			);
		}
		if (!$initParams['value'] instanceof DOMNode) {
			trigger_error(
				sprintf('%s called with invalid value argument. Must be DOMNode', __METHOD__),
				E_USER_ERROR
			);
		}
		$this->destinationXpath = $initParams['destination_xpath'];
		$this->sku = $initParams['sku'];
		$this->language = isset($initParams['language']) ? $initParams['language'] : null;
		$this->value = $initParams['value'];
		$this->_stringValue = $this->_stringifyValue();
	}
	/**
	 * Get the string representation of the DOMNode used as the value for this
	 * attribute.
	 * @return string
	 */
	protected function _stringifyValue()
	{
		$doc = new TrueAction_Dom_Document();
		$importValue = $doc->importNode($this->value, true);
		$doc->appendChild($importValue);
		return $doc->C14N();
	}
	/**
	 * Override the toString method to return a sortable, comparable "hash" of the
	 * attribute data.
	 * @return string
	 */
	public function __toString()
	{
		return $this->_weightDestination() . $this->destinationXpath . $this->language . $this->_stringValue;
	}
	/**
	 * Get a relative sort weighting for the start of the attribute destination
	 * path. Force it to be after any known ones.
	 * @return int
	 */
	protected function _weightDestination()
	{
		$destinationParts = explode('/', $this->destinationXpath);
		$basePath = $destinationParts[0];
		return isset($this->_pathBaseWeights[$basePath]) ?
			$this->_pathBaseWeights[$basePath] :
			count($this->_pathBaseWeights);
	}
	/**
	 * xpath describing where the attribute's data should be built into.
	 * @var string
	 */
	public $destinationXpath;
	/**
	 * unique product identifier string.
	 * @var string
	 */
	public $sku;
	/**
	 * language code for the value, if attribute value is not translated, this
	 * property will be null
	 * @var string
	 */
	public $language;
	/**
	 * data to be added to the document.
	 * @var DOMDocumentFragment
	 */
	public $value;
	/**
	 * string representation of the value
	 * @var string
	 */
	protected $_stringValue;
}
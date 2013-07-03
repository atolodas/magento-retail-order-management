<?php
/**
 * @package Eb2c
 */
class TrueAction_Eb2c_Order_Helper_Data extends Mage_Core_Helper_Abstract
{
	public $config;
	public $apiModel;
	public $coreHelper;
	public $constHelper;


	/**
	 * Gets a combined configuration model from core and order
	 *
	 * @return 
	 */
	public function getConfig()
	{
		if( !$this->config ) {
			$this->config = Mage::getModel('eb2ccore/config_registry')
							->addConfigModel(Mage::getModel('eb2corder/config'))
							->addConfigModel(Mage::getModel('eb2ccore/config'));
		}
		return $this->config;
	}

	/**
	 * Instantiate and save constants-values helper
	 *
	 * @return TrueAction_Eb2c_Order_Helper_Constants
	 */
	public function getConstHelper()
	{
		if (!$this->constHelper) {
			$this->constHelper = Mage::helper('eb2corder/constants');
		}
		return $this->constHelper;
	}

	/**
	 * Instantiate and save assignment of Core helper
	 *
	 * @return TrueAction_Eb2c_Core_Helper
	 */
	public function getCoreHelper()
	{
		if (!$this->coreHelper) {
			$this->coreHelper = Mage::helper('eb2ccore');
		}
		return $this->coreHelper;
	}

	/**
	 * Generate Eb2c API operation Uri from configuration settings and constants
	 *
	 * @return string, the generated operation Uri
	 */
	public function getOperationUri($operation)
	{
		$consts = $this->getConstHelper();
		$apiUri = $this->getCoreHelper()->getApiUri($consts::SERVICE, $operation);
		return $apiUri;
	}

	/**
	 * Return a usable DOMDocument of the TrueAction variety:
	 *
	 * @return TrueAction_Dom_Document
	 */
	public function getDomDocument()
	{
		return new TrueAction_Dom_Document('1.0', 'UTF-8');
	}


	/**
	 * Return the Core API model for issuing requests/ retrieving response:
	 */
	public function getApiModel()
	{
		if( !$this->apiModel ) {
			$this->apiModel = Mage::getModel('eb2ccore/api');
		}
		return $this->apiModel;
	}
}

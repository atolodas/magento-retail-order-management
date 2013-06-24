<?php
/**
 */
class TrueAction_Eb2c_Tax_Test_Overrides_Helper_DataTests extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * @test
	 * */
	public function testRewrite()
	{
		$hlpr = Mage::helper('tax');
		$this->assertSame(
			'TrueAction_Eb2c_Tax_Overrides_Helper_Data',
			get_class($hlpr)
		);
	}

	public function testNamespaceUri()
	{
		$this->assertSame(
			'http://api.gsicommerce.com/schema/checkout/1.0',
			Mage::helper('tax')->getNamespaceUri()
		);
	}
}
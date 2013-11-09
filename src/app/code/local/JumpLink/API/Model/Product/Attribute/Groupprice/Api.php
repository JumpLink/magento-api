<?php
class JumpLink_API_Model_Product_Attribute_Groupprice_Api extends Mage_Api_Model_Resource_Abstract
{
	function info($productId) {
		return "Hello World! My argument is : " . $productId;
	}

	function update($productId, $grouprices) {

		if ( !is_array($grouprices) ) {
			$this->_fault('data_invalid', 'Invalid Group Prices');
			return null;
		}

		foreach ($grouprices as $grouprice) {
			if ( !is_array($grouprice) || !isset($grouprice['website_id']) || !isset($grouprice['cust_group']) || !isset($grouprice['price']) ) {
				$this->_fault('data_invalid', 'Invalid Group Prices');
				return null;
			}
		}

		$product = Mage::getModel('catalog/product')->setStoreId($storeViewID)->load($productId);
		$product->setData('group_price', $grouprices);
		$product->save();
		return true;
	}
}
<?php
class JumpLink_API_Model_Product_Attribute_Set_Api extends Mage_Catalog_Model_Product_Attribute_Set_Api_V2 {
  
  /**
   * Retrieve attribute set list with attributes
   *
   * @return array
   */
  public function items_info()
  {
    $product_attribute = new JumpLink_API_Model_Product_Attribute_Api;
    $attributesets = $this->items();
    for ($i=0; $i < count($attributesets); $i++) { 
      $attributesets[$i]['attributes'] = $product_attribute->items($attributesets[$i]['set_id']);
    }
    return $attributesets;
  }

  /**
   * Retrieve attribute set list with full information about attribute with list of options
   *
   * @return array
   */
  public function export()
  {
    $product_attribute = new JumpLink_API_Model_Product_Attribute_Api;
    $attributesets = $this->items();
    for ($i=0; $i < count($attributesets); $i++) { 
      $attributesets[$i]['attributes'] = $product_attribute->items_info($attributesets[$i]['set_id']);
    }
    return $attributesets;
  }

}
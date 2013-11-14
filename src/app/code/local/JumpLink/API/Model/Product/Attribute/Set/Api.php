<?php
class JumpLink_API_Model_Product_Attribute_Set_Api extends Mage_Catalog_Model_Product_Attribute_Set_Api_V2 {
  
  /**
   * Retrieve attribute set info
   *
   * @param int $setId
   * @return array
   */
  public function info($setId)
  {
    $product_attribute = new JumpLink_API_Model_Product_Attribute_Api;
    $attributeSetModel = Mage::getModel("eav/entity_attribute_set");
    $attributeSetModel->load($setId);
    $attributeSetName  = $attributeSetModel->getAttributeSetName();
    $result = array(
        'set_id'     => $setId,
        'name'       => $attributeSetModel->getAttributeSetName(),
        'attributes' => $product_attribute->items($setId)
    );
    return $result;
  }

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

  protected function exportAll()
  {
    $product_attribute = new JumpLink_API_Model_Product_Attribute_Api;
    $attributesets = $this->items();
    for ($i=0; $i < count($attributesets); $i++) { 
      $attributesets[$i]['attributes'] = $product_attribute->items_info($attributesets[$i]['set_id']);
    }
    return $attributesets;
  }

  protected function exportOne($setId)
  {
    $product_attribute = new JumpLink_API_Model_Product_Attribute_Api;
    $attributeSetModel = Mage::getModel("eav/entity_attribute_set");
    $attributeSetModel->load($setId);
    $attributeSetName  = $attributeSetModel->getAttributeSetName();
    $result = array(
        'set_id'     => $setId,
        'name'       => $attributeSetModel->getAttributeSetName(),
        'attributes' => $product_attribute->items_info($setId)
    );
    return $result;
  }

  /**
   * Retrieve a list of or one attribute set with full information about attribute with list of options
   *
   * @param int $setId
   * @return array
   */
  public function export($setId = null)
  {
    if (isset($setId))
      return $this->exportOne($setId);
    else
      return $this->exportAll();
  }

}
<?php
class JumpLink_API_Model_Product_Attribute_Set_Api extends Mage_Catalog_Model_Product_Attribute_Set_Api_V2 {
  
  protected function normalize_id(&$item) {
    if(isset($item['set_id'])) {
      $item['id'] = intval($item['set_id']);
      unset($item['set_id']);
    }
  }

  protected function normalize_ids(&$collection) {
    foreach ($collection as $index => $item) {
      $this->normalize_id($collection[$index]);
    }
  }

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
        'id'         => intval($setId),
        'name'       => $attributeSetModel->getAttributeSetName(),
        'attributes' => $product_attribute->items($setId)
    );
    return $result;
  }

  /**
   * Retrieve attribute set list
   *
   * @return array
   */
  public function items()
  {
    $entityType = Mage::getModel('catalog/product')->getResource()->getEntityType();
    $collection = Mage::getResourceModel('eav/entity_attribute_set_collection')
        ->setEntityTypeFilter($entityType->getId());

    $result = array();
    foreach ($collection as $attributeSet) {
        $result[] = array(
            'id'     => intval($attributeSet->getId()),
            'name'   => $attributeSet->getAttributeSetName()
        );

    }

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
      if(isset($attributesets[$i]['set_id'])) {
        $set_id = $attributesets[$i]['set_id'];
      } else {
        $set_id = $attributesets[$i]['id'];
      }
      $attributesets[$i]['attributes'] = $product_attribute->items($set_id);
      $this->normalize_id($attributesets[$i]);
      //print_r($attributesets[$i]);
    }
    return $attributesets;
  }

  protected function exportAll()
  {
    $product_attribute = new JumpLink_API_Model_Product_Attribute_Api;
    $attributesets = $this->items();
    for ($i=0; $i < count($attributesets); $i++) { 
      if(isset($attributesets[$i]['set_id'])) {
        $set_id = $attributesets[$i]['set_id'];
      } else {
        $set_id = $attributesets[$i]['id'];
      }
      $attributesets[$i]['attributes'] = $product_attribute->items_info($set_id);
      $this->normalize_id($attributesets[$i]);
      //print_r($attributesets[$i]);
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
        'id'         => intval($setId),
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
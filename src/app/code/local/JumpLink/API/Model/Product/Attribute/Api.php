<?php
class JumpLink_API_Model_Product_Attribute_Api extends Mage_Catalog_Model_Product_Attribute_Api_V2 {

  /**
   * Retrieve attributes from specified attribute set with full information about attribute with list of options
   *
   * @param int $setId
   * @return array
   */
  public function items_info($setId)
  {
    $attributes = $this->items($setId);
    for ($i=0; $i < count($attributes); $i++) { 
      $attributes[$i] = $this->info($attributes[$i]['attribute_id']);
    }
    return $attributes;
  }

}
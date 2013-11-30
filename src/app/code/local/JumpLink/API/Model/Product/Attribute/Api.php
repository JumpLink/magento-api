<?php
class JumpLink_API_Model_Product_Attribute_Api extends Mage_Catalog_Model_Product_Attribute_Api_V2 {

  protected function set_attribute_type(&$attribute) {
    if($attribute['attribute_code'] == 'category_ids'|| $attribute['attribute_code'] == 'website_ids')
      $attribute['type'] = "array of integer";
    else
      switch ($attribute['frontend_input']) {
        case "text":
          switch ($attribute['additional_fields'][0]["value"]) {
            case 'validate-digits':
              $attribute['type'] = "integer";
            break;
            case 'validate-number':
               $attribute['type'] = "float";
            break;
            case 'validate-email':
              $attribute['type'] = "email";
            case 'validate-url':
              $attribute['type'] = "url";
            case 'validate-alpha':
              $attribute['type'] = "alpha";
            case 'validate-alphanum':
              $attribute['type'] = "alphanumeric";
            default:
              $attribute['type'] = "string";
            break;
          }
        break;
        case "date":
        case "boolean":
        case "weight":
        case "price":
          $attribute['type'] = $attribute['frontend_input'];
        break;
        case "textarea":
          $attribute['type'] = "text";
        break;
        case "select": // TODO
        case null:
        default:
          $attribute['type'] = "string";
        break;
      }
  }

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

  /**
   * Get full information about attribute with list of options
   *
   * @param integer|string $attribute attribute ID or code
   * @return array
   */
  public function info($attribute)
  {
    $result = parent::info($attribute);
    $this->set_attribute_type($result);
    return $result;
  }

}
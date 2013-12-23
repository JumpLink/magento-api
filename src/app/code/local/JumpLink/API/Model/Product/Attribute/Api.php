<?php
class JumpLink_API_Model_Product_Attribute_Api extends Mage_Catalog_Model_Product_Attribute_Api_V2 {

  protected function set_attribute_type($attribute) {
    if($attribute['attribute_code'] == 'category_ids'|| $attribute['attribute_code'] == 'website_ids')
      $attribute['type'] = "array of integer";
    else if ($attribute['attribute_code'] == 'tier_price')
      $attribute['type'] = "tier_price";
    else if ($attribute['attribute_code'] == 'updated_at' || $attribute['attribute_code'] == 'created_at')
      $attribute['type'] = "date";
    else if ($attribute['attribute_code'] == 'status')
      $attribute['type'] = "string";
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
        case "select": // TODO
          $attribute['type'] = $attribute['frontend_input'];
        break;
        case "textarea":
          $attribute['type'] = "text";
        break;
        case null:
          $attribute['type'] = "string";
        break;
        default:
          $attribute['type'] = $attribute['frontend_input'];
        break;
      }
    return $attribute;
  }


  protected function change_attribute_code ($attribute) {
    switch ($attribute['code']) {
      case 'updated_at':
        $attribute['code'] = 'updatedAt';
      break;
      case 'created_at':
        $attribute['code'] = 'createdAt';
      break;
      // WORKAROUND maybe move to sails.js?
      // case 'price_type':
      // case 'sku_type':
      // case 'weight_type':
      // case 'shipment_type':
      // case 'links_purchased_separately':
      // case 'samples_title':
      // case 'links_title':
      //   $attribute['required'] = false;
      // break;
    }
    return $attribute;
  }

  protected function set_attribute_code_as_index ($attribute) {
    $code = $attribute['code'];
    unset($attribute['code']);
    return array($code => $attribute);
  }

  protected function transorm_datatypes ($attribute) {
    foreach ($attribute as $key => $value) {
      switch ($key) {
        case 'attribute_id':
          $attribute['id'] = intval($attribute['attribute_id']);
          unset ($attribute['attribute_id']);
        break;
        case 'attribute_code':
          $attribute['code'] = $attribute['attribute_code'];
          unset ($attribute['attribute_code']);
        break;
        case 'is_unique':
          $attribute['unique'] = ($attribute['is_unique'] == true);
          unset ($attribute['is_unique']);
        break;
        case 'required':
          $attribute['required'] = ($attribute['required'] == true);
        break;
        case 'is_required':
          $attribute['required'] = ($attribute['is_required'] == true);
          unset ($attribute['is_required']);
        break;
        case 'is_configurable':
          $attribute['configurable'] = ($attribute['is_configurable'] == true);
          unset ($attribute['is_configurable']);
        break;
        case 'is_searchable':
          $attribute['searchable'] = ($attribute['is_searchable'] == true);
          unset ($attribute['is_searchable']);
        break;
        case 'is_visible_in_advanced_search':
          $attribute['visible_in_advanced_search'] = ($attribute['is_visible_in_advanced_search'] == true);
          unset ($attribute['is_visible_in_advanced_search']);
        break;
        case 'is_comparable':
          $attribute['comparable'] = ($attribute['is_comparable'] == true);
          unset ($attribute['is_comparable']);
        break;
        case 'is_used_for_promo_rules':
          $attribute['used_for_promo_rules'] = ($attribute['is_used_for_promo_rules'] == true);
          unset ($attribute['is_used_for_promo_rules']);
        break;
        case 'is_visible_on_front':
          $attribute['visible_on_front'] = ($attribute['is_visible_on_front'] == true);
          unset ($attribute['is_visible_on_front']);
        break;
        case 'used_in_product_listing':
          $attribute['used_in_product_listing'] = ($attribute['used_in_product_listing'] == true);
        break;
        default:
        # code...
        break;
      }
    }
    return $attribute;
  }

  protected function add_default_attributes ($normalized_attributes) {
    $normalized_attributes[]        = array("stock_data" => array("required" => true,  "unique" => false, "type" => "stock_data"));
    $normalized_attributes[]        = array("id" => array("required" => true,  "unique" => true,  "type" => "integer"));
    $normalized_attributes[]        = array("website_ids" => array("required" => true,  "unique" => false, "type" => "array of integer"));
    $normalized_attributes[]        = array("set" => array("required" => true,  "unique" => false, "type" => "integer"));
    $normalized_attributes[]        = array("stores" => array("required" => false,  "unique" => false, "type" => "json"));
    return $normalized_attributes;
  }

  protected function normalize_attribute ($attribute) {
    $attribute = $this->set_attribute_type($attribute);
    $attribute = $this->transorm_datatypes($attribute);
    $attribute = $this->change_attribute_code($attribute);
    $attribute = $this->set_attribute_code_as_index($attribute);
    return $attribute;
  }
  /**
   * Retrieve attributes from specified attribute set
   *
   * @param int $setId
   * @return array
   */
  public function items($setId)
  {
    $results = parent::items($setId);
    $normalized_result = array();

    foreach ($results as $index => $attribute) {
      $tmp_result = $this->normalize_attribute($results[$index]);
      // Workaround to use attribute_code as index
      foreach ($tmp_result as $key => $value) {
        $normalized_result[$key] = $value;
      }
    }

    return $normalized_result;
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
    // for ($i=0; $i < count($attributes); $i++) { 
    //   $attributes[$i] = $this->info($attributes[$i]['id']);
    // }
    $result = array();
    foreach ($attributes as $key => $value) {
      //print_r($value);
      $result[] = $this->info($value['id']);
      //$result[] = $this->info($key);
    }
    $result = $this->add_default_attributes($result);
    return $result;
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
    $result = $this->normalize_attribute($result);
    return $result;
  }

  /**
   * Return true if looking_attribute_code exists in current_attributes list
   */
  protected function attributeExists ($current_attributes, $looking_attribute_code) {
    foreach ($current_attributes as $index => $attribute) {
      reset($attribute);
      $attribute_code = key($attribute);
      if($attribute_code == $looking_attribute_code)
        return true;
    }
    return false;
  }

  /**
   * set required true only id this attribute exists in all attribute sets
   */
  protected function checkIfAllAttributesInCurrentAttributes ($current_attributes, $result_attributes) {
    foreach ($result_attributes as $attribute_code => $result_attribute) {
      if(!$this->attributeExists($current_attributes, $attribute_code)) {
        $result_attributes[$attribute_code]['required'] = false;
      }
    }
    return $result_attributes;
  }

  /**
   * Get full list of all avaible attributes in all attributesets
   *
   * @return array
   */
  public function all()
  {
    $attributeset_api = new JumpLink_API_Model_Product_Attribute_Set_Api;
    $attributesets = $attributeset_api->export();
    $result = array();
    foreach ($attributesets as $as_key => $attributeset) {
      foreach ($attributesets[$as_key]['attributes'] as $index => $attribute) {
        // print("index");
        // print_r($index);
        reset($attribute);
        $attribute_code = key($attribute);
        // Merge previous setted attribute with current attribute
        if(isset($result[$attribute_code])) {
          // required is true only if it is true in all attribute sets
          if($result[$attribute_code]['required'] === true) {
            $result[$attribute_code]['required'] = $attribute[$attribute_code]['required'];
          } else {
            $result[$attribute_code]['required'] = false;
          }
          // unique is true only if it is true in all attribute sets
          if($result[$attribute_code]['unique'] === true) {
            $result[$attribute_code]['unique'] = $attribute[$attribute_code]['unique'];
          } else {
            $result[$attribute_code]['unique'] = false;
          }
        } else {
          $result[$attribute_code] = $attribute[$attribute_code];
        }
      }
      $result = $this->checkIfAllAttributesInCurrentAttributes($attributesets[$as_key]['attributes'], $result);
    }
    return $result;
  }

}
<?php
class JumpLink_API_Model_Product_Api extends Mage_Catalog_Model_Product_Api_V2
{

  protected function extract_attribute_option(&$set, $key) {
    foreach ($set['attributes'] as $index => $attribute) {
      if($attribute['attribute_code'] == $key) {
        unset($set['attributes'][$index]);
        return $attribute;
      }
    }
  }

  protected function set_attribute_type(&$attribute) {
    if($attribute['options']['attribute_code'] == 'category_ids')
      $attribute['options']['data_type'] = "integer";
    else
      switch ($attribute['options']['frontend_input']) {
        case "text":
          switch ($attribute['options']['additional_fields'][0]["value"]) {
            case 'validate-digits':
              $attribute['options']['data_type'] = "integer";
            break;
            case 'validate-number':
               $attribute['options']['data_type'] = "float";
            break;
            case 'validate-email':
              $attribute['options']['data_type'] = "email";
            break;
            case 'validate-url':
              $attribute['options']['data_type'] = "url";
            break;
            case 'validate-alpha':
              $attribute['options']['data_type'] = "alphanumeric";
            break;
            case 'validate-alphanum': // alphanumeric and numeric
              $attribute['options']['data_type'] = "alphanum";
            break;
            default:
              $attribute['options']['data_type'] = $attribute['options']['frontend_input'];
            break;
          }
        break;
        case "select":
        case "price":
        case "date":
        case "textarea":
        case "boolean":
        case "weight":
          $attribute['options']['data_type'] = $attribute['options']['frontend_input'];
        break;
        case null:
        default:
          $attribute['options']['data_type'] = "text";
        break;
      }
  }

  protected function transform_attribute(&$attribute) {
    
    switch ($attribute['options']['data_type']) {
      case 'integer':
        if(is_array ($attribute['value']))
          foreach ($attribute['value'] as $key => $value)
            $attribute['value'][$key] = intval($value);
        else
          $attribute['value'] = intval($attribute['value']);
      break;
      case 'float':
        if(is_array ($attribute['value']))
          foreach ($attribute['value'] as $key => $value)
            $attribute['value'][$key] = floatval($value);
        else
          $attribute['value'] = floatval($attribute['value']);
      break;
      case 'boolean':
        if(is_array ($attribute['value']))
          foreach ($attribute['value'] as $key => $value)
            $attribute['value'][$key] = boolval($value);
        else
          $attribute['value'] = boolval($attribute['value']);
      break;
      case 'email':
      case 'url':
      case 'alphanumeric':
      case 'alphanum':
      case 'text':
      case 'select':
      case 'price':
      case 'date':
      case 'textarea':
      default:
      break;
    }
  }


  protected function normalize(&$product) {
    foreach ($product as $attribute_key => $attribute_value) {
      if($attribute_key != "set" && $attribute_key != "product_id" && $attribute_key != "sku") { // TODO iterate array
        $product[$attribute_key] = array('value' => $attribute_value, 'options' => $this->extract_attribute_option($product['set'], $attribute_key));
        $product[$attribute_key]['options']['attribute_code'] = $attribute_key; // Two is Better / doppelt hält besser
        $this->set_attribute_type($product[$attribute_key]);
        //print("attribute_key: $attribute_key\n");
        $this->transform_attribute($product[$attribute_key] );
        //print("attribute_value: ".$product[$attribute_key]."\n");
      } else {
        if ($$attribute_key == "product_id")
          $product[$attribute_key] = intval($attribute_value);
      }
    }
    $product['set']['unused_attributes'] = $product['set']['attributes'];
    unset($product['set']['attributes']);
  }

  protected function export_attributeset($setId) {
    if (isset($setId)) {
      $attributeset = new JumpLink_API_Model_Product_Attribute_Set_Api; // TODO set on custructor or init
      return $attributeset->export($setId);
    } else {
      print ("attributeset not set!\n");
      return array();
    }
  }

  protected function get_attributeset($setId) {
    if (isset($setId)) {
      $attributeset = new JumpLink_API_Model_Product_Attribute_Set_Api; // TODO set on custructor or init
      return $attributeset->info($setId);
    } else {
      print ("attributeset not set!\n");
      return array();
    }
  }

  protected function get_attributes($product) {
    if (isset($product)) {
      $tmp_result = array(
          'product_id' => $product->getId(),
          'sku'        => $product->getSku(),
          'name'       => $product->getName(),
          'set'        => $product->getAttributeSetId(),
          'type'       => $product->getTypeId(),
          'category_ids' => $product->getCategoryIds(),
          'website_ids'  => $product->getWebsiteIds()
      );

      $allAttributes = array();
      if (!empty($attributes->attributes)) {
          $allAttributes = array_merge($allAttributes, $attributes->attributes);
      } else {
          foreach ($product->getTypeInstance(true)->getEditableAttributes($product) as $attribute) {
              if ($this->_isAllowedAttribute($attribute, $attributes)) {
                  $allAttributes[] = $attribute->getAttributeCode();
              }
          }
      }

      $_additionalAttributeCodes = array();
      if (!empty($attributes->additional_attributes)) {
          foreach ($attributes->additional_attributes as $k => $_attributeCode) {
              $allAttributes[] = $_attributeCode;
              $_additionalAttributeCodes[] = $_attributeCode;
          }
      }

      $_additionalAttribute = 0;
      foreach ($product->getTypeInstance(true)->getEditableAttributes($product) as $attribute) {
          if ($this->_isAllowedAttribute($attribute, $allAttributes)) {
              if (in_array($attribute->getAttributeCode(), $_additionalAttributeCodes)) {
                  $tmp_result['additional_attributes'][$_additionalAttribute]['key'] = $attribute->getAttributeCode();
                  $tmp_result['additional_attributes'][$_additionalAttribute]['value'] = $product
                      ->getData($attribute->getAttributeCode());
                  $_additionalAttribute++;
              } else {
                  $tmp_result[$attribute->getAttributeCode()] = $product->getData($attribute->getAttributeCode());
              }
          }
      }
      //print("product->product_id): ".$tmp_result['product_id']."\n");
      return $tmp_result;
    } else {
      print ("product not set\n");
      return array();
    }
  }

  /**
   * Retrieve list of products with much more info
   *
   * @param array $filters
   * @param string|int $store
   * @param string $identifierType OPTIONAL If 'sku' - search product by SKU, if any except for NULL - search by ID,
   *                                        otherwise - try to determine identifier type automatically
   * @return array
   */
  public function items_info($filters = null, $store = null)
  {
    $collection = Mage::getModel('catalog/product')->getCollection()
        ->addStoreFilter($this->_getStoreId($store))
        ->addAttributeToSelect('name');

    $preparedFilters = array();
    if (isset($filters->filter)) {
        foreach ($filters->filter as $_filter) {
            $preparedFilters[$_filter->key] = $_filter->value;
        }
    }
    if (isset($filters->complex_filter)) {
        foreach ($filters->complex_filter as $_filter) {
            $_value = $_filter->value;
            $preparedFilters[$_filter->key] = array(
                $_value->key => $_value->value
            );
        }
    }

    if (!empty($preparedFilters)) {
        try {
            foreach ($preparedFilters as $field => $value) {
                if (isset($this->_filtersMap[$field])) {
                    $field = $this->_filtersMap[$field];
                }

                $collection->addFieldToFilter($field, $value);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }
    }

    $results = array();

    foreach ($collection as $product) {
      $tmp_result = $this->get_attributes($product);
      $tmp_result['set'] = $this->get_attributeset($tmp_result['set']);
      print("product->product_id): ".$tmp_result['product_id']."\n");
      $results[] = $tmp_result;
    }
    return $results;
  }

  /**
   * Retrieve list of all products from store
   *
   * @param string|int $store
   * @return array
   */
  public function items_all($store = null)
  {
    $collection = Mage::getModel('catalog/product')->getCollection()
      ->addStoreFilter($this->_getStoreId($store))
      ->addAttributeToSelect('name');

    $results = array();

    foreach ($collection as $product) {
      $tmp_result = $this->get_attributes($product);
      $tmp_result['set'] = $this->get_attributeset($tmp_result['set']);
      print("product->product_id): ".$tmp_result['product_id']."\n");
      $results[] = $tmp_result;
    }
    return $results;
  }

  /**
   * Retrieve list of products with much more info using the ImportExport Module
   *
   * @return array
   */
  protected function exportAll()
  {
    $product_export = new Mage_ImportExport_Model_Export_Entity_Product; // TODO set on custructor or init
    $array_writer = new JumpLink_ImportExport_Model_Export_Adapter_Array; // TODO set on custructor or init
    $product_export->setWriter($array_writer);
    return $product_export->export();
  }

  /**
   * Retrieve product info
   * TODO get product info for all stores
   *
   * @param int|string $productId
   * @param string|int $store
   * @param stdClass $attributes
   * @return array
   */
  protected function exportOne($productId, $store = null, $attributes = null, $identifierType = null)
  {
    $info = parent::info($productId, $store, $attributes, $identifierType);
    $info['set'] = $this->export_attributeset($info['set']);
    $this->normalize($info);
    return $info;
  }

  /**
   * Retrieve list of products with much more info using the ImportExport Module
   *
   * @return array
   */
  public function export($productId=null, $store = null, $attributes = null, $identifierType = null)
  {
    if (isset($productId))
      return $this->exportOne($productId, $store, $attributes, $identifierType);
    else
      return $this->exportAll();
  }

  /**
   * Retrieve product info
   *
   * @param int|string $productId
   * @param string|int $store
   * @param stdClass $attributes
   * @return array
   */
  public function info($productId, $store = null, $attributes = null, $identifierType = null)
  {
    $info = parent::info($productId, $store, $attributes, $identifierType);
    $info['set'] = $this->get_attributeset($info['set']);
    return $info;
  }
}
<?php
class JumpLink_API_Model_Product_Api extends Mage_Catalog_Model_Product_Api_V2
{

  protected function findAttributeInAttributeSet(&$set, $key) {
    foreach ($set['attributes'] as $index => $attribute) {
      if($attribute['attribute_code'] == $key) {
        return $index;
      }
    }
  }

  protected function extract_attribute_option(&$set, $key) {
    $index = $this->findAttributeInAttributeSet($set, $key);
    $attribute = $set['attributes'][$index];
    unset($set['attributes'][$index]);
    return $attribute;
  }

  protected function transform_attribute(&$value, $type) {
    
    switch ($type) {
      case 'integer':
        if(is_array ($value))
          foreach ($value as $key => $value)
            $value[$key] = intval($value);
        else
          $value = intval($value);
      break;
      case 'float':
      case 'weight':
      case 'price':
        if(is_array ($value))
          foreach ($value as $key => $value)
            $value[$key] = floatval($value);
        else
          $value = floatval($value);
      break;
      case 'boolean':
        if(is_array ($value))
          foreach ($value as $key => $value)
            $value[$key] = ($value == true);
        else
          $value = ($value == true);
      break;
      case 'array of integer':
        if(!is_array ($value))
          $value = array($value);
        foreach ($value as $key => $value)
          $value[$key] = intval($value);
      break;
      case 'array of boolean':
        if(!is_array ($value))
          $value = array($value);
        foreach ($value as $key => $value)
          $value[$key] = ($value == true);
      break;
      case 'array of float':
        if(!is_array ($value))
          $value = array($value);
        foreach ($value as $key => $value)
          $value[$key] = floatval($value);
      break;
      case 'array':
        if(!is_array ($value))
          $value = array($value);
      break;
      case 'date':
      case 'email':
      case 'url':
      case 'alpha':
      case 'alphanumeric':
      case 'text':
      case 'string':
      default:
      break;
    }
  }

  protected function normalize_id(&$product) {
    if(isset($product['product_id'])) {
      $product['id'] = intval($product['product_id']);
      unset($product['product_id']);
    }
  }

  protected function normalize_ids(&$collection) {
    foreach ($collection as $index => $product) {
      $this->normalize_id($collection[$index]);
    }
  }

  protected function normalizeWithIntegratedSet(&$product) {
    foreach ($product as $attribute_key => $attribute_value) {
      if($attribute_key != "set" && $attribute_key != "product_id" && $attribute_key != "sku") {
        $product[$attribute_key] = array('value' => $attribute_value, 'options' => $this->extract_attribute_option($product['set'], $attribute_key));
        $product[$attribute_key]['options']['attribute_code'] = $attribute_key; // Two is Better / doppelt hält besser
        $this->transform_attribute($product[$attribute_key]['value'], $product[$attribute_key]['options']['type'] );
      }
    }
    $product["product_id"] = intval($product["product_id"]);
    $product['id'] = $product["product_id"];
    $product['set']['unused_attributes'] = $product['set']['attributes'];
    unset($product['set']['attributes']);
  }

  protected function normalize(&$product, $attributeset) {
    foreach ($product as $attribute_key => $attribute_value) {
      if($attribute_key != "set" && $attribute_key != "product_id" && $attribute_key != "sku") {
        $attributeset_index = $this->findAttributeInAttributeSet($attributeset, $attribute_key);
        //print_r($attribute_key."\n");
        //print("\n");
        //print_r($product[$attribute_key]);
        //print("\n");
        //print_r($attributeset['attributes'][$attributeset_index]['type']);
        //print("\n\n");
        $this->transform_attribute($product[$attribute_key], $attributeset['attributes'][$attributeset_index]['type'] );
      }
    }
    $this->normalize_id ($product);
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
          'id'         => intval($product->getId()),
          'sku'        => $product->getSku(),
          'name'       => $product->getName(),
          'set'        => intval($product->getAttributeSetId()),
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
   * Retrieve list of products with basic info (id, sku, type, set, name)
   *
   * @param array $filters
   * @param string|int $store
   * @return array
   */
  public function items($filters = null, $store = null) {
    $result = parent::items($filters, $store);
    $this->normalize_ids($result);
    return $result; 
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
      $this->normalize_id ($tmp_result);
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
      $this->normalize_id ($tmp_result);
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
    $result = $product_export->export();
    $this->normalize_ids ($result);
    return $result;
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
  protected function exportOne($productId, $store = null, $attributes = null, $identifierType = null, $integrate_set = false, $normalize = true)
  {
    $info = parent::info($productId, $store, $attributes, $identifierType);
    if($integrate_set) {
      $info['set'] = $this->export_attributeset($info['set']);
      if($normalize)
        $this->normalizeWithIntegratedSet($info);
    }
    else {
      if($normalize) {
        $attributeset = $this->export_attributeset($info['set']);
        $this->normalize($info, $attributeset);
      }
    }
    return $info;
  }

  /**
   * Retrieve list of products with much more info using the ImportExport Module
   *
   * @return array
   */
  public function export($productId=null, $store = null, $attributes = null, $identifierType = null, $integrate_set = false, $normalize = true)
  {
    if (isset($productId))
      return $this->exportOne($productId, $store, $attributes, $identifierType, $integrate_set, $normalize);
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
    $this->normalize_ids($info);
    return $info;
  }
}

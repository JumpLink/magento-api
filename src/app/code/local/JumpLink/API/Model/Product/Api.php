<?php
class JumpLink_API_Model_Product_Api extends Mage_Catalog_Model_Product_Api_V2
{

  private $red   = "\033[0;31m";
  private $blue  = "\033[0;34m";
  private $reset = "\033[0m";

  public function check_filter($filters) {
    print_r ($this->blue.json_encode($filters).$this->reset."\n");

    if (isset($filters->filter)) {
      print_r ($this->blue."filters->filter is set: ".$filters->filter.$this->reset."\n");
      foreach ($filters->filter as $_filter) {
        if (!isset($_filter->key)) {
          print_r ($this->red."filter->key not set ".$this->reset."\n");
          return "filter->key not set";
        }
        else
          print_r ($this->blue."filter->key is set: ".$_filter->key.$this->reset."\n");
        if (!isset($_filter->value)) {
          print_r ($this->red."filter->value not set ".$this->reset."\n");
          return "filter->value not set";
        }
        else
          print_r ($this->blue."filter->value is set: ".$_filter->value.$this->reset."\n");
      }
    } else if(isset($filters->complex_filter)){
      print_r ($this->blue."filters->complex_filter is set: ".$filters->complex_filter.$this->reset."\n");
    } else {
      print_r ($this->red."filters->filter or filters->complex_filter not set".$this->reset."\n");
      return "filters->complex_filter not set";
    }
  }

  private function get_attributes($product) {
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
  public function export()
  {
    $product_export = new Mage_ImportExport_Model_Export_Entity_Product;
    $array_writer = new JumpLink_ImportExport_Model_Export_Adapter_Array;
    $product_export->setWriter($array_writer);
    return $product_export->export();
  }
}
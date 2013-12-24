<?php
class JumpLink_API_Model_Product_Api extends Mage_Catalog_Model_Product_Api_V2
{

  protected function findAttributeInAttributeSet(&$set, $key) {
    foreach ($set['attributes'] as $index => $attribute) {
      foreach ($attribute as $attribute_code => $attribute_options) {
        if($attribute_code == $key) {
          // print("found attribute");
          // print_r($attribute);
          // print("\nindex\n");
          // print_r($index);
          // print("\n");
          return $index;
        }
      }
    }
  }

  protected function extract_attribute_option(&$set, $key) {
    $index = $this->findAttributeInAttributeSet($set, $key);
    $attribute = $set['attributes'][$index];
    unset($set['attributes'][$index]);
    return $attribute;
  }

  protected function find_name_for_multiselect_id($multiselect_options, $id) {
    foreach ($multiselect_options as $index => $value) {
      if($value['value'] == $id) {
        return $value['label'];
      }
    }
    return null;
  }

  protected function transform_attribute(&$value, $set_options) {
    
    switch ($set_options['type']) {
      case 'select':
      case 'integer':
        if(is_array ($value))
        foreach ($value as $array_key => $array_value)
          $value[$array_key] = intval($array_value);
        else
          $value = intval($value);
      break;
      case 'float':
      case 'weight':
      case 'price':
        if(is_array ($value))
          foreach ($value as $array_key => $array_value)
            $value[$array_key] = floatval($array_value);
        else
          $value = floatval($value);
      break;
      case 'boolean':
        if(is_array ($value))
          foreach ($value as $array_key => $array_value)
            $value[$array_key] = ($array_value == true);
        else
          $value = ($value == true);
      break;
      case 'array of integer':
        if(!is_array ($value))
          $value = array($value);
        foreach ($value as $array_key => $array_value)
          $value[$array_key] = intval($array_value);
      break;
      case 'array of boolean':
        if(!is_array ($value))
          $value = array($value);
        foreach ($value as $array_key => $array_value)
          $value[$array_key] = ($array_value == true);
      break;
      case 'array of float':
        if(!is_array ($value))
          $value = array($value);
        foreach ($value as $array_key => $array_value)
          $value[$array_key] = floatval($array_value);
      break;
      case 'array':
        if(!is_array ($value))
          $value = array($value);
      break;
      case 'tier_price':
        foreach ($value as $tp_index => $tierprice) {
          $value[$tp_index]['price_id']      = intval($tierprice['price_id']);
          $value[$tp_index]['website_id']    = intval($tierprice['website_id']);
          $value[$tp_index]['all_groups']    = $tierprice['all_groups'];             // TODO is this an array?
          $value[$tp_index]['cust_group']    = intval($tierprice['cust_group']);
          $value[$tp_index]['price']         = floatval($tierprice['price']);
          $value[$tp_index]['price_qty']     = floatval($tierprice['price_qty']);
          $value[$tp_index]['website_price'] = floatval($tierprice['website_price']);
        }
      break;
      case 'multiselect':
        $value = explode(',', $value); // split string at all ','-chars
        // print("\nset_options for multiselect\n");
        // print_r($set_options);
        foreach ($value as $array_key => $array_value) {
          $value[$array_key] = $this->find_name_for_multiselect_id($set_options['options'], $array_value); // TODO check if this is also work for integrated set
        }
        // print("\nmultiselect values\n");
        // print_r($value);
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

  protected function transorm_keys (&$product) {
    foreach ($product as $key => $value) {
      switch ($key) {
        case 'product_id':
          $product['id'] = intval($value);
          unset ($product['product_id']);
        break;
        case 'sku':
          $product['sku_clean'] = preg_replace("/\/|-|\.|\s/", "", $value); // add sku_clean, do not unset sku
        break;
        case 'categories':
          $product['category_ids'] = $value;
          unset ($product['categories']);
        break;
        case 'websites':
          $product['website_ids'] = $value;
          unset ($product['websites']);
        break;
        case 'type_id':
          $product['type'] = $value;
          unset ($product['type_id']);
        break;
        case 'created_at': // make this compatible with sails.js
          $product['createdAt'] = str_replace(" ", "T", $value).".000Z";
          unset ($product['created_at']);
        break;
        case 'updated_at': // make this compatible with sails.js
          $product['updatedAt'] = str_replace(" ", "T", $value).".000Z";
          unset ($product['updated_at']);
        break;
        case 'status': // make this compatible with sails.js
          switch ($product['status']) {
            case 0:
              $product['status'] = "unset";
            break;
            case 1:
              $product['status'] = "activated";
            break;
            case 2:
              $product['status'] = "disabled";
            break;
          }
        break;
        default:
        # code...
        break;
      }
    }
    return $product;
  }

  protected function transorm_keys_each (&$collection) {
    foreach ($collection as $index => $product) {
      $this->transorm_keys($collection[$index]);
    }
  }

  protected function normalizeWithIntegratedSet(&$product) {
    foreach ($product as $attribute_key => $attribute_value) {
      if($attribute_key != "set" && $attribute_key != "product_id" && $attribute_key != "sku") {
        $product[$attribute_key] = array('value' => $attribute_value, 'options' => $this->extract_attribute_option($product['set'], $attribute_key));
        $product[$attribute_key]['options']['attribute_code'] = $attribute_key; // Two is Better / doppelt hält besser
        $this->transform_attribute($product[$attribute_key]['value'], $product[$attribute_key]['options'] );
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
        // print_r($attribute_key."\n");
        // print("\n");
        // print_r($product[$attribute_key]);
        // print("\n");
        // print_r($attributeset['attributes'][$attributeset_index][$attribute_key]['type']);
        // print("\n\n");
        $this->transform_attribute($product[$attribute_key], $attributeset['attributes'][$attributeset_index][$attribute_key] );
      }
    }
    $this->transorm_keys ($product);
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

      $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
      $stock_data = array(
        'qty'                                => $stock->getIsQtyDecimal() ? floatval($stock->getQty()) : intval($stock->getQty())
        , 'min_qty'                            => $stock->getIsQtyDecimal() ? floatval($stock->getMinQty()) : intval($stock->getMinQty())
        , 'use_config_min_qty'                 => intval($stock->getUseConfigMinQty())
        , 'is_qty_decimal'                     => ($stock->getIsQtyDecimal() == true)
        , 'backorders'                         => $stock->getBackorders()
        , 'use_config_backorders'              => ($stock->getUseConfigBackorders() == true)
        , 'min_sale_qty'                       => $stock->getIsQtyDecimal() ? floatval($stock->getMinSaleQty()) : intval($stock->getMinSaleQty())
        , 'use_config_min_sale_qty'            => ($stock->getIUseConfigMinSaleQty() == true)
        , 'max_sale_qty'                       => $stock->getIsQtyDecimal() ? floatval($stock->getMaxSaleQty()) : intval($stock->getMaxSaleQty())
        , 'use_config_max_sale_qty'            => ($stock->getIUseConfigMaxSaleQty() == true)
        , 'is_in_stock'                        => ($stock->getIsInStock() == true)
        , 'low_stock_date'                     => $stock->getLowStockDate()
        , 'notify_stock_qty'                   => ($stock->getNotifyStockQty() == true)
        , 'use_config_notify_stock_qty'        => ($stock->getUseConfigNotifyStockQty() == true)
        , 'manage_stock'                       => ($stock->getManageStock() == true)
        , 'use_config_manage_stock'            => ($stock->getUseConfigManageStock() == true)
        , 'stock_status_changed_auto'          => ($stock->getStockStatusChangedAuto() == true)
        , 'use_config_qty_increments'          => ($stock->getUseConfigQtyIncrements() == true)
        , 'qty_increments'                     => $stock->getIsQtyDecimal() ? floatval($stock->getQtyIncrements()) : intval($stock->getQtyIncrements())
        , 'use_config_enable_qty_inc'          => ($stock->getUseConfigEnableQtyInc() == true)
        , 'enable_qty_increments'              => ($stock->getEnableQtyIncrements() == true)
        , 'is_decimal_divided'                 => ($stock->getIsDecimalDivided() == true)
        , 'stock_status_changed_automatically' => ($stock->getStockStatusChangedAutomatically() == true)
        , 'use_config_enable_qty_increments'   => ($stock->getUseConfigEnableQtyIncrements() == true)
      );
      //$stock_data = $stock->getData();

      $tmp_result = array(
        'id'                               => intval($product->getId())
        , 'sku'                            => $product->getSku()
        , 'name'                           => $product->getName()
        , 'set'                            => intval($product->getAttributeSetId())
        , 'sku_type'                       => $product->getSkuType()
        //, 'sku_type'                       => $product->getData('sku_type')
        , 'type'                           => $product->getTypeId()
        , 'category_ids'                   => $product->getCategoryIds()
        , 'website_ids'                    => $product->getWebsiteIds()
        , 'stock_data'                     => $stock_data
        , 'weight_type'                    => $product->getWeightType()
        //, 'weight_type'                    => $product->getData('weight_type')
        , 'price_type'                     => $product->getPriceType()
        //, 'price_type'                     => $product->getData('price_type')
        , 'shipment_type'                  => $product->getShipmentType()
        , 'links_purchased_separately'     => $product->getLinksPurchasedSeparately()
        //, 'links_purchased_separately'     => $product->getData('links_purchased_separately')
        , 'samples_title'                  => $product->getSamplesTitle()
        //, 'samples_title'                  => $product->getData('samples_title')
        , 'price_view'                     => $product->getPriceView()
        , 'links_title'                    => $product->getLinksTitle
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
    $this->transorm_keys_each($result);
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
      //print("product->product_id): ".$tmp_result['product_id']."\n");
      $this->transorm_keys ($tmp_result);
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
      //print("product->product_id): ".$tmp_result['product_id']."\n");
      $this->transorm_keys ($tmp_result);
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
    $product_export = new Mage_ImportExport_Model_Export_Entity_Product;
    $array_writer = new JumpLink_ImportExport_Model_Export_Adapter_Array;
    $product_export->setWriter($array_writer);
    $result = $product_export->export();
    $this->transorm_keys_each ($result);
    return $result;
  }

  protected function removeDuplicates($product, $sub_product)
  {
    foreach ($sub_product as $key => $attribute) {
      if($sub_product[$key] == $product[$key])
        unset($sub_product[$key]);
    }
    return $sub_product;
  }

  protected function removeDuplicatesEachStore($product)
  {
    foreach ($product['stores'] as $store_code => $sub_product) {
      $product['stores'][$store_code] = $this->removeDuplicates($product, $sub_product);
    }
    return $product;
  }

  /**
   * Retrieve product info
   *
   * @param int|string $productId
   * @param string|int $store
   * @param boolean $all_stores
   * @param stdClass $attributes
   * @param string $identifierType
   * @param boolean $integrate_set
   * @param boolean $normalize
   * @return array
   */
  protected function exportOne($productId, $store = null, $all_stores=true, $attributes = null, $identifierType = null, $integrate_set = false, $normalize = true)
  {
    //print("product exportOne productId: $productId store: $store all_stores: $all_stores attributes: $attributes identifierType: $identifierType integrate_set: $integrate_set normalize: $normalize \n");
    $info = $this->info($productId, $store, $attributes, $identifierType);

    if($integrate_set || $normalize)
      $attributeset = $this->export_attributeset($info['set']);

    if($integrate_set) {
      $info['set'] = $attributeset;
      if($normalize)
        $this->normalizeWithIntegratedSet($info);
    } else {
      if($normalize) {
        $this->normalize($info, $attributeset);
      }
    }

    if($all_stores) {
      $info['stores'] = array();
      $store_api = new JumpLink_API_Model_Store_Api;
      $stores = $store_api->items();
      foreach ($stores as $key => $current_store) {
        $info['stores'][$current_store['code']] = $this->exportOne($productId, $current_store['code'], false, $attributes, $identifierType, $integrate_set, $normalize);
        $info['stores'][$current_store['code']] = $this->removeDuplicates($info, $info['stores'][$current_store['code']]);
      }
    }
    return $info;
  }

  /**
   * Retrieve list of products with much more info using the ImportExport Module
   *
   * @return array
   */
  public function export($productId=null, $store = null, $all_stores=true, $attributes = null, $identifierType = null, $integrate_set = false, $normalize = true)
  {
    if ($all_stores)
      $store = null;

    if (isset($productId))
      return $this->exportOne($productId, $store, $all_stores, $attributes, $identifierType, $integrate_set, $normalize);
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
    //$info = parent::info($productId, $store, $attributes, $identifierType);
    $product = $this->_getProduct($productId, $store, $identifierType);
    $info = $this->get_attributes($product);
    //$info['set'] = $this->get_attributeset($info['set']);
    //$this->transorm_keys_each($info);
    return $info;
  }
}

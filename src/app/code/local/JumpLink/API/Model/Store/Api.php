<?php
class JumpLink_API_Model_Store_Api extends Mage_Core_Model_Store_Api_V2 {

  protected function getStoreInfo($store) {
    return array(
      'id'                  => intval($store->getId()),          // Integer
      'code'                => $store->getCode(),                // String
      'website_id'          => intval($store->getWebsiteId()),   // Integer
      'group_id'            => intval($store->getGroupId()),     // Integer
      'name'                => $store->getName(),                // String
      'sort_order'          => intval($store->getSortOrder()),   // Integer
      'is_active'           => ($store->getIsActive()  == true), // Boolean
      'locale_code'         => Mage::getStoreConfig('general/locale/code', $store->getId()),
      'logo_src'            => Mage::getStoreConfig('design/header/logo_src', $store->getId()),
      'base_url'            => $store->getBaseUrl(),
      'base_url_direct_link'=> $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_DIRECT_LINK),
      'base_url_js'         => $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS),
      'base_url_link'       => $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK),
      'base_url_media'      => $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA),
      'base_url_skin'       => $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN),
      'base_url_web'        => $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB)
    );
  }

  /**
   * Retrieve website tree
   *
   * @return array
   */
  public function tree()
  {

    $websites = Mage::app()->getWebsites();

    // Make result array
    $result = array();
    $website_results = array();
    $group_results = array();
    $store_results = array();

    foreach ($websites as $website) {
      foreach ($website->getGroups() as $group) {

        $stores = $group->getStores();

        foreach ($stores as $store) {
          $store_results[] = $this->getStoreInfo($store);
        }
        $group_results[] = array(
          'id'                  => intval($group->getId()),
          'name'                => $group->getName(),
          'default_store_id'    => intval($group->getDefaultStoreId()),
          'default_store_code'  => $group->getDefaultStore()->getCode(),
          'root_cagetory_id'    => intval($group->getRootCategoryId()),
          'stores'              => $store_results
        );
      }
      $website_results[] = array(
        'id'                  => intval($website->getId()),
        'name'                => $website->getName(),
        'code'                => $website->getCode(),
        'sort_order'          => intval($website->getSortOrder()),
        'default_group_id'    => intval($website->getDefaultGroup()->getId()),
        'default_group_name'  => $website->getDefaultGroup()->getName(),
        'groups'              => $group_results
      );
    }

    return $website_results;
  }

  /**
   * Retrieve stores list
   *
   * @return array
   */
  public function items()
  {

    // Retrieve stores
    $stores = Mage::app()->getStores();

    // Make result array
    $result = array();
    foreach ($stores as $store) {
      $result[] = $this->getStoreInfo($store);
    }

    return $$result;
  }

  /**
   * Retrieve store data
   *
   * @param string|int $storeId
   * @return array
   */
  public function info($storeId)
  {
    // Retrieve store info
    try {
        $store = Mage::app()->getStore($storeId);
    } catch (Mage_Core_Model_Store_Exception $e) {
        $this->_fault('store_not_exists');
    }

    if (!$store->getId()) {
        $this->_fault('store_not_exists');
    }

    return $this->getStoreInfo($store);
  }
}
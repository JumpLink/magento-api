<?php
/**
 * More Info on:
 *  * http://stackoverflow.com/questions/5423261/how-do-you-refund-to-store-credit-a-credit-memo-in-magento
 *  * /app/code/core/Mage/Sales/Model/Order/Creditmemo/Api.php
 */
class JumpLink_API_Model_Order_Creditmemo_Api extends Mage_Sales_Model_Order_Creditmemo_Api
{
    /**
     * Delete credit memo
     *
     * @param string $creditmemoIncrementId
     * @return result
     */
    public function delete($creditmemoIncrementId) {
        $creditmemo = $this->_getCreditmemo($creditmemoIncrementId);
        $creditmemo->delete()->save();
        return true;
    }
}
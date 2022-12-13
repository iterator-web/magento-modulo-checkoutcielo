<?php
class Iterator_CieloCheckout_Block_Form_Info_Cielocheckout extends Mage_Payment_Block_Info
{
    private $_redirectUrl;

    /**
     * Set block template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('cielocheckout/form/info/cielocheckout.phtml');
    }

    public function getPaymentLink()
    {
        if (is_null($this->_redirectUrl)) {
            $this->_convertAdditionalData();
        }
        return $this->_redirectUrl;
    }

    protected function _convertAdditionalData()
    {
        $details = false;
        try {
            $details = Mage::helper('core/unserializeArray')
                ->unserialize($this->getInfo()->getAdditionalData());
        } catch (Exception $e) {
            Mage::logException($e);
        }
        if (is_array($details)) {
            $this->_redirectUrl = isset($details['checkout_url']) ? (string) $details['checkout_url'] : '';
        } else {
            $this->_redirectUrl = '';
        }
        return $this;
    }
}
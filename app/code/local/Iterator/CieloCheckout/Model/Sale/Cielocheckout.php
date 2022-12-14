<?php
/**
 * Iterator Sistemas Web
 *
 * NOTAS SOBRE LICENÇA
 *
 * Este arquivo de código-fonte está em vigência dentro dos termos da EULA.
 * Ao fazer uso deste arquivo em seu produto, automaticamente você está
 * concordando com os termos do Contrato de Licença de Usuário Final(EULA)
 * propostos pela empresa Iterator Sistemas Web.
 *
 * =================================================================
 *               MÓDULO DE PAGAMENTOS CIELO CHECKOUT
 * =================================================================
 * Este produto foi desenvolvido para o Ecommerce Magento de forma a
 * possibilitar a implementação de recursos para pagamentos por meio
 * de integração com o Checkout Cielo.
 * Através deste módulo a loja virtual do contratante do serviço
 * passará redirecionará o cliente para que finalize o pagamento
 * em ambiente seguro da Cielo.
 * =================================================================
 *
 * @category   Iterator
 * @package    Iterator_CieloCheckout
 * @author     Ricardo Auler Barrientos <contato@iterator.com.br>
 * @copyright  Copyright (c) Iterator Sistemas Web - CNPJ: 19.717.703/0001-63
 * @license    O Produto é protegido por leis de direitos autorais, bem como outras leis de propriedade intelectual.
 */

class Iterator_CieloCheckout_Model_Sale_Cielocheckout extends Mage_Payment_Model_Method_Abstract {

    protected $_code  = 'cielocheckout';
    protected $_formBlockType = 'cielocheckout/form_cielocheckout';
    protected $_infoBlockType = 'cielocheckout/form_info_cielocheckout';
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_allowCurrencyCode = array('BRL');
    protected $_canUseInternal = true;
    protected $_canUseForMultishipping = false;
    protected $_canCapturePartial = true;

    public function getOrderPlaceRedirectUrl() {
        $orderIncrementId = $this->getInfoInstance()->getQuote()->getReservedOrderId();
        if(!$orderIncrementId) {
            $orderIncrementId = Mage::getModel('sales/order')->getCollection()->getLastItem()->getIncrementId();
        }
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        $helper = Mage::helper('cielocheckout');
        $region = Mage::getModel('directory/region')->load($order->getShippingAddress()->getRegionId());
        $jsonOrder =  json_encode(
            array(
                'OrderNumber' => $orderIncrementId,
                'SoftDescriptor' => substr(Mage::getStoreConfig('payment/cielocheckout/soft_descriptor'), 0, 12),
                'Cart' => array(
                    'Discount' => array(
                        'Type' => 'Amount',
                        'Value' => $helper->formatValueForCielo($order->getDiscountAmount())
                    ),
                    'Items' => $this->getItems($order)
                ),
                'Shipping' => array(
                    'Type' => 'FixedAmount',
                    'SourceZipCode' => preg_replace('/\D/', '', Mage::getStoreConfig('shipping/origin/postcode')),
                    'TargetZipCode' => preg_replace('/\D/', '', $order->getShippingAddress()->getPostcode()),
                    'Services' => array(
                        array(
                            'Name' => 'Correios',
                            'Price' => $helper->formatValueForCielo($order->getShippingAmount()),
                            'Deadline' => 15
                        )
                    ),
                    'Address' => array(
                        'Street' => substr($order->getShippingAddress()->getStreet()[0], 0, 255),
                        'Number' => substr($order->getShippingAddress()->getStreet()[1], 0, 8),
                        'Complement' => substr($order->getShippingAddress()->getStreet()[2], 0, 13),
                        'District' => substr($order->getShippingAddress()->getStreet()[3], 0, 63),
                        'City' => substr($order->getShippingAddress()->getCity(), 0, 63),
                        'State' => $region->getCode()
                    )
                ),
                'Payment' => array(
                    'BoletoDiscount' => null,
                    'DebitDiscount' => null,
                    'Installments' => null,
                    'MaxNumberOfInstallments' => null
                ),
                'Customer' => array(
                    'Identity' => preg_replace('/\D/', '', $order->getCustomerTaxvat()),
                    'FullName' => substr($order->getCustomerFirstname().' '.$order->getCustomerLastname(), 0, 287),
                    'Email' => substr($order->getCustomerEmail(), 0, 63),
                    'Phone' => substr(preg_replace('/\D/', '', $order->getShippingAddress()->getTelephone()), 0, 12)
                ),
                'Options' => array(
                    'AntifraudEnabled' => true,
                    'ReturnUrl' => Mage::getUrl(Mage::getStoreConfig('payment/cielocheckout/return_url'), array('_secure'=>true))
                ),
                'Settings' => null
            )
        );
        $orderReturn = $helper->createOrder($jsonOrder);
        $payment = $order->getPayment();
        $payment->setAdditionalInformation('checkout_url', $orderReturn->settings->checkoutUrl);
        $payment->save();
        $order->sendNewOrderEmail();
        $order->setEmailSent(true);
        $comment = 'Link do Checkout Cielo: '.$orderReturn->settings->checkoutUrl;
        $historyItem = $order->addStatusHistoryComment($comment, $order->getStatus());
        $historyItem->setIsCustomerNotified(1)->save();
        $order->save();
        $commentEmail = 'Caso ainda não tenha efetuado o pagamento, pode acessar e fazer o pagamento do pedido diretamente no link a seguir.<br/><br/><b>Link do Checkout Cielo:</b> <a href="'.$orderReturn->settings->checkoutUrl.'">'.$orderReturn->settings->checkoutUrl.'</a>';
        $order->sendOrderUpdateEmail($notify=true, $commentEmail);

        return $orderReturn->settings->checkoutUrl;
    }

    private function getItems($order) {
        $itemsArray = array();
        foreach($order->getAllItems() as $item) {
            $itemsArray[] = array(
                'Name' => substr($item->getName(), 0, 127),
                'Description' => substr($item->getDescription(), 0, 255),
                'UnitPrice' => Mage::helper('cielocheckout')->formatValueForCielo($item->getPrice()),
                'Quantity' => (int)$item->getQtyOrdered(),
                'Type' => 'Asset',
                'Sku' => $item->getSku(),
                'Weight' => $item->getWeight() * 1000
            );
        }
        return $itemsArray;
    }
}

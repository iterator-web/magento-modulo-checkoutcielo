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

class Iterator_CieloCheckout_Model_Cron extends Mage_Core_Model_Abstract {
    
    public function conferirCielo() {
        $orderCollection = Mage::getResourceModel('sales/order_collection');
        $orderCollection->addFieldToFilter('status', 'pending');
        foreach($orderCollection as $order) {
            if($order->getPayment()->getMethodInstance()->getCode() == 'cielocheckout') {
                $helper = Mage::helper('cielocheckout');
                $response = $helper->checkPayment($order->getIncrementId());
                if($response === 'Not Found' || $response->payment_status === 1) {
                    $diferencaDias = $this->timeDiff(date("Y-m-d H:i:s"), $order->getCreatedAt()) / 86400;
                    if($diferencaDias > 7) {
                        $this->cancelOrder($order);
                    }
                } else if($response->payment_status === 2 || $response->payment_status === 7) {
                    if($order->canInvoice() && !$order->hasInvoices()) {
                        $this->generateInvoice($order);
                    }
                } else if($response->payment_status === 3 || $response->payment_status === 4 || $response->payment_status === 5 || $response->payment_status === 6 || $response->payment_status === 8) {
                    $this->cancelOrder($order);
                }
            }
        }
    }

    private function generateInvoice($order) {
        if($order->canInvoice() && !$order->hasInvoices()) {
            $invoiceId = Mage::getModel('sales/order_invoice_api')->create($order->getIncrementId(), array());
            $invoice = Mage::getModel('sales/order_invoice')->loadByIncrementId($invoiceId);
            $invoice->capture()->save();
            Mage::getModel('core/resource_transaction')->addObject($invoice)
                ->addObject($invoice->getOrder())
                ->save();
            $invoice->sendEmail(true);
            $invoice->setEmailSent(true);
            $invoice->save();
        }
    }

    private function cancelOrder($order) {
        $order->cancel();
        $msg = 'Pedido não autorizado pela Cielo ou cancelado devido à
            ausência de pagamento. Favor verificar o ocorrido e tentar novamente.<br/>
            Clique <a href="'.Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB).'sales/order/history/">aqui</a> 
            para refazer o seu pedido em 1 clique apenas. Após acessar os seus pedidos, basta clicar 
            em "Recomprar" e em seguida finalizar a compra.';
        $order->sendOrderUpdateEmail(true, $msg);
        $order->save();
    }

    private function timeDiff($dt1,$dt2) {
        $y1 = substr($dt1,0,4);
        $m1 = substr($dt1,5,2);
        $d1 = substr($dt1,8,2);
        $h1 = substr($dt1,11,2);
        $i1 = substr($dt1,14,2);
        $s1 = substr($dt1,17,2);

        $y2 = substr($dt2,0,4);
        $m2 = substr($dt2,5,2);
        $d2 = substr($dt2,8,2);
        $h2 = substr($dt2,11,2);
        $i2 = substr($dt2,14,2);
        $s2 = substr($dt2,17,2);

        $r1=date('U',mktime($h1,$i1,$s1,$m1,$d1,$y1));
        $r2=date('U',mktime($h2,$i2,$s2,$m2,$d2,$y2));
        return ($r1-$r2);
    }
    
}

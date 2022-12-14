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

class Iterator_CieloCheckout_Helper_Data extends Mage_Core_Helper_Abstract {

   public function createOrder($jsonOrder) {
      $apiUrl = Mage::getStoreConfig('payment/cielocheckout/api_url');
      $curl = curl_init();
      curl_setopt_array($curl, array(
          CURLOPT_URL => $apiUrl.'/public/v1/orders',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $jsonOrder,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_SSL_VERIFYPEER => false,
          CURLOPT_HTTPHEADER => array(
              "Accept: application/json",
              "Content-Type: application/json",
              "MerchantId: ".Mage::getStoreConfig('payment/cielocheckout/merchant_id'),
              "cache-control: no-cache"
          ),
      ));
      $resultado = curl_exec($curl);
      $erro = curl_error($curl);

      curl_close($curl);

      if($erro) {
         Mage::log('createOrder: '.$jsonOrder .' -> '. $erro, null, 'cielocheckout.log');
         return false;
      } else {
         Mage::log('createOrder: '.$jsonOrder .' -> '. $resultado, null, 'cielocheckout.log');
         return json_decode($resultado);
      }
   }

    public function checkPayment($orderIncrementId) {
        $apiUrl = Mage::getStoreConfig('payment/cielocheckout/api_url');
        $curl = curl_init();
        curl_setopt_array($curl, array(
             CURLOPT_URL => $apiUrl.'/public/v1/orders/'.Mage::getStoreConfig('payment/cielocheckout/merchant_id').'/'.$orderIncrementId,
             CURLOPT_RETURNTRANSFER => true,
             CURLOPT_ENCODING => "",
             CURLOPT_MAXREDIRS => 10,
             CURLOPT_TIMEOUT => 30,
             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
             CURLOPT_CUSTOMREQUEST => "GET",
             CURLOPT_FOLLOWLOCATION => true,
             CURLOPT_SSL_VERIFYPEER => false,
             CURLOPT_HTTPHEADER => array(
                  "Accept: application/json",
                  "Content-Type: application/json",
                  "MerchantId: ".Mage::getStoreConfig('payment/cielocheckout/merchant_id'),
                  "cache-control: no-cache"
             ),
        ));
        $resultado = curl_exec($curl);
        $erro = curl_error($curl);

        curl_close($curl);

        if($erro) {
            Mage::log('checkPayment: '.$orderIncrementId .' -> '. $erro, null, 'cielocheckout.log');
            return false;
        } else {
            Mage::log('checkPayment: '.$orderIncrementId .' -> '. $resultado, null, 'cielocheckout.log');
            return json_decode($resultado);
        }
    }

   public function formatValueForCielo($originalValue) {
      if (strpos($originalValue, ".") == false) {
         $value = $originalValue . "00";
      } else {
         list($integers, $decimals) = explode(".", $originalValue);
         if (strlen($decimals) > 2) {
            $decimals = substr($decimals, 0, 2);
         }
         while (strlen($decimals) < 2) {
            $decimals .= "0";
         }
         $value = $integers . $decimals;
      }
      return $value;
   }
}

?>

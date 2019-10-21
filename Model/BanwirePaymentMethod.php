<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Banwire\Card\Model;

// BanwirePaymentMethod
class BanwirePaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod {
  protected $_isInitializeNeeded = false;
  protected $redirect_uri;
  protected $_code = 'banwire';
  protected $_canOrder = true;
  protected $_isGateway = true;


  public function getOrderPlaceRedirectUrl() {
    return \Magento\Framework\App\ObjectManager::getInstance()
      ->get('Magento\Framework\UrlInterface')->getUrl("banwire/redirect");
  }
}

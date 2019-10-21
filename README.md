## Banwire Card Payment Gateway for Magento 2
[![Latest Stable Version](https://poser.okvpn.org/banwire/card-payment-magento-2/v/stable)](https://packagist.org/packages/banwire/card-payment-magento-2) 
[![Total Downloads](https://poser.okvpn.org/banwire/card-payment-magento-2/downloads)](https://packagist.org/packages/banwire/card-payment-magento-2) 
[![Latest Unstable Version](https://poser.okvpn.org/banwire/card-payment-magento-2/v/unstable)](https://packagist.org/packages/banwire/card-payment-magento-2) 
[![License](https://poser.okvpn.org/banwire/card-payment-magento-2/license)](https://packagist.org/packages/banwire/card-payment-magento-2)   

This extension allows you to use Banwire as payment gateway in your Magento 2 store.

## Installing via [Composer](https://getcomposer.org/)

```bash
composer require banwire/card-payment-magento-2
php bin/magento module:enable Banwire_Card --clear-static-content
php bin/magento setup:upgrade
```

Enable and configure Banwire in Magento Admin under `Stores -> Configuration -> Payment Methods -> Banwire Payment Gateway`.

## Configuration

  - **Enabled:** Mark this as "Yes" to enable this plugin.
 
  - **Title:** Test to be shown to user during checkout. For example: "Pay using AE/MC/VI"
  
  - **Banwire User:** User assigned by Banwire

  - **Debug:** If enabled you can use our [Sandbox environment](https://test.banwire.com) to test payments. 
     
  - **Payment Action:** Use only Payment Action	
    
  - **Payment from Applicable Countries:** Mark your applicable countries
  

     
## Support

For any issue send us an email to aguerrero@banwire.com and share the `banwire-card-payment.log` file. The location of `banwire-card-payment.log` file is `var/log/banwire-card-payment.log`.
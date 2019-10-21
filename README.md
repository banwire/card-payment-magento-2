## Banwire Card Payment Gateway for Magento 2

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
 
  - **Title:** Test to be shown to user during checkout. For example: "Pay using DB/CC/NB/Wallets"

  - **Checkout Label:** This is the label users will see during checkout, its default value is "Pay using Banwire". You can change it to something more generic like "Pay using Credit/Debit Card or Online Banking".
     
  - **Client ID** and **Client Secret** - Client Secret and Client ID can be generated on the [Integrations page](https://www.banwire.com/integrations/). Related support article: [How Do I Get My Client ID And Client Secret?](https://support.banwire.com/hc/en-us/articles/212214265-How-do-I-get-my-Client-ID-and-Client-Secret-)
    
  - **Test Mode:** If enabled you can use our [Sandbox environment](https://test.banwire.com) to test payments. Note that in this case you should use `Client Secret` and `Client ID` from the test account not production.

## Screenshots

![Banwire extension under admin](http://i.imgur.com/uj2wMZ1.gif)

![Configuration](http://i.imgur.com/ltxylh3.png)

![Banwire during checkout](http://i.imgur.com/Ayw3MnC.png)


## Support

For any issue send us an email to support@banwire.com and share the `banwire-card-payment.log` file. The location of `banwire-card-payment.log` file is `var/log/banwire-card-payment.log`.
/**
 * Copyright © 2019 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/

define([
    'Magento_Payment/js/view/payment/cc-form',
    'jquery',
    'Banwire_Card/js/model/credit-card-validation/validator'
], function (Component, $) {
    'use strict';
    return Component.extend({
        defaults: {
            template: 'Banwire_Card/payment/payment'
        },
        getCode: function () {
            return 'banwirecard';
        },
        isActive: function () {
            return true;
        },
        validate: function () {
            var $form = $('#' + this.getCode() + '-form');
            return $form.validation() && $form.validation('isValid');
        }
    });
});

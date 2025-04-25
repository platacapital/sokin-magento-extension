define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'SokinPay_PaymentGateway/js/action/set-payment-method-action'
    ],
    function (ko, $, Component, setPaymentMethodAction) {
        'use strict';

        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                template: 'SokinPay_PaymentGateway/payment/form',
                transactionResult: ''
            },

            afterPlaceOrder: function () {
                setPaymentMethodAction(this.messageContainer);
                return false;
            },

             getPaymentLabel: function () {
                return window.checkoutConfig.paymentLabel.paymentLabel;
            },
            getPaymentDiscription: function () {
                return window.checkoutConfig.descriptionvalue.description;
            }
        });
    }
);

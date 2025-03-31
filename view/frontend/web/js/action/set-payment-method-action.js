define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/full-screen-loader',
    'sokinRedirect'
], function ($, quote, urlBuilder, storage, errorProcessor, customer, fullScreenLoader, sokinRedirect) {
    'use strict';

    return function (messageContainer) {
        // Call your custom redirect logic
        sokinRedirect();
    };
});



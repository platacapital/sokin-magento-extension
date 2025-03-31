define([
    'jquery',
    'mage/url'
], function ($, urlBuilder) {
    'use strict';

    return function () {
        $.ajax({
            url: urlBuilder.build('sokin/sokin/redirect'),
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.redirect_url) {
                    $.mage.redirect(response.redirect_url);
                } else {
                    console.error('Redirect URL is not available.');
                }
            },
            error: function () {
                console.error('Failed to get the redirect URL.');
            }
        });
    };
});

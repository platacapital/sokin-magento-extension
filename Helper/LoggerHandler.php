<?php

namespace SokinPay\PaymentGateway\Helper;

use Monolog\Logger;

class LoggerHandler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level type
     *
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * Log File name
     *
     * @var string
     */
    protected $fileName = '/var/log/sokinpay-paymentgateway.log';
}

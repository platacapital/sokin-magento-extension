<?php
namespace SokinPay\PaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use SokinPay\PaymentGateway\Helper\ConfigHelper; // Import your helper


/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
     protected $configHelper;

    const CODE = 'sokinPay_paymentGateway';

    public function __construct(ConfigHelper $configHelper)
    {   
        $this->configHelper = $configHelper;
    }


    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE
            ],
            'paymentLabel'=>[
                'paymentLabel' => $this->configHelper->getPaymentLabel(),
            ],
            'discriptionvalue'=>[
                 'description' => $this->configHelper->getDiscription(),
            ]

        ];
    }
}



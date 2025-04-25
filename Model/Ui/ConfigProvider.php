<?php
namespace SokinPay\PaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use SokinPay\PaymentGateway\Helper\ConfigHelper;

class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'sokinPay_paymentGateway';

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * ConfigProvider constructor.
     *
     * @param ConfigHelper $configHelper
     */
    public function __construct(ConfigHelper $configHelper)
    {
        $this->configHelper = $configHelper;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
            'descriptionvalue'=>[
                 'description' => $this->configHelper->getDescription(),
            ]

        ];
    }
}

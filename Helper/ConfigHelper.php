<?php

namespace SokinPay\PaymentGateway\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\StoreManagerInterface;

class ConfigHelper extends AbstractHelper
{
    public const XML_PATH_PAYMENT_GATEWAY = 'payment/sokinPay_paymentGateway/';

    public const SOKINPAY_PAYMENTGATEWAY_TITLE = 'payment/sokinPay_paymentGateway/title';

    public const SOKINPAY_PAYMENTGATEWAY_DESCRIPTION = 'payment/sokinPay_paymentGateway/description';

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \SokinPay\PaymentGateway\Helper\Logger
     */
    public $logger;

    /**
     * ConfigHelper constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     * @param StoreManagerInterface $storeManager
     * @param Context $context
     * @param \SokinPay\PaymentGateway\Helper\Logger $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor,
        StoreManagerInterface $storeManager,
        Context $context,
        \SokinPay\PaymentGateway\Helper\Logger $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Retrieves the appropriate checkout URL based on the environment.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCheckoutUrl()
    {
        $environment = $this->getEnvironment();
        $configPath = $environment === 'sandbox'
            ? 'sandbox_checkout_url'
            : 'production_checkout_url';

        return $this->scopeConfig->getValue(
            self::XML_PATH_PAYMENT_GATEWAY . $configPath,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );
    }

    /**
     * Retrieves the environment configuration.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getEnvironment()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_PAYMENT_GATEWAY . 'environment',
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );
    }

    /**
     * Retrieves the appropriate API URL based on the environment.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getApiUrl()
    {
        $environment = $this->getEnvironment();
        $configPath = $environment === 'sandbox'
            ? 'sandbox_api_url'
            : 'production_api_url';

        return $this->scopeConfig->getValue(
            self::XML_PATH_PAYMENT_GATEWAY . $configPath,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );
    }

    /**
     * Retrieves and decrypts the secret key based on the environment.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getSecretKey()
    {
        $environment = $this->getEnvironment();
        $configPath = $environment === 'sandbox'
            ? 'sandbox_secret_key'
            : 'production_secret_key';

        $encryptedKey = $this->scopeConfig->getValue(
            self::XML_PATH_PAYMENT_GATEWAY . $configPath,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );

        return $this->encryptor->decrypt($encryptedKey);
    }

    /**
     * Function To Get the Payment label
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getPaymentLabel()
    {
        $title = $this->scopeConfig->getValue(
            self::SOKINPAY_PAYMENTGATEWAY_TITLE,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );

        return $title;
    }

    /**
     * Function To Get the Description
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getDescription()
    {
        $description = $this->scopeConfig->getValue(
            self::SOKINPAY_PAYMENTGATEWAY_DESCRIPTION,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );

        return $description;
    }
}

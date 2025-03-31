<?php

namespace SokinPay\PaymentGateway\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ConfigHelper extends AbstractHelper
{
    const XML_PATH_PAYMENT_GATEWAY = 'payment/sokinPay_paymentGateway/';

    const sokinPay_paymentGateway_title = 'payment/sokinPay_paymentGateway/title';

    const sokinPay_paymentGateway_discription = 'payment/sokinPay_paymentGateway/description';

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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * ConfigHelper constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     * @param StoreManagerInterface $storeManager
     * @param Context $context
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor,
        StoreManagerInterface $storeManager,
        Context $context,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Retrieves the environment configuration.
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
     * Retrieves the appropriate checkout URL based on the environment.
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
     * Retrieves the appropriate API URL based on the environment.
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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

      public function getPaymentLabel()
    {
        $title = $this->scopeConfig->getValue(
            self::sokinPay_paymentGateway_title,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );

        return $title;
    }

       public function getDiscription()
    {
        $discription = $this->scopeConfig->getValue(
            self::sokinPay_paymentGateway_discription,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );

        return $discription;
    }
}

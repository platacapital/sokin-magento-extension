<?php
declare(strict_types=1);
namespace SokinPay\PaymentGateway\Gateway\Request;

use Exception;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use SokinPay\PaymentGateway\Helper\ConfigHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Class AuthorizationRequest
 *
 * Handles the authorization request to the SokinPay payment gateway.
 */
class AuthorizationRequest implements BuilderInterface
{
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

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
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * Constructor
     *
     * @param ConfigHelper $configHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     * @param StoreManagerInterface $storeManager
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        ConfigHelper $configHelper,
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor,
        StoreManagerInterface $storeManager,
        CartRepositoryInterface $cartRepository
    ) {
        $this->configHelper = $configHelper;
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        $this->storeManager = $storeManager;
        $this->cartRepository = $cartRepository;
    }

    /**
     * Builds the request array to send to the SokinPay payment gateway.
     *
     * @param array $buildSubject
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \InvalidArgumentException
     * @throws Exception
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment']) || !$buildSubject['payment'] instanceof PaymentDataObjectInterface) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $payment */
        $payment = $buildSubject['payment'];
        $order = $payment->getOrder();
        $address = $order->getShippingAddress();

        $paymentMethodString = $payment->getPayment()['method'];
        $paymentMethodArray = json_decode($paymentMethodString, true);

        if (json_last_error() !== JSON_ERROR_NONE || $paymentMethodString === 'null') {
            $paymentMethodArray = ['method' => $paymentMethodString];
        }

        $storeUrl = $this->storeManager->getStore()->getBaseUrl();
        $returningUrl = $storeUrl . 'sokin/sokin/responseurl';

        $requestData = [
            'firstName' => $address->getFirstname(),
            'lastName' => $address->getLastname(),
            'email' => $address->getEmail(),
            'type' => 'SINGLE',
            'currency' => $order->getCurrencyCode(),
            'totalAmount' => $order->getGrandTotalAmount(),
            'description' => '',
            'redirectURL' => $returningUrl,
            'memo' => '',
            'referenceNo' => $order->getOrderIncrementId(),
            'country' => $address->getCountryId(),
            'addressLine1' => $address->getStreetLine1(),
            'addressLine2' => $address->getStreetLine2(),
            'addressLine3' => '',
            'postTown' => '',
            'postCode' => $address->getPostcode(),
            'city' => $address->getCity(),
            'save_card' => false,
            'isExternal' => true,
            'payment_method' => [],
        ];

        $apiKey = $this->configHelper->getSecretKey();

        $apiUrl = $this->configHelper->getApiUrl() . "/orders";

        $checkoutUrl = $this->configHelper->getCheckoutUrl();

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($requestData),
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $apiKey,
                'Content-Type: application/json'
            ],
        ]);

        $response = curl_exec($curl);
        if ($response === false) {
            throw new Exception('CURL Error: ' . curl_error($curl));
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $responseArray = json_decode($response, true);

        if ($httpCode == 201 && isset($responseArray['success']) && $responseArray['success'] == true) {
            $orderId = $responseArray['orderId'];
            $corporateId = $responseArray['corporateId'];
            $redirectUrl = "$checkoutUrl/$corporateId/$orderId";
            $payment->getPayment()->setAdditionalInformation('redirect_url', $redirectUrl);

            return [
                'success' => true,
                'order_id' => $orderId,
            ];
        }

        $errorMessage = isset($responseArray['message']) ? $responseArray['message'] : 'Unknown error';
        throw new Exception('Failed to create order: ' . $errorMessage);
    }
}

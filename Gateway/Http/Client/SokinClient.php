<?php
declare(strict_types=1);

namespace SokinPay\PaymentGateway\Gateway\Http\Client;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use SokinPay\PaymentGateway\Helper\ConfigHelper;
use Magento\Framework\Phrase;

/**
 * Class SokinClient
 *
 * Handles payment gateway requests in the SokinPay Payment Gateway module.
 */
class SokinClient implements ClientInterface
{
    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var ConfigHelper
     */
    protected ConfigHelper $configHelper;

    /**
     * Constructor
     *
     * @param Logger $logger
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        Logger $logger,
        ConfigHelper $configHelper
    ) {
        $this->logger = $logger;
        $this->configHelper = $configHelper;
    }

    /**
     * Places the request and returns the response
     *
     * @param TransferInterface $transferObject
     * @return array
     * @throws LocalizedException
     */
    public function placeRequest(TransferInterface $transferObject): array
    {
        $data = $transferObject->getBody();
        $response = $this->generateResponseForCode($data);

        $this->logger->debug([
            'request' => $transferObject->getBody(),
            'response' => $response
        ]);

        return $response;
    }

    /**
     * Generates a response based on the provided data
     *
     * @param array $data
     * @return array
     * @throws LocalizedException
     */
    protected function generateResponseForCode(array $data): array
    {
        $txnId = $this->generateTxnId();
        $resultCode = '';

        if (isset($data['refund_response'])) {
            $refundResponse = $data['refund_response'];
            if (isset($refundResponse['status'], $refundResponse['success'])
                && $refundResponse['status'] === 201 && $refundResponse['success'] === true) {
                $resultCode = 'SUCCESS';
                $txnId = $refundResponse['refundId'];
            } else {
                $errorMessage = isset($refundResponse['message'])
                    ? new Phrase($refundResponse['message'])
                    : new Phrase('Refund failed. Please try again.');
                throw new LocalizedException($errorMessage);
            }
        } else {
            if (isset($data['success']) && $data['success'] === true) {
                $resultCode = 'SUCCESS';
                $txnId = $data['order_id'] ?? $this->generateTxnId();
            } else {
                $errorMessage = new Phrase('Something went wrong. Please try again.');
                throw new LocalizedException($errorMessage);
            }
        }

        return [
            'RESULT_CODE' => $resultCode,
            'TXN_ID' => $txnId,
        ];
    }

    /**
     * Generates a unique transaction ID
     *
     * @return string
     */
    protected function generateTxnId(): string
    {
        return hash('sha256', uniqid((string) random_int(PHP_INT_MIN, PHP_INT_MAX), true));
    }
}

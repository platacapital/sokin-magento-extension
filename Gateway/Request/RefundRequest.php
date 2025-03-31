<?php
declare(strict_types=1);

namespace SokinPay\PaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use SokinPay\PaymentGateway\Helper\ConfigHelper;
use Magento\Sales\Api\CreditmemoRepositoryInterface;

/**
 * Class RefundRequest
 *
 * Handles the refund request to the SokinPay payment gateway.
 */
class RefundRequest implements BuilderInterface
{
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var CreditmemoRepositoryInterface
     */
    protected $creditmemoRepository;

    /**
     * Constructor
     *
     * @param ConfigHelper $configHelper
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     */
    public function __construct(
        ConfigHelper $configHelper,
        CreditmemoRepositoryInterface $creditmemoRepository
    ) {
        $this->configHelper = $configHelper;
        $this->creditmemoRepository = $creditmemoRepository;
    }

    /**
     * Builds the refund request array to send to the SokinPay payment gateway.
     *
     * @param array $buildSubject
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Exception
     */
    public function build(array $buildSubject)
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $amount = SubjectReader::readAmount($buildSubject);
        $order = $payment->getOrder();

        // Retrieve the payment response saved in the additional_information field
        $additionalInfo = $payment->getAdditionalInformation('payments');
        $paymentResponseArray = json_decode($additionalInfo, true);

        if (is_array($paymentResponseArray) && isset($paymentResponseArray[0])) {
            $paymentResponse = $paymentResponseArray[0];

            $creditmemo = $payment->getCreditmemo();
            $comment = '';
            if ($creditmemo) {
                $comments = $creditmemo->getComments();
                if (!empty($comments)) {
                    $comment = $comments[0]->getComment();
                }
            }
            $comment = $comment ?: '';

            $requestRefund = [
                'paymentId' => $paymentResponse['paymentId'],
                'paymentDate' => $paymentResponse['paymentDate'],
                'amount' => $amount,
                'currency' => $order->getOrderCurrencyCode(),
                'status' => $paymentResponse['status'],
                'description' => $comment,
                'installmentNumber' => $paymentResponse['installmentNumber'],
                'paymentMethod' => $paymentResponse['paymentMethod'],
            ];

            $apiKey = $this->configHelper->getSecretKey();
            $apiUrl = $this->configHelper->getApiUrl() . "/refunds";

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
                CURLOPT_POSTFIELDS => json_encode($requestRefund),
                CURLOPT_HTTPHEADER => [
                    'x-api-key: ' . $apiKey,
                    'Content-Type: application/json',
                ],
            ]);

            $refundResponse = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            $responseArray = json_decode($refundResponse, true);

            return [
                'refund_response' => $responseArray,
                'http_code' => $httpCode,
            ];
        } else {
            throw new \Exception('Invalid payment response structure.');
        }
    }
}

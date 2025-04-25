<?php
declare(strict_types=1);

namespace SokinPay\PaymentGateway\Gateway\Request;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use SokinPay\PaymentGateway\Service\MakeRequest;
use SokinPay\PaymentGateway\Service\RequestMethods;

/**
 * Class RefundRequest
 *
 * Handles the refund request to the SokinPay payment gateway.
 */
class RefundRequest implements BuilderInterface
{
    /**
     * @var CreditmemoRepositoryInterface
     */
    protected $creditmemoRepository;
    /**
     * @var MakeRequest
     */
    protected $makeRequest;

    /**
     * Constructor
     *
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param MakeRequest $makeRequest
     */
    public function __construct(
        CreditmemoRepositoryInterface $creditmemoRepository,
        MakeRequest $makeRequest
    ) {
        $this->creditmemoRepository = $creditmemoRepository;
        $this->makeRequest = $makeRequest;
    }

    /**
     * Builds the refund request array to send to the SokinPay payment gateway.
     *
     * @param array $buildSubject
     *
     * @return array
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

            $endpoint = '/refunds';
            $response = $this->makeRequest->sendRequest(
                $endpoint,
                RequestMethods::REQUEST_METHOD_POST,
                json_encode($requestRefund)
            );
            $refundResponse = (!empty($response['response']) && $response['code'] == 200) ? $response['response'] : [];
            return [
                'refund_response' => $refundResponse,
                'http_code' => $response['code'],
            ];
        } else {
            throw new LocalizedException(__('Invalid payment response structure.'));
        }
    }
}

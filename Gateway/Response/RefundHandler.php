<?php
declare(strict_types=1);

namespace SokinPay\PaymentGateway\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Class RefundHandler
 *
 * Handles the refund response from the payment gateway.
 */
class RefundHandler implements HandlerInterface
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * RefundHandler constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Handles the response for a refund request.
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws LocalizedException
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDataObject = SubjectReader::readPayment($handlingSubject);
        $payment = $paymentDataObject->getPayment();

        try {
            if (!empty($response['RESULT_CODE']) && $response['RESULT_CODE'] === 'SUCCESS' && !empty($response['TXN_ID'])) {
                $payment->setTransactionId($response['TXN_ID']);
                $payment->setIsTransactionClosed(true);
            } else {
                $errorMessage = $response['message'] ?? __('Refund failed. Please try again.');
                throw new LocalizedException(new \Magento\Framework\Phrase($errorMessage));
            }
        } catch (\Exception $e) {
            $this->logger->error('RefundHandler Error: ' . $e->getMessage(), ['response' => $response]);
            throw new LocalizedException(__('An error occurred while processing the refund: %1', $e->getMessage()));
        }
    }
}

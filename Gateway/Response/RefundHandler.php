<?php
declare(strict_types=1);

namespace SokinPay\PaymentGateway\Gateway\Response;

use Exception;
use Magento\Framework\Phrase;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class RefundHandler
 *
 * Handles the refund response from the payment gateway.
 */
class RefundHandler implements HandlerInterface
{
    /**
     * @var \SokinPay\PaymentGateway\Helper\Logger
     */
    private $logger;

    /**
     * RefundHandler constructor.
     *
     * @param \SokinPay\PaymentGateway\Helper\Logger $logger
     */
    public function __construct(\SokinPay\PaymentGateway\Helper\Logger $logger)
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
            if (!empty($response['RESULT_CODE']) &&
                $response['RESULT_CODE'] === 'SUCCESS' &&
                !empty($response['TXN_ID'])) {
                $payment->setTransactionId($response['TXN_ID']);
                $payment->setIsTransactionClosed(true);
            } else {
                $errorMessage = $response['message'] ?? __('Refund failed. Please try again.');
                throw new LocalizedException(new Phrase($errorMessage));
            }
        } catch (Exception $e) {
            $this->logger->info('RefundHandler Exception: ' . $e->getMessage());
            $this->logger->info('Response : ' . var_export(['response' => $response], true));
            throw new LocalizedException(
                __('An error occurred while processing the refund: %1', $e->getMessage())
            );
        }
    }
}

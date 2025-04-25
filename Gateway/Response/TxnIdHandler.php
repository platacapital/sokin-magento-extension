<?php
declare(strict_types=1);

namespace SokinPay\PaymentGateway\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use InvalidArgumentException;
use Exception;

/**
 * Class TxnIdHandler
 *
 * Handles the setting of the transaction ID in the payment object.
 */
class TxnIdHandler implements HandlerInterface
{
    private const TXN_ID = 'TXN_ID';

    /**
     * Handles the transaction ID by setting it in the payment object.
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function handle(array $handlingSubject, array $response): void
    {
        if (!isset($handlingSubject['payment']) || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface) {
            throw new InvalidArgumentException('Payment data object should be provided.');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];
        $payment = $paymentDO->getPayment();

        if (empty($response[self::TXN_ID])) {
            throw new Exception('Transaction ID not found in the response.');
        }

        // Set the transaction ID and keep the transaction open if needed
        $payment->setTransactionId($response[self::TXN_ID]);
        // Set Transaction Closed $payment->setIsTransactionClosed(false);
    }
}

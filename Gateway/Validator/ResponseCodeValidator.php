<?php
declare(strict_types=1);

namespace SokinPay\PaymentGateway\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use InvalidArgumentException;

/**
 * Class ResponseCodeValidator
 *
 * Validates the response code from the payment gateway.
 */
class ResponseCodeValidator extends AbstractValidator
{
    /**
     * Performs validation of the result code.
     *
     * @param array $validationSubject
     * @return ResultInterface
     * @throws InvalidArgumentException
     */
    public function validate(array $validationSubject): ResultInterface
    {
        if (!isset($validationSubject['response']) || !is_array($validationSubject['response'])) {
            throw new InvalidArgumentException('Response does not exist or is not valid.');
        }

        $response = $validationSubject['response'];

        if ($this->isSuccessfulTransaction($response)) {
            return $this->createResult(true, []);
        }

        return $this->createResult(false, [__('Gateway rejected the transaction.')]);
    }

    /**
     * Checks if the transaction is successful based on the response.
     *
     * @param array $response
     * @return bool
     */
    private function isSuccessfulTransaction(array $response): bool
    {
        return isset($response['status'], $response['success'])
            && $response['status'] === 201
            && $response['success'] === true;
    }
}

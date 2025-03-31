<?php

namespace SokinPay\PaymentGateway\Plugin\Sales\Block\Adminhtml;

use Magento\Sales\Block\Adminhtml\Order\Invoice\View as InvoiceView;
use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

class RemoveVoidButton
{
    /**
     * Remove the Void button if the payment method is sokin_paymentGateway.
     *
     * @param mixed $subject
     */
    public function beforeSetLayout($subject)
    {
        if ($subject instanceof InvoiceView) {
            $invoice = $subject->getInvoice();
            $paymentMethod = $invoice->getOrder()->getPayment()->getMethod();

            if ($paymentMethod === 'sokinPay_paymentGateway') {
                $subject->removeButton('void');
            }
        } elseif ($subject instanceof OrderView) {
            $order = $subject->getOrder();
            $paymentMethod = $order->getPayment()->getMethod();

            if ($paymentMethod === 'sokinPay_paymentGateway') {
                $subject->removeButton('void_payment');
            }
        }
    }
}

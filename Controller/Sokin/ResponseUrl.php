<?php
declare(strict_types=1);

namespace SokinPay\PaymentGateway\Controller\Sokin;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Framework\DB\Transaction;
use SokinPay\PaymentGateway\Service\MakeRequest;
use SokinPay\PaymentGateway\Service\RequestMethods;

class ResponseUrl extends Action
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;
    /**
     * @var OrderFactory
     */
    protected $orderFactory;
    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;
    /**
     * @var InvoiceSender
     */
    protected $invoiceSender;
    /**
     * @var Transaction
     */
    protected $_transaction;
    /**
     * @var MakeRequest
     */
    protected $makeRequest;

    /**
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param OrderFactory $orderFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param InvoiceSender $invoiceSender
     * @param Transaction $transaction
     * @param MakeRequest $makeRequest
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        OrderFactory $orderFactory,
        RedirectFactory $resultRedirectFactory,
        InvoiceSender $invoiceSender,
        Transaction $transaction,
        MakeRequest $makeRequest
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->invoiceSender = $invoiceSender;
        $this->_transaction = $transaction;
        $this->makeRequest = $makeRequest;
    }

    /**
     * Controller Execute Function
     *
     * @return ResponseInterface|\Magento\Framework\Controller\Result\Redirect|ResultInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $order = $this->checkoutSession->getLastRealOrder();
        if ($order && $order->getId()) {
            $lastTransId = $order->getPayment()->getLastTransId();
            $endpoint = '/orders/' . $lastTransId;
            $response = $this->makeRequest->sendRequest(
                $endpoint,
                RequestMethods::REQUEST_METHOD_GET,
                ''
            );
            $responseData = (!empty($response['response']) && $response['code'] == 200) ? $response['response'] : [];
            if ($responseData && isset($responseData['data']['order'])) {
                $orderData = $responseData['data']['order'];
                $orderStatus = $orderData['orderStatus'];
                $payments = $orderData['payments'];

                // Save payments data to additional_information field
                $additionalInfo = $order->getPayment()->getAdditionalInformation();
                $additionalInfo['payments'] = json_encode($payments);

                $order->getPayment()->setAdditionalInformation($additionalInfo);
                $order->getPayment()->save();
                $paymentStatus = $payments[0]['status'];
                if ($paymentStatus === 'declined' || $paymentStatus === 'DECLINED' || $paymentStatus === 'cancel') {
                    // Cancel the order
                    if ($order->canCancel()) {
                        $order->cancel();
                        $order->setState(Order::STATE_CANCELED)->setStatus(Order::STATE_CANCELED);
                        $order->addStatusHistoryComment(__('Order was canceled due to declined payment.'))
                            ->setIsCustomerNotified(true);
                        $order->save();
                    }
                    $this->messageManager->addErrorMessage(
                        __('Your payment was declined, and the order has been canceled.')
                    );
                    $resultRedirect->setPath('checkout/onepage/failure');

                } elseif ($orderStatus === 'PROCESSED') {
                    $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
                    if (isset($payments[0]) &&
                        ($payments[0]['status'] === 'PAID' || $payments[0]['status'] === 'COMPLETED')
                    ) {
                        $this->createInvoice($order, true);
                    }
                    $resultRedirect->setPath('checkout/onepage/success');
                } else {
                    $order->setState(Order::STATE_NEW)->setStatus(Order::STATE_PENDING_PAYMENT);
                    $order->save();
                    $resultRedirect->setPath('checkout/onepage/success');
                }
            }
        } else {
            $order->setState(Order::STATE_NEW)->setStatus(Order::STATE_PENDING_PAYMENT);
            $order->save();
            $this->messageManager->addErrorMessage(
                __('Payment failed. Please try again or choose a different payment method.')
            );
            $resultRedirect->setPath('checkout/onepage/failure');
        }
        return $resultRedirect;
    }

    /**
     * Function To Create the Invoice
     *
     * @param object $order
     * @param boolean $isPaid
     *
     * @return void
     * @throws Exception
     */
    protected function createInvoice($order, $isPaid)
    {
        if ($order->canInvoice()) {
            $invoice = $order->prepareInvoice();
            $invoice->register();
            $transactionId = $order->getPayment()->getLastTransId();
            $invoice->setTransactionId($transactionId);
            if ($isPaid) {
                $invoice->setState(Invoice::STATE_PAID);
                $invoice->pay();
            }
            $invoice->save();
            $transactionSave = $this->_transaction->addObject(
                $invoice
            )->addObject(
                $invoice->getOrder()
            );
            $transactionSave->save();
            $this->invoiceSender->send($invoice);
            $order->addStatusHistoryComment(
                __('Notified customer about invoice #%1.', $invoice->getId())
            )->setIsCustomerNotified(true)->save();
        }
    }
}

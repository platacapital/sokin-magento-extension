<?php
declare(strict_types=1);

namespace SokinPay\PaymentGateway\Controller\Sokin;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use SokinPay\PaymentGateway\Helper\ConfigHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Framework\DB\Transaction;

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
     * @var ConfigHelper
     */
    protected $configHelper;
    /**
     * @var InvoiceSender
     */
    protected $invoiceSender;
    /**
     * @var Transaction
     */
    protected $_transaction;

    /**
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param OrderFactory $orderFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param ConfigHelper $configHelper
     * @param InvoiceSender $invoiceSender
     * @param Transaction $transaction
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        OrderFactory $orderFactory,
        RedirectFactory $resultRedirectFactory,
        ConfigHelper $configHelper,
        InvoiceSender $invoiceSender,
        Transaction $transaction
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->configHelper = $configHelper;
        $this->invoiceSender = $invoiceSender;
        $this->_transaction = $transaction;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $order = $this->checkoutSession->getLastRealOrder();

        if ($order && $order->getId()) {
            $lastTransId = $order->getPayment()->getLastTransId();
            $apiKey = $this->configHelper->getSecretKey();
            $apiUlr = $this->configHelper->getApiUrl();
            $url = $apiUlr .'/orders/' . $lastTransId;
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'x-api-key: ' . $apiKey,
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $responseData = json_decode($response, true);

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
                if ($paymentStatus === 'declined' || $paymentStatus ==='DECLINED' || $paymentStatus === 'cancel') {
                    // Cancel the order
                    if ($order->canCancel())
                    {
                        $order->cancel();
                        $order->setState(Order::STATE_CANCELED)->setStatus(Order::STATE_CANCELED);
                        $order->addStatusHistoryComment(__('Order was canceled due to declined payment.'))
                            ->setIsCustomerNotified(true);
                        $order->save();
                    }
                    $this->messageManager->addErrorMessage(__('Your payment was declined, and the order has been canceled.'));
                    $resultRedirect->setPath('checkout/onepage/failure');

                } elseif ($orderStatus === 'PROCESSED') {
                    $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
                    if (isset($payments[0]) && ($payments[0]['status'] === 'PAID' || $payments[0]['status'] === 'COMPLETED')) {
                        $this->createInvoice($order, true);
                    }
                    $resultRedirect->setPath('checkout/onepage/success');

                } else {
                    $order->setState(Order::STATE_NEW)->setStatus(Order::STATE_PENDING_PAYMENT);
                    $order->save();
                    $this->messageManager->addErrorMessage(__('Payment failed. Please try again or choose a different payment method.'));
                    $resultRedirect->setPath('checkout/onepage/success');
                }
            }
        } else {
            $order->setState(Order::STATE_NEW)->setStatus(Order::STATE_PENDING_PAYMENT);
            $order->save();
            $this->messageManager->addErrorMessage(__('Payment failed. Please try again or choose a different payment method.'));
            $resultRedirect->setPath('checkout/onepage/failure');
        }
        return $resultRedirect;
    }


    /**
     * @param $order
     * @param $isPaid
     * @return void
     * @throws \Exception
     */
    protected function createInvoice($order, $isPaid)
    {
        if ($order->canInvoice()) {
            $invoice = $order->prepareInvoice();
            $invoice->register();
            $transactionId = $order->getPayment()->getLastTransId();
            $invoice->setTransactionId($transactionId);

            if ($isPaid) {
                $invoice->setState(\Magento\Sales\Model\Order\Invoice::STATE_PAID);
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

<?php

namespace SokinPay\PaymentGateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

class SetPendingStatusObserver implements ObserverInterface
{
    /**
     * Set the order status to 'pending_payment' after order placement
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        // Set order status to pending
        $order->setState(Order::STATE_NEW);
        $order->setStatus(Order::STATE_PENDING_PAYMENT);
        $order->save();
    }
}

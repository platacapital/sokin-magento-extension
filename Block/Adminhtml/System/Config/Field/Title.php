<?php
namespace SokinPay\PaymentGateway\Block\Adminhtml\System\Config\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field;

class Title extends Field
{
    /**
     * Function _getElementHtml
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $html = '<div style="padding:4px 0;">';
        $html .= 'Pay With';
        $html .= '</div>';
        return $html;
    }
}

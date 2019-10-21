<?php

namespace Banwire\Card\Model\Config\Source\Order\Action;

class Paymentaction
{
    public function toOptionArray()
    {
        return [
      ['value' => 'authorize',
        'label' => __('Authorize Only')],
      ['value' => 'authorize_capture', 'label' => __('Authorize and Capture')],];
    }
}

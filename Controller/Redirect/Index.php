<?php

namespace Banwire\Card\Controller\Redirect;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

class Index extends \Magento\Framework\App\Action\Action {
  protected $pageFactory;
  protected $_checkoutSession;


  public function __construct(
    Context $context,
    PageFactory $pageFactory) {


    $this->pageFactory = $pageFactory;


    parent::__construct($context);

  }

  public function execute() {



    return $this->pageFactory->create();
  }
}

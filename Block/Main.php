<?php
namespace Banwire\Card\Block;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Element\Template\Context;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Banwire\Card\Logger\Logger;
use Magento\Framework\App\Response\Http;
use Magento\Sales\Model\Order\Payment\Transaction\Builder as TransactionBuilder;

 
class Main extends  \Magento\Framework\View\Element\Template
{
	 protected $_objectmanager;
	 protected $checkoutSession;
	 protected $orderFactory;
	 protected $urlBuilder;
	 private $logger;
	 protected $response;
	 protected $config;
	 protected $messageManager;
	 protected $transactionBuilder;
	 protected $inbox;
	 public function __construct(Context $context,
			Session $checkoutSession,
			OrderFactory $orderFactory,
			Logger $logger,
			Http $response,
			TransactionBuilder $tb,
			 \Magento\AdminNotification\Model\Inbox $inbox
		) {

      
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->response = $response;
        $this->config = $context->getScopeConfig();
        $this->transactionBuilder = $tb;
		$this->logger = $logger;					
		$this->inbox = $inbox;					
        
		$this->urlBuilder = \Magento\Framework\App\ObjectManager::getInstance()
							->get('Magento\Framework\UrlInterface');
		parent::__construct($context);
    }

	protected function _prepareLayout()
	{
		$method_data = array();
		$orderId = $this->checkoutSession->getLastOrderId();
		$this->logger->info('Creating Order for orderId $orderId');
		$order = $this->orderFactory->create()->load($orderId);
		if ($order)
		{
			$billing = $order->getBillingAddress();
			# check if mobile no to be updated.
			$updateTelephone = $this->getRequest()->getParam('telephone');
			if($updateTelephone)
			{
				$billing->setTelephone($updateTelephone)->save();
				
			}
			$payment = $order->getPayment();
			
			$payment->setTransactionId("-1");
			  $payment->setAdditionalInformation(  
				[\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array("Transaction is yet to complete")]
			);
			$trn = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE,null,true);
			$trn->setIsClosed(0)->save();
			 $payment->addTransactionCommentsToOrder(
                $trn,
               "The transaction is yet to complete."
            );

            $payment->setParentTransactionId(null);
            $payment->save();
            $order->save();
 
			//var_dump($trn);exit;
			try{
				$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
				$client_id = $this->config->getValue("payment/banwire/client_id",$storeScope);
				$client_secret = $this->config->getValue("payment/banwire/client_secret",$storeScope);
				$testmode = $this->config->getValue("payment/banwire/banwire_testmode",$storeScope);
				$this->logger->info("Client ID: $client_id | Client Secret : $client_secret | Testmode: $testmode");
				
				
				$api_data['transaction_id'] = time() ."-". $order->getRealOrderId();
				$api_data['phone'] = $billing->getTelephone();
				$api_data['email'] = $billing->getEmail();
				$api_data['name'] = $billing->getFirstname() ." ". $billing->getLastname();
				$api_data['amount'] = round((int)$order->getGrandTotal(),2);
				$api_data['currency'] = "INR";
				$api_data['redirect_url'] = $this->urlBuilder->getUrl("banwire/response");
				$this->logger->info("Date sent for creating order ".print_r($api_data,true));
				$ds = DIRECTORY_SEPARATOR;
				include __DIR__ . "$ds..$ds/lib/Banwire.php";
				
				$api = new \Banwire($client_id,$client_secret,$testmode);
				$response = $api->createOrderPayment($api_data);
				$this->logger->info("Response from Server". print_r($response,true));
				if(isset($response->order ))
				{
					$this->setAction($response->payment_options->payment_url);
					$this->checkoutSession->setPaymentRequestId($response->order->id);		
				}
			}catch(\CurlException $e){
				// handle exception related to connection to the sever
				$this->logger->info((string)$e);
				$method_data['errors'][] = $e->getMessage();
			}catch(\ValidationException $e){
				// handle exceptions related to response from the server.
				$this->logger->info($e->getMessage()." with ");
				if(stristr($e->getMessage(),"Authorization"))
				{
					$inbox->addCritical("Banwire Authorization Error",$e->getMessage());
				}
				$this->logger->info(print_r($e->getResponse(),true)."");
				$method_data['errors'] = $e->getErrors();			
			}catch(\Exception $e)
			{ // handled common exception messages which will not get caught above.
				$method_data['errors'][] = $e->getMessage();
				$this->logger->info('Error While Creating Order : ' . $e->getMessage());
			}
			
		}
		else
		{
			$this->logger->info('Order with ID $orderId not found. Quitting :-(');
		}
		
		
		
			$showPhoneBox = false;
			if(isset($method_data['errors']) and is_array($method_data['errors']))
			{
				foreach($method_data['errors'] as $error)
				{
					if(stristr($error,"phone"))
						$showPhoneBox = true;
				}
				
			$this->setMessages($method_data['errors']);
			}
			if($showPhoneBox)
				$this->setTelephone($api_data['phone']);
			$this->setShowPhoneBox($showPhoneBox);
	}
}

<?php 
if (!function_exists("curl_init")) {
  throw new Exception("FastSpring API needs the CURL PHP extension.");
}


class FastSpring {
	private $store_id;
	private $api_username;
	private $api_password;
	
    /**
     * @var boolean
     */
	public $test_mode = false;
	
	public function __construct($store_id, $api_username, $api_password) {
		$this->store_id = $store_id;
		$this->api_username = $api_username;
		$this->api_password = $api_password;
	}
	
	/**
	 * create new order url and redirect user to it 
	 */
	public function createSubscription($product_ref, $customer_ref) {
		$url = "http://sites.fastspring.com/".$this->store_id."/product/".$product_ref."?referrer=".$customer_ref;
		$url = $this->addTestMode($url);
		
		header("Location: $url");
	}
	
	/**
	 * retrieve subscription data from fastspring API
	 */ 
	public function getSubscription($subscription_ref) {
		$url = $this->getSubscriptionUrl($subscription_ref);
		
		$ch = curl_init($url);
		
		curl_setopt($ch, CURLOPT_USERPWD, $this->api_username . ":" . $this->api_password);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		
		// turn ssl certificate verification off, i get http response 0 otherwise
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		
		if ($info["http_code"] == 200) {
			set_error_handler("domDocumentErrorHandler");
	
			try {
				$doc = new DOMDocument();
		  		$doc->loadXML($response);
	
		  		$sub = $this->parseFsprgSubscription($doc);
		  	} catch(Exception $e) {
		  		$fsprgEx = new FsprgException("An error occurred calling the FastSpring subscription service", 0, $e);
		  		$fsprgEx->httpStatusCode = $info["http_code"];
			}
			
			restore_error_handler();
		} else {
			$fsprgEx = new FsprgException("An error occurred calling the FastSpring subscription service");
			$fsprgEx->httpStatusCode = $info["http_code"];
		}
		
		if (isset($fsprgEx)) {
			throw $fsprgEx;
		}
		
  		return $sub;
	}
	
	/**
	 * update an existing subscription to fastspring API
	 */
	public function updateSubscription($subscriptionUpdate) {
		$url = $this->getSubscriptionUrl($subscriptionUpdate->reference);
		
		$ch = curl_init($url);
		
		curl_setopt($ch, CURLOPT_USERPWD, $this->api_username . ":" . $this->api_password);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/xml"));
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $subscriptionUpdate->toXML());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		
		// turn ssl certificate verification off, i get http response 0 otherwise
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		
		if ($info["http_code"] == 200) {
			set_error_handler("domDocumentErrorHandler");
	
			try {
				$doc = new DOMDocument();
			  	$doc->loadXML($response);
			  	
			  	$sub = $this->parseFsprgSubscription($doc);
			  } catch(Exception $e) {
				$fsprgEx = new FsprgException("An error occurred calling the FastSpring subscription service", 0, $e);
		  		$fsprgEx->httpStatusCode = $info["http_code"];
			}
			
			restore_error_handler();
		} else {
			$fsprgEx = new FsprgException("An error occurred calling the FastSpring subscription service");
			$fsprgEx->httpStatusCode = $info["http_code"];
		}
	  	
	  	curl_close($ch);
	  	
	  	if (isset($fsprgEx)) {
	  		throw $fsprgEx;
	  	}
	  	
	  	return $sub;
	}
	
	/**
	 * send cancel request for a subscription to fastspring API
	 */
	public function cancelSubscription($subscription_ref) {
		$url = $this->getSubscriptionUrl($subscription_ref);
		
		$ch = curl_init($url);
		
		curl_setopt($ch, CURLOPT_USERPWD, $this->api_username . ":" . $this->api_password);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		
		// turn ssl certificate verification off, i get http response 0 otherwise
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		
		if ($info["http_code"] == 200) {
			set_error_handler("domDocumentErrorHandler");
	
			try {
				$doc = new DOMDocument();
			  	$doc->loadXML($response);
			  	
			  	$sub = $this->parseFsprgSubscription($doc);
			  	
			  	$subResp->subscription = $sub;
			  } catch(Exception $e) {
				$fsprgEx = new FsprgException("An error occurred calling the FastSpring subscription service", 0, $e);
		  		$fsprgEx->httpStatusCode = $info["http_code"];
			}
			
			restore_error_handler();
		} else {
			$fsprgEx = new FsprgException("An error occurred calling the FastSpring subscription service");
			$fsprgEx->httpStatusCode = $info["http_code"];
		}
	  	
	  	curl_close($ch);
	  	
	  	if (isset($fsprgEx)) {
	  		throw $fsprgEx;
	  	}
	  	
	  	return $subResp;
	}
	
	/**
	 * send renew request for an on-demand subscription to fastspring API
	 */
	public function renewSubscription($subscription_ref) {
		$url = $this->getSubscriptionUrl($subscription_ref."/renew");
		
		$ch = curl_init($url);
		
		curl_setopt($ch, CURLOPT_USERPWD, $this->api_username . ":" . $this->api_password);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		// turn ssl certificate verification off, i get http response 0 otherwise
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		
		if ($info["http_code"] != 201) {
			$fsprgEx = new FsprgException("An error occurred calling the FastSpring subscription service");
			$fsprgEx->httpStatusCode = $info["http_code"];
			$fsprgEx->errorCode = $response;
		}
		
		curl_close($ch);
		
		if (isset($fsprgEx)) {
			throw $fsprgEx;
		}
	}
	
    /**
     * Search orders in the entire company.
     * @param string $query seqrch query.
     * The query string is case-insensitive and can be any of the following:
     * * Exact order reference. Example: ABC123-123-123
     * * Customer last name (full or 'starts with'). Example: doe
     * * Customer company name (full or 'starts with'). Example: abc
     * * Full customer email address. Example: doe@abc.com
     * * Customer email domain name, beginning with an "@" sign. Example: @abc.com
     * * Last 5 digits of a credit card number (credit card orders only). Example: 54321
     * * Last 4 digits of a credit card number (credit card orders only). Example: 4321
     * * Specific coupon code. Example search phrase: coupon XYZ123
     * * Exact referrer. Example: referrer abc
     * @param boolean $filterTest if true, only orders with test mode equal to
     * current test mode will be returned. Defaults to true.
     * @return FsprgOrder[]
     */
    public function searchOrders($query, $filterTest = true)
    {
        $url = $this->makeUrl('/orders/search?query=' . urlencode($query));
        $xml = $this->requestGet($url);
        
        $orders = array();
        foreach ($xml->order as $orderXml) {
            $order = $this->parseFsprgOrder($orderXml);
            
            if (!$filterTest || $order->test == $this->test_mode) {
                $orders[] = $order;
            }
        }
        
        return $orders;
    }
    
    /**
     * Makes http GET request and returns result xml.
     * @param string $url
     * @return SimpleXMLElement
     * @throws FsprgException
     */
    private function requestGet($url)
    {
        $ch = curl_init($url);
        return $this->request($ch);
    }
    
    /**
     * Makes http request and returns result xml.
     * @param curl $ch
     * @return SimpleXMLElement
     * @throws FsprgException
     */
    private function request($ch)
    {
        curl_setopt($ch, CURLOPT_USERPWD, $this->api_username . ":" . $this->api_password);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		
		// turn ssl certificate verification off, i get http response 0 otherwise
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		
		if ($info["http_code"] == 200) {
			set_error_handler("domDocumentErrorHandler");
	
			try {
                $xml = simplexml_load_string($response);
	
		  	} catch(Exception $e) {
		  		$fsprgEx = new FsprgException("An error occurred calling the FastSpring subscription service", 0, $e);
		  		$fsprgEx->httpStatusCode = $info["http_code"];
			}
			
			restore_error_handler();
		} else {
			$fsprgEx = new FsprgException("An error occurred calling the FastSpring subscription service");
			$fsprgEx->httpStatusCode = $info["http_code"];
		}
		
		if (isset($fsprgEx)) {
			throw $fsprgEx;
		}
		
  		return $xml;
    }
    
	/**
	 * compose customer's subscription management url for a given subscription reference
	 */
	private function getSubscriptionUrl($subscription_ref) {
		$url = $this->getBaseUrl()."/subscription/".$subscription_ref;

		$url = $this->addTestMode($url);
		
		return $url;
	}
    
    /**
     * Creates api url with url part.
     * @param string $part
     * @return string
     */
    private function makeUrl($part)
    {
        $url = $this->getBaseUrl() . $part;
		$url = $this->addTestMode($url);
        return $url;
    }
    
    /**
     * compose base subscription url
     * @return string
     */
    private function getBaseUrl()
    {
        return "https://api.fastspring.com/company/".$this->store_id;
    }
	
	/**
	 * add test parameter to url if test mode enabled
	 */
	private function addTestMode($url) {
		if ($this->test_mode) {
			if (strpos($url, '?') != false) {
				$url = $url . "&mode=test";
			} else {
				$url = $url . "?mode=test";
			}
		}
		
		return $url;
	}
	
	/**
	 * Parse order xml into order and customer data.
     * @return FsprgOrder
	 */
	private function parseFsprgOrder($doc) {
        $obj = new FsprgOrder();
		
        $obj->reference = (string) $doc->reference;
        $obj->status = (string) $doc->status;
        $obj->statusChanged = (string) $doc->statusChanged;
        $obj->test = (boolean) $doc->test;
        $obj->returnStatus = (string) $doc->returnStatus;
        $obj->customer = $this->parseFsprgCustomer($doc->customer);
  		
  		return $obj;
	}
    
	/**
	 * parse subscription xml into subscription and customer data
	 */
	private function parseFsprgSubscription($doc) {
		$sub = new FsprgSubscription();
		
		$sub->status = $doc->getElementsByTagName("status")->item(0)->nodeValue;
		$sub->statusChanged = strtotime($doc->getElementsByTagName("statusChanged")->item(0)->nodeValue);
  		$sub->statusReason = $doc->getElementsByTagName("statusReason")->item(0)->nodeValue;
  		$sub->cancelable = $doc->getElementsByTagName("cancelable")->item(0)->nodeValue;
  		$sub->reference = $doc->getElementsByTagName("reference")->item(0)->nodeValue;
  		$sub->test = $doc->getElementsByTagName("test")->item(0)->nodeValue;
  		
  		$customer = new FsprgCustomer();
  		
  		$customer->firstName = $doc->getElementsByTagName("firstName")->item(0)->nodeValue;
  		$customer->lastName = $doc->getElementsByTagName("lastName")->item(0)->nodeValue;
  		$customer->company = $doc->getElementsByTagName("company")->item(0)->nodeValue;
  		$customer->email = $doc->getElementsByTagName("email")->item(0)->nodeValue;
  		$customer->phoneNumber = $doc->getElementsByTagName("phoneNumber")->item(0)->nodeValue;
  		
  		$sub->customer = $customer;
  		
  		$sub->customerUrl = $doc->getElementsByTagName("customerUrl")->item(0)->nodeValue;
  		$sub->productName = $doc->getElementsByTagName("productName")->item(0)->nodeValue;
  		$sub->tags = $doc->getElementsByTagName("tags")->item(0)->nodeValue;
  		$sub->quantity = $doc->getElementsByTagName("quantity")->item(0)->nodeValue;
  		$sub->nextPeriodDate = strtotime($doc->getElementsByTagName("nextPeriodDate")->item(0)->nodeValue);
  		$sub->end = strtotime($doc->getElementsByTagName("end")->item(0)->nodeValue);
  		
  		return $sub;
	}

    /**
	 * Parse customer xml into customer data.
     * @return FsprgCustomer
	 */
	private function parseFsprgCustomer($doc) {
        $customer = new FsprgCustomer();
  		
  		$customer->firstName = (string) $doc->firstName;
  		$customer->lastName = (string) $doc->lastName;
  		$customer->company = (string) $doc->company;
  		$customer->email = (string) $doc->email;
  		$customer->phoneNumber = (string) $doc->phoneNumber;
        
        return $customer;
    }
}

class FsprgOrder {
    /**
     * @var string
     */
	public $reference;
    /**
     * @var string Enum (open | request | requested | acceptance | accepted | fulfillment | fulfilled | completion | completed | canceled | failed)
     */
	public $status;
    /**
     * @var string date (like "2010-08-15T00:00:00.000Z")
     */
	public $statusChanged;
    /**
     * @var boolean
     */
	public $test;
    /**
     * @var string enum (none | partial | full)
     */
    public $returnStatus;
    /**
     * @var FsprgCustomer
     */
	public $customer;
}

class FsprgSubscription {
	public $status;
	public $statusChanged;
	public $statusReason;
	public $cancelable;
	public $reference;
	public $test;
	public $customer;
	public $customerUrl;
	public $productName;
	public $tags;
	public $quantity;
}

class FsprgCustomer {
	public $firstName;
	public $lastName;
	public $company;
	public $email;
	public $phoneNumber;
}

class FsprgSubscriptionUpdate {
	public $reference;
	public $productPath;
	public $quantity;
	public $tags;
	public $noEndDate;
	public $coupon;
	public $discountDuration;
	public $proration;
	
	public function __construct($subscription_ref) {
		$this->reference = $subscription_ref;
	}
	
	public function toXML() {
		$xmlResult = new SimpleXMLElement("<subscription></subscription>");
		
		if ($this->productPath) {
			$xmlResult->productPath = $this->productPath;
		}
		if ($this->quantity) {
			$xmlResult->quantity = $this->quantity;
		}
		if ($this->tags) {
			$xmlResult->tags = $this->tags;
		}
		if (isset($this->noEndDate) && $this->noEndDate) {
			$xmlResult->addChild("no-end-date", null);
		}
		if ($this->coupon) {
			$xmlResult->coupon = $this->coupon;
		}
		if ($this->discountDuration) {
			$xmlResult->addChild("discount-duration", $this->discountDuration);
		}
		if (isset($this->proration)) {
			if ($this->proration) {
				$xmlResult->proration = "true";
			} else {
				$xmlResult->proration = "false";
			} 
		}
		
		return $xmlResult->asXML();
	}
}

class FsprgException extends Exception {
	public $httpStatusCode;
	public $errorCode;
}

function domDocumentErrorHandler($number, $error){
	if (preg_match("/^DOMDocument::load\([^:]+: (.+)$/", $error, $m) === 1) {
		throw new Exception($m[1]);
	}
}

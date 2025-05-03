<?php

namespace App\Libraries;
use PhonePe\payments\v1\PhonePePaymentClient;

class phonePeLibrary {


  function __construct() {
    require_once APPPATH . "ThirdParty/phonePe/vendor/autoload.php";
  }


  public function phonePeClient() {
  	$phonePeClient = new PhonePePaymentClient(getenv('PHPEMERCHANTID'), getenv('PHPESALTKEY'), getenv('PHPESALTINDEX'), getenv('PHPEENV'),getenv('PHPESHOULDPUBLISHEVENTS'));
  	return $phonePeClient;
  }

}
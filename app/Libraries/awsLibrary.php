<?php

namespace App\Libraries;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;

class awsLibrary {

  function __construct() {
    require_once APPPATH . "ThirdParty/aws/vendor/autoload.php";
  }


  public function s3Client() {

    //- create s3 client
    $credentials = new Credentials(getenv('s3Key'),getenv('s3Secret'));
    
    $s3 = new S3Client([
        'version'     => 'latest',
        'region'      => getenv('s3Region'),
        'credentials' => $credentials,
        'use_accelerate_endpoint' => true,
        'scheme' => "http", 
        'signature' => 'v4'
    ]);

    return $s3;

  }



}
<?php

namespace App\Libraries;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class jwtLibrary {

  function __construct() {
    require_once APPPATH . "ThirdParty/php-jwt/vendor/autoload.php";
  }


  public function JWTencode($payload, $key) {
    $jwt = JWT::encode($payload, $key, 'HS256');
    return $jwt;
  }


  public function JWTdecode($jwt, $key) {
    $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
    return $decoded;
  }

}
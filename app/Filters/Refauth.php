<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\jwtLibrary;
use Config\Services;

class Refauth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
      
      $token = $request->getHeader('Authorization');

      if (empty($token)) {

        $msg = array('status' => 401, 'error' => 'Unauthorized');
        return Services::response()->setStatusCode(401)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

      }else{

        $token = $token->getValue();

        if (strpos($token, "Bearer ") === 0) {

          $token = substr($token, 7);

          try {

            $jwt = new jwtLibrary();
            $decoded = $jwt->JWTdecode($token,getenv('JWT_SECRET'));

            if ($decoded->tokTy != 'ref') {
              throw new \Exception('Wrong Token');
            }else{

              $request->token = $token;
              $request->decoded = $decoded;
              return $request;
            }

          } catch (\Exception $e) {
            
            $msg = array('status' => 401, 'error' => 'Unauthorized');
            return Services::response()->setStatusCode(401)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

          }

        } else {

          $msg = array('status' => 401, 'error' => 'Unauthorized');
          return Services::response()->setStatusCode(401)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);          
        }
       
      }

    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do something here
    }
}


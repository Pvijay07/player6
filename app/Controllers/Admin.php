<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RequestInterface;
use App\Controllers\BaseController;
use App\Models\AdminModel;
use App\Libraries\jwtLibrary;
use Config\Services;

class Admin extends BaseController
{

    public function __construct(){
      helper('Common');
    }


    //- Login Api : object
    public function login(): object
    {
     
      $email = $this->request->getJsonVar('email');
      $password = $this->request->getJsonVar('password');

      if (!is_null($email) && !is_null($password)) {
        
        $this->validation->setRule('email', 'Email', 'required|min_length[10]|max_length[10]');  
        $this->validation->setRule('password', 'Password', 'required|min_length[6]|max_length[36]');
        $data = array('email' => $email, 'password' => $password);
        
        if (!$this->validation->run($data)) {

          $msg = array();
          if ($this->validation->hasError('email')) {
            $email = $this->validation->getError('email');
            array_push($msg, $email);
          }

          if ($this->validation->hasError('password')) {
            $password = $this->validation->getError('password');
            array_push($msg, $password);
          }

          array_push($msg, array('status' => 403,'error' => $msg));

          return $this->response->setStatusCode(403)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

        }else{

          $adminModel = new AdminModel();
          $password = md5($password);
          $emailDeails = $adminModel->checkUserCred($email,$password);

          if (count($emailDeails) > 0) {

            //- Update Login Status And Time
            $numbers = [];
            for ($i = 0; $i < 4; $i++) {
              $numbers[] = random_int(0, 9);
            }

            $data['otp'] = implode('',$numbers);
            $data['emailOtp'] = $data['otp'];
            $emailOtp = $data['emailOtp'];

            $data['ip'] = $this->request->getIPAddress();
            $data['loginTime'] = gmdate("Y-m-d H:i:s");
            $insert = $adminModel->updateLoginStatus($data, $emailDeails[0]->id);

            //- Send OTP to Email
            /*
            $email = Services::email(); 

            $email->clear();
            $email->setFrom(getenv('emailFrom'), getenv('emailDomain'));
            $email->setTo($emailDeails[0]->email);
            
            $email->setSubject('player6 admin OTP');
            $email->setMessage('Admin OTP for login '.$emailOtp);
            if (!$email->send()) {
              $data['userId'] = '0';
              $data['text'] = 'Unable To Send OTP Email';
              $data['disc'] = 'Server SMTP Failed to send SMS';
              CreateErrorLog($data);
              unset($data);
            } */

            //- Send OTP to mobile

            try {
              // User details array
              $userDetails = array(
                "Text" => $emailOtp . " is your OTP verification code to login into Player6. Enjoy playing - Spegetti Sports.",
                "Number" => "91" . $emailDeails[0]->email,
                "SenderId" => "PLRSIX",
                "DRNotifyUrl" => getenv("app_baseURL")."notifyurl",
                "DRNotifyHttpMethod" => "POST",
                "Tool" => "API"
              );
                    
              $curl = \Config\Services::curlrequest();
              // Make the POST request
                $response = $curl->request('POST', getenv("bSMURL"), [
                  'headers' => [
                      'Content-Type' => 'application/json',
                      'Authorization' => getenv("bSMSAuth")
                  ],
                  'json' => $userDetails
              ]);

              // Get HTTP response code
              $responce = $response->getStatusCode();
              $responseBody = $response->getBody();
              $error = '';

            } catch (\Exception $e) {
              $error = $e->getMessage();
              $responce = '500';
          }

            if ($responce == '200') {
              $data['userId'] = '0';
              $data['text'] = 'Unable To Send OTP Email';
              $data['disc'] = 'SMS API unable to send OTP';
              CreateErrorLog($data);
              unset($data);
            }                        

            $msg = array('status' => 200, 'msg' => 'Success', 'userId' => $emailDeails[0]->id);
            return $this->response->setStatusCode(200)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

          }else{

            $msg = array('status' => 401, 'error' => 'Account not found');
            return $this->response->setStatusCode(200)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

          }

          
        }        

      }else{

        $msg = array('status' => 404, 'error' => 'Page not found');
        return $this->response->setStatusCode(404)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);
      
      }
      
    }


    //- Verify OTP
    public function ValidateOTP(): object
    {

      $otp = $this->request->getJsonVar('otp');
      $userId = $this->request->getJsonVar('userId');
      if (!is_null($otp) && !is_null($userId)) {

        $this->validation->setRule('otp', 'OTP', 'required|min_length[4]|max_length[4]|regex_match[/^[0-9]*$/]');
        $this->validation->setRule('userId', 'User Id', 'required|');
        $data = array('otp' => $otp, 'userId' => $userId);

        if (!$this->validation->run($data)) {

          $msg = array();
          if ($this->validation->hasError('otp')) {
            $otp = $this->validation->getError('otp');
            array_push($msg, array('error' => $otp ));
          }

          if ($this->validation->hasError('userId')) {
            $userId = $this->validation->getError('userId');
            array_push($msg, array('error' => $userId ));
          }

          $msg = array('status' => 403, 'msg' => $msg);
          return $this->response->setStatusCode(403)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

        }else{

          $adminModel = new AdminModel();

          //- Verify USER AND OTP
          $userDetails = $adminModel->getUserDetailsById($userId,$otp);

          if (count($userDetails) > 0) {
            
            //- Create Session
            $jwt = new jwtLibrary();

            $userData = [
                'id'  => $userDetails[0]->id,
                'email' => $userDetails[0]->email,
                'ip_address' => $userDetails[0]->myIp,
                'last_login' => $userDetails[0]->lastLogin
            ];

            $refLoad = CreateRefreshTokenPayload($userData);
            $refToken = $jwt->JWTencode($refLoad,getenv('JWT_SECRET'));

            //- Update Refesh Token In DB
            $adminModel->updateUserToken($userId,$refToken);

            $accesLoad = CreateAccessTokenPayload($userData);
            $accesToken = $jwt->JWTencode($accesLoad,getenv('JWT_SECRET'));

            $msg = array('status' => 200, 'msg' => 'Success', 'data' => array('refToken' => $refToken, 'accesToken' => $accesToken, 'userId' => $userDetails[0]->id ) );
            return $this->response->setStatusCode(200)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);
          }else{

            $msg = array('status' => 201, 'error' => 'Invalid OTP');
            return $this->response->setStatusCode(200)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

          }

        }

      }else{ 

        $msg = array('status' => 404, 'error' => 'Page not found');
        return $this->response->setStatusCode(404)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);
      }

    }


    //- Get New Access Token
    public function getNewAccessToken(): object
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(5);

      $userData = $this->request->decoded->userData;

      if (!empty($userId) && $userId == $userData->id) {

        $adminModel = new AdminModel();
        $adminModel = $adminModel->getUserById($userId);
        if (count($adminModel) > 0 && $this->request->token == $adminModel[0]->refToken) {

            $jwt = new jwtLibrary();

            $userData = [
                'id'  => $adminModel[0]->id,
                'email' => $adminModel[0]->email,
                'ip_address' => $adminModel[0]->myIp,
                'last_login' => $adminModel[0]->lastLogin
            ];

            $accesLoad = CreateAccessTokenPayload($userData);
            $accesToken = $jwt->JWTencode($accesLoad,getenv('JWT_SECRET'));

            $msg = array('status' => 200, 'msg' => 'Success', 'accesToken' => $accesToken);
            return $this->response->setStatusCode(200)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);
          
        }else{

          $msg = array('status' => 422, 'error' => 'Request cannot be processed');
          return $this->response->setStatusCode(422)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

        }

      }else{

        $msg = array('status' => 404, 'error' => 'Page not found');
        return $this->response->setStatusCode(404)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);
                    
      }

    }


}
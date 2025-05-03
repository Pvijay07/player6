<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RequestInterface;
use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\UserKYCModel;
use App\Models\UserWalletModel;
use App\Libraries\jwtLibrary;
use App\Libraries\awsLibrary;
use App\Libraries\phonePeLibrary;
use Config\Services;
use CodeIgniter\Files\File;

class User extends BaseController
{

    public function __construct(){
      helper('Common');
    }


    public function phonePeCallBack(){

      $response = file_get_contents('php://input');
      $xVerify = $this->request->getHeader('X-VERIFY');
                
      if (!empty($response) && (!is_null($xVerify) && !empty($xVerify)) ) {

        $xVerifynew = str_replace("X-Verify: ","",$xVerify);
        
        $response = json_decode($response);
        $response = $response->response;

        $userWalletModel = new UserWalletModel();
        $phonPe = new phonePeLibrary();
        $phonPeCli = $phonPe->phonePeClient();

        $isValid = $phonPeCli->verifyCallback($response, $xVerifynew);

        if ($isValid) {
          $response = base64_decode($response);
          $response = json_decode($response);

          if ($response->code = 'PAYMENT_SUCCESS' && $response->data->state == 'COMPLETED') {

            $transac = $userWalletModel->getTransactionDetails($response->data->merchantTransactionId);

            if (count($transac) > 0) {
              
              $totalAmount = $response->data->amount / 100;
              $totalAmount = round($totalAmount, 2);
              $totalAmount = floatval($totalAmount);

              $data['transId'] = $response->data->transactionId;
              $data['state'] = $response->data->state;
              $data['respCode'] = $response->data->responseCode;
              $data['amount'] = $totalAmount;
              $data['status'] = '1';

              $userWalletModel->updateTransac($response->data->merchantTransactionId,$data);

              //- Calculate Gst
              $gst = $totalAmount * (28/100);
              $gst = round($gst, 2);
              $gst = floatval($gst);

              $amountAfterGst = $totalAmount - $gst;
              $amountAfterGst = round($amountAfterGst, 2);
              $amountAfterGst = floatval($amountAfterGst);

              $tmpWall['userId'] = $transac[0]->userId;
              $tmpWall['amount'] = $totalAmount;
              $tmpWall['transId'] = $transac[0]->id;
              $tmpWall['text'] = 'Funds deposit';
              $tmpWall['status'] = '1';
              $userWalletModel->creditWallet($tmpWall);
              unset($tmpWall);

              $tmpWall['userId'] = $transac[0]->userId;
              $tmpWall['amount'] = $gst;
              $tmpWall['transId'] = $transac[0]->id;
              $tmpWall['text'] = 'GST deducted';
              $tmpWall['status'] = '5';
              $userWalletModel->debitWallet($tmpWall);
              unset($tmpWall);

              if ($totalAmount <= 1000) {
                $tmpWall['userId'] = $transac[0]->userId;
                $tmpWall['amount'] = $gst;
                $tmpWall['transId'] = $transac[0]->id;
                $tmpWall['text'] = 'Bonus added';
                $tmpWall['status'] = '2';
                $userWalletModel->creditWallet($tmpWall);
                unset($tmpWall);
              }

            }

          }else if ($response->code = 'PAYMENT_ERROR') {

            $transac = $userWalletModel->getTransactionDetails($response->data->merchantTransactionId);

            if (count($transac) > 0) {

              $totalAmount = $response->data->amount / 100;
              $totalAmount = round($totalAmount, 2);
              $totalAmount = floatval($totalAmount);            

              $data['transId'] = $response->data->transactionId;
              $data['state'] = $response->data->state;
              $data['respCode'] = $response->data->responseCode;
              $data['amount'] = $totalAmount;
              $data['status'] = '2';

              $userWalletModel->updateTransac($response->data->merchantTransactionId,$data);
            }
            
          }
        
        }else{

          $response = base64_decode($response);
          $response = json_decode($response);

          $transac = $userWalletModel->getTransactionDetails($response->data->merchantTransactionId);

          if (count($transac) > 0) {

            $totalAmount = $response->data->amount / 100;
            $totalAmount = round($totalAmount, 2);
            $totalAmount = floatval($totalAmount);        

            $data['transId'] = $response->data->transactionId;
            $data['state'] = $response->data->state;
            $data['respCode'] = $response->data->responseCode;
            $data['amount'] = $totalAmount;
            $data['status'] = '2';

            $userWalletModel->updateTransac($response->data->merchantTransactionId,$data);
          }

        }

      }

    }


    public function strContains($haystack,$needle){
      $retValue = false;
      if (!function_exists('str_contains')) {
        if (!empty($needle)) {
          if(strpos($haystack, $needle)){
            $retValue = true;
          }
        }
      }else{
        if (str_contains($haystack, $needle)) {
          $retValue = true;
        }
      }

      return $retValue;
    }


    //- Login Api : object
    public function login(): object
    {
     
      $mnumber = $this->request->getJsonVar('mnumber');
      if (!is_null($mnumber)) {
        
        if ($this->strContains($mnumber, '@')) {
          $this->validation->setRule('mnumber', 'Email', 'required|max_length[100]|email_check', array('email_check' => 'Enter a valid email' ));
          $data = array('mnumber' => $mnumber);
        }else{
          $this->validation->setRule('mnumber', 'Number', 'required|min_length[10]|max_length[14]|regex_match[/^[0-9]*$/]');
          $data = array('mnumber' => $mnumber);
        }

        if (!$this->validation->run($data)) {

          $msg = array();
          if ($this->validation->hasError('mnumber')) {
            $mnumber = $this->validation->getError('mnumber');
            array_push($msg, array('status' => 403,'error' => $mnumber ));
          }

          return $this->response->setStatusCode(403)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

        }else{
          
          $userModel = new UserModel();

          $authUser = false;
          if ($this->strContains($mnumber, '@')) {

            $userDeails = $userModel->checkEmailExist($mnumber);
            if (count($userDeails) > 0) {
              $authUser = true;
            }

          }else{

            $userDeails = $userModel->checkNumberExist($mnumber);
            if (count($userDeails) > 0) {
              $authUser = true;
            }

          }

          if ($authUser) {

            //- Update Login Status And Time
            $numbers = [];
            for ($i = 0; $i < 4; $i++) {
              $numbers[] = random_int(0, 9);
            }

            $data['otp'] = implode('',$numbers);
            if ($this->strContains($mnumber, '@')) {  
              $data['emailOtp'] = $data['otp'];
              $emailOtp = $data['emailOtp'];
            }else{
              $data['mobileOtp'] = $data['otp'];
              $mobileOtp = $data['mobileOtp'];
            }
            $data['ip'] = $this->request->getIPAddress();
            $data['loginTime'] = gmdate("Y-m-d H:i:s");
            $insert = $userModel->updateLoginStatus($data, $userDeails[0]->id);

            if ($this->strContains($mnumber, '@')) {
              
              //- Send OTP to Email
              $email = Services::email();

              $email->clear();
              $email->setFrom(getenv('emailFrom'), getenv('emailDomain'));
              $email->setTo($userDeails[0]->email);
              
              $email->setSubject('player6 login OTP');
              $email->setMessage('OTP for login '.$emailOtp);
              if (!$email->send()) {
                $data['userId'] = '0';
                $data['text'] = 'Unable To Send OTP Email';
                $data['disc'] = 'Server SMTP Failed to send SMS';
                CreateErrorLog($data);
                unset($data);
              }

            }else{
              //- Send OTP to mobile
              try {
                  // User details array
                  $userDetails = array(
                    "Text" => $mobileOtp . " is your OTP verification code to login into Player6. Enjoy playing - Spegetti Sports.",
                    "Number" => "91" . $userDeails[0]->number,
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
              if ($responce != '200') {
                $data['userId'] = '0';
                $data['text'] = 'Unable To Send OTP';
                $data['disc'] = $error;
                CreateErrorLog($data);
                unset($data);
              }
            }

            $msg = array('status' => 200, 'msg' => 'Success', 'userId' => $userDeails[0]->id);
            return $this->response->setStatusCode(200)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

          }else{

            $msg = array('status' => 401, 'error' => 'Account not found');
            return $this->response->setStatusCode(401)
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


    //- Sign Up 
    public function SignUp(): object
    {

      $mnumber = $this->request->getJsonVar('mnumber');
      $email = $this->request->getJsonVar('email');
      if (!is_null($mnumber) && !is_null($email)) {
        
        $this->validation->setRule('mnumber', 'Number', 'required|min_length[10]|max_length[14]|regex_match[/^[0-9]*$/]');
        $this->validation->setRule('email', 'Email', 'required|max_length[100]|email_check', array('email_check' => 'Enter a valid email' ));
        $data = array('email' => $email, 'mnumber' => $mnumber);

        if (!$this->validation->run($data)) {

          $msg = array();
          if ($this->validation->hasError('mnumber')) {
            $mnumber = $this->validation->getError('mnumber');
            array_push($msg, array('error' => $mnumber ));
          }

          if ($this->validation->hasError('email')) {
            $email = $this->validation->getError('email');
            array_push($msg, array('error' => $email ));
          }

          $msg = array('status' => 403, 'msg' => $msg );

          return $this->response->setStatusCode(403)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

        }else{       
          $userWalletModel = new UserWalletModel();
          $userModel = new UserModel();
          $emailExist = $userModel->checkEmailExist($email);
          $numberExist = $userModel->checkNumberExist($mnumber);

          if (count($emailExist) == 0 && count($numberExist) == 0) {
            
            //- Create New User 
            $mobnumbers = [];
            for ($i = 0; $i < 4; $i++) {
              $mobnumbers[] = random_int(0, 9);
            }

            $emailnumbers = [];
            for ($i = 0; $i < 4; $i++) {
              $emailnumbers[] = random_int(0, 9);
            }      

            $userName = strstr($email, '@', true);
            $data['name'] = $userName;
            $data['number'] = $mnumber;
            $data['email'] = $email;
            $sendEmail = $email;
            $data['mobileOtp'] = implode('',$mobnumbers);
            $data['emailOtp'] = implode('',$emailnumbers);
            $mobileOtp = $data['mobileOtp'];
            $emailOtp = $data['emailOtp'];
            $data['loginStatus'] = '0';
            $data['ip'] = $this->request->getIPAddress();           
            $data['loginTime'] = gmdate("Y-m-d H:i:s");
            $insert = $userModel->createUser($data);
            $userDeails = $userModel->getUserDetailsByNumber($mnumber);

            //- Bonus 20 Rs        
            $tmpWall['userId'] = $userDeails[0]->id;
            $tmpWall['amount'] = 20;
            $tmpWall['text'] = 'Bonus added';
            $tmpWall['status'] = '9';
            $userWalletModel->creditWallet($tmpWall);
            unset($tmpWall);

            //- Send OTP to mobile
            try {
              // User details array
              $userDetails = array(
                "Text" => $mobileOtp . " is your OTP verification code to login into Player6. Enjoy playing - Spegetti Sports.",
                "Number" => "91" . $mnumber,
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

            if ($responce != '200') {
              $data['userId'] = '0';
              $data['text'] = 'Unable To Send OTP';
              $data['disc'] = $error;
              CreateErrorLog($data);
              unset($data);
            }

            //- Send OTP to Email
            $email = Services::email();

            $email->clear();
            $email->setFrom(getenv('emailFrom'), getenv('emailDomain'));
            $email->setTo($sendEmail);
            
            $email->setSubject('player6 login OTP');
            $email->setMessage('OTP for login '.$emailOtp);

            if (!$email->send()) {
              $data['userId'] = '0';
              $data['text'] = 'Unable To Send OTP Email';
              $data['disc'] = 'Server SMTP Failed to send SMS';
              CreateErrorLog($data);
              unset($data);
            } 

            $msg = array('status' => 200, 'msg' => 'Success', 'id' => $userDeails[0]->id); 
            return $this->response->setStatusCode(200)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

          }else{

            if (count($emailExist) > 0 && count($numberExist) > 0) {
            
              $msg = array('status' => 401, 'error' => 'Number, Email Already Exist');
              return $this->response->setStatusCode(401)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

            }else if (count($emailExist) > 0 && count($numberExist) == 0) {

              $msg = array('status' => 401, 'error' => 'Email Already Exist');
              return $this->response->setStatusCode(401)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

            }else if (count($emailExist) == 0 && count($numberExist) > 0) {
    
              $msg = array('status' => 401, 'error' => 'Number Already Exist');
              return $this->response->setStatusCode(401)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

            }

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

          $userModel = new UserModel();

          //- Verify USER AND OTP
          $userDetails = $userModel->getUserDetailsById($userId,$otp);

          if (count($userDetails) > 0 ) {

            if ($userDetails[0]->userStatus == '0') {
              
              //- Update Email or Mobile Verify Status
              if ($userDetails[0]->mobileVerify == '0' || $userDetails[0]->emailVerify == '0') {
                if ($otp == $userDetails[0]->mobileOtp) {
                  $userModel->updateMobileVerify($userId);
                }
                if ($otp == $userDetails[0]->emailOtp) {
                  $userModel->updateEmailVerify($userId);
                }
              }
              
              //- Create Session
              $jwt = new jwtLibrary();

              $userData = [
                  'id'  => $userDetails[0]->id,
                  'name'  => $userDetails[0]->name,
                  'number' => $userDetails[0]->number,
                  'email' => $userDetails[0]->email,
                  'ip_address' => $userDetails[0]->myIp,
                  'last_login' => $userDetails[0]->lastLogin
              ];

              $refLoad = CreateRefreshTokenPayload($userData);
              $refToken = $jwt->JWTencode($refLoad,getenv('JWT_SECRET'));

              //- Update Refesh Token In DB
              $userModel->updateUserToken($userId,$refToken);

              $accesLoad = CreateAccessTokenPayload($userData);
              $accesToken = $jwt->JWTencode($accesLoad,getenv('JWT_SECRET'));

              $msg = array('status' => 200, 'msg' => 'Success', 'data' => array('refToken' => $refToken, 'accesToken' => $accesToken, 'userId' => $userDetails[0]->id ) );
              return $this->response->setStatusCode(200)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);
            
            }else{

              $msg = array('status' => 200, 'error' => 'Account suspended contact admin');
              return $this->response->setStatusCode(401)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

            }

          }else{

            $msg = array('status' => 200, 'error' => 'Invalid OTP');
            return $this->response->setStatusCode(401)
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


    //- Log Out
    public function logOut(): object
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;

      $refToken = $this->request->getJsonVar('refToken');

      if (!empty($userId) && $userId == $userData->id) {

        $userModel = new UserModel();
        $details = $userModel->getUserById($userId);

        if (count($details) > 0) {
          
          if ($details[0]->refToken == $refToken) {
          
            //- Delete Token
            $userModel->deleteUserToken($userId);

            $msg = array('status' => 200, 'msg' => 'Success');
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


    //- Get New Access Token
    public function getNewAccessToken(): object
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);

      $userData = $this->request->decoded->userData;

      if (!empty($userId) && $userId == $userData->id) {

        $userModel = new UserModel();
        $userDetails = $userModel->getUserById($userId);
        if (count($userDetails) > 0 && $this->request->token == $userDetails[0]->refToken) {

            $jwt = new jwtLibrary();

            $userData = [
                'id'  => $userDetails[0]->id,
                'name'  => $userDetails[0]->name,
                'number' => $userDetails[0]->number,
                'email' => $userDetails[0]->email,
                'ip_address' => $this->request->getIPAddress(),
                'last_login' => $userDetails[0]->lastLogin
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


    //- Update Profile : object
    public function UpdateProfile(): object
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;

      $userfile = $this->request->getFile('userfile');
      $name = $this->request->getPost('name');

      if ($userId == $userData->id && (!is_null($userfile) || !is_null($name)) ) {

        $data = array();        
        if (!is_null($userfile)) {
          $this->validation->setRule('userfile', 'Profile pick', 'required|uploaded[userfile]|is_image[userfile]|max_size[userfile,2048]|mime_in[userfile,image/jpg,image/jpeg,image/gif,image/png,image/webp]');

          $data['userfile'] = $userfile;
        }

        if (!is_null($name)) {
          $this->validation->setRule('name', 'Full Name', 'required|min_length[2]|max_length[50]');
          $data['name'] = $name;
        }

        if (!$this->validation->run($data)) {
        
          $msg = array();
          if ($this->validation->hasError('userfile')) {
            $userfile = $this->validation->getError('userfile');
            array_push($msg, array('error' => $userfile ));
          }

          if ($this->validation->hasError('name')) {
            $name = $this->validation->getError('name');
            array_push($msg, array('error' => $name ));
          }

          $msg = array('status' => 403, 'msg' => $msg);
          
          return $this->response->setStatusCode(403)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);   

        }else{

          $msg = array();

          if (!is_null($userfile)) {

            $s3 = new awsLibrary();
            $s3 = $s3->s3Client();

            $profileName = $userfile->getRandomName();
            try {
              
              $s3->putObject([
                'Bucket' => getenv('s3Bucket'),
                'Key'    => getenv('userProf').$profileName,
                'ContentType' => $userfile->getClientMimeType(),
                'ContentDisposition' => 'attachment; filename='.$userfile->getRandomName(),
                'SourceFile' => $userfile->getTempName(),
                'ACL'    => 'public-read',
              ]);

              $fileUpload = true;
            } catch (Aws\S3\Exception\S3Exception $e) {
              $fileUpload = false;
            }

            if (!$fileUpload) {

              $data['userId'] = '0';
              $data['text'] = 'Profile pick upload error';
              $data['disc'] = 'Unable to upload profile pick to s3 Bucket';
              CreateErrorLog($data);
              unset($data);
            }

            //- Update In Db
            $data['profileImg'] = $profileName;
           
          }

          if (!is_null($name)) {
            $data['name'] = $name;  
          }

          $userModel = new UserModel();
          $userModel->updateProfile($userData->id,$data);
          $userDetails = $userModel->getUserById($userData->id);
          
          $msg = array('status' => 200, 'msg' => array('profileImg' => getenv('s3Url').getenv('s3Bucket').'/'.getenv('userProf').$userDetails[0]->profileImg, 'name' => $userDetails[0]->name ) );
          return Services::response()->setStatusCode(200)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);
        } 

      }else{

        $msg = array('status' => 401, 'error' => 'Unauthorized');
        return Services::response()->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);

      }

    }


    //- Send Email OTP
    public function sendEmailOtp(): object
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;
      $email = $this->request->getJsonVar('email');

      if ($userId == $userData->id && !is_null($email)) {

        $this->validation->setRule('email', 'Email', 'required|max_length[100]|email_check', array('email_check' => 'Enter a valid email' ));
        $data = array('email' => $email);

        if (!$this->validation->run($data)) {

          $msg = array();

          if ($this->validation->hasError('email')) {
            $email = $this->validation->getError('email');
            array_push($msg, array('error' => $email ));
          }

          $msg = array('status' => 403, 'msg' => $msg );

          return $this->response->setStatusCode(403)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

        }else{  

          $userModel = new UserModel();
          $sendEmail = $email;

          $emailExist = $userModel->checkUniqueEmailExist($email,$userData->id);
          if (count($emailExist) == 0) {

            $numbers = [];
            for ($i = 0; $i < 4; $i++) {
              $numbers[] = random_int(0, 9);
            }

            $emailOtp = implode('',$numbers);

            $userModel->updateEmailOtp($userData->id,$emailOtp);

            //- Send OTP to Email
            $email = Services::email();

            $email->clear();
            $email->setFrom(getenv('emailFrom'), getenv('emailDomain'));
            $email->setTo($sendEmail);
            
            $email->setSubject('player6 email verification OTP');
            $email->setMessage('OTP for email verification '.$emailOtp);

            if (!$email->send()) {
              $data['userId'] = '0';
              $data['text'] = 'Unable To Send OTP Email';
              $data['disc'] = 'Server SMTP Failed to send SMS';
              CreateErrorLog($data);
              unset($data);
            }            

            $msg = array('status' => 200, 'msg' => 'Success');
            return $this->response->setStatusCode(200)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

          }else{

            $msg = array('status' => 401, 'error' => 'Email Already Exist');
            return $this->response->setStatusCode(401)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

          }

        }
      
      }else{

        $msg = array('status' => 401, 'error' => 'Unauthorized');
        return Services::response()->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);

      }

    }


    //- Send Number OTP
    public function sendMobileOtp(): object
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;
      $mnumber = $this->request->getJsonVar('mnumber');

      if ($userId == $userData->id && !is_null($mnumber)) {

        $this->validation->setRule('mnumber', 'Number', 'required|min_length[10]|max_length[14]|regex_match[/^[0-9]*$/]');
        $data = array('mnumber' => $mnumber);

        if (!$this->validation->run($data)) {

          $msg = array();
          if ($this->validation->hasError('mnumber')) {
            $mnumber = $this->validation->getError('mnumber');
            array_push($msg, array('error' => $mnumber ));
          }

          $msg = array('status' => 403, 'msg' => $msg );

          return $this->response->setStatusCode(403)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

        }else{ 

          $userModel = new UserModel();
          
          $numberExist = $userModel->checkUniqueNumberExist($mnumber,$userData->id);
          if (count($numberExist) == 0) {

            $numbers = [];
            for ($i = 0; $i < 4; $i++) {
              $numbers[] = random_int(0, 9);
            }

            $mobOtp = implode('',$numbers);
            $userModel->updateNumberOtp($userData->id,$mobOtp);

            //- Send OTP to mobile
            try {
              // User details array
              $userDetails = array(
                "Text" => $mobOtp . " is your OTP verification code to login into Player6. Enjoy playing - Spegetti Sports.",
                "Number" => "91" . $mnumber,
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

            if ($responce != '200') {
              $data['userId'] = '0';
              $data['text'] = 'Unable To Send OTP';
              $data['disc'] = $error;
              CreateErrorLog($data);
              unset($data);
            }            



            $msg = array('status' => 200, 'msg' => 'Success');
            return $this->response->setStatusCode(200)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

          }else{

            $msg = array('status' => 401, 'error' => 'Mobile Number Already Exist');
            return $this->response->setStatusCode(401)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

          }

        }
      
      }else{

        $msg = array('status' => 401, 'error' => 'Unauthorized');
        return Services::response()->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);

      }

    }


    //- Update User Email
    public function updateEmail(): object
    {
      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;
      
      $otp = $this->request->getJsonVar('otp');
      $email = $this->request->getJsonVar('email');

      if ($userId == $userData->id && !is_null($email) && !is_null($otp)) {

        $this->validation->setRule('otp', 'OTP', 'required|min_length[4]|max_length[4]|regex_match[/^[0-9]*$/]');
        $this->validation->setRule('email', 'Email', 'required|max_length[100]|email_check', array('email_check' => 'Enter a valid email' ));
        $data = array('otp' => $otp, 'email' => $email);

        if (!$this->validation->run($data)) {

         $msg = array();
          if ($this->validation->hasError('otp')) {
            $otp = $this->validation->getError('otp');
            array_push($msg, array('error' => $otp ));
          }

          if ($this->validation->hasError('email')) {
            $email = $this->validation->getError('email');
            array_push($msg, array('error' => $email ));
          }

          $msg = array('status' => 403, 'msg' => $msg );

          return $this->response->setStatusCode(403)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

        }else{ 

          $userModel = new UserModel();
          $userDetails = $userModel->getUserById($userData->id);

          if ($userDetails[0]->emailOTP == $otp) {

            //- Update Email
            $userModel->updateEmail($userData->id,$email);

            $msg = array('status' => 200, 'msg' => 'Your email has been successfully verified.'); 
            return $this->response->setStatusCode(200)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

          }else{

            $msg = array('status' => 403, 'error' => 'Invalid OTP' );

            return $this->response->setStatusCode(403)
                        ->setHeader('Access-Control-Allow-Origin', '*')
                        ->setHeader('Access-Control-Allow-Headers', 'Origin')
                        ->setContentType('application/json', 'utf-8')
                        ->setJSON($msg);
          }

        }

      }else{

        $msg = array('status' => 401, 'error' => 'Unauthorized');
        return Services::response()->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);

      }

    }    


    //- Update User Mobile
    public function updateMobile(): object
    {
      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;
      
      $otp = $this->request->getJsonVar('otp');
      $mnumber = $this->request->getJsonVar('mnumber');

      if ($userId == $userData->id && !is_null($mnumber) && !is_null($otp)) {

        $this->validation->setRule('otp', 'OTP', 'required|min_length[4]|max_length[4]|regex_match[/^[0-9]*$/]');
        $this->validation->setRule('mnumber', 'Number', 'required|min_length[10]|max_length[14]|regex_match[/^[0-9]*$/]');
        $data = array('otp' => $otp, 'mnumber' => $mnumber);

        if (!$this->validation->run($data)) {

         $msg = array();
          if ($this->validation->hasError('otp')) {
            $otp = $this->validation->getError('otp');
            array_push($msg, array('error' => $otp ));
          }

          if ($this->validation->hasError('mnumber')) {
            $mnumber = $this->validation->getError('mnumber');
            array_push($msg, array('error' => $mnumber ));
          }

          $msg = array('status' => 403, 'msg' => $msg );

          return $this->response->setStatusCode(403)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

        }else{ 

          $userModel = new UserModel();
          $userDetails = $userModel->getUserById($userData->id);

          if ($userDetails[0]->mobileOTP == $otp) {

            //- Update Number
            $userModel->updateNumber($userData->id,$mnumber);

            $msg = array('status' => 200, 'msg' => 'Your mobile no has been successfully verified.'); 
            return $this->response->setStatusCode(200)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

          }else{

            $msg = array('status' => 403, 'error' => 'Invalid OTP' );

            return $this->response->setStatusCode(403)
                        ->setHeader('Access-Control-Allow-Origin', '*')
                        ->setHeader('Access-Control-Allow-Headers', 'Origin')
                        ->setContentType('application/json', 'utf-8')
                        ->setJSON($msg);
          }

        }

      }else{

        $msg = array('status' => 401, 'error' => 'Unauthorized');
        return Services::response()->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);

      }

    }    


    //- Send Email OTP
    public function sendEmailVerifyOtp(): object
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;
      
      if ($userId == $userData->id ) {

        $userModel = new UserModel();
        $userDetails = $userModel->getUserById($userData->id);

        if (count($userDetails) > 0) {

          $numbers = [];
          for ($i = 0; $i < 4; $i++) {
            $numbers[] = random_int(0, 9);
          }

          $emailOtp = implode('',$numbers);

          $userModel->updateEmailOtp($userData->id,$emailOtp);

          //- Send OTP to Email
          $email = Services::email();

          $email->clear();
          $email->setFrom(getenv('emailFrom'), getenv('emailDomain'));
          $email->setTo($userDetails[0]->email);
          
          $email->setSubject('player6 email verification OTP');
          $email->setMessage('OTP for email verification '.$emailOtp);

          if (!$email->send()) {
            $data['userId'] = '0';
            $data['text'] = 'Unable To Send OTP Email';
            $data['disc'] = 'Server SMTP Failed to send SMS';
            CreateErrorLog($data);
            unset($data);
          }            

          $msg = array('status' => 200, 'msg' => 'Success');
          return $this->response->setStatusCode(200)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);

        }else{

          $msg = array('status' => 401, 'error' => 'Unauthorized');
          return Services::response()->setStatusCode(401)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

        }
      
      }else{

        $msg = array('status' => 401, 'error' => 'Unauthorized');
        return Services::response()->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);

      }

    }


    //- Get Profile
    public function GetProfile(): object
    { 

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;
      if ($userId == $userData->id) {

        $userModel = new UserModel();
        $userDetails = $userModel->getUserById($userData->id); 

        $msg = array('status' => 200, 'msg' => 'Success', 'data' => array('name' => $userDetails[0]->name, 'number' => $userDetails[0]->number, 'profileImg' => getenv('s3Url').getenv('s3Bucket').'/'.getenv('userProf').$userDetails[0]->profileImg, 'email' => $userDetails[0]->email) );
        return $this->response->setStatusCode(200)
                ->setHeader('Access-Control-Allow-Origin', '*')
                ->setHeader('Access-Control-Allow-Headers', 'Origin')
                ->setContentType('application/json', 'utf-8')
                ->setJSON($msg);
      
      }else{
        $msg = array('status' => 401, 'error' => 'Unauthorized');
        return Services::response()->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);
      }      

    }


    //- Get Location From Lat, Long, IpGeo 
    public function allowedLocation(): object
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;

      if ($userId == $userData->id) {
        
        $lat = $this->request->getJsonVar('lat');
        $lng = $this->request->getJsonVar('lng');
        $page = $this->request->getJsonVar('page');
        
        $restStats = getenv('restStats');
        $restStats = explode(',', $restStats);

        if (!empty($lat) && !empty($lng)) {
          
          try {

            $curl = \Config\Services::curlrequest();
            $response = $curl->request('get', 'https://maps.googleapis.com/maps/api/geocode/json?latlng='.$lat.','.$lng.'&sensor=true&key='.getenv('gmapKey'));
            $responce = $response->getStatusCode();
            $error = '';

          } catch (\Exception $e) {
            
            $error = $e->getMessage();
            $responce = '500';

          } 

          $restArea = true;
          if ($responce == 200) {
            
            if (!empty($response->getBody())) {
              $response = json_decode($response->getBody());
              $results = $response->results;

              foreach ($restStats as $rkey => $rvalue) {
                if ($this->strContains($results[0]->formatted_address, $rvalue)) {
                  $restArea = false;
                  break;
                }
              }

            }

          }else{

            $ip = $this->request->getIPAddress();
            
            try {

              $curl = \Config\Services::curlrequest();
              $response = $curl->request('get', 'http://apiip.net/api/check?ip='.$ip.'&accessKey='.getenv('apiipKey') );
              $responce = $response->getStatusCode();
              $error = '';

            } catch (\Exception $e) {
              echo $error = $e->getMessage();
              $responce = '500';
            }

            if ($responce == 200) {
              if (!empty($response->getBody())) {

                $response = json_decode($response->getBody());
                if (in_array($response->regionName, $restStats)) {
                  $restArea = false;
                }

              }
            }
              
          }            

          //- Update Log In DB 
          $data['ip'] = $this->request->getIPAddress();
          $data['lat'] = $lat;
          $data['lng'] = $lng;
          $data['userId'] = $userData->id;
          if ($restArea) {
            $data['status'] = '1';
          }else{
            $data['status'] = '2';
          }
          $data['page'] = $page;

          //- Log Location
          $userModel = new UserModel();
          $userModel->logUserLocations($data);

          $userDetails = $userModel->getUserById($userId);

          $userWalletModel = new UserWalletModel();
          $credits = $userWalletModel->getTotalCredits($userId);
          $balTotal = 0;
          if (count($credits) > 0){
            $balTotal = floatval($credits[0]->credit) - floatval($credits[0]->debit);
            $balTotal = sprintf('%0.2f', $balTotal);
          } 

          $msg = array('status' => 200, 'msg' => 'Success', 'locVerify' => $restArea, 'aadhaarVerify' => $userDetails[0]->aadhaarVerify, 'panVerify' => $userDetails[0]->panVerify, 'email' => $userDetails[0]->emailVerify, 'number' => $userDetails[0]->mobileVerify, 'walletBal' => $balTotal);
          
          return $this->response->setStatusCode(200)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);

        }else{

          $msg = array('status' => 401, 'error' => 'Unauthorized');
          return Services::response()->setStatusCode(401)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);
        }

      }else{
            
        $msg = array('status' => 401, 'error' => 'Unauthorized');
        return Services::response()->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);

      }

    }


    //- App Main Page Banners
    public function appBanners()
    {
      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;

      if ($userId == $userData->id) {

        //- Log Location
        $userModel = new UserModel();
        $banners = $userModel->getBanners();

        if (count($banners) > 0) {
          foreach ($banners as $bakey => $bavalue) {
            $bavalue->name = getenv('s3Url').getenv('s3Bucket').'/'.getenv('appBann').$bavalue->name;
          }
        }

        $msg = array('status' => 200, 'msg' => 'Success', 'msg' => $banners);

        return $this->response->setStatusCode(200)
                ->setHeader('Access-Control-Allow-Origin', '*')
                ->setHeader('Access-Control-Allow-Headers', 'Origin')
                ->setContentType('application/json', 'utf-8')
                ->setJSON($msg);

      }else{
            
        $msg = array('status' => 401, 'error' => 'Unauthorized');
        return Services::response()->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);

      }      

    }


    //- Aadhaar Send OTP
    public function sendAadhaarOtp()
    {
      
      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;
      
      $aadhaarno = $this->request->getJsonVar('aadhaarno');

      if ($userId == $userData->id && !is_null($aadhaarno)) {


        $userModel = new UserModel();
        $userDetails = $userModel->getUserById($userId);

        if ($userDetails[0]->aadhaarVerify == 0) {

          $this->validation->setRule('aadhaarno', 'Aadhaar No', 'required|min_length[12]|max_length[12]');
          $data = array('aadhaarno' => $aadhaarno);

          if (!$this->validation->run($data)) {

            $msg = array();
            if ($this->validation->hasError('aadhaarno')) {
              $aadhaarno = $this->validation->getError('aadhaarno');
              array_push($msg, array('status' => 403,'error' => $aadhaarno ));
            }

            return $this->response->setStatusCode(403)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

          }else{

            //- Send OTP to mobile
            $curl = \Config\Services::curlrequest();

            try {
              $response = $curl->request('POST', getenv('idcengotpUrl'), [
                  'headers' => ['accept' => 'application/json', 'api-key' => getenv('idcentralKey')],
                  'json' => ['aadhaar_number' => $aadhaarno]
              ]);

              $responce = $response->getStatusCode();
              $error = '';
            } catch (\Exception $e) {
              $error = $e->getMessage();
              $responce = '500';
            }

            if ($responce == 200) {

              if (!empty($response->getBody())) {

                $responce = json_decode($response->getBody());

                if ($responce->status_code == 200 && $responce->response_code == 1) {

                  $msg = array('status' => 200, 'msg' => array('clientId' => $responce->data->client_id, 'aadhaarno' => $aadhaarno) );
                  return $this->response->setStatusCode(200)
                          ->setHeader('Access-Control-Allow-Origin', '*')
                          ->setHeader('Access-Control-Allow-Headers', 'Origin')
                          ->setContentType('application/json', 'utf-8')
                          ->setJSON($msg);

                }else{
      
                  $msg = array('status' => 203, 'error' => $responce->message);
                  return Services::response()->setStatusCode(203)
                            ->setHeader('Access-Control-Allow-Origin', '*')
                            ->setHeader('Access-Control-Allow-Headers', 'Origin')
                            ->setContentType('application/json', 'utf-8')
                            ->setJSON($msg);

                }

              }

            }else{

              $data['userId'] = '0';
              $data['text'] = 'KYC Aadhaar API Error';
              $data['disc'] = $error;
              CreateErrorLog($data);
              unset($data);

              $msg = array('status' => 203, 'error' => 'Unable to process your request please try again later');
              return Services::response()->setStatusCode(203)
                        ->setHeader('Access-Control-Allow-Origin', '*')
                        ->setHeader('Access-Control-Allow-Headers', 'Origin')
                        ->setContentType('application/json', 'utf-8')
                        ->setJSON($msg);

            }

          }

        }else{

          if ($userDetails[0]->aadhaarVerify == 1) {

            $msg = array('status' => 203, 'error' => 'Your Aadhaar card has already been confirmed');
            return Services::response()->setStatusCode(203)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

          }else if ($userDetails[0]->aadhaarVerify == 2) {

            $msg = array('status' => 203, 'error' => 'Your Aadhaar card is rejected');
            return Services::response()->setStatusCode(203)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);
 
          }

        }

      }else{
            
        $msg = array('status' => 401, 'error' => 'Unauthorized');
        return Services::response()->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);

      }        

    }


    //- Aadhaar Verify OTP
    public function verifyAadhaarOtp()
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;
      
      $aadhaarno = $this->request->getJsonVar('aadhaarno');
      $aadharClientId = $this->request->getJsonVar('aadharClientId');
      $aadharOtp = $this->request->getJsonVar('aadharOtp');

      if ($userId == $userData->id && !is_null($aadhaarno) && !is_null($aadharClientId) && !is_null($aadharOtp)) {

        $this->validation->setRule('aadhaarno', 'Aadhaar No', 'required|min_length[12]|max_length[12]');
        $this->validation->setRule('aadharClientId', 'Aadhaar Client Id', 'required');
        $this->validation->setRule('aadharOtp', 'Aadhaar OTP', 'required');
        $data = array('aadhaarno' => $aadhaarno, 'aadharClientId' => $aadharClientId, 'aadharOtp' => $aadharOtp);

        if (!$this->validation->run($data)) {
          
          $msg = array();
          if ($this->validation->hasError('aadhaarno')) {
            $aadhaarno = $this->validation->getError('aadhaarno');
            array_push($msg, array('aadhaarno' => $aadhaarno ));
          }

          if ($this->validation->hasError('aadharClientId')) {
            $aadharClientId = $this->validation->getError('aadharClientId');
            array_push($msg, array('aadharClientId' => $aadharClientId ));
          }

          if ($this->validation->hasError('aadharOtp')) {
            $aadharOtp = $this->validation->getError('aadharOtp');
            array_push($msg, array('aadharOtp' => $aadharOtp ));
          }

          $msg = array('status' => 403, 'error' => $msg);
          return $this->response->setStatusCode(403)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

        }else{


          //- Send OTP to mobile
          $curl = \Config\Services::curlrequest();

          try {
            $response = $curl->request('POST', getenv('idcensotpUrl'), [
                'headers' => ['accept' => 'application/json', 'api-key' => getenv('idcentralKey'), 'Content-Type' => 'application/json'],
                'json' => ['client_id' => $aadharClientId, 'otp' => $aadharOtp]
            ]);

            $responce = $response->getStatusCode();
            $error = '';
          } catch (\Exception $e) {
            $error = $e->getMessage();
            $responce = '500';
          }

          if ($responce == 200) {

            if (!empty($response->getBody())) {

              $responce = json_decode($response->getBody());

              if ($responce->status_code == 200 && $responce->response_code == 1) {

                $aadAddr = '';
                if (!empty($responce->data->address->landmark)) {
                  $aadAddr = $aadAddr.$responce->data->address->landmark.' ';
                }

                if (!empty($responce->data->address->house)) {
                  $aadAddr = $aadAddr.$responce->data->address->house.' ';
                }

                if (!empty($responce->data->address->street)) {
                  $aadAddr = $aadAddr.$responce->data->address->street.' ';
                }

                if (!empty($responce->data->address->subdist)) {
                  $aadAddr = $aadAddr.$responce->data->address->subdist.' ';
                }

                if (!empty($responce->data->address->vtc)) {
                  $aadAddr = $aadAddr.$responce->data->address->vtc.' ';
                }

                if (!empty($responce->data->address->loc)) {
                  $aadAddr = $aadAddr.$responce->data->address->loc.' ';
                }

                if (!empty($responce->data->address->po)) {
                  $aadAddr = $aadAddr.$responce->data->address->po.' ';
                }

                if (!empty($responce->data->address->dist)) {
                  $aadAddr = $aadAddr.$responce->data->address->dist.' ';
                }

                if (!empty($responce->data->address->state)) {
                  $aadAddr = $aadAddr.$responce->data->address->state.' ';
                } 

                if (!empty($responce->data->address->country)) {
                  $aadAddr = $aadAddr.$responce->data->address->country.' ';
                } 

                $kycModel = new UserKYCModel();

                //- Update KYC Details
                $data['userId'] = $userData->id;
                $data['aadFullName'] = $responce->data->full_name;
                $data['aadNumber'] = $responce->data->aadhaar_number;
                $data['aaddob'] = $responce->data->dob;
                $data['aadGen'] = $responce->data->gender;
                $data['aadZip'] = $responce->data->zip;
                $data['aadState'] = $responce->data->address->state;
                $data['aadAddr'] = $aadAddr;
                $data['dateCreated'] = gmdate("Y-m-d H:i:s");
                $data['dateUpdated'] = gmdate("Y-m-d H:i:s");

                $aaData = $kycModel->checkAadharExist($data);
 
                if (count($aaData) == 0) {
        
                  $kycModel->createUserKyc($data);

                  $restStats = getenv('restStats');
                  $restStats = explode(',', $restStats);

                  $restArea = true;
                  foreach ($restStats as $rkey => $rvalue) {
                    if ($this->strContains($aadAddr, $rvalue)) {
                      $restArea = false;
                      break;
                    }
                  }

                  //- Update Status
                  $userModel = new UserModel();
                  if ($restArea) {  

                    $userModel->updateAadharVerify($userData->id);

                    //- Bonus 30 Rs  
                    $userWalletModel = new UserWalletModel();      
                    $tmpWall['userId'] = $userData->id;
                    $tmpWall['amount'] = 30;
                    $tmpWall['text'] = 'Bonus added';
                    $tmpWall['status'] = '10';
                    $userWalletModel->creditWallet($tmpWall);
                    unset($tmpWall);

                    $userWallDtl = $userWalletModel->getTotalCredits($userData->id);
                    $tmpAmount = 0;
                    if (count($userWallDtl) > 0) {
                      $tmpAmount = floatval($userWallDtl[0]->credit) + floatval($userWallDtl[0]->debit);
                    }                    

                  }else{
                    $userModel->rejectAadharVerify($userData->id);
                  }

                  if ($restArea) {

                    $msg = array('status' => 200, 'msg' => array('status' => $restArea, 'msg' => 'Your Aadhar has been successfully verified.', 'walletAmount' => $tmpAmount ) );
                    return $this->response->setStatusCode(200)
                            ->setHeader('Access-Control-Allow-Origin', '*')
                            ->setHeader('Access-Control-Allow-Headers', 'Origin')
                            ->setContentType('application/json', 'utf-8')
                            ->setJSON($msg);

                  }else{

                    $msg = array('status' => 203, 'error' => 'Sorry :( We cant allow you to proceed further as you are a resident of a restricted state');
                    return Services::response()->setStatusCode(203)
                            ->setHeader('Access-Control-Allow-Origin', '*')
                            ->setHeader('Access-Control-Allow-Headers', 'Origin')
                            ->setContentType('application/json', 'utf-8')
                            ->setJSON($msg);

                  }
                
                }else{

                  $msg = array('status' => 203, 'error' => 'Your Aadhaar Card is already in use on another account');
                  return Services::response()->setStatusCode(203)
                            ->setHeader('Access-Control-Allow-Origin', '*')
                            ->setHeader('Access-Control-Allow-Headers', 'Origin')
                            ->setContentType('application/json', 'utf-8')
                            ->setJSON($msg);

                }

              }else{

                $msg = array('status' => 203, 'error' => $responce->error);
                return Services::response()->setStatusCode(203)
                          ->setHeader('Access-Control-Allow-Origin', '*')
                          ->setHeader('Access-Control-Allow-Headers', 'Origin')
                          ->setContentType('application/json', 'utf-8')
                          ->setJSON($msg);

              }

            }

          }else{

            $data['userId'] = '0';
            $data['text'] = 'KYC Aadhaar OTP API Error';
            $data['disc'] = $error;
            CreateErrorLog($data);
            unset($data);

            $msg = array('status' => 203, 'error' => 'Unable to process your request please try again later');
            return Services::response()->setStatusCode(203)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

          }

        }

      }else{
            
        $msg = array('status' => 401, 'error' => 'Unauthorized');
        return Services::response()->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);

      }  

    }


    //- Verify Pan
    public function verifyPan()
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;
      
      $pan = $this->request->getJsonVar('panno');
      $dob = $this->request->getJsonVar('dob');
      $fullName = $this->request->getJsonVar('fullName');

      if ($userId == $userData->id && !is_null($pan) && !is_null($dob) && !is_null($fullName)) {

        $userModel = new UserModel();
        $userDetails = $userModel->getUserById($userId);

        if ($userDetails[0]->aadhaarVerify == 1 && $userDetails[0]->panVerify == 0) {
        
          $this->validation->setRule('pan', 'PAN No', 'required|min_length[10]|max_length[10]');
          $this->validation->setRule('dob', 'Date Of Birth', 'required');
          $this->validation->setRule('fullName', 'Full Name', 'required');

          $data = array('pan' => $pan, 'dob' => $dob, 'fullName' => $fullName);

          if (!$this->validation->run($data)) {
            
            $msg = array();
            if ($this->validation->hasError('pan')) {
              $pan = $this->validation->getError('pan');
              array_push($msg, array('pan' => $pan ));
            }

            if ($this->validation->hasError('dob')) {
              $pan = $this->validation->getError('dob');
              array_push($msg, array('dob' => $dob ));
            }

            if ($this->validation->hasError('fullName')) {
              $pan = $this->validation->getError('fullName');
              array_push($msg, array('fullName' => $fullName ));
            }

            $msg = array('status' => 403, 'error' => $msg);
            return $this->response->setStatusCode(403)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

          }else{

            $curl = \Config\Services::curlrequest();
            try {

              $response = $curl->request('POST', getenv('idcenPanUrl'), [
                  'headers' => ['api-key' => getenv('idcentralKey'),'Content-Type' => 'application/json'],
                  'json' => ['id_number' => $pan, 'dob' => $dob, 'full_name' => $fullName]
              ]);

              $responce = $response->getStatusCode();
              $error = '';

            } catch (\Exception $e) {

              $error = $e->getMessage();
              $responce = '500';

            }

            if ($responce == 200) {

              if (!empty($response->getBody())) {
                
                $responce = json_decode($response->getBody());
                if ($responce->status_code == 200 && $responce->response_code == 1) {

                  
                  $kycModel = new UserKYCModel();

                  //- Update KYC Details
                  $data['panNumber'] = $responce->data->number;
                  $data['userId'] = $userData->id;
                  
                  if ($responce->data->name == 'MATCHING') {
                    $data['panFullName'] = $fullName;
                  }
                  
                  if ($responce->data->date_of_birth == 'MATCHING') {
                    $data['panDob'] = $dob;
                  }else{
                    $data['panDob'] = '-1';
                  }
                  
                  $data['dateCreated'] = gmdate("Y-m-d H:i:s");
                  $data['dateUpdated'] = gmdate("Y-m-d H:i:s");

                  //- Update Status
                  $kycDetails = $kycModel->getUserKyc($userData->id);
                  $status = false;

                  if ($kycDetails[0]->aadrDob == $data['panDob']) {
                    $kycModel->updateUserPan($userData->id,$data);
                    $userModel->updatePanVerify($userData->id);
                    $status = true;
                  }else{
                    $userModel->updatePanReject($userData->id);
                    $status = false;
                  }


                  if ($status) {
                    
                    $msg = array('status' => 200, 'msg' => array('status' => $status, 'msg' => 'Your Pan card has been successfully verified.' ) );
                    return $this->response->setStatusCode(200)
                            ->setHeader('Access-Control-Allow-Origin', '*')
                            ->setHeader('Access-Control-Allow-Headers', 'Origin')
                            ->setContentType('application/json', 'utf-8')
                            ->setJSON($msg);
                  }else{

                    $msg = array('status' => 203, 'error' => 'PAN Card details do not match or incorrect.');
                    return Services::response()->setStatusCode(203)
                              ->setHeader('Access-Control-Allow-Origin', '*')
                              ->setHeader('Access-Control-Allow-Headers', 'Origin')
                              ->setContentType('application/json', 'utf-8')
                              ->setJSON($msg);
                  }
 
                }else{

                  $msg = array('status' => 203, 'error' => $responce->message);
                  return $this->response->setStatusCode(200)
                          ->setHeader('Access-Control-Allow-Origin', '*')
                          ->setHeader('Access-Control-Allow-Headers', 'Origin')
                          ->setContentType('application/json', 'utf-8')
                          ->setJSON($msg);

                }

              }

            }else{

              $data['userId'] = '0';
              $data['text'] = 'KYC PAN API Error';
              $data['disc'] = $error;
              CreateErrorLog($data);
              unset($data);

              $msg = array('status' => 203, 'error' => 'Unable to process your request please try again later');
              return Services::response()->setStatusCode(203)
                        ->setHeader('Access-Control-Allow-Origin', '*')
                        ->setHeader('Access-Control-Allow-Headers', 'Origin')
                        ->setContentType('application/json', 'utf-8')
                        ->setJSON($msg);

            }
          
          }

        }else{

          if ($userDetails[0]->aadhaarVerify != 1) {

            $msg = array('status' => 203, 'error' => 'Please verify your Aadhaar before PAN Card verification');
            return Services::response()->setStatusCode(203)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

          }else if ($userDetails[0]->panVerify == 1) {

            $msg = array('status' => 203, 'error' => 'Your Pan Card has already been verified');
            return Services::response()->setStatusCode(203)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

          }else if ($userDetails[0]->panVerify == 2) {
            
            $msg = array('status' => 203, 'error' => 'PAN Card details do not match or incorrect.');
            return Services::response()->setStatusCode(203)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

          }

        }

      }else{
            
        $msg = array('status' => 401, 'error' => 'Unauthorized1');
        return Services::response()->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);

      }
    }


    //- Verify Bank Account
    public function bankAccount()
    {

      $userModel = new UserModel();
      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;
      
      $accNumber = $this->request->getJsonVar('accNumber');
      $ifsc = $this->request->getJsonVar('ifsc');
      $name = $this->request->getJsonVar('name');

      if ($userId == $userData->id && !is_null($accNumber) && !is_null($ifsc) && !is_null($name)) {

        $this->validation->setRule('accNumber', 'Bank account no', 'required|min_length[5]|max_length[35]');
        $this->validation->setRule('ifsc', 'IFSC', 'required|min_length[11]|max_length[11]');
        $this->validation->setRule('name', 'name', 'required|min_length[3]|max_length[40]');

        $data = array('accNumber' => $accNumber, 'ifsc' => $ifsc, 'name' => $name);

        $kycModel = new UserKYCModel();
        $kycDetails = $kycModel->getUserKyc($userId);

        //-  Check Account Exist 
        $allAccounts =  getUserAccounts($userId);
        $oldAccolunt = false;
        if (count($allAccounts) > 0) {
          foreach ($allAccounts as $aakey => $aavalue) {
            if ($aavalue == $accNumber) {
              $oldAccolunt = true;
            }
          }
        }

        if (!$this->validation->run($data) || count($kycDetails) == 0 || $oldAccolunt) {

          $msg = array();
          if ($this->validation->hasError('accNumber')) {
            $accNumber = $this->validation->getError('accNumber');
            array_push($msg, array('accNumber' => $accNumber ));
          }

          if ($this->validation->hasError('ifsc')) {
            $ifsc = $this->validation->getError('ifsc');
            array_push($msg, array('ifsc' => $ifsc ));
          }

          if ($this->validation->hasError('name')) {
            $name = $this->validation->getError('name');
            array_push($msg, array('name' => $name ));
          }

          if (count($kycDetails) == 0) {
            array_push($msg, array('kyc' => 'KYC has not been verified please verify to continue' ));
          }

          if ($oldAccolunt) {
            array_push($msg, array('duplicate' => 'Bank account already exists' ));
          }

          $msg = array('status' => 203, 'error' => $msg);
          return $this->response->setStatusCode(203)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

        }else{

          //- Send OTP to mobile
          $curl = \Config\Services::curlrequest();

          try {
            
            $response = $curl->request('POST', getenv('idcenBankUrl'), [
                'headers' => ['accept' => 'application/json', 'api-key' => getenv('idcentralKey'), 'Content-Type' => 'application/json'],
                'json' => ['account_number' => $accNumber, 'ifsc' => $ifsc, 'extended_data' => true]
            ]);

            $responce = $response->getStatusCode();
            $error = '';

          } catch (\Exception $e) {
          
            $error = $e->getMessage();
            $responce = '500';
          
          }

          if ($responce == 200) {

            if (!empty($response->getBody())) {
              
              $responce = json_decode($response->getBody());

              if ($responce->status_code == 200 && $responce->response_code == 1) {
                
                $bankVerify = false;
                $names = explode(' ', $responce->data->full_name);

                if (count($names) > 0) {
                  foreach ($names as $nkey => $nvalue) {

                    if (!empty($nvalue)) {
                      if (strpos(strtolower($kycDetails[0]->aadrFullName),strtolower($nvalue)) !== false) {
                        $bankVerify = true;
                      }
                    }

                  }
                } 
               
                if ($bankVerify) {

                  //- Update Details On Razor Pay
                  $userDetails = $userModel->getUserById($userId);
              
                  //- Add Contact
                  $rzCustomerId = '';
                  if (count($userDetails) > 0 && empty($userDetails[0]->rzpayConId)) {
                    
                    try {
                      
                      $userDetails = array('name' => $userDetails[0]->name,  'email' => $userDetails[0]->email, 'contact' => $userDetails[0]->number, 'type' => 'customer', 'reference_id' => $userDetails[0]->id );

                      $rzcresponse = $curl->request('POST', getenv('RazorPayContacts'),[
                        'auth' => [getenv('RazorPayKey'), getenv('RazorPaySecret')],
                        'headers' => ['Content-Type' => 'application/json'],
                        'form_params' => $userDetails
                      ]);

                      $rzcresponce = $rzcresponse->getStatusCode();
                      $error = '';

                    } catch (\Exception $e) {
                      
                      $error = $e->getMessage();
                      $rzcresponce = '500';
                      
                    }

                    if ($rzcresponce == 200 || $rzcresponce == 201) {
                      
                      if (!empty($rzcresponse->getBody())) {

                        $rzcresponce = json_decode($rzcresponse->getBody());
                        $userModel->updateRZPayCustomerId($userId,$rzcresponce->id);
                        $rzCustomerId = $rzcresponce->id;

                      }

                    }else{
                    
                      $ldata['userId'] = '0';
                      $ldata['text'] = 'RazorPay Contacts API Error';
                      $ldata['disc'] = $error;
                      CreateErrorLog($ldata);
                      unset($ldata);
                    
                    }

                  }else{
                    $rzCustomerId = $userDetails[0]->rzpayConId;
                  }

                  //- Add Fund Account
                  if (!empty($rzCustomerId)) {
                    
                    try {

                      $bankDetails = array('contact_id' => $rzCustomerId,  'account_type' => 'bank_account','bank_account' => array('name' => $name, 'ifsc' => $ifsc, 'account_number' => $accNumber) );

                      $rzbresponse = $curl->request('POST', getenv('RazorPayFundAcc'),[
                        'auth' => [getenv('RazorPayKey'), getenv('RazorPaySecret')],
                        'headers' => ['Content-Type' => 'application/json'],
                        'form_params' => $bankDetails
                      ]);
                      $rzresponce = $rzbresponse->getStatusCode();
                      $error = '';
                    
                    } catch (\Exception $e) {

                      $error = $e->getMessage();
                      $rzresponce = '500';

                    }

                    if ($rzresponce == 200 || $rzresponce == 201) {
                      
                      if (!empty($rzbresponse->getBody())) {

                        $rzbresponse = json_decode($rzbresponse->getBody());
                       
                        //- Create Bank Account
                        $userModel = new UserModel();
                        $allAccounts = $userModel->getAllUserBankAccount($userId);

                        //- Update Bank Account
                        $data['userId'] = $userId;
                        $data['name'] = $name;
                        $data['status'] = count($allAccounts) == 0 ? '1' : '0';
                        $data['fundAccountId'] = $rzbresponse->id;
                        $userModel->createUserBankAccount($data);

                        //- Update Verification Status
                        $userModel->updateBankVerified($userId);

                        $bankSuccess = true;

                      }

                    }else{
                    
                      $ldata['userId'] = '0';
                      $ldata['text'] = 'RazorPay Contacts Fund Acc API Error';
                      $ldata['disc'] = $error;
                      CreateErrorLog($ldata);
                      unset($ldata);
                    
                    }                    

                  }

              
                  if ($bankSuccess) {

                    $msg = array('status' => 200, 'msg' => 'Your bank account has been successfully verified.1');
                    return $this->response->setStatusCode(200)
                            ->setHeader('Access-Control-Allow-Origin', '*')
                            ->setHeader('Access-Control-Allow-Headers', 'Origin')
                            ->setContentType('application/json', 'utf-8')
                            ->setJSON($msg);

                  }else{

                    $msg = array('status' => 203, 'error' => 'Unable to verify bank account please try again later');
                    return Services::response()->setStatusCode(203)
                              ->setHeader('Access-Control-Allow-Origin', '*')
                              ->setHeader('Access-Control-Allow-Headers', 'Origin')
                              ->setContentType('application/json', 'utf-8')
                              ->setJSON($msg);

                  }         

                }else{

                  $msg = array('status' => 203, 'error' => 'Bank verification failed. Name on your Aadhaar('.$kycDetails[0]->aadrFullName.') and bank account('.$responce->data->full_name.') are not same');
                  return Services::response()->setStatusCode(203)
                            ->setHeader('Access-Control-Allow-Origin', '*')
                            ->setHeader('Access-Control-Allow-Headers', 'Origin')
                            ->setContentType('application/json', 'utf-8')
                            ->setJSON($msg);

                }

              }else{

                $msg = array('status' => 203, 'error' => $responce->message);
                return Services::response()->setStatusCode(203)
                          ->setHeader('Access-Control-Allow-Origin', '*')
                          ->setHeader('Access-Control-Allow-Headers', 'Origin')
                          ->setContentType('application/json', 'utf-8')
                          ->setJSON($msg);
              
              }

            }

          }else{

            $data['userId'] = '0';
            $data['text'] = 'Bank Verification API Error';
            $data['disc'] = $error;
            CreateErrorLog($data);
            unset($data);

            $msg = array('status' => 203, 'error' => 'Unable to verify bank account please try again later.');
            return Services::response()->setStatusCode(203)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

          }

        }


      }else{
            
        $msg = array('status' => 401, 'error' => 'Unauthorized');
        return Services::response()->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);

      }

    }


    //- Bank Accounts List
    public function getAllBankAccounts()
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;

      if ($userId == $userData->id) {

        $userModel = new UserModel();
        $allAccounts = $userModel->getAllUserBankAccount($userId);

        $account = array();
        if (count($allAccounts) > 0) {
          
          $curl = \Config\Services::curlrequest();
          foreach ($allAccounts as $key => $value) {
            
            try {

              $rzbresponse = $curl->request('GET', getenv('RazorPayFundAcc').'/'.$value->fcId,[
                'auth' => [getenv('RazorPayKey'), getenv('RazorPaySecret')]
              ]);
              $rzresponce = $rzbresponse->getStatusCode();
              $error = ''; 

            } catch (\Exception $e) {

              $error = $e->getMessage();
              var_dump($error);
              $rzresponce = '500';

            }

            if ($rzresponce == 200) {

              if (!empty($rzbresponse->getBody())) {
                
                $rzbresponse = json_decode($rzbresponse->getBody());
                
                $cardNumber = str_repeat('x', strlen($rzbresponse->bank_account->account_number) - 4) . substr($rzbresponse->bank_account->account_number, -4);

                array_push($account, (object)array('accNumber' => $cardNumber, 'ifsc' => $rzbresponse->bank_account->ifsc, 'name' => $rzbresponse->bank_account->name, 'bankName' => $rzbresponse->bank_account->bank_name, 'id' => $value->id, 'userId' =>  $value->userId, 'isPrimary' => $value->isPrimary ));

              }

            }else{
              
              $ldata['userId'] = '0';
              $ldata['text'] = 'RazorPay Get Contacts Fund Acc API Error';
              $ldata['disc'] = $error;
              CreateErrorLog($ldata);
              unset($ldata);
 
            }

          }

        }

        $msg = array('status' => 200, 'msg' => $account);
        return $this->response->setStatusCode(200)
                ->setHeader('Access-Control-Allow-Origin', '*')
                ->setHeader('Access-Control-Allow-Headers', 'Origin')
                ->setContentType('application/json', 'utf-8')
                ->setJSON($msg);

      }else{
            
        $msg = array('status' => 401, 'error' => 'Unauthorized');
        return Services::response()->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);

      }

    }


    //- Make Bank Primary 
    public function makeBankAccountsPrimary(){

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $bankId = $uri->getSegment(6);
      $userData = $this->request->decoded->userData;

      $tmpUserId = $this->request->getJsonVar('userId');
      $tmpBankId = $this->request->getJsonVar('bankId');

      if ($userId == $userData->id && !is_null($userId) && !is_null($bankId) && !is_null($tmpUserId) && !is_null($tmpBankId) &&  $userId == $tmpUserId && $bankId == $tmpBankId ) {

          $this->validation->setRule('userId', 'User Id', 'required');
          $this->validation->setRule('bankId', 'Bank Id', 'required');

          $data = array('userId' => $tmpUserId, 'bankId' => $tmpBankId);

          if (!$this->validation->run($data) ) {

            $msg = array();
            if ($this->validation->hasError('userId')) {
              $userId = $this->validation->getError('userId');
              array_push($msg, array('userId' => $userId ));
            }

            if ($this->validation->hasError('bankId')) {
              $bankId = $this->validation->getError('bankId');
              array_push($msg, array('bankId' => $bankId ));
            }

            $msg = array('status' => 203, 'error' => $msg);
            return $this->response->setStatusCode(203)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

          }else{

            //- Remove  Status
            $userModel = new UserModel();
            $userModel->removeBankPrimary($userId);

            //- Update Status
            $userModel->updateBankPrimary($userId,$bankId);

            $msg = array('status' => 200, 'msg' => 'Success');
            return $this->response->setStatusCode(200)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);       

          }  

      }else{

        $msg = array('status' => 401, 'error' => 'Unauthorized');
        return Services::response()->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);

      }
      
    }


    //- Verify KYC
    public function checkKYC()
    {
      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;
      
      if ($userId == $userData->id) {

        $userModel = new UserModel();
        $userDetails = $userModel->getUserById($userId);

        $msg = array('status' => 200, 'msg' => array('aadhaarVerify' => $userDetails[0]->aadhaarVerify, 'panVerify' => $userDetails[0]->panVerify, 'email' => $userDetails[0]->emailVerify, 'number' => $userDetails[0]->mobileVerify, 'bankVerify' => $userDetails[0]->bankVerify ) );
        return $this->response->setStatusCode(200)
                ->setHeader('Access-Control-Allow-Origin', '*')
                ->setHeader('Access-Control-Allow-Headers', 'Origin')
                ->setContentType('application/json', 'utf-8')
                ->setJSON($msg);

      }else{
            
        $msg = array('status' => 401, 'error' => 'Unauthorized');
        return Services::response()->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);

      }      
    }


    //- Update Device Token
    public function updateDeviceToken()
    {
      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;

      $token = $this->request->getJsonVar('token');
      
      if ($userId == $userData->id && !empty($token)) {

        $userModel = new UserModel();
        $userModel->updateDeviceToken($userId,$token);

        $msg = array('status' => 200, 'msg' => 'Success');
        return $this->response->setStatusCode(200)
                ->setHeader('Access-Control-Allow-Origin', '*')
                ->setHeader('Access-Control-Allow-Headers', 'Origin')
                ->setContentType('application/json', 'utf-8')
                ->setJSON($msg);        

      }else{
            
        $msg = array('status' => 401, 'error' => 'Unauthorized');
        return Services::response()->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);

      }

    }



    public function sendSupportRequest(){
      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;

      $message = $this->request->getJsonVar('message');
      if ($userId == $userData->id && !is_null($message)) {

        $this->validation->setRule('message', 'Message', 'required|min_length[5]|max_length[2000]');
        
        $data = array('message' => $message);
        if (!$this->validation->run($data)) {

          $msg = array();
          if ($this->validation->hasError('message')) {
            $message = $this->validation->getError('message');
            array_push($msg, array('error' => $message ));
          }

          $msg = array('status' => 403, 'msg' => $msg);
          return $this->response->setStatusCode(403)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);                  

        }else{

          $userModel = new UserModel();
          $userDetails = $userModel->getUserById($userId);

          //- Send OTP to Email
          $email = Services::email();

          $email->clear();
          $email->setFrom(getenv('emailFrom'), getenv('emailDomain'));
          $email->setTo('support@player6sports.com');
          //- $email->setTo('dinesh.xhtmlchamps@gmail.com');
          
          $email->setSubject('Support Ticket From '.$userDetails[0]->name);
          
          $sendMsg = '<div>';
            
            $sendMsg .= '<div>';
            $sendMsg .= '<p>Name : '.$userDetails[0]->name.'</p>';
            $sendMsg .= '</div>';
            
            $sendMsg .= '<div>';
            $sendMsg .= '<p>Email : '.$userDetails[0]->email.'</p>';
            $sendMsg .= '</div>';          
          
            $sendMsg .= '<div>';
            $sendMsg .= '<p>Number : '.$userDetails[0]->number.'</p>';
            $sendMsg .= '</div>';

            $sendMsg .= '<div>'; 
            $sendMsg .= '<p>Message : '.$message.'</p>';
            $sendMsg .= '</div>';

          $sendMsg .= '</div>';

          $email->setMessage($sendMsg);

          if ($email->send()) {
            
            $msg = array('status' => 200, 'msg' => 'Success');
            return $this->response->setStatusCode(200)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);        


          }else{

            $msg = array('status' => 203, 'msg' => 'Unable to send message please try again');
            return $this->response->setStatusCode(203)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);        

          }

        }

      }else{

        $msg = array('status' => 401, 'error' => 'Unauthorized');
        return Services::response()->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);

      }

    }


    //- Genarate Fack Toss
    public function genarateFackToss()
    {

      $crickmId = $this->request->getVar('crickmId');
      $matchId = $this->request->getVar('matchId');
      $teamaId = $this->request->getVar('teamaId');
      $teambId = $this->request->getVar('teambId');
      $winTeamId = $this->request->getVar('winTeamId');
      $teamaKey = $this->request->getVar('teamaKey');
      $teambKey = $this->request->getVar('teambKey');
      $winTeamKey = $this->request->getVar('winTeamKey');

      if (!is_null($crickmId) && !is_null($matchId) && !is_null($teamaId) && !is_null($teambId) && !is_null($winTeamId) && !is_null($teamaKey) && !is_null($teambKey) && !is_null($winTeamKey) ) {

        $data['eventName'] = 'tossDec';

        $msg = array('crickmId' => $crickmId, 'matchId' => $matchId, 'teamaId' => $teamaId, 'teambId' => $teambId, 'winTeamId' => $winTeamId, 'teamaKey' => $teamaKey, 'teambKey' => $teambKey, 'winTeamKey' => $winTeamKey);
        $data['eventData'] = json_encode($msg);
        CreateSselog($data);
        unset($data);
        echo "hi";
      }

    }


    //- Genarate Fack Notification
    public function genarateFackNotif()
    {

      /*
      $razorPay = new RazorpayLibrary();

      var_dump($razorPay);
      exit;
      $custRes = $razorPay->customer->create(array('name' => 'Razorpay User', 'email' => 'customer@razorpay.com','contact'=>'9123456780'));

      var_dump($custRes);
      */

      //- Send Win Notification
      /*$devToken = 'de2qLFcdQJSunsNiKib1Fm:APA91bGWSLd5WBubTl8raXwl9lYuHs73QFdRzT5UIekpfay6amuHXOQho4CJJYNautGTtAFRxvwHRSwm_5QGlzCfw_MTlD9CmQHNlOmXFjRq5OMGmZpv68s_-iHR07w51OxnMf1n3bPy';
      $notifi = array("title" => "Petsfolio", "body" => "Test one");
      $tmpBody = array('notification' => $notifi, 'to' => $devToken);
      $tmpBody = json_encode($tmpBody);

      sendPushNotifi($tmpBody);*/

    }


}

<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RequestInterface;
use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\UserWalletModel;
use App\Models\ContestModel;
use Config\Services;

class UserWallet extends BaseController{

  public function __construct(){
    helper('Common');
  }


  public function initiatePayment(){
    
    $uri = current_url(true);
    $userId = $uri->getSegment(4);
    $userData = $this->request->decoded->userData;

    $amount = $this->request->getJsonVar('amount');
    $lat = $this->request->getJsonVar('lat');
    $long = $this->request->getJsonVar('long');
    
    if ($userId == $userData->id && !is_null($amount) && !is_null($lat) && !is_null($long) ) {
      
      $this->validation->setRule('amount', 'amount', 'required|greater_than_equal_to[25]|less_than_equal_to[1000000]');

      $data = array('amount' => $amount);

      if (!$this->validation->run($data)) {

        $msg = array();
        if ($this->validation->hasError('amount')) {
          $amount = $this->validation->getError('amount');
          array_push($msg, array('amount' => $amount ));
        }


        $msg = array('status' => 403, 'error' => $msg);
        return $this->response->setStatusCode(403)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);

      }else{

        //- User Details 
        $userModel = new UserModel();
        $userWalletModel = new UserWalletModel();
        $userDetails = $userModel->getUserById($userId);

        if (!empty($userDetails[0]->number)) {
          $number = $userDetails[0]->number;
        }else{
          $number = '';
        }

        //- Create User Transaction
        $data['transId'] = strrev(uniqid());
        $data['userId'] = $userId;
        $data['amount'] = $amount;
        $data['lat'] = $lat;
        $data['long'] = $long;
        $userWalletModel->createTransac($data);

        //- Create Base64 Encoded Payload

        $base64Body = (object)array('merchantId' => getenv('PHPEMERCHANTID'), 'merchantTransactionId' => $data['transId'], 'merchantUserId'=>'pl6-u'.$userData->id, 'amount' => $amount, 'callbackUrl' => base_url('phonePeCallBack'), 'mobileNumber' => $number);

        $msg = array('status' => 200, 'msg' => 'Success', 'base64Body' => $base64Body);
        return $this->response->setStatusCode(200)
                ->setHeader('Access-Control-Allow-Origin', '*')
                ->setHeader('Access-Control-Allow-Headers', 'Origin')
                ->setContentType('application/json', 'utf-8')
                ->setJSON($msg);

      }

    }else{

      if (is_null($lat) || is_null($long)) {
        
        $msg = array('status' => 403, 'error' => 'No lat long');
        return Services::response()->setStatusCode(403)
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
      
  }


  public function WalletDetails(){

    $uri = current_url(true);
    $userId = $uri->getSegment(4);
    $userData = $this->request->decoded->userData;

    if ($userId == $userData->id) {

      $userWalletModel = new UserWalletModel();
      $userRecords = $userWalletModel->getWalletRecords($userId);

      //- Total Credit
      $credits = $userWalletModel->getTotalCredits($userId);

      $balTotal = 0;
      if (count($credits) > 0){
        $balTotal = floatval($credits[0]->credit) - floatval($credits[0]->debit);
        $balTotal = sprintf('%0.2f', $balTotal);
      }

      //- 
      $contModel = new ContestModel();
      $userAmount = $contModel->activeUserContests($userId);

      $inPlayBalen = 0;
      if (count($userAmount) > 0) {
        if (!is_null($userAmount[0]->contest_fee)) {
          $inPlayBalen = $userAmount[0]->contest_fee;
          $inPlayBalen = sprintf('%0.2f', $inPlayBalen);
        }
      }

      $withBalen = 0;
      if (floatval($balTotal) > 0 || floatval($inPlayBalen) > 0) {
        $withBalen = floatval($balTotal) + floatval($inPlayBalen);
        $withBalen = sprintf('%0.2f', $withBalen);
      }
 
      $array = array('userRecords' => $userRecords, 'totalBalen' => $withBalen, 'withBalen' => $balTotal, 'inPlayBalen' => $inPlayBalen );
      $msg = array('status' => 200, 'msg' => $array);
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


  public function getDetailsForTDS(){
    $uri = current_url(true);
    $userId = $uri->getSegment(4);
    $userData = $this->request->decoded->userData;

    if ($userId == $userData->id) {

      $userWalletModel = new UserWalletModel();

      $totCred = $userWalletModel->getTotalCredits($userId);
      $exesAmount = 0;
      if (count($totCred) > 0) {
        $ucredit = 0;
        $udebit = 0;

        if (!is_null($totCred[0]->credit)) {
          $ucredit = $totCred[0]->credit;
        }

        if (!is_null($totCred[0]->debit)) {
          $udebit = $totCred[0]->debit;
        }

        $exesAmount = $ucredit - $udebit;
        $exesAmount = round($exesAmount, 2);
        $exesAmount = floatval($exesAmount);
      }      

      //- Total Deposits
      $tmpDeposits = 0;
      $deposits = $userWalletModel->getTotalDeposits($userId);
      if (count($deposits) > 0) {
        if (!is_null($deposits[0]->credit)) {
          $tmpDeposits = $deposits[0]->credit;
        }else{
          $tmpDeposits = 0;
        }
      }

      //- Total Withdrawals
      $tmpWithDrawals = 0;
      $withDrawals = $userWalletModel->getTotalTDSWithdrawals($userId);
      if (count($withDrawals) > 0) {
        if (!is_null($withDrawals[0]->debit)) {
          $tmpWithDrawals = $withDrawals[0]->debit;
        }else{
          $tmpWithDrawals = 0;
        }
      }

      //- Total Tds
      $tmpOldTds = 0;
      $oldTDS = $userWalletModel->getTotalOldTDS($userId);
      if (count($oldTDS) > 0) {
        if (!is_null($oldTDS[0]->debit)) {
          $tmpOldTds = $oldTDS[0]->debit;
        }else{
          $tmpOldTds = 0;
        }
      }

      $array = array('deposits' => $tmpDeposits, 'withDrawals' => $tmpWithDrawals, 'maxWith' => $exesAmount, 'oldTds' => $tmpOldTds );
      $msg = array('status' => 200, 'msg' => $array);
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


  public function withdrawAmount(){
    $uri = current_url(true);
    $userId = $uri->getSegment(4);
    $userData = $this->request->decoded->userData;

    $amount = $this->request->getJsonVar('amount');
    $bankId = $this->request->getJsonVar('bankId');

    if ($userId == $userData->id && !is_null($amount) && !is_null($bankId)) {

      $userWalletModel = new UserWalletModel();

      $totCred = $userWalletModel->getTotalCredits($userId);
      $exesAmount = 0;
      if (count($totCred) > 0) {
        $ucredit = 0;
        $udebit = 0;

        if (!is_null($totCred[0]->credit)) {
          $ucredit = $totCred[0]->credit;
        }

        if (!is_null($totCred[0]->debit)) {
          $udebit = $totCred[0]->debit;
        }

        $exesAmount = $ucredit - $udebit;
      }

      if ($exesAmount > 0 && floatval($amount) <= floatval($exesAmount) && floatval($amount) >= 300 ) {

        //- Total Deposits
        $tmpDeposits = 0;
        $deposits = $userWalletModel->getTotalDeposits($userId);
        if (count($deposits) > 0) {
          if (!is_null($deposits[0]->credit)) {
            $tmpDeposits = $deposits[0]->credit;
          }else{
            $tmpDeposits = 0;
          }
        }

        //- Total Withdrawals
        $tmpWithDrawals = 0;
        $withDrawals = $userWalletModel->getTotalTDSWithdrawals($userId);
        if (count($withDrawals) > 0) {
          if (!is_null($withDrawals[0]->debit)) {
            $tmpWithDrawals = $withDrawals[0]->debit;
          }else{
            $tmpWithDrawals = 0;
          }
        }

        //- Total Tds
        $tmpOldTds = 0;
        $oldTDS = $userWalletModel->getTotalOldTDS($userId);
        if (count($oldTDS) > 0) {
          if (!is_null($oldTDS[0]->debit)) {
            $tmpOldTds = $oldTDS[0]->debit;
          }else{
            $tmpOldTds = 0;
          }
        }


        $tmpWithDrawals = floatval($tmpWithDrawals) + floatval($amount);

        //- Calculate TDS
        $tmpDiff = floatval($tmpWithDrawals) - floatval($tmpDeposits);
        $tmpTds = 0;
        if ($tmpDiff > 0) {
          $tmpTds = $tmpDiff * (30/100);
          $tmpTds = round($tmpTds, 2);
          $tmpTds = floatval($tmpTds);
        }

        if ($tmpTds > 0) {
          $tmpTds = floatval($tmpTds) - floatval($tmpOldTds);
          if ($tmpTds <= 0) {
            $tmpTds = 0;
          }
        }

        //- Final Withdraw Amount
        $finalWithAmount = 0;
        if ($tmpTds > 0) {
          $finalWithAmount = floatval($amount) - floatval($tmpTds);
          $finalWithAmount = round($finalWithAmount, 2);
          $finalWithAmount = intval($finalWithAmount);
        }else{
          $finalWithAmount = intval($amount);
        }

        $userModel = new UserModel();
        $userDetails = $userModel->getUserById($userId);
        $userBank = $userModel->getUserBankAccount($userId,$bankId);
        
        $tranSuccess = false;
        if (count($userDetails) > 0 && count($userBank) > 0 && $finalWithAmount > 0) {

          $curl = \Config\Services::curlrequest();

          $rzpayConId = $userDetails[0]->rzpayConId;
          $fundAccountId = $userBank[0]->fundAccountId;

          if (!empty($rzpayConId) && !empty($fundAccountId)) {

            //- Send Amount to user
            try {

              $rzpWithAmount = $finalWithAmount * 100;
              
              $userDetails =
               array("account_number" => getenv('RazorPayCustomerId'), "fund_account_id" => $fundAccountId, "amount" => intval($rzpWithAmount), "currency" => "INR", "mode" => "IMPS", "purpose" => "payout", "queue_if_low_balance" =>  true, "reference_id" => strval($userId), "narration" => "Player6");

              $response = $curl->request('POST', 'https://api.razorpay.com/v1/payouts',[
                'auth' => [getenv('RazorPayKey'), getenv('RazorPaySecret')],
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode($userDetails)
              ]);
              $responce = $response->getStatusCode();
              $error = '';

            } catch (\Exception $e) {
          
              $error = $e->getMessage();
              $responce = '500';
              
            }


            if ($responce == 200 || $responce == 201) {
 
              if (!empty($response->getBody())) {
                
                $responce = json_decode($response->getBody());
                
                //- Debit Wallet Amount
                $tmpWall['userId'] = $userId;
                $tmpWall['amount'] = $finalWithAmount;
                $tmpWall['payOutId'] = $responce->id;
                $tmpWall['text'] = 'Withdraw';
                $tmpWall['status'] = '7';
                $userWalletModel = new UserWalletModel();
                $userWalletModel->debitWallet($tmpWall);
                unset($tmpWall);

                //- TDS
                if ($tmpTds > 0) {
                  $tmpWall['userId'] = $userId;
                  $tmpWall['amount'] = $tmpTds;
                  $tmpWall['payOutId'] = $responce->id;
                  $tmpWall['text'] = 'Withdraw';
                  $tmpWall['status'] = '8';
                  $userWalletModel = new UserWalletModel();
                  $userWalletModel->debitWallet($tmpWall);
                  unset($tmpWall);              
                }

                $tranSuccess = true;

              }

            }else{
            
              $ldata['userId'] = '0';
              $ldata['text'] = 'RazorPay PayOut API Error';
              $ldata['disc'] = $error;
              CreateErrorLog($ldata);
              unset($ldata);
            
            }              
            
          }

        }

        if ($tranSuccess) {

          $msg = array('status' => 200, 'msg' => 'Success');
          return $this->response->setStatusCode(200)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);      

        }else{

          $msg = array('status' => 203, 'error' => 'You can not withdraw ₹'.$amount.' at this moment. Please try again later');
          return Services::response()->setStatusCode(203)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

        }


      }else{

        if (floatval($amount) < 300) {

          $msg = array('status' => 203, 'error' => 'Withdraw amount should be greater than are equal to ₹300');
          return Services::response()->setStatusCode(203)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);          

        }else {

          $msg = array('status' => 203, 'error' => 'You can not withdraw ₹'.$amount.' at this moment. Please try again later');
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


}
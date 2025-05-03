<?php

namespace App\Controllers\Admin;
use App\Controllers\BaseController;
use App\Models\Admin\UserWalletModel;
use App\Models\Admin\UserKYCModel;
use App\Models\UserModel;
use Config\Services;

class Wallets extends BaseController
{

  public function __construct(){
    helper('Common');
  }


  public function getDepositSummary(){

    $uri = current_url(true);
    $userId = $uri->getSegment(5);
    $userData = $this->request->decoded->userData;

    if ($userId == $userData->id) {

      $walletModel = new UserWalletModel();
      $kycModel = new UserKYCModel();
      $userModel = new UserModel();

    	$from = $this->request->getGet('from');
    	$to = $this->request->getGet('to');

      $uname = $this->request->getGet('uname');

      if (!is_null($uname)) {
        $tmpUata = $userModel->findUser($uname);
        if (count($tmpUata) > 0) {
          $uname = $tmpUata[0]->id;
        }else{
          $uname = '-1';
        }
      }


      if (!is_null($from) && !is_null($to)) {
        $dur = array('fromDate' => $from." 00:00:00", 'toDate' => $to." 23:59:59");
      }else{
        $toDate = date('Y-m-d');
        $fromDate = date('Y-m-d',strtotime("-30 days"));
        $dur = array('fromDate' => $fromDate." 00:00:00", 'toDate' => $toDate." 23:59:59");
      }

      $promoBonus = $walletModel->getPromosionBonus($dur,$uname);

    	$wallet = $walletModel->getAllDeposits($dur,$uname);

    	if (count($wallet) > 0) {
    		foreach ($wallet as $mdkey => $mdvalue) {
  			 
          $kycDet = $kycModel->getUserKyc($mdvalue->userId);
          if (count($kycDet) > 0) {
            $wallet[$mdkey]->state = $kycDet[0]->aadState;
          }else{
            $wallet[$mdkey]->state = '';
          }

          $depostDetails =  $walletModel->getTransactionDetails($mdvalue->depositId);
          if (count($depostDetails) > 0) {
            $wallet[$mdkey]->lat = $depostDetails[0]->lat;
            $wallet[$mdkey]->long = $depostDetails[0]->lon;
          }else{
            $wallet[$mdkey]->lat = '';
            $wallet[$mdkey]->long = '';             
          }

          $userDetails = $userModel->getUserById($mdvalue->userId);
          $mdvalue->userId = $userDetails[0]->name;

    		}
    	}

      if (count($promoBonus) > 0) {
        $promoBonus = $promoBonus[0]->credit;
        $promoBonus = sprintf('%0.2f', $promoBonus);
      }
      if (is_null($promoBonus)) {
        $promoBonus = 0; 
      }


      $msg = array('status' => 200, 'msg' => 'Success', 'data' => array('promoBonus' => $promoBonus, 'wallet' => $wallet) );
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


  public function getWithdrawalSummary(){

    $uri = current_url(true);
    $userId = $uri->getSegment(5);
    $userData = $this->request->decoded->userData;

    if ($userId == $userData->id) {

      $walletModel = new UserWalletModel();
      $kycModel = new UserKYCModel();
      $userModel = new UserModel();

      $from = $this->request->getGet('from');
      $to = $this->request->getGet('to');

      $uname = $this->request->getGet('uname');

      if (!is_null($uname)) {
        $tmpUata = $userModel->findUser($uname);
        if (count($tmpUata) > 0) {
          $uname = $tmpUata[0]->id;
        }else{
          $uname = '-1';
        }
      }


      if (!is_null($from) && !is_null($to)) {
        $dur = array('fromDate' => $from." 00:00:00", 'toDate' => $to." 23:59:59");
      }else{
        $toDate = date('Y-m-d');
        $fromDate = date('Y-m-d',strtotime("-30 days"));
        $dur = array('fromDate' => $fromDate." 00:00:00", 'toDate' => $toDate." 23:59:59");
      }

      $wallet = $walletModel->getAllWithdrawal($dur,$uname);

      if (count($wallet) > 0) {
        foreach ($wallet as $mdkey => $mdvalue) {
   
          $kycDet = $kycModel->getUserKyc($mdvalue->userId);
          if (count($kycDet) > 0) {
            $mdvalue->pan = $kycDet[0]->panNumber;
          }else{
            $mdvalue->pan = '';
          }

          $userDetails = $userModel->getUserById($mdvalue->userId);
          $mdvalue->userId = $userDetails[0]->name;

        }
      }

      $msg = array('status' => 200, 'msg' => 'Success', 'data' => $wallet );
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

}
<?php

namespace App\Controllers\Admin;
use App\Controllers\BaseController;
use App\Models\Admin\UsersModel;
use App\Models\Admin\UserKYCModel;
use App\Models\Admin\ContestModel;
use Config\Services;

class Users extends BaseController
{

  public function __construct(){
    helper('Common');
  }


  public function getAllUsers(){

    $uri = current_url(true);
    $userId = $uri->getSegment(5);
    $userData = $this->request->decoded->userData;

    if ($userId == $userData->id) {

    	$usersModel = new UsersModel();
    	$kycModel = new UserKYCModel();
    	$contModel = new ContestModel();

      $fromdate = $this->request->getGet('from');
      $todate = $this->request->getGet('to');

      $perPage = $this->request->getGet('perPage');
      $listFrom = $this->request->getGet('listFrom');

      $dur = array();
      if (!is_null($fromdate) && !is_null($todate)) {
        $dur = array('fromdate' => $fromdate." 00:00:00", 'todate' => $todate." 23:59:59");
      }

    
      $pagedata =  array('perPage' => $perPage ,'listFrom' => $listFrom );

      $uname = $this->request->getGet('uname');
      if (!is_null($uname)) {
        $tmpUata = $usersModel->findUser($uname);
        if (count($tmpUata) > 0) {
          $uname = $tmpUata[0]->id;
        }else{
          $uname = '-1';
        }
      }

    	//- Get All Users
    	$userDetails = $usersModel->getAllUsers($dur,$uname,$pagedata);
      $total = $usersModel->getTotalUsers($dur,$uname);
      

    	$details = array();
    	if (count($userDetails) > 0) {
    		foreach ($userDetails as $udkey => $udvalue) {

    			//- KYC
    			$kycDet = $kycModel->getUserKyc($udvalue->userId);
    			$state = '';
          $kycname = ''; 
    			$kycId = '';
          if (count($kycDet) > 0) {
    				$state = $kycDet[0]->aadState;
            $kycname = $kycDet[0]->aadrFullName;
            $kycId = $kycDet[0]->id;
    			}

    			//- User Game Rooms
    			$gameRooms = $contModel->getUserGameRooms($udvalue->userId);
		      if (is_null($gameRooms)) {
		        $gameRooms = 0;
		      }

          //- Total Wins
          $winDetails = $contModel->getWinningAmount($udvalue->userId);
          $winTotal = 0;
          if (count($winDetails) > 0){
            $winTotal = floatval($winDetails[0]->one_winning_price) + floatval($winDetails[0]->two_winning_price);
            $winTotal = sprintf('%0.2f', $winTotal);
          }

          //- Total Loss
          $loosDetails = $contModel->getLossAmount($udvalue->userId);
          $loosTotal = 0;
          if (count($loosDetails) > 0){
            $loosTotal = floatval($loosDetails[0]->one_lost_price) + floatval($loosDetails[0]->two_lost_price);
            $loosTotal = sprintf('%0.2f', $loosTotal);      
          } 

    			array_push($details, array('userId' => $udvalue->userId, 'name' => $udvalue->name, 'email' => $udvalue->email, 'number' => $udvalue->number, 'profileImg' => $udvalue->profileImg, 'state' => $state, 'gameRooms' => $gameRooms, 'winTotal' => $winTotal, 'loosTotal' => $loosTotal, 'userStatus' => $udvalue->userStatus, 'kycname' => $kycname, 'kycId' => $kycId) );

    		}
    	}



      $msg = array('status' => 200, 'msg' => 'Success', 'data' => array('details' => $details, 'total' => $total ) );
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


  public function updateUsersStats()
  {

    $uri = current_url(true);
    $userId = $uri->getSegment(5);
    $pl6UserId = $uri->getSegment(7);

    $userData = $this->request->decoded->userData;    

    if ($userId == $userData->id && !is_null($pl6UserId)) {

      $status = $this->request->getJsonVar('status');

      if ($status == '0' || $status == '1') {

        $usersModel = new UsersModel();

        if ($status == '0') {
          $status = '1';
        }else if ($status == '1') {
          $status = '0';
        }

        $usersModel->updateUserToken($pl6UserId,$status);

        $msg = array('status' => 200, 'msg' => 'Success', 'value' => $status);
        return Services::response()->setStatusCode(200)
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


  public function updateUsersKycName()
  {
    $uri = current_url(true);
    $userId = $uri->getSegment(5);

    $userData = $this->request->decoded->userData; 
    if ($userId == $userData->id) {

      $name = $this->request->getJsonVar('name');
      $kycId = $this->request->getJsonVar('kycId');

      if (!is_null($name) && !is_null($kycId)) {

        $userKYCModel  = new UserKYCModel();
        $status = $userKYCModel->updateKycName($kycId,$name);

        if ($status == '1') {
            
          $msg = array('status' => 200, 'msg' => 'Success', 'value' => $name);
          return Services::response()->setStatusCode(200)
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

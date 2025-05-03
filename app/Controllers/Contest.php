<?php

namespace App\Controllers;
use App\Controllers\BaseController;
use App\Models\ContestModel;
use App\Models\MatchesModel;
use App\Models\PlayersModel;
use App\Models\TeamsModel;
use App\Models\UserModel;
use App\Models\UserWalletModel;
use App\Models\ContestFinalPlayers; 
use App\Models\MatchesLookupModel;
use App\Models\MatchPlayerScores;
use App\Libraries\jwtLibrary;
use Config\Services;

class Contest extends BaseController
{

    protected $db;
    public function __construct(){
      $this->db = \Config\Database::connect();
      helper('Common');
    }


    //- Get all Contest Pricing
    public function allContestPrices()
    {
      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;

      if ($userId == $userData->id) {
        
        $contModel = new ContestModel();
        $price = $contModel->getAllContestPrices();

        $msg = array('status' => 200, 'list' => $price);
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


    //- Show Contest Pricing
    public function getPricing()
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;

      if ($userId == $userData->id) {

      	$contestId = $uri->getSegment(6);
      	$matchTypeId = $uri->getSegment(8);

      	$contModel = new ContestModel();
      	$price = $contModel->getCampPrices($contestId,$matchTypeId);

      	$msg = array('status' => 200, 'priceId' => $price[0]->priceId, 'price' =>  $price[0]->price );
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


    //- Check My Match Contest
    public function checkMatchContests()
    {
      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $matchId = $uri->getSegment(6);
      $userData = $this->request->decoded->userData;

      if ($userId == $userData->id) {

        $matchContest = new ContestModel();

        //- Check Match Is Ongoing Are Not
        $matchModel = new MatchesModel();
        $matchDetails = $matchModel->getMatcheById($matchId);

        if (count($matchDetails) > 0) {
          $tmpMatchDate = date("Y-m-d H:i:s", strtotime($matchDetails[0]->startTime." -30 minutes"));
          $currentTime = date("Y-m-d H:i:s");
          
          if ($tmpMatchDate > $currentTime) {
          
            $contests = $matchContest->getMyMatchContests($matchId,$userId);

            $tmpContests = array();
            if (count($contests) > 0) {
              foreach ($contests as $cokey => $covalue) {
                array_push($tmpContests, $covalue->contestId);
              }
            }

            $msg = array('status' => 200, 'msg' => $tmpContests);
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

      }else{

        $msg = array('status' => 401, 'error' => 'Unauthorized');
        return Services::response()->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);

      }

    }


    //- Create Match Contest
    public function createContest()
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;

      if ($userId == $userData->id) {

        $matchId = $uri->getSegment(6);
        $contestId = $uri->getSegment(8);

        $posMatchId = $this->request->getJsonVar('matchId');
        $posContestId = $this->request->getJsonVar('contestId');

        if (!empty($matchId) && !empty($contestId) && $posMatchId == $matchId && $posContestId == $contestId ) {

          //- Get Context Price
          $matchContest = new ContestModel();
          $teamModel = new TeamsModel();
          $contestDetails = array();
          
          //- Check Match Is Ongoing Are Not
          $matchModel = new MatchesModel();
          $matchDetails = $matchModel->getMatcheById($matchId);

          if (count($matchDetails) > 0) {
            $tmpMatchDate = date("Y-m-d H:i:s", strtotime($matchDetails[0]->startTime." -30 minutes"));
            $currentTime = date("Y-m-d H:i:s");

            $tossTime = $tmpMatchDate;
            $allowedTime = date("Y-m-d H:i:s", strtotime($tossTime." -60 minutes"));

            $contestDetails = $matchContest->getCampPrices($contestId,$matchDetails[0]->matchType);

          }

          //- Check User Contest Exist
          $conData = $matchContest->checkUserContestExist($userId,$matchId,$contestId);

          if (count($contestDetails) > 0 && count($matchDetails) > 0 && count($conData) == 0) {

            $userWalletModel = new UserWalletModel();
            $userWallDtl = $userWalletModel->getTotalCredits($userId);
            
            $tmpAmount = 0;
            if (count($userWallDtl) > 0) {
              $tmpAmount = floatval($userWallDtl[0]->credit) + floatval($userWallDtl[0]->debit);
            }
            
            if ($tmpMatchDate > $currentTime && $matchDetails[0]->tossWinner == '0' && floatval($tmpAmount) >= floatval($contestDetails[0]->price) ) {

              //- Get Un Paired Contest 
              $oldContest = $matchContest->getUnPairedContest($matchId,$contestId);

              if (count($oldContest) > 0) {

                //- Update Contest 
                $data['pairId'] = $oldContest[0]->pairId;
                $data['userTwo'] = $userId;
                $matchContest->updateContestPair($data);
                unset($data);

                //- Debit Wallet Amount
                $tmpWall['userId'] = $userId;
                $tmpWall['amount'] = $oldContest[0]->fee;
                $tmpWall['contestId'] = $oldContest[0]->pairId;
                $tmpWall['text'] = 'Contest fee';
                $tmpWall['status'] = '6';
                $userWalletModel = new UserWalletModel();
                $userWalletModel->debitWallet($tmpWall);
                unset($tmpWall);              

                //- Create Drafts

                //- Get Match Players
                $playerModel = new PlayersModel();

                $teamBats = array();

                //- Get Team One Players
                $teamOnePlayes = $playerModel->getTeamPlayes($matchDetails[0]->matchId,$matchDetails[0]->teamaId);

                if (count($teamOnePlayes) > 0) {
                  foreach ($teamOnePlayes as $oneKey => $oneValue) {
                    array_push($teamBats, $oneValue->playerId);
                  }
                }


                //- Get Team Two Players
                $teamTwoPlayes = $playerModel->getTeamPlayes($matchDetails[0]->matchId,$matchDetails[0]->teambId);

                if (count($teamTwoPlayes) > 0) {
                  foreach ($teamTwoPlayes as $twoKey => $twoValue) {
                    array_push($teamBats, $twoValue->playerId);
                  }
                }


                //- Random Team One Players
                $team1Keys = array_rand($teamBats,12);
                if (count($team1Keys) > 0) {
                  foreach ($team1Keys as $m1ke => $m1value) {
                    
                    //- Create Draft

                    $data['matchId'] = $oldContest[0]->matchId;
                    $data['contestId'] = $oldContest[0]->pairId;
                    $data['userId'] = $userId;
                    $data['playerId'] = $teamBats[$m1value];
                    $data['playerOrder'] = $m1ke + 1;
                    $matchContest->createDrafts($data);
                    unset($data);

                  }
                }


                //- Random Team Two Players
                $team2Keys = array_rand($teamBats,12);
                if (count($team2Keys) > 0) {
                  foreach ($team2Keys as $m2ke => $m2value) {
                    
                    //- Create Draft

                    $data['matchId'] = $oldContest[0]->matchId;
                    $data['contestId'] = $oldContest[0]->pairId;
                    $data['userId'] = $oldContest[0]->userOne;
                    $data['playerId'] = $teamBats[$m2value];
                    $data['playerOrder'] = $m2ke + 1;
                    $matchContest->createDrafts($data);
                    unset($data);

                  }
                }


                //- Send Server Event
                $data['eventName'] = 'opnSel';

                $msg = array('matchId' => $oldContest[0]->matchId, 'contestId' => $contestId, 'userOne' => $oldContest[0]->userOne, 'userTwo' => $userId, "gameId" => $oldContest[0]->pairId);
                $data['eventData'] = json_encode($msg);
                CreateSselog($data);
                unset($data);

                //- Send Server Event For Downgrade
                if ($currentTime >= $allowedTime && $currentTime < $tossTime) {

                  //- Get All Un Assigned Contest less then ContId 
                  $avilContests = $matchContest->getAllDownGradeContests($oldContest[0]->matchId);

                  $contest = array();
                  if (count($avilContests) > 0) {
                    foreach ($avilContests as $ackey => $acvalue) {
                      array_push($contest, $acvalue->contestId);
                    }
                  }

                  $data['eventName'] = 'downGradOpt';

                  $msg = array('matchId' => $oldContest[0]->matchId, 'contestId' => $contestId, 'options' => $contest);
                  $data['eventData'] = json_encode($msg);
                  CreateSselog($data);
                  unset($data);

                }

                //- Send Notification
                $userModel = new UserModel();
                $userDetails = $userModel->getUserToken($oldContest[0]->userOne);

                if (count($userDetails) > 0 && !empty($userDetails[0]->token)) {


                  $teamADetails = $teamModel->getTeamById($matchDetails[0]->teamaId);
                  $teamBDetails = $teamModel->getTeamById($matchDetails[0]->teambId);

                  $tmpCon = '';
                  if ($contestId == '1') {
                    $tmpCon = '₹1';
                  }else if ($contestId == '2') {
                    $tmpCon = '₹5';
                  }else if ($contestId == '3') {
                    $tmpCon = '₹10';
                  }else if ($contestId == '4') {
                    $tmpCon = '₹20';
                  }
                  
                  $title = $teamADetails[0]->shortName." v ".$teamBDetails[0]->shortName.' '.$tmpCon;
                  
                  $notifi = array("title" => $title, "body" => "You found an opponent. Predict the toss and update your draft");
                  $tmpBody = array('notification' => $notifi, 'to' => $userDetails[0]->token);
                  $tmpBody = json_encode($tmpBody);

                  sendPushNotifi($tmpBody);                

                }


              }else{

                //- Create Context
                $data['matchId'] = $matchId;
                $data['contestId'] = $contestId;
                $data['contestFee'] = $contestDetails[0]->price;
                $data['userOne'] = $userId;
                $matchContest->createContest($data);
                $usrContest = $matchContest->getUserOneLastContest($userId);
                unset($data);

                //- Debit Wallet Amount
                $tmpWall['userId'] = $userId;
                $tmpWall['amount'] = $contestDetails[0]->price;
                $tmpWall['contestId'] = $usrContest[0]->pairId;
                $tmpWall['text'] = 'Contest fee';
                $tmpWall['status'] = '6';
                $userWalletModel = new UserWalletModel();
                $userWalletModel->debitWallet($tmpWall);
                unset($tmpWall);              

                //- Send Server Event For Downgrade
                if ($currentTime >= $allowedTime && $currentTime < $tossTime) {

                  //- Get All Un Assigned Contest less then ContId 
                  $avilContests = $matchContest->getAllDownGradeContests($matchId);

                  $contest = array();
                  if (count($avilContests) > 0) {
                    foreach ($avilContests as $ackey => $acvalue) {
                      array_push($contest, $acvalue->contestId);
                    }
                  }

                  $data['eventName'] = 'downGradOpt';

                  $msg = array('matchId' => $matchId, 'contestId' => $contestId, 'options' => $contest);
                  $data['eventData'] = json_encode($msg);
                  CreateSselog($data);
                  unset($data);

                }

              }
                   
              $msg = array('status' => 200, 'msg' => "Success");
              return $this->response->setStatusCode(200)
                          ->setHeader('Access-Control-Allow-Origin', '*')
                          ->setHeader('Access-Control-Allow-Headers', 'Origin')
                          ->setContentType('application/json', 'utf-8')
                          ->setJSON($msg);                  
             
            }else{

              if (floatval($contestDetails[0]->price) >= floatval($tmpAmount)) {

                $msg = array('status' => 401, 'error' => 'Low wallet Balance');
                return $this->response->setStatusCode(401)
                        ->setHeader('Access-Control-Allow-Origin', '*')
                        ->setHeader('Access-Control-Allow-Headers', 'Origin')
                        ->setContentType('application/json', 'utf-8')
                        ->setJSON($msg);
              
              }else{

                $msg = array('status' => 401, 'error' => 'Match Does Not Exist');
                return $this->response->setStatusCode(401)
                        ->setHeader('Access-Control-Allow-Origin', '*')
                        ->setHeader('Access-Control-Allow-Headers', 'Origin')
                        ->setContentType('application/json', 'utf-8')
                        ->setJSON($msg);

              }

            }

          }else{

            if (count($contestDetails) == 0 ) {
              
              $msg = array('status' => 401, 'error' => 'Contest Does Not Exist');
              return $this->response->setStatusCode(401)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

            }else if (count($matchDetails) == 0) {
              
              $msg = array('status' => 401, 'error' => 'Match Does Not Exist');
              return $this->response->setStatusCode(401)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

            }else if (count($conData) > 0) {

              $msg = array('status' => 401, 'error' => 'Contest Exist');
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

      }else{

        $msg = array('status' => 401, 'error' => 'Unauthorized');
        return Services::response()->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);        

      }

    }


    //- Get All Down Grade Contests
    public function getAllDownGradeContests()
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $matchId = $uri->getSegment(6);
      $userData = $this->request->decoded->userData;

      $posMatchId = $this->request->getJsonVar('matchId');
      $posContId = $this->request->getJsonVar('contestId');

      if ($userId == $userData->id && !is_null($posMatchId) && !is_null($posContId) && $posMatchId == $matchId && $posContId == $posContId ) {

        //- Get Match Details
        $matchModel = new MatchesModel();
        $matchDetails = $matchModel->getMatcheById($matchId);

        if (count($matchDetails) > 0) {
          $tossTime = date("Y-m-d H:i:s", strtotime($matchDetails[0]->startTime." -30 minutes"));
          $currentTime = date("Y-m-d H:i:s");

          $allowedTime = date("Y-m-d H:i:s", strtotime($tossTime." -60 minutes"));
        }  

        $contest = array();
        if ($currentTime >= $allowedTime && $currentTime < $tossTime) {

          //- Get All Un Assigned Contest less then posContId and Contests not equal to User Id
          $contestModel = new ContestModel();
          $avilContests = $contestModel->getDownGradeContests($posMatchId,$posContId,$userId);

          if (count($avilContests) > 0) {
            foreach ($avilContests as $ackey => $acvalue) {
              array_push($contest, $acvalue->contestId);
            }
          }
          
        }else{
          $contest = array();
        }

        $msg = array('status' => 200, 'msg' => $contest);
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


    //- Down Grade Contests
    public function downGradeContest()
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;

      $matchId = $uri->getSegment(6);
      $contestId = $uri->getSegment(8);
      $postMatchId = $this->request->getJsonVar('matchId');
      $postContestId = $this->request->getJsonVar('contestId');

      if ($userId == $userData->id && !is_null($postMatchId) && !is_null($postContestId) && $postMatchId == $matchId && $postContestId == $contestId) {

        $newContestId = $this->request->getJsonVar('newContestId');

        //- Get Context Price
        $matchContest = new ContestModel();
        $contestDetails = array();
        
        //- Check Match Is Ongoing Are Not
        $matchModel = new MatchesModel();
        $teamModel = new TeamsModel();
        $matchDetails = $matchModel->getMatcheById($matchId);

        if (count($matchDetails) > 0) {
          $tmpMatchDate = date("Y-m-d H:i:s", strtotime($matchDetails[0]->startTime." -30 minutes"));
          $currentTime = date("Y-m-d H:i:s");

          $tossTime = $tmpMatchDate;
          $allowedTime = date("Y-m-d H:i:s", strtotime($tossTime." -60 minutes"));

          $contestDetails = $matchContest->getCampPrices($newContestId,$matchDetails[0]->matchType);
        }


        if (count($contestDetails) > 0 && count($matchDetails) > 0) {

          if ($tmpMatchDate > $currentTime && $matchDetails[0]->tossWinner == '0' ) {

            //- Check User Contest Exist
            $conData = $matchContest->checkUserContestExist($userId,$matchId,$newContestId);

            $curData = $matchContest->checkUserUnParedContest($userId,$matchId,$contestId);

            //- Cancle Old Contest
            $matchContest->suspendContest($matchId,$contestId,$userId);

            //- Get Un Paired Contest 
            $oldContest = $matchContest->getUnPairedContest($matchId,$newContestId);

            if (count($oldContest) > 0 && count($conData) == 0 && count($curData) > 0) {
              
              //- Cancle Old Contest
              $matchContest->suspendContest($matchId,$contestId,$userId);

              //- Update Contest 
              $data['pairId'] = $oldContest[0]->pairId;
              $data['userTwo'] = $userId;
              $matchContest->updateContestPair($data);
              unset($data);

              $tmpContData = $matchContest->getUserSuspendContest($matchId,$contestId,$userId);

              //- Refund Old Contest
              $tmpWall['userId'] = $userId;
              $tmpWall['amount'] = $tmpContData[0]->fee;
              $tmpWall['contestId'] = $tmpContData[0]->pairId;
              $tmpWall['text'] = 'Contest fee refund';
              $tmpWall['status'] = '3';
              $userWalletModel = new UserWalletModel();
              $userWalletModel->creditWallet($tmpWall);
              unset($tmpWall);

              //- Debit New Contest
              $tmpWall['userId'] = $userId;
              $tmpWall['amount'] = $oldContest[0]->fee;
              $tmpWall['contestId'] = $oldContest[0]->pairId;
              $tmpWall['text'] = 'Contest fee';
              $tmpWall['status'] = '6';
              $userWalletModel->debitWallet($tmpWall);
              unset($tmpWall);

              //- Create Drafts

              //- Get Match Players
              $playerModel = new PlayersModel();

              $teamBats = array();

              //- Get Team One Players
              $teamOnePlayes = $playerModel->getTeamPlayes($matchDetails[0]->matchId,$matchDetails[0]->teamaId);

              if (count($teamOnePlayes) > 0) {
                foreach ($teamOnePlayes as $oneKey => $oneValue) {
                  array_push($teamBats, $oneValue->playerId);
                }
              }


              //- Get Team Two Players
              $teamTwoPlayes = $playerModel->getTeamPlayes($matchDetails[0]->matchId,$matchDetails[0]->teambId);

              if (count($teamTwoPlayes) > 0) {
                foreach ($teamTwoPlayes as $twoKey => $twoValue) {
                  array_push($teamBats, $twoValue->playerId);
                }
              }


              //- Random Team One Players
              $team1Keys = array_rand($teamBats,12);
              if (count($team1Keys) > 0) {
                foreach ($team1Keys as $m1ke => $m1value) {
                  
                  //- Create Draft

                  $data['matchId'] = $oldContest[0]->matchId;
                  $data['contestId'] = $oldContest[0]->pairId;
                  $data['userId'] = $userId;
                  $data['playerId'] = $teamBats[$m1value];
                  $data['playerOrder'] = $m1ke + 1;
                  $matchContest->createDrafts($data);
                  unset($data);

                }
              }


              //- Random Team Two Players
              $team2Keys = array_rand($teamBats,12);
              if (count($team2Keys) > 0) {
                foreach ($team2Keys as $m2ke => $m2value) {
                  
                  //- Create Draft

                  $data['matchId'] = $oldContest[0]->matchId;
                  $data['contestId'] = $oldContest[0]->pairId;
                  $data['userId'] = $oldContest[0]->userOne;
                  $data['playerId'] = $teamBats[$m2value];
                  $data['playerOrder'] = $m2ke + 1;
                  $matchContest->createDrafts($data);
                  unset($data);

                }
              }


              //- Send Server Event
              $data['eventName'] = 'opnDownSel';

              $msg = array('matchId' => $oldContest[0]->matchId, 'userTwoContId' => $postContestId, 'userOneContId' => $newContestId, 'userOne' => $oldContest[0]->userOne, 'userTwo' => $userId, "gameId" => $oldContest[0]->pairId);
              $data['eventData'] = json_encode($msg);
              CreateSselog($data);
              unset($data);


              //- Send Server Event For Downgrade
              if ($currentTime >= $allowedTime && $currentTime < $tossTime) {

                //- Get All Un Assigned Contest less then New ContId 
                $avilContests = $matchContest->getAllDownGradeContests($oldContest[0]->matchId);

                $contest = array();
                if (count($avilContests) > 0) {
                  foreach ($avilContests as $ackey => $acvalue) {
                    array_push($contest, $acvalue->contestId);
                  }
                }

                $data['eventName'] = 'downGradOpt';

                $msg = array('matchId' => $oldContest[0]->matchId, 'contestId' => $newContestId, 'options' => $contest);
                $data['eventData'] = json_encode($msg);
                CreateSselog($data);
                unset($data);

                
                //- Get All Un Assigned Contest less then Old ContId 
                $avilContests = $matchContest->getAllDownGradeContests($oldContest[0]->matchId);

                $contest = array();
                if (count($avilContests) > 0) {
                  foreach ($avilContests as $ackey => $acvalue) {
                    array_push($contest, $acvalue->contestId);
                  }
                }

                $data['eventName'] = 'downGradOpt';

                $msg = array('matchId' => $oldContest[0]->matchId, 'contestId' => $postContestId, 'options' => $contest);
                $data['eventData'] = json_encode($msg);
                CreateSselog($data);
                unset($data);

              }

              //- Send Notification
              $userModel = new UserModel();
              $userDetails = $userModel->getUserToken($oldContest[0]->userOne);

              if (count($userDetails) && !empty($userDetails[0]->token)) {

                $teamADetails = $teamModel->getTeamById($matchDetails[0]->teamaId);
                $teamBDetails = $teamModel->getTeamById($matchDetails[0]->teambId);

                $tmpCon = '';
                if ($newContestId == '1') {
                  $tmpCon = '₹1';
                }else if ($newContestId == '2') {
                  $tmpCon = '₹5';
                }else if ($newContestId == '3') {
                  $tmpCon = '₹10';
                }else if ($newContestId == '4') {
                  $tmpCon = '₹20';
                }

                
                $title = $teamADetails[0]->shortName." v ".$teamBDetails[0]->shortName.' '.$tmpCon;

                $notifi = array("title" => $title, "body" => "You found an opponent. Predict the toss and update your draft");
                $tmpBody = array('notification' => $notifi, 'to' => $userDetails[0]->token);
                $tmpBody = json_encode($tmpBody);

                sendPushNotifi($tmpBody);                

              }


              $msg = array('status' => 200, 'msg' => "Success");
              return $this->response->setStatusCode(200)
                          ->setHeader('Access-Control-Allow-Origin', '*')
                          ->setHeader('Access-Control-Allow-Headers', 'Origin')
                          ->setContentType('application/json', 'utf-8')
                          ->setJSON($msg);

            }else{

              $matchContest->activeContest($matchId,$contestId,$userId);

              $msg = array('status' => 401, 'error' => 'No participant is available to downgrade. Please try again');
              return $this->response->setStatusCode(401)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

            }

          }else{

            $msg = array('status' => 401, 'error' => 'Match Does Not Exist');
            return $this->response->setStatusCode(401)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

          }

        }else{

          if (count($contestDetails) == 0 ) {
            
            $msg = array('status' => 401, 'error' => 'Contest Does Not Exist');
            return $this->response->setStatusCode(401)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

          }else if (count($matchDetails) == 0) {
            
            $msg = array('status' => 401, 'error' => 'Match Does Not Exist');
            return $this->response->setStatusCode(401)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

          }else if (count($conData) > 0) {

            $msg = array('status' => 401, 'error' => 'Contest Exist');
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


    //- Get Game Details
    public function getGameDetails()
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $gameId = $uri->getSegment(6);
      $userData = $this->request->decoded->userData;
      $posGameId = $this->request->getJsonVar('gameId');

      if ($userId == $userData->id && $posGameId == $gameId) {

        $contModel = new ContestModel();
        $gameDetails = $contModel->getGameDetailsById($gameId,$userId);

        if (count($gameDetails) > 0) {

          $matchModel = new MatchesModel();
          $matchDetails = $matchModel->getMatcheById($gameDetails[0]->matchId);

          $teamModel = new TeamsModel();
          $contOne = $teamModel->checkTeamExist($matchDetails[0]->teamaKey); 
          $contTwo = $teamModel->checkTeamExist($matchDetails[0]->teambKey); 

          $tossWinner = $gameDetails[0]->tossWinner;
          if ($gameDetails[0]->status == '4' && $gameDetails[0]->tossWinner == '0') {

            if ($gameDetails[0]->userOneTeam == $gameDetails[0]->tossWinnerTeam) {
              $tossWinner = $gameDetails[0]->userOne;
            }else if ($gameDetails[0]->userTwoTeam == $gameDetails[0]->tossWinnerTeam) {
              $tossWinner = $gameDetails[0]->userTwo;
            }else{
              $tossWinner = $gameDetails[0]->userTwo;
            }

          }
        
          $data = array(
            'banTitle' => $matchDetails[0]->title,
            'startTime' => $matchDetails[0]->startTime,
            'tosTime' => date("Y-m-d H:i:s", strtotime($matchDetails[0]->startTime." -30 minutes")),
            'contOne' => $matchDetails[0]->teamaId,
            'contOneImg' => $contOne[0]->logo,
            'contOneSnam' => $contOne[0]->shortName,
            'contTwo' => $matchDetails[0]->teambId,
            'contTwoImg' => $contTwo[0]->logo,
            'contTwoSnam' => $contTwo[0]->shortName,
            'gameId' => $gameDetails[0]->pairId,
            'status' => $gameDetails[0]->status,
            'userOne' => $gameDetails[0]->userOne,
            'userTwo' => $gameDetails[0]->userTwo,
            'userOneTeam' => $gameDetails[0]->userOneTeam,
            'userTwoTeam' => $gameDetails[0]->userTwoTeam,
            'matchId' => $gameDetails[0]->matchId,
            'tossWinner' => $tossWinner,
            'oneCapSel' => $gameDetails[0]->oneCapSel,
            'twoCapSel' => $gameDetails[0]->twoCapSel            
          );

          $msg = array('status' => 200, 'data' => $data);
          return $this->response->setStatusCode(200)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);          

        }else{

          $msg = array('status' => 401, 'error' => 'Unauthorized');
          return $this->response->setStatusCode(401)
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


    //- Get all games
    public function getAllGames()
    {
      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;

      if ($userId == $userData->id) {

        $contModel = new ContestModel();
        $gameDetails = $contModel->getAllGameById($userId);

        if (count($gameDetails) > 0) {

          $list = array();
          foreach ($gameDetails as $gdkey => $gdvalue) {

            $matchModel = new MatchesModel();
            $matchDetails = $matchModel->getMatcheById($gdvalue->matchId);

            $matchLookModel = new MatchesLookupModel();
            $lookDetails = $matchLookModel->checkMatchLookup($gdvalue->matchId);

            $player11 = 0;
            $player11ST = '';
            if (count($lookDetails) > 0) {
              $player11 = $lookDetails[0]->player11;
              $player11ST = $lookDetails[0]->player11ST;
            }

            $teamModel = new TeamsModel();
            $contOne = $teamModel->checkTeamExist($matchDetails[0]->teamaKey); 
            $contTwo = $teamModel->checkTeamExist($matchDetails[0]->teambKey);
            
            $currentTime = gmdate("Y-m-d H:i:s");

            //- if (date("Y-m-d H:i:s", strtotime($matchDetails[0]->endTime." +120 minutes")) > $currentTime ) {


              if ($gdvalue->status == '4' && $gdvalue->tossWinner == '0') {
                # code...

                if ($gdvalue->userOneTeam == $gdvalue->tossWinnerTeam) {
                  $gdvalue->tossWinner = $gdvalue->userOne;
                }else if ($gdvalue->userTwoTeam == $gdvalue->tossWinnerTeam) {
                  $gdvalue->tossWinner = $gdvalue->userTwo;
                }else{
                  $gdvalue->tossWinner = $gdvalue->userTwo;
                }

              }


              if ($gdvalue->userOne == $userId) {
                $isCapSel = $gdvalue->uoneisCapSel;
              }else if ($gdvalue->userTwo == $userId) {
                $isCapSel = $gdvalue->utwoisCapSel;
              }              
            
              array_push($list, array('banTitle' => $matchDetails[0]->name.' '.$matchDetails[0]->title, 'startTime' => $matchDetails[0]->startTime, 'tosTime' => date("Y-m-d H:i:s", strtotime($matchDetails[0]->startTime." -30 minutes")), 'contOne' => $matchDetails[0]->teamaId, 'contOneImg' => $contOne[0]->logo, 'contOneSnam' => $contOne[0]->shortName, 'contTwo' => $matchDetails[0]->teambId, 'contTwoImg' => $contTwo[0]->logo, 'contTwoSnam' => $contTwo[0]->shortName, 'gameId' => $gdvalue->pairId, 'status' => $gdvalue->status, 'userOne' => $gdvalue->userOne, 'userTwo' => $gdvalue->userTwo, 'matchId' => $gdvalue->matchId, 'contestId' => $gdvalue->contestId, 'userOneTeam' => $gdvalue->userOneTeam,'userTwoTeam' => $gdvalue->userTwoTeam, 'matchType' => $matchDetails[0]->matchType, 'tossWinner' => $gdvalue->tossWinner, 'finalPlayers' => $gdvalue->finalPlayers, 'isCapSel' => $isCapSel, 'player11' => $player11, 'player11ST' => $player11ST ));
            
            /*}*/

          }

          if (count($list) > 0) {
            $key_values = array_column($list, 'startTime');
            array_multisort($key_values, SORT_ASC, $list);
          }

          $msg = array('status' => 200, 'data' => $list);
          return Services::response()->setStatusCode(200)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

        }else{
          $msg = array('status' => 200, 'data' => array());
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


    //- Select A Team For The Game
    public function selectTeam()
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $gameId = $uri->getSegment(6);
      $userData = $this->request->decoded->userData;
      $posUserId = $this->request->getJsonVar('userId');
      $posGameId = $this->request->getJsonVar('gameId');
      $selteam = $this->request->getJsonVar('team');

      if ($userId == $userData->id && $posGameId == $gameId && $posUserId == $userId && !is_null($selteam)) {

        //- Check User Contest Exist
        $contModel = new ContestModel();
        $gameDetails = $contModel->getGameDetailsById($gameId,$userId);
       
        //- Check Match Is Ongoing Are Not
        $isMatchStarted = false;
        if (count($gameDetails) > 0) { 

          $matchModel = new MatchesModel();
          $matchDetails = $matchModel->getMatcheById($gameDetails[0]->matchId);
  
          if (count($matchDetails) > 0) {
            $tmpMatchDate = date("Y-m-d H:i:s", strtotime($matchDetails[0]->startTime." -30 minutes"));
            $currentTime = date("Y-m-d H:i:s");

            if ($matchDetails[0]->startTime > $currentTime && $gameDetails[0]->userOne = $userId && ($matchDetails[0]->teamaId == $selteam || $matchDetails[0]->teambId == $selteam ) ) {
              $isMatchStarted = true;
            }
          }

        }

        if (count($gameDetails) > 0 && $isMatchStarted) { 

          //- Update Team
          if ($matchDetails[0]->teamaId == $selteam) {
            $data['userOneTeam'] = $matchDetails[0]->teamaId;
            $data['userTwoTeam'] = $matchDetails[0]->teambId;
          }else if ($matchDetails[0]->teambId == $selteam) {
            $data['userOneTeam'] = $matchDetails[0]->teambId;
            $data['userTwoTeam'] = $matchDetails[0]->teamaId;
          }

          $data['pairId'] = $gameDetails[0]->pairId;
          $contModel = new ContestModel();
          $contModel->updateContestTeams($data);

          $teamsModel = new TeamsModel();
          $teamDetails = $teamsModel->getTeamById($data['userTwoTeam']);
          $teamADetails = $teamsModel->getTeamById($data['userOneTeam']);

          //- Send Server Event
          $ssedata['eventName'] = 'teamSel';

          $msg = array('matchId' => $gameDetails[0]->matchId, "gameId" => $gameDetails[0]->pairId, 'userOneTeam' => $data['userOneTeam'],'contOne' => $data['userOneTeam'],'userTwoTeam' => $data['userTwoTeam'],'contTwo' => $data['userTwoTeam'], 'contOneSnam' => $teamADetails[0]->shortName, 'contOneImg' => $teamADetails[0]->logo, 'contTwoSnam' => $teamDetails[0]->shortName, 'contTwoImg' => $teamDetails[0]->logo);
          $ssedata['eventData'] = json_encode($msg);
          CreateSselog($ssedata);
          unset($data);
          unset($ssedata);
    
          //- Send Notification
    
          $userModel = new UserModel();
          $userDetails = $userModel->getUserToken($gameDetails[0]->userTwo);

          if (count($teamDetails) && count($userDetails) && !empty($userDetails[0]->token)) {

            $contest = '';
            if ($gameDetails[0]->contestId == '1') {
              $contest = '₹1';
            }else if ($gameDetails[0]->contestId == '2') {
              $contest = '₹5';
            }else if ($gameDetails[0]->contestId == '3') {
              $contest = '₹10';
            }else if ($gameDetails[0]->contestId == '4') {
              $contest = '₹20';
            }
            
            $body = "Your team for the toss is ".$teamDetails[0]->name." Update your draft";

            $title = $teamADetails[0]->shortName.' v '.$teamDetails[0]->shortName.' '.$contest;

            $notifi = array("title" => $title, "body" => $body);
            $tmpBody = array('notification' => $notifi, 'to' => $userDetails[0]->token);
            $tmpBody = json_encode($tmpBody);

            sendPushNotifi($tmpBody);                

          }

          $msg = array('status' => 200, 'msg' => $msg);
          return $this->response->setStatusCode(200)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);  

        }else{

          $msg = array('status' => 401, 'error' => 'Unauthorized');
          return $this->response->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);
        }

      }else{

        $msg = array('status' => 401, 'error' => 'Unauthorized');
        return $this->response->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);        

      }     

    }


    //- Get All Team Players
    public function getAllPlayers()
    {
      
      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $gameId = $uri->getSegment(6);
      $userData = $this->request->decoded->userData;
      $posUserId = $this->request->getJsonVar('userId');
      $posGameId = $this->request->getJsonVar('gameId');

      if ($userId == $userData->id && $posGameId == $gameId && $posUserId == $userId) {

        //- Get Game Under Draft 
        $contModel = new ContestModel();
        $gameDetails = $contModel->getDeaftContest($userId,$posGameId);

        if (count($gameDetails) > 0) {

          //- Get Match Details
          $matchModel = new MatchesModel();
          $matchDetails = $matchModel->getMatcheById($gameDetails[0]->matchId);

          //- Get Team Details
          $teamModel = new TeamsModel();
          $teamDetails = $teamModel->getMultipleTeams(array($matchDetails[0]->teamaId,$matchDetails[0]->teambId));

          //- Get My Draft Players
          $dafts = $contModel->getGameDraftsById($gameDetails[0]->matchId,$gameId,$userId);
          $myDrafts = array();
          if (count($dafts) > 0) {
            foreach ($dafts as $dkey => $dvalue) {

              array_push($myDrafts, array('playerId' => $dvalue->playerId, 'draftId' => $dvalue->draftId, 'playerOrder' => $dvalue->playerOrder));
            }
          }

          //- Get All Match Players
          $playerModel = new PlayersModel(); 
          $players = $playerModel->getAllMatchPlayers($gameDetails[0]->matchId);

          $playerList = array();
          if (count($players) > 0) {
            foreach ($players as $pkey => $pvalue) {

              $draftId = '';
              $draftOrder = -1;
              if (count($myDrafts) > 0) {
                foreach ($myDrafts as $mdkey => $mdvalue) {
                  if ($pvalue->playerId == $mdvalue['playerId']) {
                    $draftId = $mdvalue['draftId'];
                    $draftOrder = intval($mdvalue['playerOrder']);
                    break;
                  }
                }
              }

              $conName = '';
              if (count($teamDetails) > 0) {
                foreach ($teamDetails as $tdkey => $tdvalue) {
                  if ($tdvalue->teamId == $pvalue->teamId) {
                    $conName = $tdvalue->shortName;
                    break;
                  }
                }
              }


              if ($matchDetails[0]->matchType == '1') {
                $tmpType = '1';
              }else if ($matchDetails[0]->matchType == '2') {
                $tmpType = '2';
              }else if ($matchDetails[0]->matchType == '3') {
                $tmpType = '3';
              }else{
                $tmpType = '2';
              }                   

              $pdetails = $playerModel->getPlayersDetails($pvalue->playerId,$tmpType);

              if (count($pdetails) == 0) {
                $pdetails = $playerModel->getPlayersDetailsNoScore($pvalue->playerId);
                if (count($pdetails) > 0) {
                  $pdetails[0]->avg = 0;
                  $pdetails[0]->hs = 0;
                }
              }

              array_push($playerList, array('name' => $pdetails[0]->name, 'image' => $pdetails[0]->faceImageId, 'country' => $conName,'avg' => $pdetails[0]->avg, 'hs' => $pdetails[0]->hs, 'draftId' => $draftId, 'draftOrder' => $draftOrder, 'matchId' => $gameDetails[0]->matchId, 'playerId' =>  $pvalue->playerId, 'teamId' => $pvalue->teamId));
            }
          }

          if (count($playerList) > 0) {
            $key_values = array_column($playerList, 'draftOrder'); 
            array_multisort($key_values, SORT_DESC, $playerList);
          }

          $msg = array('status' => 200, 'playerList' =>  $playerList);
          return $this->response->setStatusCode(200)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

        }else{

          $msg = array('status' => 401, 'error' => 'Unauthorized');
          return $this->response->setStatusCode(401)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

        }

      }else{

        $msg = array('status' => 401, 'error' => 'Unauthorized');
        return $this->response->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);        

      }     

    }


    //- Select Match Draft Player
    public function selectDraftPlayer()
    {
      
      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $gameId = $uri->getSegment(6);
      $playerId = $uri->getSegment(8);
      $userData = $this->request->decoded->userData;

      $posUserId = $this->request->getJsonVar('userId');
      $posGameId = $this->request->getJsonVar('gameId');
      $posPlayerId = $this->request->getJsonVar('playerId');
      $teamId = $this->request->getJsonVar('teamId');
      $matchId = $this->request->getJsonVar('matchId');

      if ($userId == $userData->id && $posGameId == $gameId && $posUserId == $userId && $posPlayerId == $playerId && !is_null($teamId) && !is_null($matchId)) {

        //- Check Match Is Ongoing Are Not
        $matchModel = new MatchesModel();
        $matchDetails = $matchModel->getMatcheById($matchId);
        if (count($matchDetails) > 0) {
          $tmpMatchDate = date("Y-m-d H:i:s", strtotime($matchDetails[0]->startTime." -30 minutes"));
          $currentTime = date("Y-m-d H:i:s");
        }else{
          $tmpMatchDate = date("Y-m-d H:i:s");
          $currentTime = date("Y-m-d H:i:s");
        }

        //- Check Match Player 
        $playerModel = new PlayersModel();
        $playerCheck = $playerModel->createMatchPlayerExist($matchId,$playerId,$teamId);

        //- Check Player Exist
        $contModel = new ContestModel();
        $draft = $contModel->getGameDraftPlaye($userId,$matchId,$gameId,$playerId);

        //- Total Draft Players
        $draftTotal = $contModel->getGameDraftsById($matchId,$gameId,$userId);

        if ($tmpMatchDate > $currentTime && count($playerCheck) > 0 && count($draft) == 0 && count($draftTotal) < 12) {

          //- Add Player
          $data['matchId'] = $matchId;
          $data['contestId'] = $gameId;
          $data['userId'] = $userId;
          $data['playerId'] = $playerId;
          $data['playerOrder'] = 0;
          $contModel->createDrafts($data);
          unset($data);
          
          $msg = array('status' => 200, 'msg' => 'success');
          return $this->response->setStatusCode(200)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

        }else{

          $msg = array('status' => 401, 'error' => 'Unauthorized');
            return $this->response->setStatusCode(401)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);        
        }

      }else{

        $msg = array('status' => 401, 'error' => 'Unauthorized');
          return $this->response->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);        

      } 

    }


    //- Remove Match Draft Player
    public function removeDraftPlayer()
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $gameId = $uri->getSegment(6);
      $playerId = $uri->getSegment(8);
      $userData = $this->request->decoded->userData;
      
      $posUserId = $this->request->getJsonVar('userId');
      $posGameId = $this->request->getJsonVar('gameId');
      $posPlayerId = $this->request->getJsonVar('playerId');
      $teamId = $this->request->getJsonVar('teamId');
      $matchId = $this->request->getJsonVar('matchId');

      if ($userId == $userData->id && $posGameId == $gameId && $posUserId == $userId && $posPlayerId == $playerId && !is_null($teamId) && !is_null($matchId)) {

        //- Check Match Is Ongoing Are Not
        $matchModel = new MatchesModel();
        $matchDetails = $matchModel->getMatcheById($matchId);
        if (count($matchDetails) > 0) {
          $tmpMatchDate = date("Y-m-d H:i:s", strtotime($matchDetails[0]->startTime." -30 minutes"));
          $currentTime = date("Y-m-d H:i:s");
        }else{
          $tmpMatchDate = date("Y-m-d H:i:s");
          $currentTime = date("Y-m-d H:i:s");
        }

        //- Check Match Player 
        $playerModel = new PlayersModel();
        $playerCheck = $playerModel->createMatchPlayerExist($matchId,$playerId,$teamId);

        //- Check Player Exist
        $contModel = new ContestModel();
        $draft = $contModel->getGameDraftPlaye($userId,$matchId,$gameId,$playerId);

        if ($tmpMatchDate > $currentTime && count($playerCheck) > 0 && count($draft) > 0 ) {

          $contModel->removeDraftPlayer($draft[0]->draftId);

          $msg = array('status' => 200, 'msg' => 'success');
          return $this->response->setStatusCode(200)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);


        }else{

          $msg = array('status' => 401, 'error' => 'Unauthorized');
            return $this->response->setStatusCode(401)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);        
        }

      }else{

        $msg = array('status' => 401, 'error' => 'Unauthorized');
          return $this->response->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);        

      } 

    }


    //- Get Game Drafted Player
    public function getDraftPlayers()
    {
      
      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $gameId = $uri->getSegment(6);
      $draftUId = $uri->getSegment(8);
      $userData = $this->request->decoded->userData;

      if ($userId == $userData->id) {

        $contModel = new ContestModel();
        $gameDetails = $contModel->getGameDetailsById($gameId,$userId);

        if (count($gameDetails) > 0) {

          if ($gameDetails[0]->userOne == $draftUId || $gameDetails[0]->userTwo == $draftUId) {
            
            //- Check Match Is Ongoing Are Not
            $matchModel = new MatchesModel();
            $matchDetails = $matchModel->getMatcheById($gameDetails[0]->matchId);
            if (count($matchDetails) > 0) {
            
              $tmpMatchDate = date("Y-m-d H:i:s", strtotime($matchDetails[0]->startTime." -30 minutes"));
              $currentTime = date("Y-m-d H:i:s");
            
            }else{
              
              $tmpMatchDate = date("Y-m-d H:i:s");
              $currentTime = date("Y-m-d H:i:s");

            }

            if ($tmpMatchDate > $currentTime ) {

              //- Get Draft Status
              $userDraftStatus = false;
              if ($gameDetails[0]->userOne == $draftUId) {

                if ($gameDetails[0]->oneDraft == '1') {
                  $userDraftStatus = true;
                }
                
              }else if ($gameDetails[0]->userTwo == $draftUId) {

                if ($gameDetails[0]->twoDraft == '1') {
                  $userDraftStatus = true;
                }

              }

              //- Get Drafted Players
              $draftedList = $contModel->getGameDraftsById($gameDetails[0]->matchId,$gameId,$draftUId);

              //- Get Match Details
              $playerModel = new PlayersModel(); 
              $matchModel = new MatchesModel();
              $matchDetails = $matchModel->getMatcheById($gameDetails[0]->matchId);

              //- Get Team Details
              $teamModel = new TeamsModel();
              $teamDetails = $teamModel->getMultipleTeams(array($matchDetails[0]->teamaId,$matchDetails[0]->teambId));

              $playerList = array();
              if (count($draftedList) > 0) {
                foreach ($draftedList as $dlkey => $dlvalue) {

                  if ($matchDetails[0]->matchType == '1') {
                    $tmpType = '1';
                  }else if ($matchDetails[0]->matchType == '2') {
                    $tmpType = '2';
                  }else if ($matchDetails[0]->matchType == '3') {
                    $tmpType = '3';
                  }else{
                    $tmpType = '2';
                  }                

                  $pdetails = $playerModel->getPlayersDetails($dlvalue->playerId,$tmpType);

                  if (count($pdetails) == 0) {
                    $pdetails = $playerModel->getPlayersDetailsNoScore($dlvalue->playerId);
                    if (count($pdetails) > 0) {
                      $pdetails[0]->avg = 0;
                      $pdetails[0]->hs = 0;
                    }
                  }

                  $matchPlayer = $playerModel->getMatchPlayer($gameDetails[0]->matchId,$dlvalue->playerId);

                  $conName = '';
                  if (count($matchPlayer) > 0 && count($teamDetails) > 0) {
                    foreach ($teamDetails as $tdkey => $tdvalue) {
                      if ($tdvalue->teamId == $matchPlayer[0]->teamId) {
                        $conName = $tdvalue->shortName;
                      }
                    }
                  }
                
                  array_push($playerList, array('name' => $pdetails[0]->name, 'image' => $pdetails[0]->faceImageId, 'country' => $conName,'avg' => $pdetails[0]->avg, 'hs' => $pdetails[0]->hs, 'orderId' => $dlvalue->playerOrder, 'matchId' => $gameDetails[0]->matchId, 'playerId' => $dlvalue->playerId, 'teamId' => $matchPlayer[0]->teamId, 'draftId' => $dlvalue->draftId));

                }
              }

              if (count($playerList) > 0) {
                $key_values = array_column($playerList, 'orderId'); 
                array_multisort($key_values, SORT_ASC, $playerList);
              }

              $userModel = new UserModel();
              $details = $userModel->getUserById($draftUId);

              $msg = array('status' => 200, 'playerList' =>  $playerList, 'isDrafted' => $userDraftStatus, 'name' => $details[0]->name, 'profile' => getenv('s3Url').getenv('s3Bucket').'/'.getenv('userProf').$details[0]->profileImg);
              return $this->response->setStatusCode(200)
                          ->setHeader('Access-Control-Allow-Origin', '*')
                          ->setHeader('Access-Control-Allow-Headers', 'Origin')
                          ->setContentType('application/json', 'utf-8')
                          ->setJSON($msg);

            }else{

              $msg = array('status' => 401, 'error' => 'Unauthorized');
              return $this->response->setStatusCode(401)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

            }

          }else{
            $msg = array('status' => 401, 'error' => 'Unauthorized');
              return $this->response->setStatusCode(401)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);
          }    

        }else{
          $msg = array('status' => 401, 'error' => 'Unauthorized');
            return $this->response->setStatusCode(401)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);        
        }

      }else{

        $msg = array('status' => 401, 'error' => 'Unauthorized');
          return $this->response->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);        

      } 

    }


    //- Save Drafted Players
    public function saveDraftPlayers()
    {
      
      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $gameId = $uri->getSegment(6);
      $userData = $this->request->decoded->userData;

      $gameId = $this->request->getJsonVar('gameId');

      if ($userId == $userData->id && $gameId == $gameId) {

        $contModel = new ContestModel();
        $gameDetails = $contModel->getGameDetailsById($gameId,$userId);

        if (count($gameDetails) > 0) {

          //- Check Match Is Ongoing Are Not
          $matchModel = new MatchesModel();
          $matchDetails = $matchModel->getMatcheById($gameDetails[0]->matchId);
          if (count($matchDetails) > 0) {
            $tmpMatchDate = date("Y-m-d H:i:s", strtotime($matchDetails[0]->startTime." -30 minutes"));
            $currentTime = date("Y-m-d H:i:s");
          }else{
            $tmpMatchDate = date("Y-m-d H:i:s");
            $currentTime = date("Y-m-d H:i:s");
          }

          $playerList = $this->request->getJsonVar('playerList');

          if ($tmpMatchDate > $currentTime && is_array($playerList) && count($playerList) == 12) {

            //- Get Drafted Players
            $draftedList = $contModel->getGameDraftsById($gameDetails[0]->matchId,$gameId,$userId);

            if (count($draftedList) > 0) {

              //- Player Data Validation
              $isFakePlayer = true;
              foreach ($playerList as $pkey => $pvalue) {
                
                $isFakePlayer = true;
                
                foreach ($draftedList as $dkey => $dvalue) {
                  if ($dvalue->draftId == $pvalue->draftId && $dvalue->playerId == $pvalue->playerId  ) {
                    $isFakePlayer = false;
                  }
                }

                if ($isFakePlayer) {
                  break;
                }

              }

              if (!$isFakePlayer) {
                
                //- Update Player Draft Order
                foreach ($playerList as $pkey => $pvalue) {
                  $contModel->updateDraftPlayerOrder($pvalue->draftId,$pvalue->playerId,$pvalue->orderId);
                }

                //- Update Player Draft Status
                if ($gameDetails[0]->userOne == $userId) {
                  $contModel->updateUserDraftStatus($gameId,'one');
                }else if ($gameDetails[0]->userTwo == $userId) {
                  $contModel->updateUserDraftStatus($gameId,'two');
                }
                
                $msg = array('status' => 200, 'msg' => 'success');
                return $this->response->setStatusCode(200)
                            ->setHeader('Access-Control-Allow-Origin', '*')
                            ->setHeader('Access-Control-Allow-Headers', 'Origin')
                            ->setContentType('application/json', 'utf-8')
                            ->setJSON($msg);                  
              
              }else{
                
                $msg = array('status' => 401, 'error' => 'Unauthorized');
                return $this->response->setStatusCode(401)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);     
              }           
              
            }else{
              $msg = array('status' => 401, 'error' => 'Unauthorized');
              return $this->response->setStatusCode(401)
                        ->setHeader('Access-Control-Allow-Origin', '*')
                        ->setHeader('Access-Control-Allow-Headers', 'Origin')
                        ->setContentType('application/json', 'utf-8')
                        ->setJSON($msg); 
            }

          }else{
            
            $msg = array('status' => 401, 'error' => 'Unauthorized');
            return $this->response->setStatusCode(401)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg); 
          }

        }else{

          $msg = array('status' => 401, 'error' => 'Unauthorized');
          return $this->response->setStatusCode(401)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg); 

        }
      
      }else{

        $msg = array('status' => 401, 'error' => 'Unauthorized');
          return $this->response->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);        

      }

    }


    //- Update Draft Players
    public function updateDraftPlayers()
    {
      
      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $gameId = $uri->getSegment(6);
      $userData = $this->request->decoded->userData;

      $gameId = $this->request->getJsonVar('gameId');

      if ($userId == $userData->id && $gameId == $gameId) {

        $contModel = new ContestModel();
        $gameDetails = $contModel->getGameDetailsById($gameId,$userId);
        
        if (count($gameDetails) > 0) {

          //- Check Match Is Ongoing Are Not
          $matchModel = new MatchesModel();
          $matchDetails = $matchModel->getMatcheById($gameDetails[0]->matchId);

          if (count($matchDetails) > 0) {
            $tmpMatchDate = date("Y-m-d H:i:s", strtotime($matchDetails[0]->startTime." -30 minutes"));
            $currentTime = date("Y-m-d H:i:s");
          }else{
            $tmpMatchDate = date("Y-m-d H:i:s");
            $currentTime = date("Y-m-d H:i:s");
          }

          $playerList = $this->request->getJsonVar('playerList');

          if ($tmpMatchDate > $currentTime && is_array($playerList) && count($playerList) == 12) {

            //- Remove Drafts
            $contModel->removeUserMatchContestDraft($gameDetails[0]->matchId,$gameDetails[0]->pairId,$userId);

            //- Create Users 
            foreach ($playerList as $pkey => $pvalue) {

              $plData['matchId'] = $gameDetails[0]->matchId;
              $plData['contestId'] = $gameDetails[0]->pairId;
              $plData['userId'] = $userId;
              $plData['playerId'] = $pvalue;
              $plData['playerOrder'] = $pkey+1;

              $contModel->createDrafts($plData);
              unset($plData);
            }

            //- Get All Draft Players
            $draftedList = $contModel->getGameDraftsById($gameDetails[0]->matchId,$gameDetails[0]->pairId,$userId);
            
            //- Get Team Details
            $playerModel = new PlayersModel(); 
            $teamModel = new TeamsModel();
            $teamDetails = $teamModel->getMultipleTeams(array($matchDetails[0]->teamaId,$matchDetails[0]->teambId));

            $playerList = array();
            if (count($draftedList) > 0) {
              foreach ($draftedList as $dlkey => $dlvalue) {

                if ($matchDetails[0]->matchType == '1') {
                  $tmpType = '1';
                }else if ($matchDetails[0]->matchType == '2') {
                  $tmpType = '2';
                }else if ($matchDetails[0]->matchType == '3') {
                  $tmpType = '3';
                }else{
                  $tmpType = '2';
                }                   

                $pdetails = $playerModel->getPlayersDetails($dlvalue->playerId,$tmpType);

                if (count($pdetails) == 0) {
                  $pdetails = $playerModel->getPlayersDetailsNoScore($dlvalue->playerId);
                  if (count($pdetails) > 0) {
                    $pdetails[0]->avg = 0;
                    $pdetails[0]->hs = 0;
                  }
                }

                $matchPlayer = $playerModel->getMatchPlayer($gameDetails[0]->matchId,$dlvalue->playerId);

                $conName = '';
                if (count($matchPlayer) > 0 && count($teamDetails) > 0) {
                  foreach ($teamDetails as $tdkey => $tdvalue) {
                    if ($tdvalue->teamId == $matchPlayer[0]->teamId) {
                      $conName = $tdvalue->shortName;
                    }
                  }
                }
              
                array_push($playerList, array('name' => $pdetails[0]->nickName, 'image' => $pdetails[0]->faceImageId, 'country' => $conName,'avg' => $pdetails[0]->avg, 'hs' => $pdetails[0]->hs, 'orderId' => $dlvalue->playerOrder, 'matchId' => $gameDetails[0]->matchId, 'playerId' => $dlvalue->playerId, 'teamId' => $matchPlayer[0]->teamId, 'draftId' => $dlvalue->draftId));

              }
            }


            $msg = array('status' => 200, 'msg' => $playerList);
            return $this->response->setStatusCode(200)
                        ->setHeader('Access-Control-Allow-Origin', '*')
                        ->setHeader('Access-Control-Allow-Headers', 'Origin')
                        ->setContentType('application/json', 'utf-8')
                        ->setJSON($msg);
          }else{
            
            $msg = array('status' => 401, 'error' => 'Unauthorized');
            return $this->response->setStatusCode(401)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg); 
          }          

        }else{

          $msg = array('status' => 401, 'error' => 'Unauthorized');
          return $this->response->setStatusCode(401)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg); 

        }


      }else{

        $msg = array('status' => 401, 'error' => 'Unauthorized');
          return $this->response->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);        

      }
    }


    //- Get Match Players11
    public function gatMatchPlayers11()
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $gameId = $uri->getSegment(6);
      $userData = $this->request->decoded->userData;

      $utcCTime = $this->request->getVar('utcCTime');
      $tmpSerTime = gmdate("Y-m-d H:i:s");
      $tmpClinTime = date("Y-m-d H:i:s", strtotime('+4 seconds',  strtotime($utcCTime)));

      if ($userId == $userData->id && $tmpClinTime > $tmpSerTime) {

        $playerModel = new PlayersModel();
        $teamsModel = new TeamsModel();
        $contModel = new ContestModel();
        $matchModel = new MatchesModel();
        $userModel = new UserModel();
        $conFinPlayer = new ContestFinalPlayers();
        
        $gameDetails = $contModel->getGameDetailsById($gameId,$userId);
        $tmpCurTime = gmdate("Y-m-d H:i:s");

        if (count($gameDetails) > 0) {   

          $this->db->transStart();

          $matchDetails = $matchModel->getMatcheById($gameDetails[0]->matchId);
          
          $tmpATeam = $gameDetails[0]->userOneTeam;
          $tmpBTeam = $gameDetails[0]->userTwoTeam;

          //- Team One Details
          $teamOneDetails = $teamsModel->getTeamById($gameDetails[0]->userOneTeam);
          
          //- Team One Details
          $teamTwoDetails = $teamsModel->getTeamById($gameDetails[0]->userTwoTeam);
          
          if ($gameDetails[0]->userOne == $userId) {

            //- Get Team A Players
            $teamOne = $playerModel->getTeamAllPlaye11($gameDetails[0]->matchId,$gameDetails[0]->userOneTeam);

            //- Get Team B Players
            $teamTwo = $playerModel->getTeamAllPlaye11($gameDetails[0]->matchId,$gameDetails[0]->userTwoTeam);

            //- Oponent Details 
            $opnDetails = $userModel->getUserById($gameDetails[0]->userTwo);

            //- Team One Final Players
            $teamOneFinal = $conFinPlayer->getFinalGamePlayers($gameDetails[0]->matchId,$gameDetails[0]->pairId,$gameDetails[0]->userOne); 

            //- Team Two Final Players
            $teamTwoFinal = $conFinPlayer->getFinalGamePlayers($gameDetails[0]->matchId,$gameDetails[0]->pairId,$gameDetails[0]->userTwo);

          }else {
            

            //- Get Team B Players
            $teamOne = $playerModel->getTeamAllPlaye11($gameDetails[0]->matchId,$gameDetails[0]->userTwoTeam);

            //- Get Team B Players
            $teamTwo = $playerModel->getTeamAllPlaye11($gameDetails[0]->matchId,$gameDetails[0]->userOneTeam);

            //- Oponent Details 
            $opnDetails = $userModel->getUserById($gameDetails[0]->userOne);

            //- Team One Final Players
            $teamOneFinal = $conFinPlayer->getFinalGamePlayers($gameDetails[0]->matchId,$gameDetails[0]->pairId,$gameDetails[0]->userTwo); 

            //- Team Two Final Players
            $teamTwoFinal = $conFinPlayer->getFinalGamePlayers($gameDetails[0]->matchId,$gameDetails[0]->pairId,$gameDetails[0]->userOne);

          }

          $teamA11 = array();
          $teamABan = array();

          $teamB11 = array();
          $teamBBan = array();

          $myPlayers = array();
          $oponLPlayers = array();

          $toUpdt = array();
          if (!empty($teamOne) > 0) {
            foreach ($teamOne as $tokey => $tovalue) {
              if (count($teamOneFinal) > 0) {
                foreach ($teamOneFinal as $tofkey => $ftofvalue) {
                  if ($tovalue->playerId == $ftofvalue->playerId) {
                    if ($tmpCurTime >= $ftofvalue->endTime) {
                      array_push($toUpdt, $ftofvalue->fpId);
                    }
                  }
                }
              }
              if (count($teamTwoFinal) > 0) {
                foreach ($teamTwoFinal as $ttofkey => $fttofvalue) {
                  if ($tovalue->playerId == $fttofvalue->playerId) { 
                    if ($tmpCurTime >= $fttofvalue->endTime) {
                      array_push($toUpdt, $fttofvalue->fpId);
                    }
                  }
                }
              }
            }
          }

          if (!empty($teamTwo) > 0) {
            foreach ($teamTwo as $ttkey => $ttvalue) {
              if (count($teamOneFinal) > 0) {
                foreach ($teamOneFinal as $tofkey => $stofvalue) {
                  if ($ttvalue->playerId == $stofvalue->playerId) {
                    if ($tmpCurTime >= $stofvalue->endTime) {
                      array_push($toUpdt, $stofvalue->fpId);
                    }
                  }
                }
              }
              if (count($teamTwoFinal) > 0) {
                foreach ($teamTwoFinal as $ttofkey => $sttofvalue) {
                  if ($ttvalue->playerId == $sttofvalue->playerId) {
                    if ($tmpCurTime >= $sttofvalue->endTime) {
                      array_push($toUpdt, $sttofvalue->fpId);
                    }
                  }
                }
              }
            }
          } 

          if (count($toUpdt) > 0) {
            $conFinPlayer->updateFinPlayerStatus($toUpdt);
          }

          if (!empty($teamOne) > 0) {

            foreach ($teamOne as $tokey => $tovalue) {

              if ($matchDetails[0]->matchType == '1') {
                $tmpType = '2';
              }else if ($matchDetails[0]->matchType == '2') {
                $tmpType = '3';
              }else if ($matchDetails[0]->matchType == '3') {
                $tmpType = '1';
              }else{
                $tmpType = '2';
              }

              $pdetails = $playerModel->getPlayersDetails($tovalue->playerId,$tmpType);

              if (count($pdetails) == 0) {
                $pdetails = $playerModel->getPlayersDetailsNoScore($tovalue->playerId);
                if (count($pdetails) > 0) {
                  $pdetails[0]->avg = 0;
                  $pdetails[0]->hs = 0;
                }
              }

              $teamName = '';
              if ($teamOneDetails[0]->teamId == $tovalue->teamId) {
                $teamName = $teamOneDetails[0]->shortName;
              }else if ($teamTwoDetails[0]->teamId == $tovalue->teamId) {
                $teamName = $teamTwoDetails[0]->shortName;
              }

              $isSel = -1;
              $selBy = 0;
              $endTime = '';
              $orderBy = '';
              
              if (count($teamOneFinal) > 0) {
                foreach ($teamOneFinal as $tofkey => $ftofvalue) {
                  if ($tovalue->playerId == $ftofvalue->playerId) {
                    
                    if ($tmpCurTime >= $ftofvalue->endTime) {
                      $isSel = '1';
                      //- $conFinPlayer->updatePlayerStatus($ftofvalue->matchId,$ftofvalue->contestId,$ftofvalue->userId,$ftofvalue->playerId);
                    }else{
                      $isSel = $ftofvalue->status;
                    }

                    $selBy = $ftofvalue->userId;
                    $endTime = $ftofvalue->endTime;
                    $orderBy = $ftofvalue->order;

                    if ($ftofvalue->userId == $userData->id && $isSel == '0') {
                      array_push($myPlayers, array('playerId' => $ftofvalue->playerId, 'order' => $ftofvalue->order, 'endTime' => $ftofvalue->endTime, 'isSel' => $isSel, 'userId' => $ftofvalue->userId));
                    }                    

                  }
                }
              }

              if (count($teamTwoFinal) > 0) {
                foreach ($teamTwoFinal as $ttofkey => $fttofvalue) {
                  if ($tovalue->playerId == $fttofvalue->playerId) {                    
                    if ($tmpCurTime >= $fttofvalue->endTime) {
                      $isSel = '1';
                      //- $conFinPlayer->updatePlayerStatus($fttofvalue->matchId,$fttofvalue->contestId,$fttofvalue->userId,$fttofvalue->playerId);
                    }else{
                      $isSel = $fttofvalue->status;
                    }
                    
                    $selBy = $fttofvalue->userId;
                    $endTime = $fttofvalue->endTime;
                    $orderBy = $fttofvalue->order;

                    if ($fttofvalue->userId == $userData->id && $isSel == '0') {
                      array_push($myPlayers, array('playerId' => $fttofvalue->playerId, 'order' => $fttofvalue->order, 'endTime' => $fttofvalue->endTime, 'isSel' => $isSel, 'userId' => $fttofvalue->userId));
                    }

                  }
                }
              }

              if ($selBy != $userData->id && $isSel == '0') {
                array_push($oponLPlayers, array('playerId' => $tovalue->playerId, 'order' => $orderBy, 'endTime' => $endTime, 'selBy' => $selBy, 'isSel' => '1'));
              }              
          
              if ($tmpBTeam == $tovalue->teamId) {
                if ($tovalue->isBench == '1') {
                  array_push($teamBBan, array('name' => $pdetails[0]->name, 'imageId' => $pdetails[0]->faceImageId, 'teamName' => $teamName, 'avg' => $pdetails[0]->avg, 'hs' => $pdetails[0]->hs, 'orderId' => $orderBy, 'playerId' => $tovalue->playerId, 'isSel' => $isSel, 'selBy' => $selBy, 'endTime' => $endTime, 'playOrderId' => $tovalue->orderId ));
                }else{ 
                  array_push($teamB11, array('name' => $pdetails[0]->name, 'imageId' => $pdetails[0]->faceImageId, 'teamName' => $teamName, 'avg' => $pdetails[0]->avg, 'hs' => $pdetails[0]->hs, 'orderId' => $orderBy, 'playerId' => $tovalue->playerId, 'isSel' => $isSel, 'selBy' => $selBy, 'endTime' => $endTime, 'playOrderId' => $tovalue->orderId ));
                }
              }

              if ($tmpATeam == $tovalue->teamId) {
                if ($tovalue->isBench == '1') {
                  array_push($teamABan, array('name' => $pdetails[0]->name, 'imageId' => $pdetails[0]->faceImageId, 'teamName' => $teamName, 'avg' => $pdetails[0]->avg, 'hs' => $pdetails[0]->hs, 'orderId' => $orderBy, 'playerId' => $tovalue->playerId, 'isSel' => $isSel, 'selBy' => $selBy, 'endTime' => $endTime, 'playOrderId' => $tovalue->orderId ));
                }else{
                  array_push($teamA11, array('name' => $pdetails[0]->name, 'imageId' => $pdetails[0]->faceImageId, 'teamName' => $teamName, 'avg' => $pdetails[0]->avg, 'hs' => $pdetails[0]->hs, 'orderId' => $orderBy, 'playerId' => $tovalue->playerId, 'isSel' => $isSel, 'selBy' => $selBy, 'endTime' => $endTime, 'playOrderId' => $tovalue->orderId ));
                }
              }
      
            }
          }

          if (!empty($teamTwo) > 0) {
            foreach ($teamTwo as $ttkey => $ttvalue) {

              if ($matchDetails[0]->matchType == '1') {
                $tmpType = '2';
              }else if ($matchDetails[0]->matchType == '2') {
                $tmpType = '3';
              }else if ($matchDetails[0]->matchType == '3') {
                $tmpType = '1';
              }else{
                $tmpType = '2';
              }

              $pdetails = $playerModel->getPlayersDetails($ttvalue->playerId,$tmpType);

              if (count($pdetails) == 0) {
                $pdetails = $playerModel->getPlayersDetailsNoScore($ttvalue->playerId);
                if (count($pdetails) > 0) {
                  $pdetails[0]->avg = 0;
                  $pdetails[0]->hs = 0;
                }
              }

              $teamName = '';
              if ($teamOneDetails[0]->teamId == $ttvalue->teamId) {
                $teamName = $teamOneDetails[0]->shortName;
              }else if ($teamTwoDetails[0]->teamId == $ttvalue->teamId) {
                $teamName = $teamTwoDetails[0]->shortName;
              }
          
              $isSel = -1;
              $selBy = 0;
              $endTime = '';
              $orderBy = '';
              
              if (count($teamOneFinal) > 0) {
                foreach ($teamOneFinal as $tofkey => $stofvalue) {
                  if ($ttvalue->playerId == $stofvalue->playerId) {
                    
                    if ($tmpCurTime >= $stofvalue->endTime) {
                      $isSel = '1';
                      //- $conFinPlayer->updatePlayerStatus($stofvalue->matchId,$stofvalue->contestId,$stofvalue->userId,$stofvalue->playerId);
                    }else{
                      $isSel = $stofvalue->status;
                    }

                    $selBy = $stofvalue->userId;
                    $endTime = $stofvalue->endTime;
                    $orderBy = $stofvalue->order;
                    
                    if ($stofvalue->userId == $userData->id && $isSel == '0') {
                      array_push($myPlayers, array('playerId' => $stofvalue->playerId, 'order' => $stofvalue->order, 'endTime' => $stofvalue->endTime, 'isSel' => $isSel, 'userId' => $stofvalue->userId));
                    }                 

                  }
                }
              }

              if (count($teamTwoFinal) > 0) {
                foreach ($teamTwoFinal as $ttofkey => $sttofvalue) {
                  if ($ttvalue->playerId == $sttofvalue->playerId) {
                    
                    if ($tmpCurTime >= $sttofvalue->endTime) {
                      $isSel = '1';
                      //- $conFinPlayer->updatePlayerStatus($sttofvalue->matchId,$sttofvalue->contestId,$sttofvalue->userId,$sttofvalue->playerId);
                    }else{
                      $isSel = $sttofvalue->status;
                    }
                    
                    $selBy = $sttofvalue->userId;
                    $endTime = $sttofvalue->endTime;
                    $orderBy = $sttofvalue->order;

                    if ($sttofvalue->userId == $userData->id && $isSel == '0') {
                      array_push($myPlayers, array('playerId' => $sttofvalue->playerId, 'order' => $sttofvalue->order, 'endTime' => $sttofvalue->endTime, 'isSel' => $isSel, 'userId' => $sttofvalue->userId));
                    }

                  }
                }
              }
          
              if ($selBy != $userData->id && $isSel == '0') {
                array_push($oponLPlayers, array('playerId' => $ttvalue->playerId, 'order' => $orderBy, 'endTime' => $endTime, 'selBy' => $selBy, 'isSel' => '1'));
              }

              if ($tmpBTeam == $ttvalue->teamId) {
                if ($ttvalue->isBench == '1') {
                  array_push($teamBBan, array('name' => $pdetails[0]->name, 'imageId' => $pdetails[0]->faceImageId, 'teamName' => $teamName, 'avg' => $pdetails[0]->avg, 'hs' => $pdetails[0]->hs, 'orderId' => $orderBy, 'playerId' => $ttvalue->playerId, 'isSel' => $isSel, 'selBy' => $selBy, 'endTime' => $endTime, 'playOrderId' => $ttvalue->orderId ));
                }else{
                  array_push($teamB11, array('name' => $pdetails[0]->name, 'imageId' => $pdetails[0]->faceImageId, 'teamName' => $teamName, 'avg' => $pdetails[0]->avg, 'hs' => $pdetails[0]->hs, 'orderId' => $orderBy, 'playerId' => $ttvalue->playerId, 'isSel' => $isSel, 'selBy' => $selBy, 'endTime' => $endTime, 'playOrderId' => $ttvalue->orderId ));
                }
              }

              if ($tmpATeam == $ttvalue->teamId) {
                if ($ttvalue->isBench == '1') {
                  array_push($teamABan, array('name' => $pdetails[0]->name, 'imageId' => $pdetails[0]->faceImageId, 'teamName' => $teamName, 'avg' => $pdetails[0]->avg, 'hs' => $pdetails[0]->hs, 'orderId' => $orderBy, 'playerId' => $ttvalue->playerId, 'isSel' => $isSel, 'selBy' => $selBy, 'endTime' => $endTime, 'playOrderId' => $ttvalue->orderId ));
                }else{
                  array_push($teamA11, array('name' => $pdetails[0]->name, 'imageId' => $pdetails[0]->faceImageId, 'teamName' => $teamName, 'avg' => $pdetails[0]->avg, 'hs' => $pdetails[0]->hs, 'orderId' => $orderBy, 'playerId' => $ttvalue->playerId, 'isSel' => $isSel, 'selBy' => $selBy, 'endTime' => $endTime, 'playOrderId' => $ttvalue->orderId ));
                }
              }
      
            }
          }

          if (count($teamA11) > 0) {
            $key_values = array_column($teamA11, 'playOrderId'); 
            array_multisort($key_values, SORT_ASC, $teamA11);
          }

          if (count($teamB11) > 0) {
            $key_values = array_column($teamB11, 'playOrderId');
            array_multisort($key_values, SORT_ASC, $teamB11);
          }

          if (count($myPlayers) > 0) {
            $key_values = array_column($myPlayers, 'order'); 
            array_multisort($key_values, SORT_ASC, $myPlayers);
          }

          $tmpCurTime = gmdate("Y-m-d H:i:s");

          $myNxtRecords = $conFinPlayer->getLatestPlayers($gameDetails[0]->matchId,$gameDetails[0]->pairId,$userId,$tmpCurTime);

          $nxtTime = '';
          $nxtTimeEnd = '';
          $nxtOpt = '';
          if (count($myNxtRecords) > 0) {
            $nxtTime = $myNxtRecords[0]->startTime;
            $nxtTimeEnd = $myNxtRecords[0]->endTime;
            if ($myNxtRecords[0]->userId == $userId) {
              $nxtOpt = 'Open';
            }else{
              $nxtOpt = 'Close';
            }
          }

          $oponLPlayer = '';
          if (count($oponLPlayers) > 0) {
            $key_values = array_column($oponLPlayers, 'order'); 
            array_multisort($key_values, SORT_ASC, $oponLPlayers);
            $oponLPlayer = $oponLPlayers[0];
          }

          $this->db->transComplete();
          if ($this->db->transStatus() === false) {
            $this->db->transRollback();
            $msg = array();
            return $this->response->setStatusCode(429)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);
          } else {
            $this->db->transCommit();
            $msg = array('status' => 200, 'selStartTm' => $gameDetails[0]->selStartTm,'myPlayers' => $myPlayers, 'teamA11' => $teamA11, 'teamABan' => $teamABan, 'teamB11' => $teamB11, 'teamBBan' => $teamBBan, 'opnName' => $opnDetails[0]->name, 'opnImg' => getenv('s3Url').getenv('s3Bucket').'/'.getenv('userProf').$opnDetails[0]->profileImg, 'nxtTime' => $nxtTime, 'nxtOpt' => $nxtOpt, 'nxtTimeEnd' => $nxtTimeEnd, 'serveCurTime' => $tmpCurTime, 'oponLPlayer' => $oponLPlayer);
            return $this->response->setStatusCode(200)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);
          }

        }else{

          $msg = array('status' => 401, 'error' => 'Unauthorized');
          return $this->response->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);
        }
 
      }else{

        if ($tmpSerTime >= $tmpClinTime) {

          $msg = array('status' => 202, 'error' => 'Slow Internet');
          return $this->response->setStatusCode(202)
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


    //- Update Game Final Players
    public function updateFinalPlayers()
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $gameId = $uri->getSegment(6);
      $userData = $this->request->decoded->userData;

      $pgameId = $this->request->getJsonVar('gameId');
      $playerId = $this->request->getJsonVar('playerId');
      $utcCTime = $this->request->getJsonVar('utcCTime');

      $tmpSerTime = gmdate("Y-m-d H:i:s");
      $tmpClinTime = date("Y-m-d H:i:s", strtotime('+4 seconds',  strtotime($utcCTime)));

      if ($userId == $userData->id && !is_null($pgameId) && $gameId == $pgameId && !is_null($playerId) && $tmpClinTime > $tmpSerTime) {


        //- Check Game Exist
        $contModel = new ContestModel();
        $contFinPlayModel = new ContestFinalPlayers();
        $gameDetails = $contModel->getGameForFinalSelById($gameId,$userId);

        if (count($gameDetails) > 0) {

          //- Check Match Is Ongoing Are Not
          $matchLookModel = new MatchesLookupModel();
          $matchDetails = $matchLookModel->checkMatchLookup($gameDetails[0]->matchId);

          //- Check Player Exist 
          $playExist = $contFinPlayModel->checkPlayerSelected($gameDetails[0]->matchId,$gameDetails[0]->pairId,$playerId);

          //- Check Match Player
          if (count($matchDetails) > 0 && count($playExist) == 0) {
            
            $this->db->transStart();

            $myUser = '';
            $opnUser = '';
            $myTeam = '';
            $opnTeam = '';
            if ($gameDetails[0]->userOne == $userId) {
              $myUser = $gameDetails[0]->userOne;
              $opnUser = $gameDetails[0]->userTwo;
              $myTeam = $gameDetails[0]->userOneTeam;
              $opnTeam = $gameDetails[0]->userTwoTeam;
            }else if ($gameDetails[0]->userTwo == $userId) {
              $myUser = $gameDetails[0]->userTwo;
              $opnUser = $gameDetails[0]->userOne;
              $myTeam = $gameDetails[0]->userTwoTeam;
              $opnTeam = $gameDetails[0]->userOneTeam;
            }


            //- Auto Assign Old Final Players For First User (My & Opnent)

            //- ************** Start **************
            
            $toUpdt = array();

            $tmpMyFDrft = $contFinPlayModel->getSelGamePlayers($gameDetails[0]->matchId,$gameDetails[0]->pairId,$myUser);

            $tmpOpnFDrft = $contFinPlayModel->getSelGamePlayers($gameDetails[0]->matchId,$gameDetails[0]->pairId,$opnUser);

            if (count($tmpMyFDrft)  == 0 && count($tmpOpnFDrft) == 0) {
              
              $FTtmpCurTime = gmdate("Y-m-d H:i:s");

              //- My Final Players
              $firstTeamOneFinal = $contFinPlayModel->getFinalGamePlayers($gameDetails[0]->matchId,$gameDetails[0]->pairId,$myUser); 

              //- Oponent Final Players
              $firstTeamTwoFinal = $contFinPlayModel->getFinalGamePlayers($gameDetails[0]->matchId,$gameDetails[0]->pairId,$opnUser);

              if (count($firstTeamOneFinal) > 0) {
                foreach ($firstTeamOneFinal as $Ftofkey => $Ftofvalue) {
                  if ($FTtmpCurTime >= $Ftofvalue->endTime && $Ftofvalue->playerId != $playerId) {

                    array_push($toUpdt, $Ftofvalue->fpId);

                    //- $contFinPlayModel->updatePlayerStatus($Ftofvalue->matchId,$Ftofvalue->contestId,$Ftofvalue->userId,$Ftofvalue->playerId);
                  }
                }
              }

              $FTtmpCurTime = gmdate("Y-m-d H:i:s");
              if (count($firstTeamTwoFinal) > 0) {
                foreach ($firstTeamTwoFinal as $FtTfkey => $FtTfvalue) {
                  if ($FTtmpCurTime >= $FtTfvalue->endTime && $FtTfvalue->playerId != $playerId) {
                    
                    array_push($toUpdt, $FtTfvalue->fpId);

                    //- $contFinPlayModel->updatePlayerStatus($FtTfvalue->matchId,$FtTfvalue->contestId,$FtTfvalue->userId,$FtTfvalue->playerId);
                  }
                }
              }

              $myFDrft = $contFinPlayModel->getSelGamePlayers($gameDetails[0]->matchId,$gameDetails[0]->pairId,$myUser);

              $opnFDrft = $contFinPlayModel->getSelGamePlayers($gameDetails[0]->matchId,$gameDetails[0]->pairId,$opnUser);
              
            }else{
              
              //- Get My Player Final List
              $myFDrft = $tmpMyFDrft;

              //- Get Opn Player Final List
              $opnFDrft = $tmpOpnFDrft;

            }

            if (count($toUpdt) > 0) {
              $contFinPlayModel->updateFinPlayerStatus($toUpdt);
            }


            //- ************** Enf ************** 
            
            if (count($myFDrft) < 6) {

              //- Get Player One Drafts
              $myOldDrft = $contModel->getGameDraftsById($gameDetails[0]->matchId,$gameDetails[0]->pairId,$myUser);            

              //- Get Player Two Drafts
              $opnOldDrft = $contModel->getGameDraftsById($gameDetails[0]->matchId,$gameDetails[0]->pairId,$opnUser);

              //- Find Final Rearrange Draft For One && For Two
              $finalTeam = CreateFinalModifiedDraftPlayers($myOldDrft,$opnOldDrft,$myFDrft,$opnFDrft,$playerId);

              //- Count Of My Final Draft, Opponent Final Draft
              $seleStartTime = gmdate("Y-m-d H:i:s");

              $tmpLast = end($opnFDrft);
              if (!empty($tmpLast)) {
                $startTime = date("Y-m-d H:i:s", strtotime('+1 seconds',  strtotime($tmpLast->endTime)));
              }else{
                $startTime = $gameDetails[0]->selStartTime;
              }
              

              $tmpPlaySel = array();

              //- Update Final Player
              $tmpData['matchId'] = $gameDetails[0]->matchId;
              $tmpData['contestId'] = $gameDetails[0]->pairId;
              $tmpData['userId'] = $myUser;
              $tmpData['playerId'] = $playerId;
              $tmpData['order'] = count($myFDrft)+1;
              if ($startTime != false) {
                $tmpData['startTime'] = $startTime;
              }
              $tmpData['endTime'] = $seleStartTime;
              $contFinPlayModel->createPlayersFinal($tmpData);
              $tmpPlaySel = array('playerId' => $playerId, 'order' => $tmpData['order'], 'endTime' => $seleStartTime );
              unset($tmpData);
              
              $tmpMyFinal = array();
              if ($finalTeam['myFinal'] > 0) {

                //- Delete All My Only Final Drafts
                $contFinPlayModel->removePlayerDrafts($gameDetails[0]->matchId, $gameDetails[0]->pairId, $myUser);
                
                $newMyDraftCou = 6 - (count($myFDrft) + 1);
                if ($newMyDraftCou > 0) {
                  $newMyDraft = array_slice($finalTeam['myFinal'], 0, $newMyDraftCou);
                }else{
                  $newMyDraft = $finalTeam['myFinal'];
                }
                
                
                $newMyDraftCou = count($myFDrft) + 2;
                if (count($newMyDraft) > 0) {
                  foreach ($newMyDraft as $mfskey => $mfsvalue) {

                    $addTime = 30 * (($mfskey + 1) * 2);
                    $addTime = '+'.$addTime.' seconds';
                    $endTime = date("Y-m-d H:i:s", strtotime($addTime,  strtotime($seleStartTime)));

                    $startTime = date("Y-m-d H:i:s", strtotime('-29 seconds',  strtotime($endTime)));
                    
                    $tmpData['matchId'] = $gameDetails[0]->matchId;
                    $tmpData['contestId'] = $gameDetails[0]->pairId;
                    $tmpData['userId'] = $myUser;
                    $tmpData['playerId'] = $mfsvalue->playerId;
                    $tmpData['order'] = $newMyDraftCou;
                    $tmpData['startTime'] = $startTime;
                    $tmpData['endTime'] = $endTime;

                    $contFinPlayModel->createFinalPlayers($tmpData);
                    unset($tmpData);
                    array_push($tmpMyFinal, array('playerId' => $mfsvalue->playerId, 'order' => $newMyDraftCou, 'endTime' => $endTime ));     
                    $newMyDraftCou++;

                  }
                }

              }
              
              $tmpOpFinal = array();
              if ($finalTeam['opnFinal'] > 0) {

                //- Delete All My Only Final Drafts
                $contFinPlayModel->removePlayerDrafts($gameDetails[0]->matchId, $gameDetails[0]->pairId, $opnUser);

                $newOpnDraftCou = 6 - count($opnFDrft);
                if ($newOpnDraftCou > 0) {
                  $newOpnDraft = array_slice($finalTeam['opnFinal'], 0, $newOpnDraftCou);
                }else{
                  $newOpnDraft = $finalTeam['opnFinal'];
                }
                
                $newOpnDraftCou = count($opnFDrft) + 1;

                if (count($newOpnDraft) > 0) {
                  foreach ($newOpnDraft as $mopskey => $mopsvalue) {

                    $addTime = 30 + ($mopskey * 60);
                    $addTime = '+'.$addTime.' seconds';
                    $endTime = date("Y-m-d H:i:s", strtotime($addTime, strtotime($seleStartTime)));
                    $startTime = date("Y-m-d H:i:s", strtotime('-29 seconds',  strtotime($endTime)));

                    $tmpData['matchId'] = $gameDetails[0]->matchId;
                    $tmpData['contestId'] = $gameDetails[0]->pairId;
                    $tmpData['userId'] = $opnUser;
                    $tmpData['playerId'] = $mopsvalue->playerId;
                    $tmpData['order'] = $newOpnDraftCou;
                    $tmpData['startTime'] = $startTime;
                    $tmpData['endTime'] = $endTime;

                    $contFinPlayModel->createFinalPlayers($tmpData);
                    array_push($tmpOpFinal, array('playerId' => $mopsvalue->playerId, 'order' => $newOpnDraftCou, 'endTime' => $endTime));
                    unset($tmpData);              
                    $newOpnDraftCou++;

                  }
                }

              }

              //- Send Server Event
              $data['eventName'] = 'conFnalSel';

              $msg = array('matchId' => $gameDetails[0]->matchId, 'contestId' => $gameDetails[0]->pairId, 'userId' => $userData->id, 'myFinal' => $tmpMyFinal, 'opFinal' => $tmpOpFinal, 'playerSel' => $tmpPlaySel);
              $data['eventData'] = json_encode($msg);
              CreateSselog($data);
              unset($data);

              $this->db->transComplete();
              if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                $msg = array();
                return $this->response->setStatusCode(429)
                          ->setHeader('Access-Control-Allow-Origin', '*')
                          ->setHeader('Access-Control-Allow-Headers', 'Origin')
                          ->setContentType('application/json', 'utf-8')
                          ->setJSON($msg);
              } else {
                $this->db->transCommit();
                $msg = array('status' => 200, 'myFinal' => $tmpMyFinal, 'opFinal' => $tmpOpFinal, 'msg' => "success"); 
                return $this->response->setStatusCode(200)
                          ->setHeader('Access-Control-Allow-Origin', '*')
                          ->setHeader('Access-Control-Allow-Headers', 'Origin')
                          ->setContentType('application/json', 'utf-8')
                          ->setJSON($msg);
              }                  

            }else{

              $this->db->transComplete();
              if ($this->db->transStatus() === false) {
                
                $this->db->transRollback();
                $msg = array();
                return $this->response->setStatusCode(429)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);

              } else {

                $this->db->transCommit();
                $msg = array('status' => 401, 'error' => 'Unauthorized');
                return $this->response->setStatusCode(401)
                          ->setHeader('Access-Control-Allow-Origin', '*')
                          ->setHeader('Access-Control-Allow-Headers', 'Origin')
                          ->setContentType('application/json', 'utf-8')
                          ->setJSON($msg);
              
              }
            
            }

          }else{

            if (count($playExist) > 0) {

              $msg = array('status' => 203, 'error' => 'Duplicate Player');
              return $this->response->setStatusCode(203)
                        ->setHeader('Access-Control-Allow-Origin', '*')
                        ->setHeader('Access-Control-Allow-Headers', 'Origin')
                        ->setContentType('application/json', 'utf-8')
                        ->setJSON($msg);

            }else{

              $msg = array('status' => 401, 'error' => 'Unauthorized');
              return $this->response->setStatusCode(401)
                        ->setHeader('Access-Control-Allow-Origin', '*')
                        ->setHeader('Access-Control-Allow-Headers', 'Origin')
                        ->setContentType('application/json', 'utf-8')
                        ->setJSON($msg);
            }
          
          }

        }else{

          $msg = array('status' => 401, 'error' => 'Unauthorized');
          return $this->response->setStatusCode(401)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg); 

        }

      }else{

        if ($tmpSerTime >= $tmpClinTime) {

          $msg = array('status' => 202, 'error' => 'Slow Internet');
          return $this->response->setStatusCode(202)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

        }else{

          $msg = array('status' => 401, 'error' => 'Unauthorized');
          return $this->response->setStatusCode(401)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg); 
        }

      }          

    }


    //- Get Final 6 Players
    public function gatfinalSixPlayers()
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $gameId = $uri->getSegment(6);
      $userData = $this->request->decoded->userData;
      if ($userId == $userData->id) {

        $playerModel = new PlayersModel();
        $teamsModel = new TeamsModel();
        $contModel = new ContestModel();
        $gameDetails = $contModel->getGameDetailsById($gameId,$userId);
        
        if (count($gameDetails) > 0) { 

          $this->db->transStart();

          $matchModel = new MatchesModel();
          $matchDetails = $matchModel->getMatcheById($gameDetails[0]->matchId);

          //- Team One Details
          $teamOneDetails = $teamsModel->getTeamById($gameDetails[0]->userOneTeam);
          
          //- Team One Details
          $teamTwoDetails = $teamsModel->getTeamById($gameDetails[0]->userTwoTeam);        

          //- Get User Game Final Players
          $conFinlPlayModel = new ContestFinalPlayers();
          $finalPlayer = $conFinlPlayModel->getFinalGamePlayers($gameDetails[0]->matchId,$gameDetails[0]->pairId,$userId);

          $tmpFinal = array();
          $seleStartTime = gmdate("Y-m-d H:i:s");

          $toUpdt = array();
          if (count($finalPlayer) > 0) {
            foreach ($finalPlayer as $fpkey => $fpvalue) {
              if ($seleStartTime >= $fpvalue->endTime) {
                array_push($toUpdt, $fpvalue->fpId);
              }
            }
          }

          if (count($toUpdt) > 0) {
            $conFinlPlayModel->updateFinPlayerStatus($toUpdt);
          }

          if (count($finalPlayer) > 0) {
            foreach ($finalPlayer as $fpkey => $fpvalue) {

              if ($matchDetails[0]->matchType == '1') {
                $tmpType = '2';
              }else if ($matchDetails[0]->matchType == '2') {
                $tmpType = '3';
              }else if ($matchDetails[0]->matchType == '3') {
                $tmpType = '1';
              }else{
                $tmpType = '2';
              }
              /*
              if ($seleStartTime >= $fpvalue->endTime) {
                $conFinlPlayModel->updatePlayerStatus($fpvalue->matchId,$fpvalue->contestId,$fpvalue->userId,$fpvalue->playerId);
              }*/          

              $pdetails = $playerModel->getPlayersDetails($fpvalue->playerId,$tmpType);

              if (count($pdetails) == 0) {
                $pdetails = $playerModel->getPlayersDetailsNoScore($fpvalue->playerId);
                if (count($pdetails) > 0) {
                  $pdetails[0]->avg = 0;
                  $pdetails[0]->hs = 0;
                }
              }

              $playTeam = $playerModel->getMatchPlayer($gameDetails[0]->matchId,$fpvalue->playerId);

              $teamName = '';
              if ($teamOneDetails[0]->teamId == $playTeam[0]->teamId) {
                $teamName = $teamOneDetails[0]->shortName;
              }else if ($teamTwoDetails[0]->teamId == $playTeam[0]->teamId) {
                $teamName = $teamTwoDetails[0]->shortName;
              }

              array_push($tmpFinal, array('name' => $pdetails[0]->name, 'imageId' => $pdetails[0]->faceImageId, 'teamName' => $teamName, 'avg' => $pdetails[0]->avg, 'hs' => $pdetails[0]->hs, 'orderId' => $fpvalue->order, 'playerId' => $fpvalue->playerId, 'isCap' =>  $fpvalue->isCap, 'isvCap' =>  $fpvalue->isvCap ));

            }
          }

          $this->db->transComplete();
          if ($this->db->transStatus() === false) {
            $this->db->transRollback();
            $msg = array();
            return $this->response->setStatusCode(429)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);
          }else{
            $this->db->transCommit();
            $msg = array('status' => 200, 'list' => $tmpFinal);
            return $this->response->setStatusCode(200)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);
          }

        }else{

          $msg = array('status' => 401, 'error' => 'Unauthorized');
          return $this->response->setStatusCode(401)
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


    //- Update Captain
    public function updateCaptain()
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $gameId = $uri->getSegment(6);
      $userData = $this->request->decoded->userData;

      $pgameId = $this->request->getJsonVar('gameId');
      $capId = $this->request->getJsonVar('capId');
      $viCapId = $this->request->getJsonVar('viCapId');
      $selTimeId = $this->request->getJsonVar('selTimeId');

      if ($userId == $userData->id && !is_null($pgameId) && $gameId == $pgameId && ( (!is_null($capId) || !is_null($viCapId)) || !is_null($selTimeId)) ) {

        $contModel = new ContestModel();
        $gameDetails = $contModel->getGameDetailsById($gameId,$userId);
        
        if (count($gameDetails) > 0 && (!is_null($capId) && !empty($capId) ) || (!is_null($viCapId) && !empty($viCapId)) ) {

          //- Check Player Exist
          $conFinlPlayModel = new ContestFinalPlayers();

          if (!is_null($capId)) {
            $capExist = $conFinlPlayModel->checkPlayerExist($gameDetails[0]->matchId,$gameDetails[0]->pairId,$userId,$capId);
          }else{
            $capExist = array();
          }
          
          if (!is_null($viCapId)) {
            $vicCapExist = $conFinlPlayModel->checkPlayerExist($gameDetails[0]->matchId,$gameDetails[0]->pairId,$userId,$viCapId);
          }else{
            $vicCapExist = array();
          }

          if (count($capExist) > 0 || count($vicCapExist) > 0) {
            
            $this->db->transStart();

            if (!is_null($selTimeId)) {

              if ($gameDetails[0]->userOne == $userId) {
                $contModel->updateOneCapSel($gameId,$userId);
              }else if ($gameDetails[0]->userTwo == $userId) {
                $contModel->updateTwoCapSel($gameId,$userId);
              }
              
            }
            
            //- Update
            if (count($capExist) > 0) {
              $conFinlPlayModel->updateCaptain($capExist[0]->fpId);
            }

            if (count($vicCapExist) > 0) {
              $conFinlPlayModel->updateViceCaptain($vicCapExist[0]->fpId);
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
              
              $this->db->transRollback();
              $msg = array();
              return $this->response->setStatusCode(429)
                        ->setHeader('Access-Control-Allow-Origin', '*')
                        ->setHeader('Access-Control-Allow-Headers', 'Origin')
                        ->setContentType('application/json', 'utf-8')
                        ->setJSON($msg);

            } else {

              $this->db->transCommit();
              $msg = array('status' => 200, 'msg' => 'success');
              return $this->response->setStatusCode(200)
                          ->setHeader('Access-Control-Allow-Origin', '*')
                          ->setHeader('Access-Control-Allow-Headers', 'Origin')
                          ->setContentType('application/json', 'utf-8')
                          ->setJSON($msg);
            }

          }else{
         
            $msg = array('status' => 401, 'error' => 'Unauthorized');
            return $this->response->setStatusCode(401)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);             
          }

        }else{

          if (!is_null($selTimeId)) {          

            if ($gameDetails[0]->userOne == $userId) {
              $contModel->updateOneCapSel($gameId,$userId);
            }else if ($gameDetails[0]->userTwo == $userId) {
              $contModel->updateTwoCapSel($gameId,$userId);
            }
            
            $msg = array('status' => 200, 'msg' => 'success');
            return $this->response->setStatusCode(200)
                        ->setHeader('Access-Control-Allow-Origin', '*')
                        ->setHeader('Access-Control-Allow-Headers', 'Origin')
                        ->setContentType('application/json', 'utf-8')
                        ->setJSON($msg);    

          }else{

            $msg = array('status' => 401, 'error' => 'Unauthorized');
            return $this->response->setStatusCode(401)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg); 
          }

        }

      }else{

        $msg = array('status' => 401, 'error' => 'Unauthorized');
          return $this->response->setStatusCode(401)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);        

      }      

    }
    

    //- Get Final Player Scores 
    public function getGameScores()
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $gameId = $uri->getSegment(6);
      $userData = $this->request->decoded->userData;

      $pgameId = $this->request->getJsonVar('gameId');

      if ($userId == $userData->id && !is_null($pgameId) && $pgameId == $gameId) {

        $conFinPlayer = new ContestFinalPlayers();
        $contModel = new ContestModel();
        $playerModel = new PlayersModel();
        $playerScores = new MatchPlayerScores();
        $matchModel = new MatchesModel();
        $userModel = new UserModel();
        $gameDetails = $contModel->getGameDetailsById($gameId,$userId);

        if (count($gameDetails) > 0) {

          $myUsrId = '';
          $opnUsrId =  '';

          if ($userId == $gameDetails[0]->userOne) {
            $myUsrId = $gameDetails[0]->userOne;
            $opnUsrId =  $gameDetails[0]->userTwo;
          }else{
            $myUsrId = $gameDetails[0]->userTwo;
            $opnUsrId =  $gameDetails[0]->userOne;            
          }

          $teamModel = new TeamsModel();
          $userOneTeam = $teamModel->getTeamById($gameDetails[0]->userOneTeam);
          $userTwoTeam = $teamModel->getTeamById($gameDetails[0]->userTwoTeam);


          $matchDetails = $matchModel->getMatcheById($gameDetails[0]->matchId);
          $oponenDetails = $userModel->getUserById($opnUsrId);

          //- Final My Players
          $myPlayers = $conFinPlayer->getFinalGamePlayers($gameDetails[0]->matchId,$gameDetails[0]->pairId,$myUsrId);

          //- Final Opponent Players
          $opnPlayers = $conFinPlayer->getFinalGamePlayers($gameDetails[0]->matchId,$gameDetails[0]->pairId,$opnUsrId);

          $tmpMyPlayer = array();
          if (count($myPlayers) > 0) {
            foreach ($myPlayers as $mpkey => $mpvalue) {
              
              //- Get Player Scores
              $playerDetails = $playerModel->getPlayerById($mpvalue->playerId);

              $playerScors = $playerScores->getMatchPlayerTotalScores($mpvalue->matchId,$mpvalue->playerId);

              $playerTeam = $playerModel->getMatchPlayer($mpvalue->matchId,$mpvalue->playerId);

              $teamName = '';
              foreach ($userOneTeam as $uotkey => $uotvalue) {
                if ($uotvalue->teamId == $playerTeam[0]->teamId) {
                  $teamName = $uotvalue->shortName;
                }
              }

              foreach ($userTwoTeam as $uttkey => $uttvalue) {
                if ($uttvalue->teamId == $playerTeam[0]->teamId) {
                  $teamName = $uttvalue->shortName;
                }
              }              

              array_push($tmpMyPlayer, array('playerId' => $mpvalue->playerId, 'matchId' => $mpvalue->matchId, 'isCap' => $mpvalue->isCap, 'isvCap' => $mpvalue->isvCap, 'order' => $mpvalue->order, 'name' => $playerDetails[0]->name, 'faceImageId' => $playerDetails[0]->faceImageId, 'scors' => $playerScors[0]->runs, 'teamId' => $teamName, 'status' => $mpvalue->status));

            }
          }

          $tmpOpnPlayer = array();
          if (count($opnPlayers) > 0) {
            foreach ($opnPlayers as $oppkey => $oppvalue) {
              
              //- Get Player Scores
              $playerDetails = $playerModel->getPlayerById($oppvalue->playerId);

              $playerScors = $playerScores->getMatchPlayerTotalScores($oppvalue->matchId,$oppvalue->playerId);

              $playerTeam = $playerModel->getMatchPlayer($oppvalue->matchId,$oppvalue->playerId);

              $teamName = '';
              foreach ($userOneTeam as $uotkey => $uotvalue) {
                if ($uotvalue->teamId == $playerTeam[0]->teamId) {
                  $teamName = $uotvalue->shortName;
                }
              }

              foreach ($userTwoTeam as $uttkey => $uttvalue) {
                if ($uttvalue->teamId == $playerTeam[0]->teamId) {
                  $teamName = $uttvalue->shortName;
                }
              }  

              array_push($tmpOpnPlayer, array('playerId' => $oppvalue->playerId, 'matchId' => $oppvalue->matchId, 'isCap' => $oppvalue->isCap, 'isvCap' => $oppvalue->isvCap, 'order' => $oppvalue->order, 'name' => $playerDetails[0]->name, 'faceImageId' => $playerDetails[0]->faceImageId, 'scors' => $playerScors[0]->runs, 'teamId' => $teamName, 'status' => $oppvalue->status ));

            }
          }
      

          $msg = array('status' => 200, 'myPlayer' => $tmpMyPlayer, 'opnPlayer' => $tmpOpnPlayer, 'matchType' => $matchDetails[0]->matchType, 'profileImg' => getenv('s3Url').getenv('s3Bucket').'/'.getenv('userProf').$oponenDetails[0]->profileImg, 'name' => $oponenDetails[0]->name);
          
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


    //- Get Games History
    public function gamesHistory(){
      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;

      if ($userId == $userData->id) {

        $contModel = new ContestModel();
        $matchModel = new MatchesModel();
        $teamModel = new TeamsModel();
        $conFinPlayer = new ContestFinalPlayers();
        $playerScores = new MatchPlayerScores();
        $playerModel = new PlayersModel();

        $gameDetails = $contModel->getAllCompletedGame($userId);

        if (count($gameDetails) > 0) {

          $list = array();
          
          foreach ($gameDetails as $gdkey => $gdvalue) {

            $matchDetails = $matchModel->getMatcheById($gdvalue->matchId);
            
            $contOne = $teamModel->checkTeamExist($matchDetails[0]->teamaKey);
            $contTwo = $teamModel->checkTeamExist($matchDetails[0]->teambKey);
         
            $amount = '';
            if ($gdvalue->winnStatus == '1') {
              
              if ($gdvalue->winnerId == $userId) {

                if ($gdvalue->userOne == $userId) {
                  $amount = $gdvalue->oneWinningPrice;
                  $amount = round($amount, 2);
                }else if ($gdvalue->userTwo == $userId) {
                  $amount = $gdvalue->twoWinningPrice;
                  $amount = round($amount, 2);
                }

              }else{

                if ($gdvalue->userOne == $userId) {
                  $amount = $gdvalue->oneLostPrice;
                }else if ($gdvalue->userTwo == $userId) {
                  $amount = $gdvalue->twoLostPrice;
                }

              }

            }else{
              
              if ($gdvalue->userOne == $userId) {
                $amount = 0;
              }

              if ($gdvalue->userTwo == $userId) {
                $amount = 0;
              }

            }


            if ($gdvalue->userOne == $userId) {
              
              $userOnePlaye = $conFinPlayer->getFinalGamePlayers($gdvalue->matchId, $gdvalue->pairId, $gdvalue->userOne);
              
              $userTwoPlaye = $conFinPlayer->getFinalGamePlayers($gdvalue->matchId, $gdvalue->pairId, $gdvalue->userTwo);

              $myScore = $gdvalue->userOneScore;
              $openScore = $gdvalue->userTwoScore;

            }else if ($gdvalue->userTwo == $userId) {
             
              $userTwoPlaye = $conFinPlayer->getFinalGamePlayers($gdvalue->matchId, $gdvalue->pairId, $gdvalue->userOne);
              
              $userOnePlaye = $conFinPlayer->getFinalGamePlayers($gdvalue->matchId, $gdvalue->pairId, $gdvalue->userTwo);

              $openScore = $gdvalue->userOneScore;
              $myScore = $gdvalue->userTwoScore;                  

            }
            
            $tmpUonePlay = array();
            if (count($userOnePlaye) > 0) {
              foreach ($userOnePlaye as $uopkey => $uopvalue) {
                
                $playerScore = $playerScores->getMatchPlayerTotalScores($gdvalue->matchId, $uopvalue->playerId);

                $playDeta = $playerModel->getPlayerById($uopvalue->playerId);

                $tmpOScore = 0;
                if (count($playerScore) > 0) {
                  $tmpOScore = intval($playerScore[0]->runs);
                }

                array_push($tmpUonePlay, array('score' => $tmpOScore, 'name' => $playDeta[0]->nickName,  'faceImageId' => $playDeta[0]->faceImageId, 'isCap' => $uopvalue->isCap, 'isvCap' => $uopvalue->isvCap ));
              }
            }

            $tmpUtwoPlay = array();
            if (count($userTwoPlaye) > 0) {
              foreach ($userTwoPlaye as $utpkey => $utpvalue) {
                
                $playerScore = $playerScores->getMatchPlayerTotalScores($gdvalue->matchId, $utpvalue->playerId);

                $playDeta = $playerModel->getPlayerById($utpvalue->playerId);

                $tmpTScore = 0;
                if (count($playerScore) > 0) {
                  $tmpTScore = intval($playerScore[0]->runs);
                }

                array_push($tmpUtwoPlay, array('score' => $tmpTScore, 'name' => $playDeta[0]->nickName,  'faceImageId' => $playDeta[0]->faceImageId, 'isCap' => $utpvalue->isCap, 'isvCap' => $utpvalue->isvCap ));
              }
            }

            array_push($list, array('title' => $matchDetails[0]->name.' '.$matchDetails[0]->title, 'startTime' => $matchDetails[0]->startTime, 'contOne' => $matchDetails[0]->teamaId, 'contOneImg' => $contOne[0]->logo, 'contOneSnam' => $contOne[0]->shortName, 'contTwo' => $matchDetails[0]->teambId, 'contTwoImg' => $contTwo[0]->logo, 'contTwoSnam' => $contTwo[0]->shortName, 'contestId' => $gdvalue->contestId, 'userOneScore' => round($myScore), 'userTwoScore' => round($openScore), 'winnerId' => $gdvalue->winnerId, 'winnStatus' => $gdvalue->winnStatus,'myPlayers' => $tmpUonePlay, 'openPlayers' => $tmpUtwoPlay, 'amount' => $amount));

          }
        
          //- Total Wins
          $winDetails = $contModel->getWinningAmount($userId);
          $winTotal = 0;
          if (count($winDetails) > 0){
            $winTotal = floatval($winDetails[0]->one_winning_price) + floatval($winDetails[0]->two_winning_price);
            $winTotal = sprintf('%0.2f', $winTotal);
          }

          //- Total Loss
          $loosDetails = $contModel->getLossAmount($userId);
          $loosTotal = 0;
          if (count($loosDetails) > 0){
            $loosTotal = floatval($loosDetails[0]->one_lost_price) + floatval($loosDetails[0]->two_lost_price);
            $loosTotal = sprintf('%0.2f', $loosTotal);      
          } 

          $msg = array('status' => 200, 'data' => $list, 'winTotal' => $winTotal, 'loosTotal' => $loosTotal );
          return Services::response()->setStatusCode(200)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);

        }else{
          
          $msg = array('status' => 200, 'data' => array());
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
        
}
<?php

namespace App\Controllers\Cron;
use App\Controllers\BaseController;
use App\Models\MatchesModel;
use App\Models\MatchesLookupModel;
use App\Models\UserModel;
use App\Models\ContestModel;
use App\Models\ContestFinalPlayers; 
use App\Models\UserWalletModel;
use App\Models\TeamsModel;
use Config\Services;


class Toss extends BaseController
{

    public function __construct(){
      helper('Common');
    }


    //- Create Match Look Up Data
    public function createMatchLookUpData()
    {
    	
    	$stime = gmdate('Y-m-d');
      $etime = gmdate('Y-m-d',strtotime("+1 days"));

    	$startTime = $stime.' 00:00:00';
    	$endTime = $etime.' 23:59:59';

    	$matchModel = new MatchesModel();
    	$matchLookUpModel = new MatchesLookupModel();
    	$matchDetails = $matchModel->getOnGoingMatches($startTime,$endTime);

    	if (count($matchDetails) > 0) {
    		foreach ($matchDetails as $mDkey => $mDvalue) {
    			
    			//-  Check Look Up Data
    			$matchDetails = $matchLookUpModel->checkMatchLookup($mDvalue->matchId);

    			if (count($matchDetails) == 0) {
    				
    				//- Create Look Up Data
    				$data['matchId'] = $mDvalue->matchId;
    				$data['cricksId'] = $mDvalue->cricksId;
    				$data['crickmId'] = $mDvalue->crickmId;
    				$data['teamaKey'] = $mDvalue->teamaKey;
    				$data['teambKey'] = $mDvalue->teambKey;
    				$data['teamaId'] = $mDvalue->teamaId;
    				$data['teambId'] = $mDvalue->teambId;
    				$data['starttime'] = $mDvalue->startTime;
    				$data['endtime'] = $mDvalue->endTime;
    				$data['matchType'] = $mDvalue->matchType;

    				$matchLookUpModel->createMatchLookup($data);

    			}
    			
    		}
    	}

    }


    //- Update Match Live Toss(Toss not declared) Start Befour 32 min and ends after 15 min of start time. Keep 0.4s interwell of crickbuzz
    public function checkLiveToss()
    {

      $starttime = gmdate("Y-m-d H:i:s");
      $endtime = gmdate("Y-m-d H:i:s", strtotime('+67 min'));

      $matchLookupModel = new MatchesLookupModel();
      $matchModel = new MatchesModel();
      $userModel = new UserModel();
      $contestModel = new ContestModel();
      $teamModel = new TeamsModel();
      $lookUpData = $matchLookupModel->getLiveTossMatches($starttime,$endtime);

      if (count($lookUpData) > 0) {
        foreach ($lookUpData as $ldkey => $ldvalue) {
         
          $ctime = gmdate('Y-m-d H:i:s');
          $tosTime = date("Y-m-d H:i:s", strtotime($ldvalue->startTime. ' -34 min'));

          $closeTime = date("Y-m-d H:i:s", strtotime($ldvalue->startTime. ' -10 min'));

          if ($ctime >= $tosTime && $ctime <= $closeTime) {
            
            $matcheUrl = getenv('matche');
            $matcheUrl = str_replace("%matchId%", $ldvalue->crickmId, $matcheUrl);
           
            try {

              $curl = \Config\Services::curlrequest();
              $response = $curl->request('get', $matcheUrl.'&api_token='.getenv('sportMonksKey') );
              $responce = $response->getStatusCode();
              $error = '';

            } catch (\Exception $e) {
              $error = $e->getMessage();
              $responce = '500';
            }

            if ($responce == 200) {
              
              if (!empty($response->getBody())) {

                $responce = json_decode($response->getBody());
                $tossResults = $responce->data->tosswon;
                $shortStatus = $responce->data->status;

                if (!is_null($tossResults)) {

                  $winningTimer = gmdate('Y-m-d H:i:s');
                  $looser = '';
                  $winner = '';
                  if ($tossResults->id == $ldvalue->teamaKey) {
                    $winner = $ldvalue->teamaId;
                    $looser = $ldvalue->teambId;
                  }else if ($tossResults->id == $ldvalue->teambKey) {
                    $winner = $ldvalue->teambId;
                    $looser = $ldvalue->teamaId;
                  }

                  //- 
                  $lookUpData = $matchLookupModel->checkMatchLookup($ldvalue->matchId);

                  if ($lookUpData[0]->tossStatus == '0') {
                    
                    //- Update Lookup
                    $data['winningTeamKey'] = $tossResults->id;
                    $data['tossDecision'] = $responce->data->note;
                    $data['tossTime'] = $winningTimer;
                    $data['winningTeamId'] = $winner;
                    $data['matchLookUpId'] = $ldvalue->id;
                    $matchLookupModel->updateTossDecision($data);
                    unset($data);

                    //- Update Match Table
                    $data['tossDecision'] = $responce->data->note;
                    $data['tossTime'] = $winningTimer;
                    $data['winningTeamId'] = $winner;
                    $data['matchId'] = $ldvalue->matchId;
                    $matchModel->updateTossDecision($data);
                    unset($data);

                    //- Update Toss
                    $contestModel->updateToss($ldvalue->matchId,$winner);

                    //- Update Auto assign toss
                    $contestModel->updateAutoAssignTossDecision($ldvalue->matchId,$winner,$looser);

                    //- Close Un Paired Contest
                    $contestModel->closeUnPairedContest($ldvalue->matchId);

                    //- Send Server Event
                    $data['eventName'] = 'tossDec';

                    $msg = array('crickmId' => $ldvalue->crickmId, 'matchId' => $ldvalue->matchId, 'teamaId' => $ldvalue->teamaId, 'teambId' => $ldvalue->teambId, 'winTeamId' => $winner, 'teamaKey' => $ldvalue->teamaKey, 'teambKey' => $ldvalue->teambKey, 'winTeamKey' => $tossResults->id);
                    $data['eventData'] = json_encode($msg);
                    CreateSselog($data);
                    unset($data);

                    //- Send Win Notification
                    $winnADtl = $contestModel->getContestTossWinUsersOnes($ldvalue->matchId,$winner);
                    $winnBDtl = $contestModel->getContestTossWinUsersTwo($ldvalue->matchId,$winner);

                    $tokents = array();
                    if (count($winnADtl) > 0) {
                      foreach ($winnADtl as $wakey => $wavalue) {
                        if (!in_array($wavalue->token,$tokents)) {
                          array_push($tokents, $wavalue->token);
                        }
                      }
                    }

                    if (count($winnBDtl) > 0) {
                      foreach ($winnBDtl as $wbkey => $wbvalue) {
                        if (!in_array($wbvalue->token,$tokents)) {
                          array_push($tokents, $wbvalue->token);
                        }
                      }
                    }

                    if (count($tokents) > 0) {

                      $teamADetails = $teamModel->getTeamById($ldvalue->teamaId);
                      $teamBDetails = $teamModel->getTeamById($ldvalue->teambId);  

                      $title = $teamADetails[0]->shortName.' v '.$teamBDetails[0]->shortName;                    

                      $notifi = array("title" => $title, "body" => "Yay! You won the toss");
                      $tmpBody = array('notification' => $notifi, 'registration_ids' => $tokents);
                      $tmpBody = json_encode($tmpBody);

                      sendPushNotifi($tmpBody);          

                    }

                    //- Send Loss Notification
                    $lossADtl = $contestModel->getContestTossLooseUsersOnes($ldvalue->matchId,$looser);
                    $lossBDtl = $contestModel->getContestTossLoosUsersTwo($ldvalue->matchId,$looser);

                    $tokents = array();
                    if (count($lossADtl) > 0) {
                      foreach ($lossADtl as $lakey => $lavalue) {
                        if (!in_array($lavalue->token,$tokents)) {
                          array_push($tokents, $lavalue->token);
                        }
                      }
                    }

                    if (count($lossBDtl) > 0) {
                      foreach ($lossBDtl as $lbkey => $lbvalue) {
                        if (!in_array($lbvalue->token,$tokents)) {
                          array_push($tokents, $lbvalue->token);
                        }
                      }
                    }          

                    if (count($tokents) > 0) {


                      $teamADetails = $teamModel->getTeamById($ldvalue->teamaId);
                      $teamBDetails = $teamModel->getTeamById($ldvalue->teambId);  

                      $title = $teamADetails[0]->shortName.' v '.$teamBDetails[0]->shortName;

                      $notifi = array("title" => $title, "body" => "Oh no :( You lost the toss");
                      $tmpBody = array('notification' => $notifi, 'registration_ids' => $tokents);
                      $tmpBody = json_encode($tmpBody);

                      sendPushNotifi($tmpBody);          

                    }

                    //- Refund Unpaired Contests
                    refundUnpairedContests($ldvalue->matchId);

                  }

                }

                //- Match Abandon
                if ($shortStatus == "Aban.") {
                  
                  //- Update Match Status In Lookup Table
                  $matchLookupModel->updateCloseStatus($ldvalue->id);
                  
                  //- Update Contest Close Status
                  $contestModel->closeAllMatchContest($ldvalue->matchId);

                  //- Send Server Event
                  $data['eventName'] = 'contestClosed';

                  $msg = array('crickmId' => $ldvalue->crickmId, 'matchId' => $ldvalue->matchId);
                  $data['eventData'] = json_encode($msg);
                  CreateSselog($data);
                  unset($data);

                  //- Refund Closed Match All Contests Amounts
                  refundClosedMatchContests($ldvalue->matchId);            

                }
                

              }

            }else{
      
              //- Update Error Log 
              if (!empty($error)) {
                
                $data['userId'] = '0';
                $data['text'] = 'Unable To Fetch Toss Data';
                $data['disc'] = $error;
                CreateErrorLog($data);
                unset($data);

              }else{

                $data['userId'] = '0';
                $data['text'] = 'Unable To Fetch Toss Data';
                $data['disc'] = 'Cricbuzz API not responding need manual inspection';
                CreateErrorLog($data);
                unset($data);

              }

              //- Send Message To Admin
              $data['subject'] = 'Toss Data Fetch Failed';
              $data['message'] = 'News Fetch error. Please run it manually';
              SendErrorEmail($data);
              unset($data);

            }

          }else{

            if ($ctime >= $closeTime) {
                  
              //- Update Match Status In Lookup Table
              $matchLookupModel->updateCloseStatus($ldvalue->id);
              
              //- Update Contest Close Status
              $contestModel = new ContestModel();
              $contestModel->closeAllMatchContest($ldvalue->matchId);

              //- Send Server Event
              $data['eventName'] = 'contestClosed';

              $msg = array('crickmId' => $ldvalue->crickmId, 'matchId' => $ldvalue->matchId);
              $data['eventData'] = json_encode($msg);
              CreateSselog($data);
              unset($data);

              //- Refund Closed Match All Contests Amounts
              refundClosedMatchContests($ldvalue->matchId);

            }

          }


          //- Send Match Notification Befour A Hour
          $ctime = gmdate('Y-m-d H:i:s');
          $tosTime = date("Y-m-d H:i:s", strtotime($ldvalue->startTime. ' -63 min'));
          if ($tosTime >= $ctime && $ldvalue->notifiSend == '0') {

            $userAllDetails = $userModel->getAllUserToken();
            $allTokens = array();
            
            if (count($userAllDetails) > 0) {
              foreach ($userAllDetails as $uakey => $uavalue) {
                if (!in_array($uavalue->token,$allTokens)) {
                  array_push($allTokens, $uavalue->token);
                }
              }
            } 

            //- Get Match Details
            $matchDetails = $matchModel->getMatcheById($ldvalue->matchId);

            //- Send Notification
            if (count($matchDetails) > 0 && count($allTokens) > 0) {

              $teamADetails = $teamModel->getTeamById($matchDetails[0]->teamaId);
              $teamBDetails = $teamModel->getTeamById($matchDetails[0]->teambId);

              $body = $matchDetails[0]->name.' '.$matchDetails[0]->title.' '.$teamADetails[0]->shortName." v ".$teamBDetails[0]->shortName." starts soon. Like to play? ";

              $notifi = array("title" => "Player6", "body" => $body);
              $tmpBody = array('notification' => $notifi, 'registration_ids' => $allTokens);
              $tmpBody = json_encode($tmpBody);

              sendPushNotifi($tmpBody);

            }

            //- Update Status
            $matchLookupModel->updateNotifyStatus($ldvalue->id);

            //- Get
            $unPairedList = $contestModel->getUnPairedContestUsers($ldvalue->matchId);
            $upaToken = array();
            if (count($unPairedList) > 0) {
              foreach ($unPairedList as $upkey => $upvalue) {
                if (!in_array($upvalue->token,$upaToken)) {
                  array_push($upaToken, $upvalue->token);
                }
              }
            } 

            //- Send Notification
            if (count($upaToken) > 0) {
              
              $body = "Still searching for an opponent. Like to join another gameroom instantly?";

              $notifi = array("title" => "Player6", "body" => $body);
              $tmpBody = array('notification' => $notifi, 'registration_ids' => $upaToken);
              $tmpBody = json_encode($tmpBody);

              sendPushNotifi($tmpBody);

            }


          }


        }
      }

      //- mail("dinesh.xhtmlchamps@gmail.com","checkLiveToss",'cron');
    }


    //- Get Finale Players
    public function getFinalPlayers()
    {

      $matchModel = new MatchesModel();
      $matchLookupModel = new MatchesLookupModel();
      $teamModel = new TeamsModel();
      $allMatches = $matchLookupModel->getBoutToStartMatches();

      if (count($allMatches) > 0) {
        foreach ($allMatches as $amkey => $amvalue) {

          $ctime = gmdate('Y-m-d H:i:s');
          $closeTime = date("Y-m-d H:i:s", strtotime($amvalue->startTime. ' -2 min'));

          $tempDetail = $matchLookupModel->checkMatchLookup($amvalue->matchId);

          if ($tempDetail[0]->player11 == '0' && $tempDetail[0]->matchStatus != '4') {

            if ($ctime <= $closeTime) {
            
              $teamAsucces = false;
              $teamBsucces = false;

              //- Get Match Player One
              $play11Url = getenv('play11');
              $play11Url = str_replace("%matchId%", $amvalue->crickmId, $play11Url);

              try {

                $curl = \Config\Services::curlrequest();
                $response = $curl->request('GET', $play11Url.'&api_token='.getenv('sportMonksKey') );

                $responce = $response->getStatusCode();
                $error = '';

              } catch (\Exception $e) {
                $error = $e->getMessage();
                $responce = '500';
              
              }

              if ($responce == 200) {

                if (!empty($response->getBody())) {
                  
                  $responce = json_decode($response->getBody());
                  $responce = $responce->data;


                  if (count($responce->lineup) > 0) {

                    $tramA = array();
                    $tramASub = array();
                    $tramB = array();
                    $tramBSub = array();

                    foreach ($responce->lineup as $lpkey => $lpvalue) {
      
                      if ($lpvalue->lineup->team_id == $amvalue->teamaKey) {

                        if($lpvalue->lineup->substitution == false){
                          array_push($tramA, $lpvalue);
                        }else{
                          array_push($tramASub, $lpvalue);
                        }

                      }else if ($lpvalue->lineup->team_id == $amvalue->teambKey) {
          
                        if($lpvalue->lineup->substitution == false){
                          array_push($tramB, $lpvalue);
                        }else{
                          array_push($tramBSub, $lpvalue);
                        }  

                      }
                      
                    }


                    if (count($tramA) >= 11 && count($tramB) >= 11) {

                      CreateFinalPlayers($tramA,$amvalue->matchId,$amvalue->teamaId);
                      CreateMatchPlayers($tramASub,$amvalue->matchId,$amvalue->teamaId);
                      CreateFinalPlayers($tramB,$amvalue->matchId,$amvalue->teambId);
                      CreateMatchPlayers($tramBSub,$amvalue->matchId,$amvalue->teambId);
                      $matchLookupModel->updateFinalPlayers($amvalue->id); 

                      $tempNewDetail = $matchLookupModel->checkMatchLookup($amvalue->matchId);
                      if ($tempNewDetail[0]->player11 == '0' && $tempNewDetail[0]->matchStatus != '4') {
                        $teamAsucces = true;
                        $teamBsucces = true;
                      }


                    }


                  }            

                }

              }else{

                //- Update Error Log 
                if (!empty($error)) {
                  
                  $data['userId'] = '0';
                  $data['text'] = 'Unable To Fetch Players For Live Match - '.$amvalue->crickmId.' - team '.$amvalue->teamaKey;
                  $data['disc'] = $error;
                  CreateErrorLog($data);
                  unset($data);

                }else{

                  $data['userId'] = '0';
                  $data['text'] = 'Unable To Fetch Players For Live Match - '.$amvalue->crickmId.' - team '.$amvalue->teamaKey;
                  $data['disc'] = 'Cricbuzz API not responding need manual inspection';
                  CreateErrorLog($data);
                  unset($data);
                }

                
                //- Send Message To Admin
                $data['subject'] = 'Match Live Players Failed';
                $data['message'] = 'Match Live Players Api error. Please run it manually - '.$amvalue->crickmId.' - team '.$amvalue->teamaKey;
                SendErrorEmail($data);
                unset($data);
              
              }

              //- Update Match Player Status
              if ($teamAsucces && $teamBsucces) {
                
                $matchLookupModel->updateFinalPlayers($amvalue->id);

                //- Get All Contest Players Tokens
                $contestModel = new ContestModel();
                $userOne = $contestModel->getContestUsersOnes($amvalue->matchId);
                $userTwo = $contestModel->getContestUsersTwo($amvalue->matchId);


                $tokents = array();
                if (count($userOne) > 0) {
                  foreach ($userOne as $uokey => $uovalue) {
                    if (!in_array($uovalue->token,$tokents)) {
                      array_push($tokents, $uovalue->token);
                    }
                  }
                }

                if (count($userTwo) > 0) {
                  foreach ($userTwo as $utkey => $utvalue) {
                    if (!in_array($utvalue->token,$tokents)) {
                      array_push($tokents, $utvalue->token);
                    }
                  }
                }


                if (count($tokents) > 0) {

                  $matchDetails = $matchModel->getMatcheById($amvalue->matchId);

                  $teamADetails = $teamModel->getTeamById($matchDetails[0]->teamaId);
                  $teamBDetails = $teamModel->getTeamById($matchDetails[0]->teambId);

                  $title = $teamADetails[0]->shortName." v ".$teamBDetails[0]->shortName;

                  $notifi = array("title" => $title, "body" => "Playing 11s are out. Get ready for live player selection!");

                  $tmpBody = array('notification' => $notifi, 'registration_ids' => $tokents);
                  $tmpBody = json_encode($tmpBody);

                  sendPushNotifi($tmpBody);

                }

              }

            }else{

              //- Update Match Status In Lookup Table
              $matchLookupModel->updateCloseStatus($amvalue->id);
              
              //- Update Contest Close Status
              $contestModel = new ContestModel();
              $contestModel->closeAllMatchContest($amvalue->matchId);

              //- Send Server Event
              $data['eventName'] = 'contestClosed';

              $msg = array('crickmId' => $amvalue->crickmId, 'matchId' => $amvalue->matchId);
              $data['eventData'] = json_encode($msg);
              CreateSselog($data);
              unset($data);

              //- Refund Closed Match All Contests Amounts
              refundClosedMatchContests($amvalue->matchId);

            }

          }

        }
      }

    }


    //- Get Finale Draft Players
    public function createFinalDraftPlayers()
    {

      $conFinlPlayModel = new ContestFinalPlayers();
      $matchLookupModel = new MatchesLookupModel();
      $allMatches = $matchLookupModel->getAllFinalDraftMatches();

      if (count($allMatches) > 0) {
        foreach ($allMatches as $amkey => $amvalue) {

          //- if ($amvalue->matchId == '3') {
    
            //- Get All Match Contests
            $contestModel = new ContestModel();
            $conDetal = $contestModel->getAllMatchContestsForDrafts($amvalue->matchId);

            $completCount = 0;
            if (count($conDetal) > 0) {
              foreach ($conDetal as $conkey => $convalue) {

                //- Find Toss Winner
                $firstSelTeam = '';
                $firstSelUser = '';

                $secondSelTeam = '';
                $secondSelUser = '';

                if ($convalue->userTwoTeam == $convalue->winningTeam) {
                  $firstSelTeam = $convalue->userTwoTeam;
                  $firstSelUser = $convalue->userTwo;
                  $secondSelTeam = $convalue->userOneTeam;
                  $secondSelUser = $convalue->userOne;
                }else if ($convalue->userOneTeam == $convalue->winningTeam) {
                  $firstSelTeam = $convalue->userOneTeam;
                  $firstSelUser = $convalue->userOne;
                  $secondSelTeam = $convalue->userTwoTeam;
                  $secondSelUser = $convalue->userTwo;
                }
                
                //- Get Player One Drafts
                $frstUDrft = $contestModel->getGameDraftsById($amvalue->matchId,$convalue->pairId,$firstSelUser);

                //- Get Player Two Drafts
                $secUDrft = $contestModel->getGameDraftsById($amvalue->matchId,$convalue->pairId,$secondSelUser);

                //- Find Final Draft For One && For Two
                $finalTeam = CreateFinalDraftPlayers($frstUDrft,$secUDrft);

                //- Update Status
                $seleStartTime = gmdate("Y-m-d H:i:s", strtotime('+20 seconds'));

                //- Create Final Draft For One
                if (count($finalTeam['firtTeam']) > 0) {
                  
                  foreach ($finalTeam['firtTeam'] as $ftkey => $ftvalue) {

                    $chek = $conFinlPlayModel->checkPlayerExist($amvalue->matchId,$convalue->pairId,$firstSelUser,$ftvalue->playerId);
                    
                    if (count($chek) == 0) {

                      $addTime = 30 + ($ftkey * 60);
                      $addTime = '+'.$addTime.' seconds';
                      $endTime = date("Y-m-d H:i:s", strtotime($addTime, strtotime($seleStartTime)));
                      $startTime = date("Y-m-d H:i:s", strtotime('-29 seconds',  strtotime($endTime)));

                      $data['matchId'] = $amvalue->matchId;
                      $data['contestId'] = $convalue->pairId;
                      $data['userId'] = $firstSelUser;
                      $data['playerId'] = $ftvalue->playerId;
                      $data['order'] = $ftkey+1;
                      $data['startTime'] = $startTime;
                      $data['endTime'] = $endTime;
                      $conFinlPlayModel->createFinalPlayers($data);
                      unset($data);

                    }

                  }
                }

                //- Create Final Draft For Two
                if (count($finalTeam['secTeam']) > 0) {
                  foreach ($finalTeam['secTeam'] as $stkey => $stvalue) {

                    $chek = $conFinlPlayModel->checkPlayerExist($amvalue->matchId,$convalue->pairId,$secondSelUser,$stvalue->playerId);
                    
                    if (count($chek) == 0) {

                      $addTime = 30 * (($stkey + 1) * 2);
                      $addTime = '+'.$addTime.' seconds';
                      $endTime = date("Y-m-d H:i:s", strtotime($addTime,  strtotime($seleStartTime)));
                      $startTime = date("Y-m-d H:i:s", strtotime('-29 seconds',  strtotime($endTime)));          

                      $data['matchId'] = $amvalue->matchId;
                      $data['contestId'] = $convalue->pairId;
                      $data['userId'] = $secondSelUser;
                      $data['playerId'] = $stvalue->playerId;
                      $data['order'] = $stkey+1;
                      $data['startTime'] = $startTime;
                      $data['endTime'] = $endTime;
                      $conFinlPlayModel->createFinalPlayers($data);
                      unset($data);

                    }

                  }
                }

                //- Send Server Event
                if (count($finalTeam['firtTeam']) > 0 && count($finalTeam['secTeam']) > 0) {     

                  $completCount = intval($completCount) + 1;          
                  $contestModel->updateFinalDraftStatus($convalue->pairId,$firstSelUser,$seleStartTime);

                  //- Send Server Event TODO NEED TO MOVE TO FINAL PLAYER DRAFTS
                  $data['eventName'] = 'matFinlList';
                  $msg = array('matchId' => $amvalue->matchId, 'pairId' => $convalue->pairId);
                  $data['eventData'] = json_encode($msg);
                  CreateSselog($data);
                  unset($data);

                }

              }
            }

            //- Update Final Draft Status

            if ($completCount == count($conDetal)) {
              $matchLookupModel->updateFinalDraft($amvalue->id);
            } 
            
          /*}*/

        }
      }

    }


    //- Get

}
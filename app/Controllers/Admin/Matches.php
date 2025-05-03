<?php

namespace App\Controllers\Admin;
use App\Controllers\BaseController;
use App\Models\Admin\MatchesModel;
use App\Models\Admin\SeriesModel;
use App\Models\Admin\TeamsModel;
use App\Models\Admin\PlayersModel;
use App\Models\Admin\ContestModel;
use App\Models\Admin\MatchesLookupModel;
use App\Models\Admin\UserWalletModel;
use App\Models\Admin\UsersModel;
use Config\Services;

class Matches extends BaseController
{

  public function __construct(){
    helper('Common');
  }

  //- Get All Running Series : object
  public function getSeries(): object
  {

    $uri = current_url(true);
    $userId = $uri->getSegment(5);
    $userData = $this->request->decoded->userData;
  
    if ($userId == $userData->id) {

      $matchModel = new MatchesModel();
      $seriesModel = new SeriesModel();

      $startTime = gmdate("Y-m-d H:i:s");
      $endTime = gmdate("Y-m-d H:i:s",strtotime("+8 days"));

      $matchDetails = $matchModel->getOnlyOnGoingSeries($startTime,$endTime);

      $data = array();
      if (count($matchDetails) > 0) {
        foreach ($matchDetails as $mdkey => $mdvalue) {

          $tmpSeries = $seriesModel->getSeriesById($mdvalue->seriesId);
          if (count($tmpSeries) > 0) {

            array_push($data, array('seriesName' => $tmpSeries[0]->name, 'seriesId' => $mdvalue->seriesId ));
          }

        }
      }

      $msg = array('status' => 200, 'msg' => 'Success', 'data' => $data );
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


  //- Show All Matches : object
  public function getMatches(): object
  {
    
    $uri = current_url(true);
    $userId = $uri->getSegment(5);
    $userData = $this->request->decoded->userData;

    $seriesId = $this->request->getGet('seriesId');
  
    if ($userId == $userData->id) {

      $matchModel = new MatchesModel();
      $teamModel = new TeamsModel();
      $seriesModel = new SeriesModel();

      $startTime = gmdate("Y-m-d H:i:s");
      $endTime = gmdate("Y-m-d H:i:s",strtotime("+8 days"));
      if (is_null($seriesId)) {
        $seriesId = '';
      }

      $matchDetails = $matchModel->getOnlyOnGoingMatches($startTime,$endTime,$seriesId);

      if (!empty($seriesId)) {
        $tmpSeriesName = $seriesModel->getSeriesById($seriesId);
        if (count($tmpSeriesName) > 0) {
          $tmpSeriesName = $tmpSeriesName[0]->name;
        }else{
          $tmpSeriesName = 'All Matches';
        } 
      }else{
        $tmpSeriesName = 'All Matches';
      }

      if (count($matchDetails) > 0) {
        foreach ($matchDetails as $mdkey => $mdvalue) {
          
          $tmpTeamaId = $teamModel->getTeamById($mdvalue->teamaId);
          if (count($tmpTeamaId) > 0) {
            $matchDetails[$mdkey]->teamaName = $tmpTeamaId[0]->name;
          }else{
            $matchDetails[$mdkey]->teamaName = '';
          }

          $tmpTeambId = $teamModel->getTeamById($mdvalue->teambId);
          if (count($tmpTeambId) > 0) {
            $matchDetails[$mdkey]->teambName = $tmpTeambId[0]->name;
          }else{
            $matchDetails[$mdkey]->teambName = '';
          }

          $tmpSeries = $seriesModel->getSeriesById($mdvalue->seriesId);
          if (count($tmpSeries) > 0) {
            $matchDetails[$mdkey]->seriesName = $tmpSeries[0]->name;
          }else{
            $matchDetails[$mdkey]->seriesName = '';
          }          

        }
      }

      $msg = array('status' => 200, 'msg' => 'Success', 'data' => $matchDetails, 'series' => $tmpSeriesName );
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


  public function getRunnMatches(): object
  {
    
    $uri = current_url(true);
    $userId = $uri->getSegment(5);
    $userData = $this->request->decoded->userData;

    $seriesId = $this->request->getGet('seriesId');
  
    if ($userId == $userData->id) {

      $matchModel = new MatchesModel();
      $teamModel = new TeamsModel();
      $seriesModel = new SeriesModel();

      if (is_null($seriesId)) {
        $seriesId = '';
      }

      $matchDetails = $matchModel->getOnlyRunningMatches($seriesId);

      if (!empty($seriesId)) {
        $tmpSeriesName = $seriesModel->getSeriesById($seriesId);
        if (count($tmpSeriesName) > 0) {
          $tmpSeriesName = $tmpSeriesName[0]->name;
        }else{
          $tmpSeriesName = 'All Matches';
        } 
      }else{
        $tmpSeriesName = 'All Matches';
      }

      if (count($matchDetails) > 0) {
        foreach ($matchDetails as $mdkey => $mdvalue) {
          
          $tmpTeamaId = $teamModel->getTeamById($mdvalue->teamaId);
          if (count($tmpTeamaId) > 0) {
            $matchDetails[$mdkey]->teamaName = $tmpTeamaId[0]->name;
          }else{
            $matchDetails[$mdkey]->teamaName = '';
          }

          $tmpTeambId = $teamModel->getTeamById($mdvalue->teambId);
          if (count($tmpTeambId) > 0) {
            $matchDetails[$mdkey]->teambName = $tmpTeambId[0]->name;
          }else{
            $matchDetails[$mdkey]->teambName = '';
          }

          $tmpSeries = $seriesModel->getSeriesById($mdvalue->seriesId);
          if (count($tmpSeries) > 0) {
            $matchDetails[$mdkey]->seriesName = $tmpSeries[0]->name;
          }else{
            $matchDetails[$mdkey]->seriesName = '';
          }          

        }
      }

      $msg = array('status' => 200, 'msg' => 'Success', 'data' => $matchDetails, 'series' => $tmpSeriesName );
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

  //- Get Matche Details By Id
  public function getMatcheDetails()
  {

    $uri = current_url(true);
    $userId = $uri->getSegment(5);
    $userData = $this->request->decoded->userData;

    $matchId = $uri->getSegment(7);

    if ($userId == $userData->id) {
      
      $matchLookupModel = new MatchesLookupModel();
      $matchModel = new MatchesModel();
      $teamModel = new TeamsModel();
      $seriesModel = new SeriesModel();
      $playerModel = new PlayersModel();

      $matchDetails = $matchModel->getMatcheById($matchId);

      if (count($matchDetails) > 0) {

        $matchLookDetails = $matchLookupModel->checkMatchLookup($matchId);

        $details = array();
        $details['matchId'] = $matchDetails[0]->matchId;
        $details['seriesId'] = $matchDetails[0]->seriesId;
        $details['title'] = $matchDetails[0]->title;
        $details['name'] = $matchDetails[0]->name;
        $details['tossWinner'] = $matchDetails[0]->tossWinner;
        $details['startTime'] = $matchDetails[0]->startTime;
        
        if (count($matchLookDetails) > 0) {
          $details['matchStatus'] = $matchLookDetails[0]->matchStatus; 
          $details['player11'] = $matchLookDetails[0]->player11;
        }else{
          $details['matchStatus'] = '0';
          $details['player11'] = '0';
        }

        $teamaId = $teamModel->getTeamById($matchDetails[0]->teamaId);
        if (count($teamaId) > 0) {
          $details['teamaName'] = $teamaId[0]->shortName;
          $details['teamaLogo'] = $teamaId[0]->logo;
          $details['teamaTeamId'] = $matchDetails[0]->teamaId;
        }else{
          $details['teamaName'] = '';
          $details['teamaLogo'] = '';
          $details['teamaTeamId'] = '';
        }

        $teambId = $teamModel->getTeamById($matchDetails[0]->teambId);
        if (count($teambId) > 0) {
          $details['teambName'] = $teambId[0]->shortName;
          $details['teambLogo'] = $teambId[0]->logo;
          $details['teambTeamId'] = $matchDetails[0]->teambId;
        }else{
          $details['teambName'] = '';
          $details['teambLogo'] = '';
          $details['teambTeamId'] = '';
        }

        //- Team A Players
        $aTeamPlayers = $playerModel->getTeamPlayes($matchDetails[0]->matchId,$matchDetails[0]->teamaId);
        if (count($aTeamPlayers) > 0) {
          foreach ($aTeamPlayers as $atpkey => $atpvalue) {
            
            $tmpPlayDtl  = $playerModel->getPlayerById($atpvalue->playerId);
            if (count($tmpPlayDtl) > 0) {
              $aTeamPlayers[$atpkey]->name = $tmpPlayDtl[0]->name;
              $aTeamPlayers[$atpkey]->nickName = $tmpPlayDtl[0]->nickName;
              $aTeamPlayers[$atpkey]->faceImageId = $tmpPlayDtl[0]->faceImageId;
            }else{
              $aTeamPlayers[$atpkey]->name = '';
              $aTeamPlayers[$atpkey]->nickName = '';
              $aTeamPlayers[$atpkey]->faceImageId = '';
            }

          }
        }
        $details['teamaPlayers'] = $aTeamPlayers;

        //- Team B Players
        $bTeamPlayers = $playerModel->getTeamPlayes($matchDetails[0]->matchId,$matchDetails[0]->teambId);
        if (count($bTeamPlayers) > 0) {
          foreach ($bTeamPlayers as $btpkey => $btpvalue) {
            
            $tmpPlayDtl  = $playerModel->getPlayerById($btpvalue->playerId);
            if (count($tmpPlayDtl) > 0) {
              $bTeamPlayers[$btpkey]->name = $tmpPlayDtl[0]->name;
              $bTeamPlayers[$btpkey]->nickName = $tmpPlayDtl[0]->nickName;
              $bTeamPlayers[$btpkey]->faceImageId = $tmpPlayDtl[0]->faceImageId;
            }else{
              $bTeamPlayers[$btpkey]->name = '';
              $bTeamPlayers[$btpkey]->nickName = '';
              $bTeamPlayers[$btpkey]->faceImageId = '';
            }

          }
        }
        $details['teambPlayers'] = $bTeamPlayers;


        $msg = array('status' => 200, 'msg' => 'Success', 'data' => $details );
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


  public function ManualToss()
  {
    
    $uri = current_url(true);
    $userId = $uri->getSegment(5);
    $matchId = $uri->getSegment(7);
    $userData = $this->request->decoded->userData;

    if ($userId == $userData->id && !is_null($matchId)) {

      //- $winningId = $this->request->getJsonVar('winningId');
      $winningId = $this->request->getJsonVar('winningId');

      $matchLookupModel = new MatchesLookupModel();
      $matchModel = new MatchesModel();
      $contestModel = new ContestModel();

      $matchDetails = $matchLookupModel->checkMatchLookup($matchId);

      $ctime = gmdate('Y-m-d H:i:s');
      $tosTime = date("Y-m-d H:i:s", strtotime($matchDetails[0]->startTime. ' -34 min'));

      $closeTime = date("Y-m-d H:i:s", strtotime($matchDetails[0]->startTime. ' -10 min'));

      if ($ctime >= $tosTime && $ctime <= $closeTime && ($winningId == $matchDetails[0]->teamaId || $winningId == $matchDetails[0]->teambId) ) {

        if ($winningId == $matchDetails[0]->teamaId) {
          $winningId == $matchDetails[0]->teamaId;
          $looser = $matchDetails[0]->teambId;
          $winnTId = $matchDetails[0]->teamaKey;
          $lossTId = $matchDetails[0]->teambKey;
        }else if ($winningId == $matchDetails[0]->teambId) {
          $winningId = $matchDetails[0]->teambId;
          $looser = $matchDetails[0]->teamaId;
          $winnTId = $matchDetails[0]->teambKey;
          $lossTId = $matchDetails[0]->teamaKey;
        }

        $winningTimer = gmdate('Y-m-d H:i:s');

        $tempDetails = $matchLookupModel->checkMatchLookup($matchId);
        if ($tempDetails[0]->tossStatus == '0') {

          //- Update Lookup
          $data['winningTeamKey'] = $winnTId;
          $data['tossDecision'] = 'Manual Update';
          $data['tossTime'] = $winningTimer;
          $data['winningTeamId'] = $winningId;
          $data['matchLookUpId'] = $tempDetails[0]->id;
          $matchLookupModel->updateTossDecision($data);
          unset($data);

          //- Update Match Table
          $data['tossDecision'] = 'Manual Update';
          $data['tossTime'] = $winningTimer;
          $data['winningTeamId'] = $winningId;
          $data['matchId'] = $tempDetails[0]->matchId;
          $matchModel->updateTossDecision($data);
          unset($data);   

          //- Update Toss
          $contestModel->updateToss($tempDetails[0]->matchId,$winningId);

          //- Update Auto assign toss
          $contestModel->updateAutoAssignTossDecision($tempDetails[0]->matchId,$winningId,$looser);

          //- Close Un Paired Contest
          $contestModel->closeUnPairedContest($tempDetails[0]->matchId);

          //- Send Server Event
          $data['eventName'] = 'tossDec';

          $msg = array('crickmId' => $tempDetails[0]->crickmId, 'matchId' => $tempDetails[0]->matchId, 'teamaId' => $tempDetails[0]->teamaId, 'teambId' => $tempDetails[0]->teambId, 'winTeamId' => $winningId, 'teamaKey' => $tempDetails[0]->teamaKey, 'teambKey' => $tempDetails[0]->teambKey, 'winTeamKey' => $winnTId);
          $data['eventData'] = json_encode($msg);
          CreateSselog($data);
          unset($data);

          //- Send Win Notification
          $winnADtl = $contestModel->getContestTossWinUsersOnes($tempDetails[0]->matchId,$winningId);
          $winnBDtl = $contestModel->getContestTossWinUsersTwo($tempDetails[0]->matchId,$winningId);

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
            $notifi = array("title" => "Player6", "body" => "Yayy! You won the toss! Get ready for Live Player Selection ");
            $tmpBody = array('notification' => $notifi, 'registration_ids' => $tokents);
            $tmpBody = json_encode($tmpBody);

            sendPushNotifi($tmpBody);          
          }

          //- Send Loss Notification
          $lossADtl = $contestModel->getContestTossLooseUsersOnes($tempDetails[0]->matchId,$looser);
          $lossBDtl = $contestModel->getContestTossLoosUsersTwo($tempDetails[0]->matchId,$looser);

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

            $notifi = array("title" => "Player6", "body" => "Oh, no :( You lost the toss. Get ready for Live Player Selection");
            $tmpBody = array('notification' => $notifi, 'registration_ids' => $tokents);
            $tmpBody = json_encode($tmpBody);

            sendPushNotifi($tmpBody);           

          }

          //- Refund Unpaired Contests
          refundUnpairedContests($tempDetails[0]->matchId);
  
          $msg = array('status' => 200, 'msg' => 'Success');
          return Services::response()->setStatusCode(200)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);  

        }else{
          $msg = array('status' => 202, 'error' => 'Cannot declare toss manually. Toss has already been declared');
          return Services::response()->setStatusCode(202)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);
        }

      }else{

        if (!($ctime >= $tosTime && $ctime <= $closeTime)) {
          $msg = array('status' => 202, 'error' => 'Cannot declare toss manually. Toss can be declared 30 min before match start time ');
          return Services::response()->setStatusCode(202)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);         
        }else{
          $msg = array('status' => 202, 'error' => 'Cannot declare toss manually for this match');
          return Services::response()->setStatusCode(202)
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


  public function abandonMatch()
  {

    $uri = current_url(true);
    $userId = $uri->getSegment(5);
    $matchId = $uri->getSegment(7);
    //- $postmatchId = $this->request->getJsonVar('matchId');
    $postmatchId = $this->request->getJsonVar('matchId');
    $userData = $this->request->decoded->userData;

    if ($userId == $userData->id && !is_null($matchId) && $matchId == $postmatchId) {
    
      $matchLookupModel = new MatchesLookupModel();
      $contestModel = new ContestModel();

      $matchDetails = $matchLookupModel->checkMatchLookup($matchId);

      if (count($matchDetails) > 0) {

        $ctime = gmdate('Y-m-d H:i:s');
        $closeTime = date("Y-m-d H:i:s", strtotime($matchDetails[0]->startTime. ' +15 min'));

        if ($ctime > $closeTime && $matchDetails[0]->matchStatus != '4') {

          //- Update Match Status In Lookup Table
          $matchLookupModel->updateCloseStatus($matchDetails[0]->id);
          
          //- Update Contest Close Status
          $contestModel->closeAllMatchContest($matchDetails[0]->matchId);

          //- Send Server Event
          $data['eventName'] = 'contestClosed';

          $msg = array('crickmId' => $matchDetails[0]->crickmId, 'matchId' => $matchDetails[0]->matchId);
          $data['eventData'] = json_encode($msg);
          CreateSselog($data);
          unset($data);

          //- Refund Closed Match All Contests Amounts
          refundClosedMatchContests($matchDetails[0]->matchId);         
          
          $msg = array('status' => 200, 'msg' => 'Success');
          return Services::response()->setStatusCode(200)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);  
        
        }else{

          if ($matchDetails[0]->matchStatus == '4') {

            $msg = array('status' => 202, 'error' => 'Cannot abandon match manually. Match has already been suspended');
            return Services::response()->setStatusCode(202)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);           

          }else{

            $msg = array('status' => 202, 'error' => 'Cannot abandon match manually. Match can be suspended 15 minutes after it has started');
            return Services::response()->setStatusCode(202)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);
          }

        } 

      }else{

        $msg = array('status' => 202, 'error' => 'Cannot suspended match manually. Match not at started');
        return Services::response()->setStatusCode(202)
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


  public function updatePlayer11()
  {
    
    $uri = current_url(true);
    $userId = $uri->getSegment(5);
    $matchId = $uri->getSegment(7);
    $userData = $this->request->decoded->userData;
    //- $postmatchId = $this->request->getJsonVar('matchId');
    $postmatchId = $this->request->getJsonVar('matchId');

    if ($userId == $userData->id && !is_null($postmatchId) && $postmatchId == $matchId) {

      $playersModel = new PlayersModel();

      //- $teamId = $this->request->getJsonVar('teamId');
      $teamId = $this->request->getJsonVar('teamId');
      //- $playerId = $this->request->getJsonVar('playerId');
      $playerId = $this->request->getJsonVar('playerId');
      $selvalue = $this->request->getJsonVar('value');
     

      //- Check Player Exist
      $checkPlayer = $playersModel->getMatchPlayer($matchId,$playerId);

      //-  Check Team Player11
      $teamPlayers = $playersModel->getTeamPlayes11($matchId,$teamId);

      if ((count($teamPlayers) <= 10 || $selvalue == '1') && count($checkPlayer) > 0) {

        if ($selvalue == '1') {
          $playersModel->updateAsBanchPl($matchId,$teamId,$playerId);
        }else if ($selvalue == '0') {
          $playersModel->updateAsPlayer11($matchId,$teamId,$playerId);
        }

        $msg = array('status' => 200, 'msg' => 'Success' , 'value' => $selvalue);
        return $this->response->setStatusCode(200)
                ->setHeader('Access-Control-Allow-Origin', '*')
                ->setHeader('Access-Control-Allow-Headers', 'Origin')
                ->setContentType('application/json', 'utf-8')
                ->setJSON($msg);        

      }else{

        if (count($teamPlayers) >= 11) {
          $msg = array('status' => 202, 'error' => 'Cant select more then 11 players');
          return Services::response()->setStatusCode(202)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);
        }else{
          $msg = array('status' => 202, 'error' => 'Cannot update player manually');
          return Services::response()->setStatusCode(202)
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


  public function releasePlayer11()
  {

    $uri = current_url(true);
    $userId = $uri->getSegment(5);
    $matchId = $uri->getSegment(7);
    $userData = $this->request->decoded->userData;
    # $postmatchId = $this->request->getJsonVar('matchId');
    $postmatchId = $this->request->getJsonVar('matchId');

    if ($userId == $userData->id && !is_null($matchId) && $matchId == $postmatchId) {

      $matchLookupModel = new MatchesLookupModel();
      $matchDetails = $matchLookupModel->checkMatchLookup($matchId);

      if (count($matchDetails) > 0) {

        $ctime = gmdate('Y-m-d H:i:s');
        $tosTime = date("Y-m-d H:i:s", strtotime($matchDetails[0]->startTime. ' -34 min'));

        $closeTime = date("Y-m-d H:i:s", strtotime($matchDetails[0]->startTime. ' -10 min'));

        $playersModel = new PlayersModel();
        $teamAPlayers = $playersModel->getTeamPlayes11($matchId,$matchDetails[0]->teamaId);
        $teamBPlayers = $playersModel->getTeamPlayes11($matchId,$matchDetails[0]->teambId);

        if ($ctime >= $tosTime && $ctime <= $closeTime && $matchDetails[0]->tossStatus == '1' && $matchDetails[0]->player11 == '0' && $matchDetails[0]->matchStatus != '4' && count($teamAPlayers) >= 11 && count($teamBPlayers) >= 11 ) {

          //- Update Match Player Status
          $matchLookupModel->updateFinalPlayers($matchDetails[0]->id);

          //- Get All Contest Players Tokens
          $contestModel = new ContestModel();
          $userOne = $contestModel->getContestUsersOnes($matchDetails[0]->matchId);
          $userTwo = $contestModel->getContestUsersTwo($matchDetails[0]->matchId);

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

            $teamModel = new TeamsModel();
            $teamADetails = $teamModel->getTeamById($matchDetails[0]->teamaId);
            $teamBDetails = $teamModel->getTeamById($matchDetails[0]->teambId);

            $notifi = array("title" => "Player6", "body" => $teamADetails[0]->shortName." v ".$teamBDetails[0]->shortName." player11 are out. Are you ready?");

            $tmpBody = array('notification' => $notifi, 'registration_ids' => $tokents);
            $tmpBody = json_encode($tmpBody);

            sendPushNotifi($tmpBody);

          }

          $msg = array('status' => 200, 'msg' => 'Success');
          return $this->response->setStatusCode(200)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);        

        }else{

          if (count($teamAPlayers) < 11 || count($teamBPlayers) < 11) {
            $msg = array('status' => 202, 'error' => 'Please select 11 players each on both teams');
            return Services::response()->setStatusCode(202)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);   
          }else if ($matchDetails[0]->tossStatus != '1') {
            $msg = array('status' => 202, 'error' => 'Toss has not announced at');
            return Services::response()->setStatusCode(202)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg); 
          }else if ($matchDetails[0]->player11 == '1') {
            $msg = array('status' => 202, 'error' => 'Player11 have been already announce live. You can only perform this action prior to announcing Player11 through the API');
            return Services::response()->setStatusCode(202)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);        
          }else{
            $msg = array('status' => 202, 'error' => 'Cannot announce players 11 manually');
            return Services::response()->setStatusCode(202)
                      ->setHeader('Access-Control-Allow-Origin', '*')
                      ->setHeader('Access-Control-Allow-Headers', 'Origin')
                      ->setContentType('application/json', 'utf-8')
                      ->setJSON($msg);
          }

        }

      }else{
        $msg = array('status' => 202, 'error' => 'Cannot announce players 11 manually. Toss has not announced at');
        return Services::response()->setStatusCode(202)
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


  public function changeStartTime()
  {

    $uri = current_url(true);
    $userId = $uri->getSegment(5);
    $matchId = $uri->getSegment(7);
    
    $userData = $this->request->decoded->userData;

    $postmatchId = $this->request->getJsonVar('matchId');
    $postdate = $this->request->getJsonVar('date');

    if ($userId == $userData->id && !is_null($matchId) && $matchId == $postmatchId && !is_null($postdate) ) {

      $matchModel = new MatchesModel();
      $matchLookupModel = new MatchesLookupModel();
      $matchDetails = $matchLookupModel->checkMatchLookup($matchId);

      if (count($matchDetails) > 0) {

        $ctime = gmdate('Y-m-d H:i:s');
        $closeTime = date("Y-m-d H:i:s", strtotime($matchDetails[0]->startTime. ' -15 min'));

        if ($ctime <= $closeTime) {
          
          $startTime = date("Y-m-d H:i:s", strtotime($postdate));

          $matchLookupModel->updateMatchTime($matchId,$startTime);
          $matchModel->updateMatchTime($matchId,$startTime);

          $msg = array('status' => 200, 'msg' => 'Success');
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



  public function suspendMatch()
  {

    $uri = current_url(true);
    $userId = $uri->getSegment(5);
    $matchId = $uri->getSegment(7);
    $userData = $this->request->decoded->userData;
    $postmatchId = $this->request->getJsonVar('matchId');

    if ($userId == $userData->id && !is_null($matchId) && $matchId == $postmatchId) {

      //- Check any contest exist are not
      $matchLookupModel = new MatchesLookupModel();
      $matchModel = new MatchesModel();
      $contestModel = new ContestModel();
      $allContests = $contestModel->getMatchContests($matchId);

      if (count($allContests) == 0) {
        
        //- Suspend Match
        $matchModel->suspendMatch($matchId);

        $matchLookupModel->closeMatch($matchId);

        $msg = array('status' => 200, 'msg' => 'Success');
        return Services::response()->setStatusCode(200)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg);

      }else{
        $msg = array('status' => 202, 'error' => 'Matches cannot be suspended as there are one or more contests running on this match');
        return Services::response()->setStatusCode(202)
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


  public function getStatsSummary()
  {

    $uri = current_url(true);
    $userId = $uri->getSegment(5);

    $userData = $this->request->decoded->userData;

    if ($userId == $userData->id ) {
    
      $usersModel = new UsersModel();
      $userWalletModel = new UserWalletModel();
      $contestModel = new ContestModel();
      $matchesModel = new MatchesModel();
      $teamModel = new TeamsModel();
      $matchesLookUp = new MatchesLookupModel();

      $fromdate = $this->request->getGet('historyFromdate');
      $todate = $this->request->getGet('historyTodate');

      $res = array();
      if (!is_null($fromdate) && !is_null($todate)) {
        $res = array('fromdate' => $fromdate." 00:00:00", 'todate' => $todate." 23:59:59");
      }

      $signUps = $usersModel->getTotalSignUps($res);
      if (is_null($signUps)) {
        $signUps = 0;
      }
      
      $deposits = $userWalletModel->getTotalDeposits($res);
      if (count($deposits) > 0) {
        $deposits = $deposits[0]->credit;
        $deposits = sprintf('%0.2f', $deposits);
      }
      if (is_null($deposits)) {
        $deposits = 0;
      }

      $bonus = $userWalletModel->getTotalBonus($res);
      if (count($bonus) > 0) {
        $bonus = $bonus[0]->credit;
        $bonus = sprintf('%0.2f', $bonus);
      }
      if (is_null($bonus)) {
        $bonus = 0;
      }

      $promobonus = $userWalletModel->getTotalPromosionBonus($res);
      if (count($promobonus) > 0) {
        $promobonus = $promobonus[0]->credit;
        $promobonus = sprintf('%0.2f', $promobonus);
      }
      if (is_null($promobonus)) {
        $promobonus = 0;
      }

      $gst = $userWalletModel->getTotalGst($res);
      if (count($gst) > 0) {
        $gst = $gst[0]->debit;
        $gst = sprintf('%0.2f', $gst);
      }
      if (is_null($gst)) {
        $gst = 0;
      }      

      $tds = $userWalletModel->getTotalTDS($res);
      if (count($tds) > 0) {
        $tds = $tds[0]->debit;
        $tds = sprintf('%0.2f', $tds);
      }            
      if (is_null($tds)) {
        $tds = 0;
      }

      $withDraw = $userWalletModel->getTotalWithDraw($res);
      if (count($withDraw) > 0) {
        $withDraw = $withDraw[0]->debit;
        $withDraw = sprintf('%0.2f', $withDraw);
      }
      if (is_null($withDraw)) {
        $withDraw = 0;
      }

      $complMatchs = $matchesLookUp->getTotalCompletedMatch($res);
      if (is_null($complMatchs)) {
        $complMatchs = 0;
      }

      $gameRooms = $contestModel->getGameRooms($res);
      if (is_null($gameRooms)) {
        $gameRooms = 0;
      }

      $platformFee = $contestModel->getPlatFormFee($res);
      if (count($platformFee) > 0) {
        $platformFee = $platformFee[0]->platform;
        $platformFee = sprintf('%0.2f', $platformFee);
      }
      if (is_null($platformFee)) {
        $platformFee = 0; 
      }


      $matches = $contestModel->getRunningMatches();

      $livedata = array();
      if (count($matches) > 0) {
        foreach ($matches as $mkey => $mvalue) {
          
          $totalRooms = $contestModel->getTotalMatchRooms($mvalue->matchId);

          $matchDetails = $matchesModel->getMatcheById($mvalue->matchId);

          $room1 = $contestModel->getTotal1Rooms($mvalue->matchId);
          $room2 = $contestModel->getTotal2Rooms($mvalue->matchId);
          $room3 = $contestModel->getTotal3Rooms($mvalue->matchId);
          $room4 = $contestModel->getTotal4Rooms($mvalue->matchId);
          $waitroom = $contestModel->getTotalWaitingRooms($mvalue->matchId);

          $teama = $teamModel->getTeamById($matchDetails[0]->teamaId);
          $teamb = $teamModel->getTeamById($matchDetails[0]->teambId);

          $name = $teama[0]->shortName.' vs '.$teamb[0]->shortName;

          array_push($livedata, array('total' => $totalRooms, 'room1' => $room1, 'room2' => $room2, 'room3' => $room3, 'room4' => $room4, 'waitroom' => $waitroom, 'name' => $name, 'matchId' => $mvalue->matchId, 'startTime' => $matchDetails[0]->startTime));

        }
      }      

      $data = array('platformFee' => $platformFee, 'signUps' => $signUps, 'deposits' => $deposits, 'bonus' => $bonus, 'gst' => $gst, 'tds' => $tds, 'withDraw' => $withDraw, 'complMatchs' => $complMatchs, 'gameRooms' => $gameRooms, 'platformFee' => $platformFee, 'livedata' => $livedata, 'promobonus' => $promobonus );

      $msg = array('status' => 200, 'data' => $data);
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

  }



  public function getAllMatches()
  {

    $uri = current_url(true);
    $userId = $uri->getSegment(5);
    $userData = $this->request->decoded->userData;

    if ($userId == $userData->id) {
    
      $fromdate = $this->request->getGet('from');
      $todate = $this->request->getGet('to');

      $dur = array();
      if (!is_null($fromdate) && !is_null($todate)) {
        $dur = array('fromdate' => $fromdate." 00:00:00", 'todate' => $todate." 23:59:59");
      }

      //- Get All Matches
      $contestModel = new ContestModel();
      $matchModel = new MatchesModel();
      $teamModel = new TeamsModel();
      $matchDetails = $matchModel->getAllMatches($dur);

      $details = array();
      if (count($matchDetails) > 0) {
        foreach ($matchDetails as $mdkey => $mdvalue) {

          $teamaId = $teamModel->getTeamById($mdvalue->teamaId);
          if (count($teamaId) > 0) {
            $teamaId = $teamaId[0]->shortName;
          }else{
            $teamaId = '';
          }
          
          $teambId = $teamModel->getTeamById($mdvalue->teambId);
          if (count($teambId) > 0) {
            $teambId = $teambId[0]->shortName;
          }else{
            $teambId = '';
          }

          $matchRooms = $contestModel->getTotalAllMatchRooms($mdvalue->matchId);

          array_push($details, array('matchId' => $mdvalue->matchId, 'seriesId' => $mdvalue->seriesId, 'title' => $mdvalue->title, 'name' => $mdvalue->name, 'teamaId' => $teamaId, 'teambId' => $teambId, 'startTime' => $mdvalue->startTime, 'matchType' => $mdvalue->matchType, 'matchRooms' => $matchRooms ) );
          
        }
      }

      $msg = array('status' => 200, 'msg' => 'Success', 'data' => $details );
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

  
  public function getAllGameRooms()
  {

    $uri = current_url(true);
    $userId = $uri->getSegment(5);
    $matchId = $uri->getSegment(7);
    $type = $this->request->getGet('type');

    $userData = $this->request->decoded->userData;

    if ($userId == $userData->id) {
    

      $teamModel = new TeamsModel();
      $usersModel = new UsersModel();
      $contestModel = new ContestModel();
      $contests = $contestModel->getAllMatchContests($matchId);

      if (count($contests) > 0) {
        foreach ($contests as $cokey => $covalue) {
          
          $userOne = $usersModel->getUserById($covalue->userOne);
          $userTwo = $usersModel->getUserById($covalue->userTwo); 

          if (count($userOne) > 0) {
            $covalue->userOneName = $userOne[0]->name;
          }else{
            $covalue->userOneName = '';
          }

          if (count($userTwo) > 0) {
            $covalue->userTwoName = $userTwo[0]->name;
          }else{
            $covalue->userTwoName = '';
          }

          $userOneTeam = $teamModel->getTeamById($covalue->userOneTeam);
          if (count($userOneTeam) > 0) {
            $covalue->userOneTeamName = $userOneTeam[0]->shortName;
          }else{
            $covalue->userOneTeamName = '';
          }

          $userTwoTeam = $teamModel->getTeamById($covalue->userTemTeam);
          if (count($userTwoTeam) > 0) {
            $covalue->userTemTeamName = $userTwoTeam[0]->shortName;
          }else{
            $covalue->userTemTeamName = '';
          }

          $priceDetails = $contestModel->getCampPrices($covalue->contestId,$type);
          if (count($priceDetails) > 0) {
            $covalue->tmpDiff = $priceDetails[0]->maxDiff;
          }else{
            $covalue->tmpDiff = 0;
          }

        }
      }

      $msg = array('status' => 200, 'msg' => 'Success', 'data' => $contests );
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
<?php

namespace App\Controllers;
use App\Models\MatchesModel;
use App\Models\TeamsModel;
use App\Models\PlayersModel;
use App\Models\SeriesModel;
use App\Models\MatchesLookupModel;
use App\Models\MatchPlayerScores;
use App\Models\UserWalletModel;
use Config\Services;

class Matches extends BaseController
{

    //- Show All On Going Matches : object
    public function onGoingMatches(): object
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;

      if ($userId == $userData->id) {

        $startTime = gmdate("Y-m-d H:i:s",strtotime("+31 min"));
        $endTime = gmdate("Y-m-d H:i:s",strtotime("+1 days"));

        $userWalletModel = new UserWalletModel();
      	$matchModel = new MatchesModel();
      	$teamModel = new TeamsModel();
      	$matchDetails = $matchModel->getOnlyOnGoingMatches($startTime,$endTime);

        $bonusPop = false;
        $bonus = $userWalletModel->getSignUpBonus($userId);
        if (count($bonus) > 0) {
          $tmpDate = date("Y-m-d H:i:s",strtotime($bonus[0]->dateCreated));
          $tmpDts = gmdate("Y-m-d H:i:s",strtotime("-5 min"));
          if ($tmpDate > $tmpDts) {
            $bonusPop = true;
          }
        }

      	$matchesList = array();
      	if (count($matchDetails) > 0) {
      		foreach ($matchDetails as $key => $value) {
      
      			$team1 = $teamModel->getTeamById($value->teamaId);
      			$team2 = $teamModel->getTeamById($value->teambId);

      			array_push($matchesList, array('title' => $value->name.' '.$value->title, 'startTime' => $value->startTime, 'matchId' => $value->matchId, 'matchType' => $value->matchType, 'team1Name' => $team1[0]->shortName, 'team1Logo' => $team1[0]->logo, 'team2Name' => $team2[0]->shortName, 'team2Logo' => $team2[0]->logo, 'hasPlayers' => $value->hasPlayers));
      		}
      	}

      	$msg = array('list' => $matchesList, 'version' => '1.0.3', 'bonusPop' => $bonusPop);
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


    //- Show All Up Coming Matches
    public function upComingMatches(): object
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;

      if ($userId == $userData->id) {
        /*
        $startTime = gmdate("Y-m-d",strtotime("+3 days"));
        $startTime = $startTime.' 00:00:00';
        */
        $startTime = gmdate("Y-m-d H:i:s",strtotime("+1 days"));

        $endTime = gmdate("Y-m-d",strtotime("+6 days"));
        $endTime = $endTime.' 23:59:59';

      	$matchModel = new MatchesModel();
      	$teamModel = new TeamsModel();
      	$matchDetails = $matchModel->getOnGoingMatches($startTime,$endTime);

      	$matchesList = array();
      	if (count($matchDetails) > 0) {
      		foreach ($matchDetails as $key => $value) {
      
      			$team1 = $teamModel->getTeamById($value->teamaId);
      			$team2 = $teamModel->getTeamById($value->teambId);

      			array_push($matchesList, array('title' => $value->name.' '.$value->title, 'startTime' => $value->startTime, 'matchId' => $value->matchId, 'matchType' => $value->matchType, 'team1Name' => $team1[0]->shortName, 'team1Logo' => $team1[0]->logo, 'team2Name' => $team2[0]->shortName, 'team2Logo' => $team2[0]->logo, 'hasPlayers' => $value->hasPlayers ));
      		}
      	}

      	$msg = array('list' => $matchesList);
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


    //- Get Match Squads List
    public function matchSquadsExist(): object
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $userData = $this->request->decoded->userData;

      if ($userId == $userData->id) {

        //- Check Ongoing Match, Match Exist 
        $matchId = $uri->getSegment(6);

        $startTime = gmdate("Y-m-d H:i:s",strtotime("+31 min"));
        $endTime = gmdate("Y-m-d",strtotime("+8 days"));
        $endTime = $endTime.' 00:00:00';

        $matchModel = new MatchesModel();
        $matchDetails = $matchModel->checkOnGoingMatche($matchId,$startTime,$endTime);

        if (count($matchDetails) > 0) {

          if ($matchDetails[0]->tossWinner == '0') {

            $playerModel = new PlayersModel();

            //- Check Team One Exist 
            $team1 = $playerModel->getTeamPlayes($matchDetails[0]->matchId,$matchDetails[0]->teamaId);

            //- Check Team Two Exist
            $team2 = $playerModel->getTeamPlayes($matchDetails[0]->matchId,$matchDetails[0]->teambId);

            if (count($team1) > 0 && count($team2) > 0) {
                  
              $msg = array('msg' => array('team1' => $team1, 'team2' => $team2), 'status' => true);
              return $this->response->setStatusCode(200)
                          ->setHeader('Access-Control-Allow-Origin', '*')
                          ->setHeader('Access-Control-Allow-Headers', 'Origin')
                          ->setContentType('application/json', 'utf-8')
                          ->setJSON($msg);           

            }else{

              $msg = array('status' => 204, 'msg' => 'No Squad', 'status' => false);
              return $this->response->setStatusCode(200)
                          ->setHeader('Access-Control-Allow-Origin', '*')
                          ->setHeader('Access-Control-Allow-Headers', 'Origin')
                          ->setContentType('application/json', 'utf-8')
                          ->setJSON($msg);

            }

          }else{

            $msg = array('status' => 204, 'msg' => 'Started', 'status' => false);
            return $this->response->setStatusCode(200)
                        ->setHeader('Access-Control-Allow-Origin', '*')
                        ->setHeader('Access-Control-Allow-Headers', 'Origin')
                        ->setContentType('application/json', 'utf-8')
                        ->setJSON($msg);            
          }

        }else{

          $msg = array('status' => 204, 'error' => 'No Content', 'status' => false);
          return $this->response->setStatusCode(204)
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


    //- Get Player Stats
    public function getPlayerStats()
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $matchId = $uri->getSegment(6);
      $playerId = $uri->getSegment(8);
      $userData = $this->request->decoded->userData;

      if ($userId == $userData->id) {

        //- Check Match Player 
        $playerModel = new PlayersModel();
        $matchPlayer = $playerModel->getMatchPlayer($matchId,$playerId);

        if (count($matchPlayer) > 0) {

          //- Get Player By Id
          $playerDetails = $playerModel->getPlayerById($matchPlayer[0]->playerId);

          if (count($playerDetails) > 0) {
            
            $teamModel = new TeamsModel();
            $teamDetails = $teamModel->getTeamById($matchPlayer[0]->teamId);
            $stats = $playerModel->getPlayerStats($playerId);

            $msg = array('name' => $playerDetails[0]->name, 'image' => $playerDetails[0]->faceImageId, 'country' => $teamDetails[0]->name, 'stats' => $stats);

            $msg = array('status' => 200, 'msg' => $msg);
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


    //- Show All Up Coming Matches
    public function upWebComingMatches(): object
    {

      $startTime = gmdate("Y-m-d H:i:s",strtotime("+1 days"));
      $startTime = $startTime.' 00:00:00';

      $endTime = gmdate("Y-m-d",strtotime("+6 days"));
      $endTime = $endTime.' 23:59:59';

      $matchModel = new MatchesModel();
      $teamModel = new TeamsModel();
      $matchDetails = $matchModel->getOnGoingMatches($startTime,$endTime);

      $matchesList = array();
      if (count($matchDetails) > 0) {
        foreach ($matchDetails as $key => $value) {

          $team1 = $teamModel->getTeamById($value->teamaId);
          $team2 = $teamModel->getTeamById($value->teambId);
          $team1Logo = $team1[0]->logo;
          $team2Logo = $team2[0]->logo;

          if ($value->name == 'Indian Premier League') {
            $team1Logo = '';
            $team2Logo = '';
          }

          array_push($matchesList, array('title' => $value->name.' '.$value->title, 'startTime' => $value->startTime, 'matchId' => $value->matchId, 'matchType' => $value->matchType, 'team1Name' => $team1[0]->shortName, 'team1Logo' => $team1Logo, 'team2Name' => $team2[0]->shortName, 'team2Logo' => $team2Logo ));


        }
      }   

      $msg = array('list' => $matchesList);
      return $this->response->setStatusCode(200)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg); 

    }


    //- Show Web Up Completed Matches
    public function upWebCompletedMatches()
    {

      $matchLookModel = new MatchesLookupModel();
      $teamModel = new TeamsModel();
      $seriesModel = new SeriesModel();
      $matchDetails = $matchLookModel->getCompletedMatches();

      $matchesList = array();
      if (count($matchDetails) > 0) {
        foreach ($matchDetails as $mdkey => $mdvalue) {
          
          $series = $seriesModel->checkSeriesExist($mdvalue->cricksId);

          $team1 = $teamModel->getTeamById($mdvalue->teamaId);
          $team2 = $teamModel->getTeamById($mdvalue->teambId);

          array_push($matchesList, array('title' => $series[0]->name,'startTime' => $mdvalue->startTime, 'matchId' => $mdvalue->matchId, 'matchType' => $mdvalue->matchType, 'team1Name' => $team1[0]->shortName, 'team1Logo' => $team1[0]->logo, 'team2Name' => $team2[0]->shortName, 'team2Logo' => $team2[0]->logo, 'teamAScore' => $mdvalue->teamAScore,'teamAWick' => $mdvalue->teamAWick, 'teamAOvers' => $mdvalue->teamAOvers, 'teamBScore' => $mdvalue->teamBScore, 'teamBWick' => $mdvalue->teamBWick, 'teamBOvers' =>  $mdvalue->teamBOvers, 'lookupId' => $mdvalue->id ));

        }
      }

      $msg = array('list' => $matchesList);
      return $this->response->setStatusCode(200)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg); 


    }


    //- Show Web Running Matches 
    public function upWebRunningMatches()
    {

      $teamModel = new TeamsModel();
      $seriesModel = new SeriesModel();
      $matchLookModel = new MatchesLookupModel();
      $matchDetails = $matchLookModel->getRunningMatches();

      $matchesList = array();
      if (count($matchDetails) > 0) {
        foreach ($matchDetails as $mdkey => $mdvalue) {
          
          $series = $seriesModel->checkSeriesExist($mdvalue->cricksId);

          $team1 = $teamModel->getTeamById($mdvalue->teamaId);
          $team2 = $teamModel->getTeamById($mdvalue->teambId);

          array_push($matchesList, array('title' => $series[0]->name,'startTime' => $mdvalue->startTime, 'matchId' => $mdvalue->matchId, 'matchType' => $mdvalue->matchType, 'team1Name' => $team1[0]->shortName, 'team1Logo' => $team1[0]->logo, 'team2Name' => $team2[0]->shortName, 'team2Logo' => $team2[0]->logo, 'teamAScore' => $mdvalue->teamAScore,'teamAWick' => $mdvalue->teamAWick, 'teamAOvers' => $mdvalue->teamAOvers, 'teamBScore' => $mdvalue->teamBScore, 'teamBWick' => $mdvalue->teamBWick, 'teamBOvers' =>  $mdvalue->teamBOvers, 'lookupId' => $mdvalue->id ));

        }
      }

      $msg = array('list' => $matchesList);
      return $this->response->setStatusCode(200)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg); 


    }


    //- Get Match Scores
    public function upWebMatcheScores()
    {

      $uri = current_url(true);
      $matchId = $uri->getSegment(2);

      $matchLookModel = new MatchesLookupModel();
      $playerScores = new MatchPlayerScores();
      $players = new PlayersModel();
      $teamModel = new TeamsModel();

      if (!empty($matchId)) {

        $matcInni = array();
        $matchDetails = $matchLookModel->getMatchById($matchId);

        if (count($matchDetails) > 0) {

          $minnings = $playerScores->getMatchInnings($matchDetails[0]->matchId);

          if (count($minnings) > 0) {
            foreach ($minnings as $mikey => $mivalue) {

              $team = $teamModel->getTeamById($mivalue->teamId);

              $in = ['1','2'];
              $score = 0;
              $wick = 0;
              $overs = 0;
              if (in_array($mivalue->inningsId, $in)) {

                if ($mivalue->teamId == $matchDetails[0]->teamaId) {
                  $score = $matchDetails[0]->teamAInn1Score;
                  $wick = $matchDetails[0]->teamAInn1Wick;
                  $overs = $matchDetails[0]->teamAInn1Overs;
                }else if ($mivalue->teamId == $matchDetails[0]->teambId) {
                  $score = $matchDetails[0]->teamBInn1Score;
                  $wick = $matchDetails[0]->teamBInn1Wick;
                  $overs = $matchDetails[0]->teamBInn1Overs;
                }

              }else{

                if ($mivalue->teamId == $matchDetails[0]->teamaId) {
                  $score = $matchDetails[0]->teamAInn2Score;
                  $wick = $matchDetails[0]->teamAInn2Wick;
                  $overs = $matchDetails[0]->teamAInn2Overs;
                }else if ($mivalue->teamId == $matchDetails[0]->teambId) {
                  $score = $matchDetails[0]->teamBInn2Score;
                  $wick = $matchDetails[0]->teamBInn2Wick;
                  $overs = $matchDetails[0]->teamBInn2Overs;
                }

              }
              
              //- Get Scores By Innings
              $scoreCard = $playerScores->getMatchInningScores($matchDetails[0]->matchId,$mivalue->inningsId);

              if (count($scoreCard) > 0) {
                foreach ($scoreCard as $skey => $svalue) {
                  $payDeal = $players->getPlayerById($svalue->playerId);
                  $scoreCard[$skey]->name = $payDeal[0]->name;
                }
              }

              array_push($matcInni, array('inningsId' => $mivalue->inningsId, 'teamId' => $mivalue->teamId, 'name' => $team[0]->name, 'image' => $team[0]->logo, 'score' => $score, 'wick' => $wick, 'overs' => $overs, 'scoreCard' => $scoreCard));


            }
          }

        }

        $msg = array('innings' => $matcInni);
        return $this->response->setStatusCode(200)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);        
       
      }

    }


    //- 
    public function appMatcheScores()
    {

      $uri = current_url(true);
      $userId = $uri->getSegment(4);
      $matchId = $uri->getSegment(6);
      
      $userData = $this->request->decoded->userData;

      if (!empty($matchId) && $userId == $userData->id ) {

        $matchLookModel = new MatchesLookupModel();
        $playerScores = new MatchPlayerScores();
        $players = new PlayersModel();
        $teamModel = new TeamsModel();      

        $matcInni = array();
        $matchDetails = $matchLookModel->checkMatchLookup($matchId);

        if (count($matchDetails) > 0) {

          $teamAdetails = $teamModel->getTeamById($matchDetails[0]->teamaId);
          $teamBdetails = $teamModel->getTeamById($matchDetails[0]->teambId);

          if (count($teamAdetails) > 0) {
            $teamAdetails = array('name' => $teamAdetails[0]->name, 'logo' => $teamAdetails[0]->logo);
          }else{
            $teamAdetails = array();
          }

          if (count($teamBdetails) > 0) {
            $teamBdetails = array('name' => $teamBdetails[0]->name, 'logo' => $teamBdetails[0]->logo);
          }else{
            $teamBdetails = array();
          }

          $minnings = $playerScores->getMatchInnings($matchDetails[0]->matchId);

          if (count($minnings) > 0) {
            foreach ($minnings as $mikey => $mivalue) {

              $team = $teamModel->getTeamById($mivalue->teamId);

              $in = ['1','2'];
              $score = 0;
              $wick = 0;
              $overs = 0;
              if (in_array($mivalue->inningsId, $in)) {

                if ($mivalue->teamId == $matchDetails[0]->teamaId) {
                  $score = $matchDetails[0]->teamAInn1Score;
                  $wick = $matchDetails[0]->teamAInn1Wick;
                  $overs = $matchDetails[0]->teamAInn1Overs;
                }else if ($mivalue->teamId == $matchDetails[0]->teambId) {
                  $score = $matchDetails[0]->teamBInn1Score;
                  $wick = $matchDetails[0]->teamBInn1Wick;
                  $overs = $matchDetails[0]->teamBInn1Overs;
                }

              }else{

                if ($mivalue->teamId == $matchDetails[0]->teamaId) {
                  $score = $matchDetails[0]->teamAInn2Score;
                  $wick = $matchDetails[0]->teamAInn2Wick;
                  $overs = $matchDetails[0]->teamAInn2Overs;
                }else if ($mivalue->teamId == $matchDetails[0]->teambId) {
                  $score = $matchDetails[0]->teamBInn2Score;
                  $wick = $matchDetails[0]->teamBInn2Wick;
                  $overs = $matchDetails[0]->teamBInn2Overs;
                }

              }
              
              //- Get Scores By Innings
              $scoreCard = $playerScores->getMatchInningScores($matchDetails[0]->matchId,$mivalue->inningsId);

              if (count($scoreCard) > 0) {
                foreach ($scoreCard as $skey => $svalue) {
                  $payDeal = $players->getPlayerById($svalue->playerId);
                  $scoreCard[$skey]->name = $payDeal[0]->name;
                }
              }

              array_push($matcInni, array('inningsId' => $mivalue->inningsId, 'teamId' => $mivalue->teamId, 'name' => $team[0]->name, 'image' => $team[0]->logo, 'score' => $score, 'wick' => $wick, 'overs' => $overs, 'scoreCard' => $scoreCard));


            }
          }

        }

        $msg = array('innings' => $matcInni, 'teamAdetails' => $teamAdetails, 'teamBdetails' => $teamBdetails);
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


    public function matchtimeupdate(){

      $startTime = gmdate("Y-m-d H:i:s");
      $startTime = $startTime.' 00:00:00';

      $endTime = gmdate("Y-m-d",strtotime("+6 days"));
      $endTime = $endTime.' 23:59:59';

      $matchModel = new MatchesModel();
      $teamModel = new TeamsModel();
      $matchDetails = $matchModel->getOnGoingMatches($startTime,$endTime);

      $matchesList = array();
      if (count($matchDetails) > 0) {
        foreach ($matchDetails as $key => $value) {

          $team1 = $teamModel->getTeamById($value->teamaId);
          $team2 = $teamModel->getTeamById($value->teambId);

          array_push($matchesList, array('title' => $value->name.' '.$value->title, 'matchId' => $value->matchId));

        } 
      }

      $data = array('list' => $matchesList);  

      return view('matcheslist',$data);

    }


    public function updatematchtime(){
      
      $matchId = $this->request->getPost('matchId');
      $startTime = $this->request->getPost('startTime');
      $startTime = date("Y-m-d H:i:s", strtotime($startTime));

      $matchModel = new MatchesModel();
      $matchLookModel = new MatchesLookupModel();

      $matchLookModel->updateMatchTime($matchId,$startTime);
      $matchModel->updateMatchTime($matchId,$startTime);

      echo "Match Time Updated..";

    }


}
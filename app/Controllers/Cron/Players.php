<?php

namespace App\Controllers\Cron;
use App\Controllers\BaseController;
use App\Models\MatchesModel;
use App\Models\UserModel;
use App\Models\SeriesModel;
use App\Models\TeamsModel;
use Config\Services;

class Players extends BaseController
{

    public function __construct(){
    	helper('Common');
    }


    //- Get Players on the day
    public function getMatchPlayers(){
      
      $time = gmdate('Y-m-d');
      $etime = gmdate('Y-m-d', strtotime("+2160 minutes"));

      $startTime = $time.' 00:00:00';
      $endTime = $etime.' 23:59:59';

      $matchModel = new MatchesModel();
      $matchDetails = $matchModel->getNoPlayerMatches($startTime,$endTime);


      //- Get All User Tokens
      $teamsModel = new TeamsModel();
      $userModel = new UserModel();
      $serModel = new SeriesModel();

      /*  
      $userAllDetails = $userModel->getAllUserToken();
      $allTokens = array();
      
      if (count($userAllDetails) > 0) {
        foreach ($userAllDetails as $uakey => $uavalue) {
          if (!in_array($uavalue->token,$allTokens)) {
            array_push($allTokens, $uavalue->token);
          }
        }
      }*/

      if (count($matchDetails) > 0) {
        foreach ($matchDetails as $mkey => $mvalue) {

          $teamAsucces = false;
          $teamBsucces = false;

          $playersUrl = getenv('players');
          $playersUrl = str_replace("%teamId%", $mvalue->teamaKey, $playersUrl);
          $playersUrl = str_replace("%seasonId%", $mvalue->seasonId, $playersUrl);

          try {

            $curl = \Config\Services::curlrequest();
            $response = $curl->request('GET', $playersUrl.'?api_token='.getenv('sportMonksKey'));

            $responce = $response->getStatusCode();
            $error = '';

          } catch (\Exception $e) {
            $error = $e->getMessage();
            $responce = '500';
          }

          if ($responce == 200) {

            if (!empty($response->getBody())) {

              $responce = json_decode($response->getBody());
              if (count($responce->data->squad) > 0) {
                $palays = $responce->data->squad;
                CreateMatchPlayers($palays,$mvalue->matchId,$mvalue->teamaId);
                $teamAsucces = true;
              }

            }

          }else{

            //- Update Error Log 
            if (!empty($error)) {
              
              $data['userId'] = '0';
              $data['text'] = $mvalue->startTime.' Unable To Fetch Players For Match - '.$mvalue->crickmId.' - team '.$mvalue->teamaKey;
              $data['disc'] = $error;
              CreateErrorLog($data);
              unset($data);

            }else{

              $data['userId'] = '0';
              $data['text'] = $mvalue->startTime.' Unable To Fetch Players For Match - '.$mvalue->crickmId.' - team '.$mvalue->teamaKey;
              $data['disc'] = 'Cricbuzz API not responding need manual inspection';
              CreateErrorLog($data);
              unset($data);
            }

            
            //- Send Message To Admin
            $data['subject'] = 'Match Players Failed';
            $data['message'] = 'Match Players Api error. Please run it manually - '.$mvalue->crickmId.' - team '.$mvalue->teamaKey;
            SendErrorEmail($data);
            unset($data);
          
          }

          //- Team B
          $playersUrl = getenv('players');
          $playersUrl = str_replace("%teamId%", $mvalue->teambKey, $playersUrl);
          $playersUrl = str_replace("%seasonId%", $mvalue->seasonId, $playersUrl);

          try {

            $curl = \Config\Services::curlrequest();
            $response = $curl->request('GET', $playersUrl.'?api_token='.getenv('sportMonksKey'));

            $responce = $response->getStatusCode();
            $error = '';

          } catch (\Exception $e) {
            $error = $e->getMessage();
            $responce = '500';
          }

          if ($responce == 200) {

            if (!empty($response->getBody())) {

              $responce = json_decode($response->getBody());
              if (count($responce->data->squad) > 0) {
                $palays = $responce->data->squad;
                CreateMatchPlayers($palays,$mvalue->matchId,$mvalue->teambId);
                $teamBsucces = true;
              }

            }            

          }else{

            //- Update Error Log 
            if (!empty($error)) {
              
              $data['userId'] = '0';
              $data['text'] = $mvalue->startTime.' Unable To Fetch Players For Match - '.$mvalue->crickmId.' - team '.$mvalue->teambKey;
              $data['disc'] = $error;
              CreateErrorLog($data);
              unset($data);

            }else{

              $data['userId'] = '0';
              $data['text'] = $mvalue->startTime.' Unable To Fetch Players For Match - '.$mvalue->crickmId.' - team '.$mvalue->teambKey;
              $data['disc'] = 'Cricbuzz API not responding need manual inspection';
              CreateErrorLog($data);
              unset($data);
            }

            
            //- Send Message To Admin
            $data['subject'] = 'Match Players Failed';
            $data['message'] = 'Match Players Api error. Please run it manually - '.$mvalue->crickmId.' - team '.$mvalue->teambKey;
            SendErrorEmail($data);
            unset($data);
          
          }


          //- Update Match Player Status
          if ($teamAsucces && $teamBsucces) {
            
            $matchModel->updateMatchHasPlayers($mvalue->matchId);

            //- Send Notification
            /*if (count($allTokens) > 0) {
              
              $teamADetails = $teamsModel->getTeamById($mvalue->teamaId);
              $teamBDetails = $teamsModel->getTeamById($mvalue->teambId);

              $body = "Final squads for ".$mvalue->name." ".$mvalue->title." (".$teamADetails[0]->shortName." vs ".$teamBDetails[0]->shortName.") are out. Join a contest NOW!!";

              $notifi = array("title" => "Player6", "body" => $body);
              $tmpBody = array('notification' => $notifi, 'registration_ids' => $allTokens);
              $tmpBody = json_encode($tmpBody);

              sendPushNotifi($tmpBody);

            }*/

          }

          //- End

        }
      }



    }


}
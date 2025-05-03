<?php

namespace App\Controllers\Cron;
use App\Controllers\BaseController;
use App\Models\SeriesModel;
use App\Models\MatchesModel;
use App\Models\MatchesLookupModel;
use Config\Services;

class Matches extends BaseController
{

    public function __construct(){
      helper('Common');
    }


    //- Get All Matches In a Day From Sportmonks Api
    public function getMatches()
    {

      //- Today
      $tmpStartDate = gmdate('Y-m-d');
      $tmpEndDate = gmdate('Y-m-d',strtotime("+7 days"));
      $utcTimeNow =  gmdate('Y-m-d H:i:s');
    
      try {

        $curl = \Config\Services::curlrequest();
        $response = $curl->request('get', getenv('matches').$tmpStartDate.','.$tmpEndDate.'&api_token='.getenv('sportMonksKey'));
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
          
          if (count($responce) > 0) {
            $seriesModel = new SeriesModel();
            $matchModel = new MatchesModel();
            $matchLookModel = new MatchesLookupModel();
            foreach ($responce as $rkey => $rvalue) {

              //- Check Series Exist
              $seriesDeails = $seriesModel->checkSeriesExist($rvalue->league->id);

              if (count($seriesDeails) == 0) {

                //- Update Series name and crick key and notify admin
                $data['serId'] = $rvalue->league->id;
                $data['name'] = $rvalue->league->name;
                $data['sname'] = $rvalue->league->code;
                $data['date'] = date("Y-m-d H:i:s", strtotime($rvalue->league->updated_at));
                $data['type'] = $rvalue->league->type;
                CreateSeries($data);
                unset($data);

                $data['userId'] = '0';
                $data['text'] = 'New Series Got Added';
                $data['disc'] = 'A New Series Got Created. Unable To Fetch Start Date And End Date Need To Add manually';
                CreateErrorLog($data);
                unset($data);

                $seriesDeails = $seriesModel->checkSeriesExist($rvalue->league->id);
              }

              //- Match info loop
              $startDate = date("Y-m-d H:i:s", strtotime($rvalue->starting_at));

              if ($startDate > $utcTimeNow && $seriesDeails[0]->status == '1') {
                
                //- Check Team One
                if (!empty($rvalue->localteam->id)) {

                  $t1Details = CheckTeam($rvalue->localteam->id);
                  if (count($t1Details) == 0) {
                    $image_path = str_replace(getenv('teamImg'), "", $rvalue->localteam->image_path);
             
                    $data['cricktId'] = $rvalue->localteam->id;
                    $data['name'] = $rvalue->localteam->name;
                    $data['shortName'] = $rvalue->localteam->code;
                    $data['logo'] = $image_path;
                    CreateTeam($data);
                    unset($data);
                    $t1Details = CheckTeam($rvalue->localteam->id);
                  }

                }

                //- Check Team Two
                if (!empty($rvalue->visitorteam->id)) {

                  $t2Details = CheckTeam($rvalue->visitorteam->id);
                  if (count($t2Details) == 0) {
                    $image_path = str_replace(getenv('teamImg'), "", $rvalue->visitorteam->image_path);

                    $data['cricktId'] = $rvalue->visitorteam->id;
                    $data['name'] = $rvalue->visitorteam->name;
                    $data['shortName'] = $rvalue->visitorteam->code;
                    $data['logo'] = $image_path;
                    CreateTeam($data);
                    unset($data);
                    $t2Details = CheckTeam($rvalue->visitorteam->id);
                  }

                }

                //- Check Match
                $matchDetails = $matchModel->checkMatchExist($rvalue->id);

                if (count($matchDetails) == 0) {

                  $sdate = date('Y-m-d H:i:s', strtotime($rvalue->starting_at));

                  if ($rvalue->type == 'ODI' || $rvalue->type == 'Youth ODI' || $rvalue->type == 'List A') {
                    
                    $data['matchType'] = '1';
                    $edate = date('Y-m-d H:i:s', strtotime($sdate." +480 minutes"));

                  }else if ($rvalue->type == 'TEST' || $rvalue->type == '4day' || $rvalue->type == 'Test/5day') {
                    
                    $data['matchType'] = '3';
                    $edate = date('Y-m-d H:i:s', strtotime($sdate." +5 days"));

                  }else if ($rvalue->type == 'T20' || $rvalue->type == 'T20I' || $rvalue->type == 'T10') {
                    
                    $data['matchType'] = '2';
                    $edate = date('Y-m-d H:i:s', strtotime($sdate." +90 minutes"));

                  }else{
                    
                    $data['matchType'] = '2';
                    $edate = date('Y-m-d H:i:s', strtotime($sdate." +90 minutes"));

                  }

                  $arrayName = array('One Day International','One Day International Women', 'International League T20', 'Twenty20 International', 'One-Day Cup');

                  if (in_array($rvalue->league->name, $arrayName)) {
                    $name = $rvalue->stage->name;
                  }else{
                    $name = $rvalue->league->name;
                  }

                  $data['seriesId'] = $seriesDeails[0]->seriesId;
                  $data['cricksId'] = $rvalue->league_id;
                  $data['crickmId'] = $rvalue->id;
                  $data['teamaKey'] = $t1Details[0]->crickTeamId;
                  $data['teambKey'] = $t2Details[0]->crickTeamId;
                  $data['title'] = $rvalue->round;
                  $data['teamaId'] = $t1Details[0]->teamId;
                  $data['teambId'] = $t2Details[0]->teamId;
                  $data['starttime'] = $sdate;
                  $data['endtime'] = $edate;
                  $data['status'] = '1';
                  $data['name'] = $name;
                  $data['seasonId'] = $rvalue->season_id;
                  $matchModel->createMatch($data);
                  unset($data);

                }else{

                  $sdate = date('Y-m-d H:i:s', strtotime($rvalue->starting_at));

                  if ($rvalue->type == 'ODI' || $rvalue->type == 'Youth ODI' || $rvalue->type == 'List A') {
                    
                    $data['matchType'] = '1';
                    $edate = date('Y-m-d H:i:s', strtotime($sdate." +480 minutes"));

                  }else if ($rvalue->type == 'TEST' || $rvalue->type == '4day' || $rvalue->type == 'Test/5day') {
                    
                    $data['matchType'] = '3';
                    $edate = date('Y-m-d H:i:s', strtotime($sdate." +5 days"));

                  }else if ($rvalue->type == 'T20' || $rvalue->type == 'T20I' || $rvalue->type == 'T10') {
                    
                    $data['matchType'] = '2';
                    $edate = date('Y-m-d H:i:s', strtotime($sdate." +90 minutes"));

                  }else{
                    
                    $data['matchType'] = '2';
                    $edate = date('Y-m-d H:i:s', strtotime($sdate." +90 minutes"));

                  }

                  if ($rvalue->league->name == 'One Day International') {
                    $name = $rvalue->stage->name;
                  }else{
                    $name = $rvalue->league->name;
                  }

                  if ( ($matchDetails[0]->teamaKey != $t1Details[0]->crickTeamId) || ($matchDetails[0]->teambKey != $t2Details[0]->crickTeamId) || ($t1Details[0]->teamId != $matchDetails[0]->teamaId) || ($t2Details[0]->teamId != $matchDetails[0]->teambId) || ($sdate != $matchDetails[0]->startTime) || ($edate != $matchDetails[0]->endTime) || ($rvalue->season_id != $matchDetails[0]->seasonId) || ($rvalue->round != $matchDetails[0]->title) || ($name != $matchDetails[0]->name) || ($data['matchType'] != $matchDetails[0]->matchType) ) {
                      

                    # Update
                    if ($matchDetails[0]->teamaKey != $t1Details[0]->crickTeamId) {
                      $data['teamaKey'] = $t1Details[0]->crickTeamId;
                    }else{
                      $data['teamaKey'] = '';
                    }

                    if ($matchDetails[0]->teambKey != $t2Details[0]->crickTeamId) {
                      $data['teambKey'] = $t2Details[0]->crickTeamId;
                    }else{
                      $data['teambKey'] = '';
                    }
                    
                    if ($rvalue->round != $matchDetails[0]->title) {
                      $data['title'] = $rvalue->round;
                    }else{
                      $data['title'] = '';
                    }

                    if ($t1Details[0]->teamId != $matchDetails[0]->teamaId) {
                      $data['teamaId'] = $t1Details[0]->teamId;
                    }else{
                      $data['teamaId'] = '';
                    }

                    if ($t2Details[0]->teamId != $matchDetails[0]->teambId) {
                      $data['teambId'] = $t2Details[0]->teamId;
                    }else{
                      $data['teambId'] = '';
                    }

                    if ($sdate != $matchDetails[0]->startTime) {
                      $data['starttime'] = $sdate;
                    }else{
                      $data['starttime'] = '';
                    }
                    
                    if ($edate != $matchDetails[0]->endTime) {
                      $data['endtime'] = $edate;
                    }else{
                      $data['endtime'] = '';
                    }

                    if ($name != $matchDetails[0]->name) {
                      $data['name'] = $name;
                    }else{
                      $data['name'] = '';
                    }
                    
                    if ($rvalue->season_id != $matchDetails[0]->seasonId) {
                      $data['seasonId'] = $rvalue->season_id;
                    }else{
                      $data['seasonId'] = '';
                    }

                    if ($data['matchType'] == $matchDetails[0]->matchType) {
                      $data['matchType'] = '';
                    }

                    if (count($data) > 0) {

                      $matchModel->updateMatch($matchDetails[0]->id,$data);

                      //- Update Lookup Data
                      $matchLookModel->updateMatchLookup($matchDetails[0]->id,$data);

                    }
                    unset($data);

                  }

                }

              }

            }
          }

        }

      }else{

        //- Update Error Log 
        if (!empty($error)) {
          
          $data['userId'] = '0';
          $data['text'] = 'Unable To Fetch Matches';
          $data['disc'] = $error;
          CreateErrorLog($data);
          unset($data);

        }else{

          $data['userId'] = '0';
          $data['text'] = 'Unable To Fetch Matches';
          $data['disc'] = 'Sportmonks API not responding need manual inspection';
          CreateErrorLog($data);
          unset($data);

        }

        
        //- Send Message To Admin
        $data['subject'] = 'Matches Failed';
        $data['message'] = 'Matches Api error. Please run it manually';
        SendErrorEmail($data);
        unset($data);

      }


    }


}
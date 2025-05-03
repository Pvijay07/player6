<?php

if (!function_exists('SendErrorEmail')) {
  function SendErrorEmail($data){

    $email = \Config\Services::email();

    $email->clear();
    $email->setFrom(getenv('emailFrom'), getenv('emailDomain'));
    $email->setTo(getenv('adminEmail'));
    
    $email->setSubject($data['subject']);
    $email->setMessage($data['message']);
    $email->send();

  }
}


if (!function_exists('CreateErrorLog')) {
  
  function CreateErrorLog($data){
    $errorModel = new \App\Models\ErrorModel();
    $errorModel->createErrorLog($data);
    unset($errorModel);
    return true;
  }

}


if (!function_exists('CreateAccessTokenPayload')) {
  function CreateAccessTokenPayload($userData){

    $siteUrl = site_url();
    $iat = time();
    $exp = $iat + 3600;

    $payload = [
        'iss' => $siteUrl,
        'sub' => 'ou=api,o=player6,c=in',
        'aud' => 'urn:appUsers',
        'iat' => $iat,
        'nbf' => $iat,
        'exp' => $exp,
        'tokTy' => 'aces',
        'userData' => $userData      
    ];

    return $payload;


  }
}


if (!function_exists('CreateRefreshTokenPayload')) {
  function CreateRefreshTokenPayload($userData){

    $siteUrl = site_url();
    $iat = time();
    $exp = $iat + 2592000;

    $payload = [
        'iss' => $siteUrl,
        'sub' => 'ou=api,o=player6,c=in',
        'aud' => 'urn:appUsers',
        'iat' => $iat,
        'nbf' => $iat,
        'exp' => $exp,
        'tokTy' => 'ref',
        'userData' => $userData      
    ];

    return $payload;

  }
}


if (!function_exists('CreateTeam')) {

  function CreateTeam($data)
  {
    $teamModel = new \App\Models\TeamsModel();
    $teamModel->createTeam($data);
    unset($teamModel);
    return true;
  }

}


if (!function_exists('CheckTeam')) {

  function CheckTeam($data){
    $teamDetails = array();
    $teamModel = new \App\Models\TeamsModel();
    $teamDetails = $teamModel->checkTeamExist($data);
    unset($teamModel);
    return $teamDetails;
  }

}


if (!function_exists('CreateSeries')) {

  function CreateSeries($data){
    $seriesModel = new \App\Models\SeriesModel();
    $seriesModel = $seriesModel->createSeries($data);
    unset($seriesModel);
    return true;
  }

}


if (!function_exists('CreateMatchPlayers')) {
  function CreateMatchPlayers($palays,$matchId,$teamaId){

    $playerModel = new \App\Models\PlayersModel();

    if (count($palays) > 0) {
      foreach ($palays as $pkey => $pvalue) {
          
        //- Check Player Exist
        $playerDetails = $playerModel->checkPlayerExist($pvalue->id);
        if (count($playerDetails) == 0) {

          $faceImageId = str_replace(getenv('playerImg'), '', $pvalue->image_path);
          if (strpos($faceImageId, 'placeholder.png') !== false) {
            $faceImageId = '';
          }

          $data['playerKey'] = $pvalue->id;
          $data['nickName'] = $pvalue->lastname;
          $data['name'] = $pvalue->fullname;
          $data['faceImageId'] = $faceImageId;
          if (!empty($pvalue->position->name)) {
            $data['role'] = $pvalue->position->name;
          }else{
            $data['role'] = '';
          }

          if (!empty($pvalue->battingstyle)) {
            $data['battingStyle'] = $pvalue->battingstyle;
          }else{
            $data['battingStyle'] = '';
          }

          if (!empty($pvalue->bowlingstyle)) {
            $data['bowlingStyle'] = $pvalue->bowlingstyle;
          }else{
            $data['bowlingStyle'] = '';
          }
          
          $playerModel->createPlayer($data);
          $playerDetails = $playerModel->checkPlayerExist($pvalue->id); 
          unset($data);

          //- Get Players States 
          $statsUrl = getenv('playerStats');
          $statsUrl = str_replace("%playerId%", $pvalue->id, $statsUrl);

          try {

            $curl = \Config\Services::curlrequest();
            $sresponce = $curl->request('get', $statsUrl.'&api_token='.getenv('sportMonksKey'));
            $statsresponse = $sresponce->getStatusCode();
            $statserror = '';

          } catch (\Exception $e) {
            
            $statserror = $e->getMessage();
            $statsresponse = '500';

          }

          if ($statsresponse == '200') {

            $sresponce = json_decode($sresponce->getBody());
            $sresponce = $sresponce->data->career;

            if (count($sresponce) > 0) {
              
              $test = array('matches' => 0, 'innings' => 0, 'runs' => 0, 'highest' => 0, 'average' => 0);
              $odi = array('matches' => 0, 'innings' => 0, 'runs' => 0, 'highest' => 0, 'average' => 0);
              $t20 = array('matches' => 0, 'innings' => 0, 'runs' => 0, 'highest' => 0, 'average' => 0);

              foreach ($sresponce as $srkey => $srvalue) {

                if (!is_null($srvalue->batting)) {
                  
                  if ($srvalue->type == 'ODI' || $srvalue->type == 'Youth ODI' || $srvalue->type == 'List A') {
                    
                    if (property_exists($srvalue->batting, 'matches')) {
                      $tmpMatch = intval($odi['matches']) + intval($srvalue->batting->matches);
                    }else{
                      $tmpMatch = intval($odi['matches']) + 0;
                    }

                    if (property_exists($srvalue->batting, 'innings')) {
                      $tmpInnings = intval($odi['innings']) + intval($srvalue->batting->innings);
                    }else{
                      $tmpInnings = intval($odi['innings']) + 0;
                    }
                    

                    if (property_exists($srvalue->batting, 'runs_scored')) {
                      $tmpRuns = intval($odi['runs']) + intval($srvalue->batting->runs_scored);
                    }else{
                      $tmpRuns = intval($odi['runs']) + 0;
                    }
                    
                    
                    if (property_exists($srvalue->batting, 'highest_inning_score')) {

                      if(intval($srvalue->batting->highest_inning_score) > intval($odi['highest'])){
                        $tmpHighest = intval($srvalue->batting->highest_inning_score);
                      }else{
                        $tmpHighest = intval($odi['highest']);
                      }

                    }else{
                      $tmpHighest = intval($odi['highest']);
                    }

                    $odi = array('matches' => $tmpMatch, 'innings' => $tmpInnings, 'runs' => $tmpRuns, 'highest' => $tmpHighest, 'average' => 0);

                    unset($tmpMatch);
                    unset($tmpInnings);
                    unset($tmpRuns);
                    unset($tmpHighest);              

                  }else if ($srvalue->type == 'TEST' || $srvalue->type == '4day' || $srvalue->type == 'Test/5day') {

                    if (property_exists($srvalue->batting, 'matches')) {
                      $tmpMatch = intval($test['matches']) + intval($srvalue->batting->matches);
                    }else{
                      $tmpMatch = intval($test['matches']) + 0;
                    }

                    if (property_exists($srvalue->batting, 'innings')) {
                      $tmpInnings = intval($test['innings']) + intval($srvalue->batting->innings);
                    }else{
                      $tmpInnings = intval($test['innings']) + 0;
                    }


                    if (property_exists($srvalue->batting, 'runs_scored')) {
                      $tmpRuns = intval($test['runs']) + intval($srvalue->batting->runs_scored);
                    }else{
                      $tmpRuns = intval($test['runs']) + 0;
                    }
                    
                    if (property_exists($srvalue->batting, 'highest_inning_score')) {

                      if(intval($srvalue->batting->highest_inning_score) > intval($test['highest'])){
                        $tmpHighest = intval($srvalue->batting->highest_inning_score);
                      }else{
                        $tmpHighest = intval($test['highest']);
                      }

                    }else{
                      $tmpHighest = intval($test['highest']);
                    }

                    $test = array('matches' => $tmpMatch, 'innings' => $tmpInnings, 'runs' => $tmpRuns, 'highest' => $tmpHighest, 'average' => 0);                  

                    unset($tmpMatch);
                    unset($tmpInnings);
                    unset($tmpRuns);
                    unset($tmpHighest);

                  }else if ($srvalue->type == 'T20' || $srvalue->type == 'T20I' || $srvalue->type == 'T10' || $srvalue->type == '100-Ball') {

                    if (property_exists($srvalue->batting, 'matches')) {
                      $tmpMatch = intval($t20['matches']) + intval($srvalue->batting->matches);
                    }else{
                      $tmpMatch = intval($t20['matches']) + 0;
                    }

                    if (property_exists($srvalue->batting, 'innings')) {
                      $tmpInnings = intval($t20['innings']) + intval($srvalue->batting->innings);
                    }else{
                      $tmpInnings = intval($t20['innings']) + 0;
                    }

                    if (property_exists($srvalue->batting, 'runs_scored')) {
                      $tmpRuns = intval($t20['runs']) + intval($srvalue->batting->runs_scored);
                    }else{
                      $tmpRuns = intval($t20['runs']) + 0;
                    }
                    
                    if (property_exists($srvalue->batting, 'highest_inning_score')) {

                      if(intval($srvalue->batting->highest_inning_score) > intval($t20['highest'])){
                        $tmpHighest = intval($srvalue->batting->highest_inning_score);
                      }else{
                        $tmpHighest = intval($t20['highest']);
                      }

                    }else{
                      $tmpHighest = intval($t20['highest']);
                    }

                    $t20 = array('matches' => $tmpMatch, 'innings' => $tmpInnings, 'runs' => $tmpRuns, 'highest' => $tmpHighest, 'average' => 0);                  

                    unset($tmpMatch);
                    unset($tmpInnings);
                    unset($tmpRuns);
                    unset($tmpHighest);

                  }
                }
              }

              if ($test['matches'] > 0 && $test['runs'] > 0 ) {
                $tmpAvg = $test['runs'] / $test['matches'];
              }else{
                $tmpAvg = 0;
              }
              $test['average'] = $tmpAvg;

              if ($odi['matches'] > 0 && $odi['runs'] > 0 ) {
                $tmpAvg = $odi['runs'] / $odi['matches'];
              }else{
                $tmpAvg = 0;
              }
              $odi['average'] = $tmpAvg;

              if ($t20['matches'] > 0 && $t20['runs'] > 0 ) {
                $tmpAvg = $t20['runs'] / $t20['matches'];
              }else{
                $tmpAvg = 0;
              }
              $t20['average'] = $tmpAvg;

              $playerModel->createPlayerStats($playerDetails[0]->playerId, '1',$odi);
              $playerModel->createPlayerStats($playerDetails[0]->playerId, '2',$t20);
              $playerModel->createPlayerStats($playerDetails[0]->playerId, '3',$test);

            }

          }else{

            //- Update Error Log 
            if (!empty($statserror)) {
              
              $data['userId'] = '0';
              $data['text'] = 'Unable To Fetch Player States -'.$pvalue->id;
              $data['disc'] = $statserror;
              CreateErrorLog($data);
              unset($data);

            }else{

              $data['userId'] = '0';
              $data['text'] = 'Unable To Fetch Player States -'.$pvalue->id;
              $data['disc'] = 'Cricbuzz API not responding need manual inspection';
              CreateErrorLog($data);
              unset($data);

            }

            
            //- Send Message To Admin
            $data['subject'] = 'Player States Failed'; 
            $data['message'] = 'Player States Api error. Please run it manually -'.$pvalue->id;
            SendErrorEmail($data);
            unset($data);

          }
          

        }else{

          //- Get Players States 
          $statsUrl = getenv('playerStats');
          $statsUrl = str_replace("%playerId%", $pvalue->id, $statsUrl);

          try {

            $curl = \Config\Services::curlrequest();
            $sresponce = $curl->request('get', $statsUrl.'&api_token='.getenv('sportMonksKey'));
            $statsresponse = $sresponce->getStatusCode();
            $statserror = '';

          } catch (\Exception $e) {
            
            $statserror = $e->getMessage();
            $statsresponse = '500';

          }

          if ($statsresponse == '200') {

            $sresponce = json_decode($sresponce->getBody());
            $sresponce = $sresponce->data->career;

            if (count($sresponce) > 0) {
              
              $test = array('matches' => 0, 'innings' => 0, 'runs' => 0, 'highest' => 0, 'average' => 0);
              $odi = array('matches' => 0, 'innings' => 0, 'runs' => 0, 'highest' => 0, 'average' => 0);
              $t20 = array('matches' => 0, 'innings' => 0, 'runs' => 0, 'highest' => 0, 'average' => 0);

              foreach ($sresponce as $srkey => $srvalue) {

                if (!is_null($srvalue->batting)) {
                  
                  if ($srvalue->type == 'ODI' || $srvalue->type == 'Youth ODI' || $srvalue->type == 'List A') {
                    
                    if (property_exists($srvalue->batting, 'matches')) {
                      $tmpMatch = intval($odi['matches']) + intval($srvalue->batting->matches);
                    }else{
                      $tmpMatch = intval($odi['matches']) + 0;
                    }

                    if (property_exists($srvalue->batting, 'innings')) {
                      $tmpInnings = intval($odi['innings']) + intval($srvalue->batting->innings);
                    }else{
                      $tmpInnings = intval($odi['innings']) + 0;
                    }
                    

                    if (property_exists($srvalue->batting, 'runs_scored')) {
                      $tmpRuns = intval($odi['runs']) + intval($srvalue->batting->runs_scored);
                    }else{
                      $tmpRuns = intval($odi['runs']) + 0;
                    }
                    
                    
                    if (property_exists($srvalue->batting, 'highest_inning_score')) {

                      if(intval($srvalue->batting->highest_inning_score) > intval($odi['highest'])){
                        $tmpHighest = intval($srvalue->batting->highest_inning_score);
                      }else{
                        $tmpHighest = intval($odi['highest']);
                      }

                    }else{
                      $tmpHighest = intval($odi['highest']);
                    }

                    $odi = array('matches' => $tmpMatch, 'innings' => $tmpInnings, 'runs' => $tmpRuns, 'highest' => $tmpHighest, 'average' => 0);

                    unset($tmpMatch);
                    unset($tmpInnings);
                    unset($tmpRuns);
                    unset($tmpHighest);              

                  }else if ($srvalue->type == 'TEST' || $srvalue->type == '4day' || $srvalue->type == 'Test/5day') {

                    if (property_exists($srvalue->batting, 'matches')) {
                      $tmpMatch = intval($test['matches']) + intval($srvalue->batting->matches);
                    }else{
                      $tmpMatch = intval($test['matches']) + 0;
                    }

                    if (property_exists($srvalue->batting, 'innings')) {
                      $tmpInnings = intval($test['innings']) + intval($srvalue->batting->innings);
                    }else{
                      $tmpInnings = intval($test['innings']) + 0;
                    }


                    if (property_exists($srvalue->batting, 'runs_scored')) {
                      $tmpRuns = intval($test['runs']) + intval($srvalue->batting->runs_scored);
                    }else{
                      $tmpRuns = intval($test['runs']) + 0;
                    }
                    
                    if (property_exists($srvalue->batting, 'highest_inning_score')) {

                      if(intval($srvalue->batting->highest_inning_score) > intval($test['highest'])){
                        $tmpHighest = intval($srvalue->batting->highest_inning_score);
                      }else{
                        $tmpHighest = intval($test['highest']);
                      }

                    }else{
                      $tmpHighest = intval($test['highest']);
                    }

                    $test = array('matches' => $tmpMatch, 'innings' => $tmpInnings, 'runs' => $tmpRuns, 'highest' => $tmpHighest, 'average' => 0);                  

                    unset($tmpMatch);
                    unset($tmpInnings);
                    unset($tmpRuns);
                    unset($tmpHighest);

                  }else if ($srvalue->type == 'T20' || $srvalue->type == 'T20I' || $srvalue->type == 'T10' || $srvalue->type == '100-Ball') {

                    if (property_exists($srvalue->batting, 'matches')) {
                      $tmpMatch = intval($t20['matches']) + intval($srvalue->batting->matches);
                    }else{
                      $tmpMatch = intval($t20['matches']) + 0;
                    }

                    if (property_exists($srvalue->batting, 'innings')) {
                      $tmpInnings = intval($t20['innings']) + intval($srvalue->batting->innings);
                    }else{
                      $tmpInnings = intval($t20['innings']) + 0;
                    }

                    if (property_exists($srvalue->batting, 'runs_scored')) {
                      $tmpRuns = intval($t20['runs']) + intval($srvalue->batting->runs_scored);
                    }else{
                      $tmpRuns = intval($t20['runs']) + 0;
                    }
                    
                    if (property_exists($srvalue->batting, 'highest_inning_score')) {

                      if(intval($srvalue->batting->highest_inning_score) > intval($t20['highest'])){
                        $tmpHighest = intval($srvalue->batting->highest_inning_score);
                      }else{
                        $tmpHighest = intval($t20['highest']);
                      }

                    }else{
                      $tmpHighest = intval($t20['highest']);
                    }

                    $t20 = array('matches' => $tmpMatch, 'innings' => $tmpInnings, 'runs' => $tmpRuns, 'highest' => $tmpHighest, 'average' => 0);                  

                    unset($tmpMatch);
                    unset($tmpInnings);
                    unset($tmpRuns);
                    unset($tmpHighest);

                  }
                }

              }

              if ($test['matches'] > 0 && $test['runs'] > 0 ) {
                $tmpAvg = $test['runs'] / $test['matches'];
              }else{
                $tmpAvg = 0;
              }
              $test['average'] = $tmpAvg;

              if ($odi['matches'] > 0 && $odi['runs'] > 0 ) {
                $tmpAvg = $odi['runs'] / $odi['matches'];
              }else{
                $tmpAvg = 0;
              }
              $odi['average'] = $tmpAvg;

              if ($t20['matches'] > 0 && $t20['runs'] > 0 ) {
                $tmpAvg = $t20['runs'] / $t20['matches'];
              }else{
                $tmpAvg = 0;
              }
              $t20['average'] = $tmpAvg;

              $playerModel->updatePlayerStats($playerDetails[0]->playerId, '1',$odi);
              $playerModel->updatePlayerStats($playerDetails[0]->playerId, '2',$t20);
              $playerModel->updatePlayerStats($playerDetails[0]->playerId, '3',$test);

            }

          }else{

            //- Update Error Log 
            if (!empty($statserror)) {
              
              $data['userId'] = '0';
              $data['text'] = 'Unable To Fetch Player States Update -'.$pvalue->id;
              $data['disc'] = $statserror;
              CreateErrorLog($data);
              unset($data);

            }else{

              $data['userId'] = '0';
              $data['text'] = 'Unable To Fetch Player States Update -'.$pvalue->id;
              $data['disc'] = 'Cricbuzz API not responding need manual inspection';
              CreateErrorLog($data);
              unset($data);

            }

            
            //- Send Message To Admin
            $data['subject'] = 'Player States Failed Update'; 
            $data['message'] = 'Player States Update Api error. Please run it manually -'.$pvalue->id;
            SendErrorEmail($data);
            unset($data);

          }

        }

        $mplaDetails = $playerModel->createMatchPlayerExist($matchId,$playerDetails[0]->playerId,$teamaId);
        if (count($mplaDetails) == 0) {
          $data['matchId'] = $matchId;
          $data['teamId'] = $teamaId;
          $data['playerId'] = $playerDetails[0]->playerId;

          if (!empty($pvalue->position->name)) {
            if ($pvalue->position->name == 'Top Order Batter' || $pvalue->position->name == 'Batsman') {
              $data['orderId'] = '1';
            }else if ($pvalue->position->name == 'Middle Order Batter' || $pvalue->position->name == 'Batting Allrounder' || $pvalue->position->name == 'Allrounder' || $pvalue->position->name == 'Bowling Allrounder') {
              $data['orderId'] = '2';
            }else if($pvalue->position->name == 'Wicketkeeper'){
              $data['orderId'] = '3';
            }else{
              $data['orderId'] = '4';
            }
            
          }else{
            $data['orderId'] = '4';
          }


          $playerModel->createMatchPlayer($data);
          unset($data);
        }        

      }
    }

  }
}


if (!function_exists('CreateSselog')) {
  function CreateSselog($data){
    $sselModel = new \App\Models\SselogModel();
    $sselModel = $sselModel->createLog($data);
    unset($sselModel);
    return true;
  }
}


if (!function_exists('clearSselog')) {
  function clearSselog(){
    $sselModel = new \App\Models\SselogModel();
    $sselModel = $sselModel->deleteOldLog();
    unset($sselModel);
    return true;
  }
}


if (!function_exists('updateBoldDescription')) {
  function updateBoldDescription($text,$arr){

    $searchTxt = array();
    $replacTxt = array();
    if (count($arr) > 0) {
      foreach ($arr as $akey => $avalue) {
        array_push($searchTxt, $avalue['id']);
        array_push($replacTxt, "<b>".$avalue['value']."</b>");
      }
    }

    if (count($searchTxt) > 0 && count($replacTxt) > 0) {
      $text = str_replace($searchTxt, $replacTxt, $text);
    }else{
      $text = '';
    }

    return $text;
  }
}


if (!function_exists('updateDescription')) {
  function updateDescription($text,$arr){

    $searchTxt = array();
    $replacTxt = array();
    if (count($arr) > 0) {
      foreach ($arr as $akey => $avalue) {
        array_push($searchTxt, $avalue['id']);
        array_push($replacTxt, $avalue['value']);
      }
    }

    if (count($searchTxt) > 0 && count($replacTxt) > 0) {
      $text = str_replace($searchTxt, $replacTxt, $text);
    }else{
      $text = '';
    }

    return $text;
  }
}


if (!function_exists('updateInningsScores')) {
  function updateInningsScores($data,$playData){

    $playScorsModel = new \App\Models\MatchPlayerScores();
    $playModel = new \App\Models\PlayersModel();    


    if (count($playData) > 0) {
       
        $playerScor = $playScorsModel->checkPlayerScore($data['matchId'],$data['teamId'],$data['innings'],$playData['player_id']);
        if (count($playerScor) > 0) {
          
          if ($playerScor[0]->status != '1') {
            
            # Update Player Score
            if (!is_null($playData['score'])) {
              $tmpdata['runs'] = $playData['score'];
            }else{
              $tmpdata['runs'] = 0;
            }

            if (!is_null($playData['ball'])) {
              $tmpdata['balls'] = $playData['ball'];
            }else{
              $tmpdata['balls'] = 0;
            }
            
            if (!is_null($playData['four_x'])) {
              $tmpdata['fours'] = $playData['four_x'];
            }else{
              $tmpdata['fours'] = 0;
            }

            if (!is_null($playData['six_x'])) {
              $tmpdata['sixes'] = $playData['six_x'];
            }else{
              $tmpdata['sixes'] = 0;
            }
            
            if (!is_null($playData['rate'])) {
              $tmpdata['strikeRate'] = $playData['rate'];
            }else{
              $tmpdata['strikeRate'] = 0;
            }
            
            $tmpdata['outDesc'] = '';
            if (is_null($playData['catch_stump_player_id']) && is_null($playData['runout_by_id']) && is_null($playData['batsmanout_id']) && is_null($playData['bowling_player_id']) ) {
              $tmpdata['status'] = 0;
            }else{
              $tmpdata['status'] = 1;
            }

            if ($tmpdata['status'] == 0) {
              $tmpdata['outDesc'] = 'batting';
            }else{
              $tmpdata['outDesc'] = 'Out';
            }     

            if ($tmpdata['status'] == 1 || ($tmpdata['status'] == 0 && (intval($tmpdata['runs']) > intval($playerScor[0]->runs) || intval($tmpdata['balls']) > intval($playerScor[0]->balls) ))) {
            
                $playScorsModel->updatePlayerScore($playerScor[0]->id,$tmpdata);
                
                //- Send Server Event
                if (intval($tmpdata['runs']) > 0 || $tmpdata['status'] == 1) {
                  $ssedata['eventName'] = 'playerScore';
                  $msg = array('matchId' => $data['matchId'], 'playerId' => $playerScor[0]->playerId, 'runs' => $tmpdata['runs'], 'balls'=> $tmpdata['balls'], 'fours' => $tmpdata['fours'], 'sixes' => $tmpdata['sixes'], 'strikeRate' => $tmpdata['strikeRate'], 'outDesc' => $tmpdata['outDesc'], 'status' => $tmpdata['status']);
                  $ssedata['eventData'] = json_encode($msg);
                  CreateSselog($ssedata);
                  unset($ssedata);  
                }

            }

            unset($tmpdata);
            
          }else{

            if (is_null($playData['catch_stump_player_id']) && is_null($playData['runout_by_id']) && is_null($playData['batsmanout_id']) && is_null($playData['bowling_player_id']) ) {
                
                $tmpdata['outDesc'] = 'batting';
                $tmpdata['status'] = 0;

                # Update Player Score
                if (!is_null($playData['score'])) {
                  $tmpdata['runs'] = $playData['score'];
                }else{
                  $tmpdata['runs'] = 0;
                }

                if (!is_null($playData['ball'])) {
                  $tmpdata['balls'] = $playData['ball'];
                }else{
                  $tmpdata['balls'] = 0;
                }
                
                if (!is_null($playData['four_x'])) {
                  $tmpdata['fours'] = $playData['four_x'];
                }else{
                  $tmpdata['fours'] = 0;
                }

                if (!is_null($playData['six_x'])) {
                  $tmpdata['sixes'] = $playData['six_x'];
                }else{
                  $tmpdata['sixes'] = 0;
                }
                
                if (!is_null($playData['rate'])) {
                  $tmpdata['strikeRate'] = $playData['rate'];
                }else{
                  $tmpdata['strikeRate'] = 0;
                }

                $playScorsModel->updatePlayerScore($playerScor[0]->id,$tmpdata);
                
                //- Send Server Event
                if (intval($tmpdata['runs']) > 0 || $tmpdata['status'] == 1) {
                  $ssedata['eventName'] = 'playerScore';
                  $msg = array('matchId' => $data['matchId'], 'playerId' => $playerScor[0]->playerId, 'runs' => $tmpdata['runs'], 'balls'=> $tmpdata['balls'], 'fours' => $tmpdata['fours'], 'sixes' => $tmpdata['sixes'], 'strikeRate' => $tmpdata['strikeRate'], 'outDesc' => $tmpdata['outDesc'], 'status' => $tmpdata['status']);
                  $ssedata['eventData'] = json_encode($msg);
                  CreateSselog($ssedata);
                  unset($ssedata);  
                }

                unset($tmpdata);

            } 

          }

        }else{

          # Get Player Id
          $playerDetails = $playModel->checkPlayerExist($playData['player_id']);

          # Create Player Score
          if (count($playerDetails) > 0) {
              
            $tmpdata['matchId'] = $data['matchId'];
            $tmpdata['teamId'] = $data['teamId'];
            $tmpdata['inningsId'] = $data['innings'];
            $tmpdata['playerId'] = $playerDetails[0]->playerId;
            $tmpdata['playerKey'] = $playData['player_id'];
            $tmpdata['outDesc'] = '';

            if (!is_null($playData['score'])) {
              $tmpdata['runs'] = $playData['score'];
            }else{
              $tmpdata['runs'] = 0;
            }

            if (!is_null($playData['ball'])) {
              $tmpdata['balls'] = $playData['ball'];
            }else{
              $tmpdata['balls'] = 0;
            }
            
            if (!is_null($playData['four_x'])) {
              $tmpdata['fours'] = $playData['four_x'];
            }else{
              $tmpdata['fours'] = 0;
            }
            
            if (!is_null($playData['six_x'])) {
              $tmpdata['sixes'] = $playData['six_x'];
            }else{
              $tmpdata['sixes'] = 0;
            }

            if (!is_null($playData['rate'])) {
              $tmpdata['strikeRate'] = $playData['rate'];
            }else{
              $tmpdata['strikeRate'] = 0;
            }

            if (!is_null($playData['sort'])) {
              $tmpdata['orderId'] = $playData['sort'];
            }else{
              $tmpdata['orderId'] = 0;
            }

            if (is_null($playData['catch_stump_player_id']) && is_null($playData['runout_by_id']) && is_null($playData['batsmanout_id']) && is_null($playData['bowling_player_id']) ) {
              $tmpdata['status'] = 0;
            }else{
              $tmpdata['status'] = 1;
            }

            if ($tmpdata['status'] == 0) {
              $tmpdata['outDesc'] = 'batting';
            }else{
              $tmpdata['outDesc'] = 'Out';
            }
            

            $playScorsModel->createPlayerScore($tmpdata);
            
            $ssedata['eventName'] = 'playerScore';
            $msg = array('matchId' => $data['matchId'], 'playerId' => $playerDetails[0]->playerId, 'runs' => $playData['score'], 'balls'=> $playData['ball'], 'fours' => $playData['four_x'], 'sixes' => $playData['six_x'], 'strikeRate' => $playData['rate'], 'outDesc' => $tmpdata['outDesc'], 'status' => $tmpdata['status']);
            $ssedata['eventData'] = json_encode($msg);
            CreateSselog($ssedata);
            unset($ssedata); 
            unset($tmpdata);           

          }
           
        }

    
    }

  }
}


if (!function_exists('sendPushNotifi')) {
  function sendPushNotifi($body){

    $curl = \Config\Services::curlrequest();
    $response = $curl->request('POST', 'https://fcm.googleapis.com/fcm/send', ['headers' => ['Content-Type' => 'application/json', 'Authorization' => 'key='.getenv('fcmKey') ], 'body' => $body]);
    $responce = $response->getStatusCode();

  }
}


if (!function_exists('CreateFinalPlayers')) {
  function CreateFinalPlayers($palays,$matchId,$teamaId){

    $playerModel = new \App\Models\PlayersModel();

    if (count($palays) > 0) {
      foreach ($palays as $pkey => $pvalue) {
          
        //- Check Player Exist
        $playerDetails = $playerModel->checkPlayerExist($pvalue->id);
        if (count($playerDetails) == 0) {

          $faceImageId = str_replace(getenv('playerImg'), '', $pvalue->image_path);
          if (strpos($faceImageId, 'placeholder.png') !== false) {
            $faceImageId = '';
          }

          $data['playerKey'] = $pvalue->id;
          $data['nickName'] = $pvalue->lastname;
          $data['name'] = $pvalue->fullname;
          $data['faceImageId'] = $faceImageId;
          if (!empty($pvalue->position->name)) {
            $data['role'] = $pvalue->position->name;
          }else{
            $data['role'] = '';
          }

          if (!empty($pvalue->battingstyle)) {
            $data['battingStyle'] = $pvalue->battingstyle;
          }else{
            $data['battingStyle'] = '';
          }

          if (!empty($pvalue->bowlingstyle)) {
            $data['bowlingStyle'] = $pvalue->bowlingstyle;
          }else{
            $data['bowlingStyle'] = '';
          }
          
          $playerModel->createPlayer($data);
          $playerDetails = $playerModel->checkPlayerExist($pvalue->id); 
          unset($data);

          //- Get Players States 
          $statsUrl = getenv('playerStats');
          $statsUrl = str_replace("%playerId%",  $pvalue->id, $statsUrl);

          try {

            $curl = \Config\Services::curlrequest();
            $sresponce = $curl->request('get', $statsUrl.'&api_token='.getenv('sportMonksKey'));
            $statsresponse = $sresponce->getStatusCode();
            $statserror = '';

          } catch (\Exception $e) {
            
            $statserror = $e->getMessage();
            $statsresponse = '500';

          }

          if ($statsresponse == '200') {

            $sresponce = json_decode($sresponce->getBody());
            $sresponce = $sresponce->data->career;

            if (count($sresponce) > 0) {
              
              $test = array('matches' => 0, 'innings' => 0, 'runs' => 0, 'highest' => 0, 'average' => 0);
              $odi = array('matches' => 0, 'innings' => 0, 'runs' => 0, 'highest' => 0, 'average' => 0);
              $t20 = array('matches' => 0, 'innings' => 0, 'runs' => 0, 'highest' => 0, 'average' => 0);

              foreach ($sresponce as $srkey => $srvalue) {
               
                if ($srvalue->type == 'ODI' || $srvalue->type == 'Youth ODI' || $srvalue->type == 'List A') {
                  
                  $tmpMatch = intval($odi['matches']) + intval($srvalue->batting->matches);
                  $tmpInnings = intval($odi['innings']) + intval($srvalue->batting->innings);
                  $tmpRuns = intval($odi['runs']) + intval($srvalue->batting->runs_scored);

                  if(intval($srvalue->batting->highest_inning_score) > intval($odi['highest'])){
                    $tmpHighest = intval($srvalue->batting->highest_inning_score);
                  }else{
                    $tmpHighest = intval($odi['highest']);
                  }

                  $odi = array('matches' => $tmpMatch, 'innings' => $tmpInnings, 'runs' => $tmpRuns, 'highest' => $tmpHighest, 'average' => 0);

                  unset($tmpMatch);
                  unset($tmpInnings);
                  unset($tmpRuns);
                  unset($tmpHighest);              

                }else if ($srvalue->type == 'TEST' || $srvalue->type == '4day' || $srvalue->type == 'Test/5day') {

                  $tmpMatch = intval($test['matches']) + intval($srvalue->batting->matches);
                  $tmpInnings = intval($test['innings']) + intval($srvalue->batting->innings);
                  $tmpRuns = intval($test['runs']) + intval($srvalue->batting->runs_scored);

                  if(intval($srvalue->batting->highest_inning_score) > intval($test['highest'])){
                    $tmpHighest = intval($srvalue->batting->highest_inning_score);
                  }else{
                    $tmpHighest = intval($test['highest']);
                  }

                  $test = array('matches' => $tmpMatch, 'innings' => $tmpInnings, 'runs' => $tmpRuns, 'highest' => $tmpHighest, 'average' => 0);                  

                  unset($tmpMatch);
                  unset($tmpInnings);
                  unset($tmpRuns);
                  unset($tmpHighest);

                }else if ($srvalue->type == 'T20' || $srvalue->type == 'T20I' || $srvalue->type == 'T10') {

                  $tmpMatch = intval($t20['matches']) + intval($srvalue->batting->matches);
                  $tmpInnings = intval($t20['innings']) + intval($srvalue->batting->innings);
                  $tmpRuns = intval($t20['runs']) + intval($srvalue->batting->runs_scored);

                  if(intval($srvalue->batting->highest_inning_score) > intval($t20['highest'])){
                    $tmpHighest = intval($srvalue->batting->highest_inning_score);
                  }else{
                    $tmpHighest = intval($t20['highest']);
                  }

                  $t20 = array('matches' => $tmpMatch, 'innings' => $tmpInnings, 'runs' => $tmpRuns, 'highest' => $tmpHighest, 'average' => 0);                  

                  unset($tmpMatch);
                  unset($tmpInnings);
                  unset($tmpRuns);
                  unset($tmpHighest);

                }
                
              }

              if ($test['matches'] > 0 && $test['runs'] > 0 ) {
                $tmpAvg = $test['runs'] / $test['matches'];
              }else{
                $tmpAvg = 0;
              }
              $test['average'] = $tmpAvg;

              if ($odi['matches'] > 0 && $odi['runs'] > 0 ) {
                $tmpAvg = $odi['runs'] / $odi['matches'];
              }else{
                $tmpAvg = 0;
              }
              $odi['average'] = $tmpAvg;

              if ($t20['matches'] > 0 && $t20['runs'] > 0 ) {
                $tmpAvg = $t20['runs'] / $t20['matches'];
              }else{
                $tmpAvg = 0;
              }
              $t20['average'] = $tmpAvg;

              $playerModel->createPlayerStats($playerDetails[0]->playerId, '1',$odi);
              $playerModel->createPlayerStats($playerDetails[0]->playerId, '2',$t20);
              $playerModel->createPlayerStats($playerDetails[0]->playerId, '3',$test);

            }

          }else{

            //- Update Error Log 
            if (!empty($statserror)) {
              
              $data['userId'] = '0';
              $data['text'] = 'Unable To Fetch Player States -'.$pvalue->id;
              $data['disc'] = $statserror;
              CreateErrorLog($data);
              unset($data);

            }else{

              $data['userId'] = '0';
              $data['text'] = 'Unable To Fetch Player States -'.$pvalue->id;
              $data['disc'] = 'Cricbuzz API not responding need manual inspection';
              CreateErrorLog($data);
              unset($data);

            }

            
            //- Send Message To Admin
            $data['subject'] = 'Player States Failed'; 
            $data['message'] = 'Player States Api error. Please run it manually -'.$pvalue->id;
            SendErrorEmail($data);
            unset($data);

          }
          
        }

        $mplaDetails = $playerModel->createMatchPlayerExist($matchId,$playerDetails[0]->playerId,$teamaId);
        if (count($mplaDetails) == 0) {
          $data['matchId'] = $matchId;
          $data['teamId'] = $teamaId;
          $data['playerId'] = $playerDetails[0]->playerId;

          if (!empty($playerDetails[0]->role)) {
            if ($playerDetails[0]->role == 'Top Order Batter' || $playerDetails[0]->role == 'Batsman') {
              $data['orderId'] = '1';
            }else if ($playerDetails[0]->role == 'Middle Order Batter' || $playerDetails[0]->role == 'Batting Allrounder' || $playerDetails[0]->role == 'Allrounder' || $playerDetails[0]->role == 'Bowling Allrounder') {
              $data['orderId'] = '2';
            }else if($playerDetails[0]->role == 'Wicketkeeper'){
              $data['orderId'] = '3';
            }else{
              $data['orderId'] = '4';
            }
            
          }else{
            $data['orderId'] = '4';
          }

          $playerModel->createFinalMatchPlayer($data);
          unset($data);
        }else{
          //- Update Has Playing
          $playerModel->updateAsPlayer11($matchId,$teamaId,$playerDetails[0]->playerId);
        }        

      }
    }

  }
}


if (!function_exists('CreateFinalDraftPlayers')) {
  function CreateFinalDraftPlayers($frstUDrft,$secUDrft){

    $tmpFirstFinal = array();
    $firstTmp = array();
    $tmpSecFinal = array();
    $secTmp = array();

    $tmpFirstCount = 0;
    $tmpSecCount = 0;

    for ($i=0; $i < 6; $i++) { 
      
      if (count($frstUDrft) > 0 && $tmpFirstCount < 6) {
                            
        if (in_array($frstUDrft[0]->playerId,$secTmp)){
          
          //- Check Player Exist In Second List 
          for ($fi=0; $fi < 12; $fi++) { 
            if (in_array($frstUDrft[0]->playerId,$secTmp)){
              array_shift($frstUDrft);
            }else{
              //- Check Player Doesn't Exist In Second List
              $tmpData = array_shift($frstUDrft);
              array_push($firstTmp, $tmpData->playerId);
              array_push($tmpFirstFinal, $tmpData);
              $tmpFirstCount = $tmpFirstCount+1;
              break;
            }
          }
          
        }else{
          //- Check Player Doesn't Exist In Second List
          $tmpData = array_shift($frstUDrft);
          array_push($firstTmp, $tmpData->playerId);
          array_push($tmpFirstFinal, $tmpData);
          $tmpFirstCount = $tmpFirstCount+1;

        }                    
        
      }


      if (count($secUDrft) > 0 && $tmpSecCount < 6) {

        if (in_array($secUDrft[0]->playerId,$firstTmp)){
          
          //- Check Player Exist In Second List 
          for ($fi=0; $fi < 12; $fi++) { 
            if (in_array($secUDrft[0]->playerId,$firstTmp)){
              array_shift($secUDrft);
            }else{
              //- Check Player Doesn't Exist In Second List
              $tmpData = array_shift($secUDrft);
              array_push($secTmp, $tmpData->playerId);
              array_push($tmpSecFinal, $tmpData);
              $tmpSecCount = $tmpSecCount+1;
              break;
            }
          }
          
        }else{
          //- Check Player Doesn't Exist In Second List
          $tmpData = array_shift($secUDrft);
          array_push($secTmp, $tmpData->playerId);
          array_push($tmpSecFinal, $tmpData);
          $tmpSecCount = $tmpSecCount+1;
        } 

      }

    }

    $retVal = array('firtTeam' => $tmpFirstFinal, 'secTeam' => $tmpSecFinal);
    return $retVal;

  }
}


if (!function_exists('checkObjDiff')) {
  function checkObjDiff($old,$final){
    return $old->playerId - $final->playerId;
  }
}


if (!function_exists('checkSelPlayDiff')) {
  function checkSelPlayDiff($old,$final){
    if ($old->playerId == $final) {
      return 0;
    }else{
      return -1;
    }
  }
}


if (!function_exists('CreateFinalModifiedDraftPlayers')) {
  function CreateFinalModifiedDraftPlayers($myOldDrft,$opnOldDrft,$myFDrft,$opnFDrft,$selplyrId){


    $usrSelPlayer = array($selplyrId);

    //- Comb of My and Open final palyers
    $allSel = array_merge($myFDrft,$opnFDrft);

    //- My Old Users, Both Selected Players 
    $myOldDrft = array_udiff($myOldDrft,$allSel,"checkObjDiff");
    $myOldDrft = array_udiff($myOldDrft,$usrSelPlayer,"checkSelPlayDiff");
    $myOldDrft = array_values($myOldDrft);
    
    //- Opone Old Users, Both Selected Players 
    $opnOldDrft = array_udiff($opnOldDrft,$allSel,"checkObjDiff");
    $opnOldDrft = array_udiff($opnOldDrft,$usrSelPlayer,"checkSelPlayDiff");
    $opnOldDrft = array_values($opnOldDrft);

    $myFDrfCou = count($myFDrft) + 1;
    $opnFDrft = count($opnFDrft);

    $loopCount = 0;
    if ($myFDrfCou >= $opnFDrft) {
      $loopCount = $opnFDrft;
    }else if ($myFDrfCou <= $opnFDrft) {
      $loopCount = $myFDrfCou;
    }

    $tmpMyFinal = array();
    $myTmp = array();
    $tmpOpnFinal = array();
    $opnTmp = array();

    $tmpMyCount = 0;
    $tmpOpCount = 0;

    for ($i = $loopCount; $i < 6; $i++) { 

      if (count($myOldDrft) > 0 && $tmpMyCount < 6) {
        
        if (in_array($myOldDrft[0]->playerId,$opnTmp)){
        
          //- Check Player Exist In Second List 
          for ($fi=0; $fi < 12; $fi++) { 
            if (count($myOldDrft) > 0) {
              if (in_array($myOldDrft[0]->playerId,$opnTmp)){
                array_shift($myOldDrft);
              }else{
                //- Check Player Doesn't Exist In Second List
                $tmpData = array_shift($myOldDrft);
                array_push($myTmp, $tmpData->playerId);
                array_push($tmpMyFinal, $tmpData);
                $tmpMyCount = $tmpMyCount+1;
                break;
              }
            }
          }        

        }else{
          
          //- Check Player Doesn't Exist In Second List
          $tmpData = array_shift($myOldDrft);
          array_push($myTmp, $tmpData->playerId);
          array_push($tmpMyFinal, $tmpData);
          $tmpMyCount = $tmpMyCount+1;

        }

      }

      if (count($opnOldDrft) > 0 && $tmpOpCount < 6) {
        
        if (in_array($opnOldDrft[0]->playerId,$myTmp)){
          
          //- Check Player Exist In Second List 
          for ($fi=0; $fi < 12; $fi++) { 
            if (count($opnOldDrft) > 0) {
              if (in_array($opnOldDrft[0]->playerId,$myTmp)){
                array_shift($opnOldDrft);
              }else{
                //- Check Player Doesn't Exist In Second List
                $tmpData = array_shift($opnOldDrft);
                array_push($opnTmp, $tmpData->playerId);
                array_push($tmpOpnFinal, $tmpData);
                $tmpOpCount = $tmpOpCount+1;
                break;
              }
            }
          }

        }else{
          //- Check Player Doesn't Exist In Second List
          $tmpData = array_shift($opnOldDrft);
          array_push($opnTmp, $tmpData->playerId);
          array_push($tmpOpnFinal, $tmpData);
          $tmpOpCount = $tmpOpCount+1;
        }

      }

    }

    $retVal = array('myFinal' => $tmpMyFinal, 'opnFinal' => $tmpOpnFinal);
    return $retVal;

  }
}


if (!function_exists('refundClosedMatchContests')) {

  function refundClosedMatchContests($matchId){
    
    $contestModel = new \App\Models\ContestModel();
    $userWalletModel = new \App\Models\UserWalletModel();
    $allContest = $contestModel->getAllActivMatchContests($matchId);

    if (count($allContest) > 0) {
      foreach ($allContest as $alConkey => $alConvalue) {

        if (!empty($alConvalue->userOne) && $alConvalue->userOne != '0') {

          $transDetails = $userWalletModel->checkCreditTransaction($alConvalue->userOne,$alConvalue->pairId,'3',$alConvalue->fee);

          if (count($transDetails) == 0) {
            $tmpWall['userId'] = $alConvalue->userOne;
            $tmpWall['amount'] = $alConvalue->fee;
            $tmpWall['contestId'] = $alConvalue->pairId;
            $tmpWall['text'] = 'Contest fee refund';
            $tmpWall['status'] = '3';
            $userWalletModel->creditWallet($tmpWall);
            unset($tmpWall);
          }

        }

        if (!empty($alConvalue->userTwo) && $alConvalue->userTwo != '0') {
          
          $transDetails = $userWalletModel->checkCreditTransaction($alConvalue->userTwo,$alConvalue->pairId,'3',$alConvalue->fee);

          if (count($transDetails) == 0) {
            $tmpWall['userId'] = $alConvalue->userTwo;
            $tmpWall['amount'] = $alConvalue->fee;
            $tmpWall['contestId'] = $alConvalue->pairId;
            $tmpWall['text'] = 'Contest fee refund';
            $tmpWall['status'] = '3';
            $userWalletModel->creditWallet($tmpWall);
            unset($tmpWall);
          }

        }

      }
    }
    
  }

}


if (!function_exists('refundUnpairedContests')) {

  function refundUnpairedContests($matchId){

    $contestModel = new \App\Models\ContestModel();
    $userWalletModel = new \App\Models\UserWalletModel();
    $allContest = $contestModel->getAllUnpairedContests($matchId);
    if (count($allContest) > 0) {
      foreach ($allContest as $alConkey => $alConvalue) {

        if (!empty($alConvalue->userOne)) {
          $transDetails = $userWalletModel->checkCreditTransaction($alConvalue->userOne,$alConvalue->pairId,'3',$alConvalue->fee);

          if (count($transDetails) == 0) {
            $tmpWall['userId'] = $alConvalue->userOne;
            $tmpWall['amount'] = $alConvalue->fee;
            $tmpWall['contestId'] = $alConvalue->pairId;
            $tmpWall['text'] = 'Contest fee refund';
            $tmpWall['status'] = '3';
            $userWalletModel->creditWallet($tmpWall);
            unset($tmpWall);
          }

        }

      }
    }    

  }
    
}


if (!function_exists('getUserAccounts')) {
  function getUserAccounts($userId){

    $userModel = new \App\Models\UserModel();
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
            array_push($account, $rzbresponse->bank_account->account_number);
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

    return $account;

  }
}

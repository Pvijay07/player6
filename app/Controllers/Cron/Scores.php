<?php

namespace App\Controllers\Cron;
use App\Controllers\BaseController;
use App\Models\ContestModel;
use App\Models\MatchesModel;
use App\Models\MatchesLookupModel;
use App\Models\ContestFinalPlayers;
use App\Models\MatchPlayerScores;
use App\Models\UserWalletModel;
use App\Models\UserModel;
use App\Models\TeamsModel;
use Config\Services;

class Scores extends BaseController
{

    public function __construct(){
      helper('Common');
    }


    //- Get Live Scores
    public function getLiveScores()
    {
      
      $playScorsModel = new MatchPlayerScores();
      $matchLookupModel = new MatchesLookupModel();
      $lookUpData = $matchLookupModel->getAllOngoingMatches();

      if (count($lookUpData) > 0) {
      	foreach ($lookUpData as $ldkey => $ldvalue) {
	      	
          $scoresUrl = getenv('scores');
          $scoresUrl = str_replace("%matchId%", $ldvalue->crickmId, $scoresUrl);

	        try {

	          $curl = \Config\Services::curlrequest();
	          $response = $curl->request('get', $scoresUrl.'&api_token='.getenv('sportMonksKey') );
	          $responce = $response->getStatusCode();
	          $error = '';

	        } catch (\Exception $e) {
	          
	          $error = $e->getMessage();
	          $responce = '500';

	        }


		      if ($responce == 200) {

		      	if (!empty($response->getBody())) {

		          $responce = json_decode($response->getBody());
		          $scorCard = $responce->data->runs;
		          $resStatus = $responce->data->status;
		          $responce = $responce->data;
		          
		          
		          if (count($responce->batting) > 0) {
		          	$records = $responce->batting;

		          	if ($ldvalue->matchType == '1' || $ldvalue->matchType == '2') {
			          	
		          		//- For ODI && T20
		          		$s2 = 'false';
		          		foreach ($records as $irkey => $irvalue) {
		          			
		          			if ($irvalue->scoreboard == 'S1') {

				          		//- Update Innings 1 Player Scores
		          				$teamAInn1 = false;
		          				$teamBInn1 = false;

		          				if ($irvalue->team_id == $ldvalue->teamaKey) {
		          					$innings1 = $ldvalue->teamAInn1;
				          			
				          			if ($ldvalue->teamAInn1 == 0) {
				          				$teamAInn1 = true;
				          			}else{
				          				$teamAInn1 = false;
				          			}

		          				}else if ($irvalue->team_id == $ldvalue->teambKey) {
		          					$innings1 = $ldvalue->teamBInn1;

				          			if ($ldvalue->teamBInn1 == 0) {
				          				$teamBInn1 = true;
				          			}else{
				          				$teamBInn1 = false;
				          			}

		          				}

		          				if ($innings1 == '0') {

		          					$data['matchId'] = $ldvalue->matchId;
					          		$data['innings'] = '1';
					          		if ($irvalue->team_id == $ldvalue->teamaKey) {
					          			$data['teamId'] = $ldvalue->teamaId;
					          		}else if ($irvalue->team_id == $ldvalue->teambKey) {
					          			$data['teamId'] = $ldvalue->teambId;
					          		}

					          		updateInningsScores($data,(array)$irvalue);

					          		
					          		if (count($scorCard) > 0) {
					          			foreach ($scorCard as $scrkey => $scrvalue) {
					          				if ($scrvalue->inning == '1') {
								          
			                        //- Update Socket
			                        $socdata['eventName'] = 'innScors';

			                        $msg = array('matchId' => $data['matchId'], 'innings' => $data['innings'], 'teamId' => $data['teamId'], 'runs' => $scrvalue->score, 'wickets' => $scrvalue->wickets, 'overs' => $scrvalue->overs);

			                        $socdata['eventData'] = json_encode($msg);
			                        CreateSselog($socdata);
			                        unset($socdata);

					          				}

														if ($scrvalue->inning == '2' && $s2 == 'false' ) {
															$s2 = 'true';
														}

					          			}
					          		}

					          		unset($data);

					          		if ($s2 == 'true' || $resStatus == "Finished") {
					          			$data['lookId'] = $ldvalue->id;

						          		if (count($scorCard) > 0) {
						          			foreach ($scorCard as $scrkey => $scrvalue) {
						          				if ($scrvalue->inning == '1') {

						          					if ($teamAInn1) {
						          						$data['teamAInn1'] = '1';
						          						$data['teamAInn1Score'] = $scrvalue->score;
																	$data['teamAInn1Wickets'] = $scrvalue->wickets;
																	$data['teamAInn1Overs'] = $scrvalue->overs;
																}else{
																	$data['teamAInn1'] = '0';
																}
						          					
																$data['teamAInn2'] = '0';
						          					if ($teamBInn1) {
						          						$data['teamBInn1'] = '1';
																	$data['teamBInn1Score'] = $scrvalue->score;
																	$data['teamBInn1Wickets'] = $scrvalue->wickets;
																	$data['teamBInn1Overs'] = $scrvalue->overs;
						          					}else{
						          						$data['teamBInn1'] = '0';
						          					}

						          					$data['teamBInn2'] = '0';

																if ($resStatus == "Finished") {
																	$data['matchStatus'] = '1';
																}

								          			$matchLookupModel->updateInningsStatus($data);
								          			unset($data);

						          				}
						          			}
						          		}

						          		//- Update Player Batting Status To Notout
						          		$data['matchId'] = $ldvalue->matchId;
						          		$data['innings'] = '1';
						          		if ($irvalue->team_id == $ldvalue->teamaKey) {
						          			$data['teamId'] = $ldvalue->teamaId;
						          		}else if ($irvalue->team_id == $ldvalue->teambKey) {
						          			$data['teamId'] = $ldvalue->teambId;
					          			}

					          			$playScorsModel->updateOldPlayerScore($data);

					          			unset($data);


					          		}else{

					          			$data['lookId'] = $ldvalue->id;

						          		if (count($scorCard) > 0) {
						          			foreach ($scorCard as $scrkey => $scrvalue) {
						          				if ($scrvalue->inning == '1') {

						          					if ($teamAInn1) {
						          						$data['teamAInn1Score'] = $scrvalue->score;
																	$data['teamAInn1Wickets'] = $scrvalue->wickets;
																	$data['teamAInn1Overs'] = $scrvalue->overs;

						          					}

						          					if ($teamBInn1) {
																	$data['teamBInn1Score'] = $scrvalue->score;
																	$data['teamBInn1Wickets'] = $scrvalue->wickets;
																	$data['teamBInn1Overs'] = $scrvalue->overs;
						          					}

								          			$matchLookupModel->updateInningsStatus($data);
								          			unset($data);

						          				}
						          			}
						          		}

					          		}

		          				}

		          			}else if ($irvalue->scoreboard == 'S2') {
		          				
				          		//- Update Innings 1 Player Scores
		          				$teamAInn1 = false;
		          				$teamBInn1 = false;

		          				if ($irvalue->team_id == $ldvalue->teamaKey) {
		          					$innings2 = $ldvalue->teamAInn1;
				          			
				          			if ($ldvalue->teamAInn1 == 0) {
				          				$teamAInn1 = true;
				          			}else{
				          				$teamAInn1 = false;
				          			}

		          				}else if ($irvalue->team_id == $ldvalue->teambKey) {
		          					$innings2 = $ldvalue->teamBInn1;

				          			if ($ldvalue->teamBInn1 == 0) {
				          				$teamBInn1 = true;
				          			}else{
				          				$teamBInn1 = false;
				          			}

		          				}

		          				if ($innings2 == '0') {

		          					$data['matchId'] = $ldvalue->matchId;
					          		$data['innings'] = '2';
					          		if ($irvalue->team_id == $ldvalue->teamaKey) {
					          			$data['teamId'] = $ldvalue->teamaId;
					          		}else if ($irvalue->team_id == $ldvalue->teambKey) {
					          			$data['teamId'] = $ldvalue->teambId;
					          		}

					          		updateInningsScores($data,(array)$irvalue);

					          		if (count($scorCard) > 0) {
					          			foreach ($scorCard as $scrkey => $scrvalue) {
					          				if ($scrvalue->inning == '2') {
								          
			                        //- Update Socket
			                        $socdata['eventName'] = 'innScors';

			                        $msg = array('matchId' => $data['matchId'], 'innings' => $data['innings'], 'teamId' => $data['teamId'], 'runs' => $scrvalue->score, 'wickets' => $scrvalue->wickets, 'overs' => $scrvalue->overs);

			                        $socdata['eventData'] = json_encode($msg);
			                        CreateSselog($socdata);
			                        unset($socdata);

					          				}
					          			}
					          		}

					          		unset($data);

					          		if ($resStatus == "Finished") {
					          			$data['lookId'] = $ldvalue->id;

						          		if (count($scorCard) > 0) {
						          			foreach ($scorCard as $scrkey => $scrvalue) {
						          				if ($scrvalue->inning == '2') {

						          					if ($teamAInn1) {
						          						$data['teamAInn1'] = '1';
						          						$data['teamAInn1Score'] = $scrvalue->score;
																	$data['teamAInn1Wickets'] = $scrvalue->wickets;
																	$data['teamAInn1Overs'] = $scrvalue->overs;
																}else{
																	$data['teamAInn1'] = '0';
																}
						          					
																$data['teamAInn2'] = '0';
						          					if ($teamBInn1) {
						          						$data['teamBInn1'] = '1';
																	$data['teamBInn1Score'] = $scrvalue->score;
																	$data['teamBInn1Wickets'] = $scrvalue->wickets;
																	$data['teamBInn1Overs'] = $scrvalue->overs;
						          					}else{
						          						$data['teamBInn1'] = '0';
						          					}

						          					$data['teamBInn2'] = '0';

																$data['matchStatus'] = '1';

								          			$matchLookupModel->updateInningsStatus($data);
								          			unset($data);

						          				}
						          			}
						          		}

						          		//- Update Player Batting Status To Notout
						          		$data['matchId'] = $ldvalue->matchId;
						          		$data['innings'] = '2';
						          		if ($irvalue->team_id == $ldvalue->teamaKey) {
						          			$data['teamId'] = $ldvalue->teamaId;
						          		}else if ($irvalue->team_id == $ldvalue->teambKey) {
						          			$data['teamId'] = $ldvalue->teambId;
					          			}

					          			$playScorsModel->updateOldPlayerScore($data);
					          			unset($data);


					          		}else{

					          			$data['lookId'] = $ldvalue->id;

						          		if (count($scorCard) > 0) {
						          			foreach ($scorCard as $scrkey => $scrvalue) {
						          				if ($scrvalue->inning == '2') {

						          					if ($teamAInn1) {
						          						$data['teamAInn1Score'] = $scrvalue->score;
																	$data['teamAInn1Wickets'] = $scrvalue->wickets;
																	$data['teamAInn1Overs'] = $scrvalue->overs;

						          					}

						          					if ($teamBInn1) {
																	$data['teamBInn1Score'] = $scrvalue->score;
																	$data['teamBInn1Wickets'] = $scrvalue->wickets;
																	$data['teamBInn1Overs'] = $scrvalue->overs;
						          					}

								          			$matchLookupModel->updateInningsStatus($data);
								          			unset($data);

						          				}
						          			}
						          		}

					          		}

		          				}
		          			
		          			}


		          			
		          		}
		          	
		          	}else if ($ldvalue->matchType == '3') {

		          		$s2 = 'false';
		          		$s3 = 'false';
		          		$s4 = 'false';
		          		foreach ($records as $irkey => $irvalue) {
		          			
		          			if ($irvalue->scoreboard == 'S1') {

				          		//- Update Innings 1 Player Scores
		          				$teamAInn1 = false;
		          				$teamBInn1 = false;
											$teamAInn2 = false;
											$teamBInn2 = false;

		          				if ($irvalue->team_id == $ldvalue->teamaKey) {
		          					$innings1 = $ldvalue->teamAInn1;
				          			
				          			if ($ldvalue->teamAInn1 == 0) {
				          				$teamAInn1 = true;
				          				$teamAInn2 = false;
				          			}else{
				          				$teamAInn1 = false;
				          				$teamAInn2 = true;
				          			}

		          				}else if ($irvalue->team_id == $ldvalue->teambKey) {
		          					$innings1 = $ldvalue->teamBInn1;

				          			if ($ldvalue->teamBInn1 == 0) {
				          				$teamBInn1 = true;
				          				$teamBInn2 = false;
				          			}else{
				          				$teamBInn1 = false;
				          				$teamBInn2 = true;
				          			}

		          				}

		          				if ($innings1 == '0') {

		          					$data['matchId'] = $ldvalue->matchId;
					          		$data['innings'] = '1';
					          		if ($irvalue->team_id == $ldvalue->teamaKey) {
					          			$data['teamId'] = $ldvalue->teamaId;
					          		}else if ($irvalue->team_id == $ldvalue->teambKey) {
					          			$data['teamId'] = $ldvalue->teambId;
					          		}

					          		updateInningsScores($data,(array)$irvalue);

					          		
					          		if (count($scorCard) > 0) {
					          			foreach ($scorCard as $scrkey => $scrvalue) {
					          				if ($scrvalue->inning == '1') {
								          
			                        //- Update Socket
			                        $socdata['eventName'] = 'innScors';

			                        $msg = array('matchId' => $data['matchId'], 'innings' => $data['innings'], 'teamId' => $data['teamId'], 'runs' => $scrvalue->score, 'wickets' => $scrvalue->wickets, 'overs' => $scrvalue->overs);

			                        $socdata['eventData'] = json_encode($msg);
			                        CreateSselog($socdata);
			                        unset($socdata);

					          				}

														if ($scrvalue->inning == '2' && $s2 == 'false' ) {
															$s2 = 'true';
														}	          				

					          			}
					          		}

					          		unset($data);

					          		if ($s2 == 'true' || $resStatus == "Finished") {
					          			$data['lookId'] = $ldvalue->id;

						          		if (count($scorCard) > 0) {
						          			foreach ($scorCard as $scrkey => $scrvalue) {
						          				if ($scrvalue->inning == '1') {

						          					if ($teamAInn1) {
						          						$data['teamAInn1'] = '1';
						          						$data['teamAInn1Score'] = $scrvalue->score;
																	$data['teamAInn1Wickets'] = $scrvalue->wickets;
																	$data['teamAInn1Overs'] = $scrvalue->overs;
																}else{
																	$data['teamAInn1'] = '0';
																}

						          					if ($teamAInn2) {
						          						$data['teamAInn2'] = '1';
						          						$data['teamAInn2Score'] = $scrvalue->score;
																	$data['teamAInn2Wickets'] = $scrvalue->wickets;
																	$data['teamAInn2Overs'] = $scrvalue->overs;
																}else{
																	$data['teamAInn1'] = '0';
																}

						          					if ($teamBInn1) {
						          						$data['teamBInn1'] = '1';
																	$data['teamBInn1Score'] = $scrvalue->score;
																	$data['teamBInn1Wickets'] = $scrvalue->wickets;
																	$data['teamBInn1Overs'] = $scrvalue->overs;
						          					}else{
						          						$data['teamBInn1'] = '0';
						          					}

						          					if ($teamBInn2) {
						          						$data['teamBInn2'] = '1';
																	$data['teamBInn2Score'] = $scrvalue->score;
																	$data['teamBInn2Wickets'] = $scrvalue->wickets;
																	$data['teamBInn2Overs'] = $scrvalue->overs;
						          					}else{
						          						$data['teamBInn2'] = '0';
						          					}						          					

																if ($resStatus == "Finished") {
																	$data['matchStatus'] = '1';
																}

								          			$matchLookupModel->updateInningsStatus($data);
								          			unset($data);

						          				}
						          			}
						          		}

						          		//- Update Player Batting Status To Notout
						          		$data['matchId'] = $ldvalue->matchId;
						          		$data['innings'] = '1';
						          		if ($irvalue->team_id == $ldvalue->teamaKey) {
						          			$data['teamId'] = $ldvalue->teamaId;
						          		}else if ($irvalue->team_id == $ldvalue->teambKey) {
						          			$data['teamId'] = $ldvalue->teambId;
					          			}

					          			$playScorsModel->updateOldPlayerScore($data);
					          			unset($data);

					          		}else{

					          			$data['lookId'] = $ldvalue->id;

						          		if (count($scorCard) > 0) {
						          			foreach ($scorCard as $scrkey => $scrvalue) {
						          				if ($scrvalue->inning == '1') {

						          					if ($teamAInn1) {
						          						$data['teamAInn1Score'] = $scrvalue->score;
																	$data['teamAInn1Wickets'] = $scrvalue->wickets;
																	$data['teamAInn1Overs'] = $scrvalue->overs;

						          					}

											    			if ($teamAInn2) {
																	$data['teamAInn2Score'] = $scrvalue->score;
																	$data['teamAInn2Wickets'] = $scrvalue->wickets;
																	$data['teamAInn2Overs'] = $scrvalue->overs;
											    			}

						          					if ($teamBInn1) {
																	$data['teamBInn1Score'] = $scrvalue->score;
																	$data['teamBInn1Wickets'] = $scrvalue->wickets;
																	$data['teamBInn1Overs'] = $scrvalue->overs;
						          					}

											    			if ($teamBInn2) {
																	$data['teamBInn2Score'] = $scrvalue->score;
																	$data['teamBInn2Wickets'] = $scrvalue->wickets;
																	$data['teamBInn2Overs'] = $scrvalue->overs;
											    			}

								          			$matchLookupModel->updateInningsStatus($data);
								          			unset($data);

						          				}
						          			}
						          		}

					          		}

		          				}

		          			}else if ($irvalue->scoreboard == 'S2') {
		          				
				          		//- Update Innings 1 Player Scores
		          				$teamAInn1 = false;
		          				$teamBInn1 = false;
											$teamAInn2 = false;
											$teamBInn2 = false;

		          				if ($irvalue->team_id == $ldvalue->teamaKey) {
		          					$innings2 = $ldvalue->teamAInn1;
				          			
				          			if ($ldvalue->teamAInn1 == 0) {
				          				$teamAInn1 = true;
				          				$teamAInn2 = false;
				          			}else{
				          				$teamAInn1 = false;
				          				$teamAInn2 = true;
				          			}

		          				}else if ($irvalue->team_id == $ldvalue->teambKey) {
		          					$innings2 = $ldvalue->teamBInn1;

				          			if ($ldvalue->teamBInn1 == 0) {
				          				$teamBInn1 = true;
				          				$teamBInn2 = false;
				          			}else{
				          				$teamBInn1 = false;
				          				$teamBInn2 = true;
				          			}

		          				}

		          				if ($innings2 == '0') {

		          					$data['matchId'] = $ldvalue->matchId;
					          		$data['innings'] = '2';
					          		if ($irvalue->team_id == $ldvalue->teamaKey) {
					          			$data['teamId'] = $ldvalue->teamaId;
					          		}else if ($irvalue->team_id == $ldvalue->teambKey) {
					          			$data['teamId'] = $ldvalue->teambId;
					          		}

					          		updateInningsScores($data,(array)$irvalue);

					          		if (count($scorCard) > 0) {
					          			foreach ($scorCard as $scrkey => $scrvalue) {
					          				if ($scrvalue->inning == '2') {
								          
			                        //- Update Socket
			                        $socdata['eventName'] = 'innScors';

			                        $msg = array('matchId' => $data['matchId'], 'innings' => $data['innings'], 'teamId' => $data['teamId'], 'runs' => $scrvalue->score, 'wickets' => $scrvalue->wickets, 'overs' => $scrvalue->overs);

			                        $socdata['eventData'] = json_encode($msg);
			                        CreateSselog($socdata);
			                        unset($socdata);

					          				}

														if ($scrvalue->inning == '3' && $s3 == 'false' ) {
															$s3 = 'true';
														}

					          			}
					          		}

					          		unset($data);

					          		if ($s3 == 'true' || $resStatus == "Finished") {
					          			$data['lookId'] = $ldvalue->id;

						          		if (count($scorCard) > 0) {
						          			foreach ($scorCard as $scrkey => $scrvalue) {
						          				if ($scrvalue->inning == '2') {

						          					if ($teamAInn1) {
						          						$data['teamAInn1'] = '1';
						          						$data['teamAInn1Score'] = $scrvalue->score;
																	$data['teamAInn1Wickets'] = $scrvalue->wickets;
																	$data['teamAInn1Overs'] = $scrvalue->overs;
																}else{
																	$data['teamAInn1'] = '0';
																}
						          					
																if ($teamAInn2) {
																	$data['teamAInn2'] = '1';
																	$data['teamAInn2Score'] = $scrvalue->score;
																	$data['teamAInn2Wickets'] = $scrvalue->wickets;
																	$data['teamAInn2Overs'] = $scrvalue->overs;
																}else{
																	$data['teamAInn2'] = '0';
																}

						          					if ($teamBInn1) {
						          						$data['teamBInn1'] = '1';
																	$data['teamBInn1Score'] = $scrvalue->score;
																	$data['teamBInn1Wickets'] = $scrvalue->wickets;
																	$data['teamBInn1Overs'] = $scrvalue->overs;
						          					}else{
						          						$data['teamBInn1'] = '0';
						          					}

																if ($teamBInn2) {
																	$data['teamBInn2'] = '1';
																	$data['teamBInn2Score'] = $scrvalue->score;
																	$data['teamBInn2Wickets'] = $scrvalue->wickets;
																	$data['teamBInn2Overs'] = $scrvalue->overs;
																}else{
																	$data['teamBInn2'] = '0';
																}

																if ($resStatus == "Finished") {
																	$data['matchStatus'] = '1';
																}

								          			$matchLookupModel->updateInningsStatus($data);
								          			unset($data);

						          				}
						          			}
						          		}

						          		//- Update Player Batting Status To Notout
						          		$data['matchId'] = $ldvalue->matchId;
						          		$data['innings'] = '2';
						          		if ($irvalue->team_id == $ldvalue->teamaKey) {
						          			$data['teamId'] = $ldvalue->teamaId;
						          		}else if ($irvalue->team_id == $ldvalue->teambKey) {
						          			$data['teamId'] = $ldvalue->teambId;
					          			}

					          			$playScorsModel->updateOldPlayerScore($data);
					          			unset($data);

					          		}else{

					          			$data['lookId'] = $ldvalue->id;

						          		if (count($scorCard) > 0) {
						          			foreach ($scorCard as $scrkey => $scrvalue) {
						          				if ($scrvalue->inning == '2') {

						          					if ($teamAInn1) {
						          						$data['teamAInn1Score'] = $scrvalue->score;
																	$data['teamAInn1Wickets'] = $scrvalue->wickets;
																	$data['teamAInn1Overs'] = $scrvalue->overs;

						          					}

											    			if ($teamAInn2) {
																	$data['teamAInn2Score'] = $scrvalue->score;
																	$data['teamAInn2Wickets'] = $scrvalue->wickets;
																	$data['teamAInn2Overs'] = $scrvalue->overs;
											    			}

						          					if ($teamBInn1) {
																	$data['teamBInn1Score'] = $scrvalue->score;
																	$data['teamBInn1Wickets'] = $scrvalue->wickets;
																	$data['teamBInn1Overs'] = $scrvalue->overs;
						          					}

											    			if ($teamBInn2) {
																	$data['teamBInn2Score'] = $scrvalue->score;
																	$data['teamBInn2Wickets'] = $scrvalue->wickets;
																	$data['teamBInn2Overs'] = $scrvalue->overs;
											    			}

								          			$matchLookupModel->updateInningsStatus($data);
								          			unset($data);

						          				}
						          			}
						          		}

					          		}

		          				}
		          			
		          			}else if ($irvalue->scoreboard == 'S3') {
		          				
				          		//- Update Innings 1 Player Scores
		          				$teamAInn1 = false;
		          				$teamBInn1 = false;
											$teamAInn2 = false;
											$teamBInn2 = false;

		          				if ($irvalue->team_id == $ldvalue->teamaKey) {
		          					$innings3 = $ldvalue->teamAInn2;
				          			
				          			if ($ldvalue->teamAInn1 == 0) {
				          				$teamAInn1 = true;
				          				$teamAInn2 = false;
				          			}else{
				          				$teamAInn1 = false;
				          				$teamAInn2 = true;
				          			}

		          				}else if ($irvalue->team_id == $ldvalue->teambKey) {
		          					$innings3 = $ldvalue->teamBInn2;

				          			if ($ldvalue->teamBInn1 == 0) {
				          				$teamBInn1 = true;
				          				$teamBInn2 = false;
				          			}else{
				          				$teamBInn1 = false;
				          				$teamBInn2 = true;
				          			}

		          				}

		          				if ($innings3 == '0') {

		          					$data['matchId'] = $ldvalue->matchId;
					          		$data['innings'] = '3';
					          		if ($irvalue->team_id == $ldvalue->teamaKey) {
					          			$data['teamId'] = $ldvalue->teamaId;
					          		}else if ($irvalue->team_id == $ldvalue->teambKey) {
					          			$data['teamId'] = $ldvalue->teambId;
					          		}

					          		updateInningsScores($data,(array)$irvalue);

					          		if (count($scorCard) > 0) {
					          			foreach ($scorCard as $scrkey => $scrvalue) {
					          				if ($scrvalue->inning == '3') {
								          
			                        //- Update Socket
			                        $socdata['eventName'] = 'innScors';

			                        $msg = array('matchId' => $data['matchId'], 'innings' => $data['innings'], 'teamId' => $data['teamId'], 'runs' => $scrvalue->score, 'wickets' => $scrvalue->wickets, 'overs' => $scrvalue->overs);

			                        $socdata['eventData'] = json_encode($msg);
			                        CreateSselog($socdata);
			                        unset($socdata);

					          				}

														if ($scrvalue->inning == '4' && $s4 == 'false' ) {
															$s4 = 'true';
														}

					          			}
					          		}

					          		unset($data);

					          		if ($s4 == 'true' || $resStatus == "Finished") {
					          			$data['lookId'] = $ldvalue->id;

						          		if (count($scorCard) > 0) {
						          			foreach ($scorCard as $scrkey => $scrvalue) {
						          				if ($scrvalue->inning == '3') {

						          					if ($teamAInn1) {
						          						$data['teamAInn1'] = '1';
						          						$data['teamAInn1Score'] = $scrvalue->score;
																	$data['teamAInn1Wickets'] = $scrvalue->wickets;
																	$data['teamAInn1Overs'] = $scrvalue->overs;
																}else{
																	$data['teamAInn1'] = '0';
																}
						          					
																if ($teamAInn2) {
																	$data['teamAInn2'] = '1';
																	$data['teamAInn2Score'] = $scrvalue->score;
																	$data['teamAInn2Wickets'] = $scrvalue->wickets;
																	$data['teamAInn2Overs'] = $scrvalue->overs;
																}else{
																	$data['teamAInn2'] = '0';
																}

						          					if ($teamBInn1) {
						          						$data['teamBInn1'] = '1';
																	$data['teamBInn1Score'] = $scrvalue->score;
																	$data['teamBInn1Wickets'] = $scrvalue->wickets;
																	$data['teamBInn1Overs'] = $scrvalue->overs;
						          					}else{
						          						$data['teamBInn1'] = '0';
						          					}

																if ($teamBInn2) {
																	$data['teamBInn2'] = '1';
																	$data['teamBInn2Score'] = $scrvalue->score;
																	$data['teamBInn2Wickets'] = $scrvalue->wickets;
																	$data['teamBInn2Overs'] = $scrvalue->overs;
																}else{
																	$data['teamBInn2'] = '0';
																}

																if ($resStatus == "Finished") {
																	$data['matchStatus'] = '1';
																}

								          			$matchLookupModel->updateInningsStatus($data);
								          			unset($data);

						          				}
						          			}
						          		}


						          		//- Update Player Batting Status To Notout
						          		$data['matchId'] = $ldvalue->matchId;
						          		$data['innings'] = '3';
						          		if ($irvalue->team_id == $ldvalue->teamaKey) {
						          			$data['teamId'] = $ldvalue->teamaId;
						          		}else if ($irvalue->team_id == $ldvalue->teambKey) {
						          			$data['teamId'] = $ldvalue->teambId;
					          			}

					          			$playScorsModel->updateOldPlayerScore($data);
					          			unset($data);


					          		}else{

					          			$data['lookId'] = $ldvalue->id;

						          		if (count($scorCard) > 0) {
						          			foreach ($scorCard as $scrkey => $scrvalue) {
						          				if ($scrvalue->inning == '3') {

						          					if ($teamAInn1) {
						          						$data['teamAInn1Score'] = $scrvalue->score;
																	$data['teamAInn1Wickets'] = $scrvalue->wickets;
																	$data['teamAInn1Overs'] = $scrvalue->overs;

						          					}

											    			if ($teamAInn2) {
																	$data['teamAInn2Score'] = $scrvalue->score;
																	$data['teamAInn2Wickets'] = $scrvalue->wickets;
																	$data['teamAInn2Overs'] = $scrvalue->overs;
											    			}

						          					if ($teamBInn1) {
																	$data['teamBInn1Score'] = $scrvalue->score;
																	$data['teamBInn1Wickets'] = $scrvalue->wickets;
																	$data['teamBInn1Overs'] = $scrvalue->overs;
						          					}

											    			if ($teamBInn2) {
																	$data['teamBInn2Score'] = $scrvalue->score;
																	$data['teamBInn2Wickets'] = $scrvalue->wickets;
																	$data['teamBInn2Overs'] = $scrvalue->overs;
											    			}

								          			$matchLookupModel->updateInningsStatus($data);
								          			unset($data);

						          				}
						          			}
						          		}

					          		}

		          				}
		          					          			
		          			}else if ($irvalue->scoreboard == 'S4') {
		          				
				          		//- Update Innings 1 Player Scores
		          				$teamAInn1 = false;
		          				$teamBInn1 = false;
											$teamAInn2 = false;
											$teamBInn2 = false;

		          				if ($irvalue->team_id == $ldvalue->teamaKey) {
		          					$innings4 = $ldvalue->teamAInn2;
				          			
				          			if ($ldvalue->teamAInn1 == 0) {
				          				$teamAInn1 = true;
				          				$teamAInn2 = false;
				          			}else{
				          				$teamAInn1 = false;
				          				$teamAInn2 = true;
				          			}

		          				}else if ($irvalue->team_id == $ldvalue->teambKey) {
		          					$innings4 = $ldvalue->teamBInn2;

				          			if ($ldvalue->teamBInn1 == 0) {
				          				$teamBInn1 = true;
				          				$teamBInn2 = false;
				          			}else{
				          				$teamBInn1 = false;
				          				$teamBInn2 = true;
				          			}

		          				}

		          				if ($innings4 == '0') {

		          					$data['matchId'] = $ldvalue->matchId;
					          		$data['innings'] = '4';
					          		if ($irvalue->team_id == $ldvalue->teamaKey) {
					          			$data['teamId'] = $ldvalue->teamaId;
					          		}else if ($irvalue->team_id == $ldvalue->teambKey) {
					          			$data['teamId'] = $ldvalue->teambId;
					          		}

					          		updateInningsScores($data,(array)$irvalue);

					          		if (count($scorCard) > 0) {
					          			foreach ($scorCard as $scrkey => $scrvalue) {
					          				if ($scrvalue->inning == '4') {
								          
			                        //- Update Socket
			                        $socdata['eventName'] = 'innScors';

			                        $msg = array('matchId' => $data['matchId'], 'innings' => $data['innings'], 'teamId' => $data['teamId'], 'runs' => $scrvalue->score, 'wickets' => $scrvalue->wickets, 'overs' => $scrvalue->overs);

			                        $socdata['eventData'] = json_encode($msg);
			                        CreateSselog($socdata);
			                        unset($socdata);

					          				}
					          			}
					          		}

					          		unset($data);

					          		if ($resStatus == "Finished") {
					          			$data['lookId'] = $ldvalue->id;

						          		if (count($scorCard) > 0) {
						          			foreach ($scorCard as $scrkey => $scrvalue) {
						          				if ($scrvalue->inning == '3') {

						          					if ($teamAInn1) {
						          						$data['teamAInn1'] = '1';
						          						$data['teamAInn1Score'] = $scrvalue->score;
																	$data['teamAInn1Wickets'] = $scrvalue->wickets;
																	$data['teamAInn1Overs'] = $scrvalue->overs;
																}else{
																	$data['teamAInn1'] = '0';
																}
						          					
																if ($teamAInn2) {
																	$data['teamAInn2'] = '1';
																	$data['teamAInn2Score'] = $scrvalue->score;
																	$data['teamAInn2Wickets'] = $scrvalue->wickets;
																	$data['teamAInn2Overs'] = $scrvalue->overs;
																}else{
																	$data['teamAInn2'] = '0';
																}

						          					if ($teamBInn1) {
						          						$data['teamBInn1'] = '1';
																	$data['teamBInn1Score'] = $scrvalue->score;
																	$data['teamBInn1Wickets'] = $scrvalue->wickets;
																	$data['teamBInn1Overs'] = $scrvalue->overs;
						          					}else{
						          						$data['teamBInn1'] = '0';
						          					}

																if ($teamBInn2) {
																	$data['teamBInn2'] = '1';
																	$data['teamBInn2Score'] = $scrvalue->score;
																	$data['teamBInn2Wickets'] = $scrvalue->wickets;
																	$data['teamBInn2Overs'] = $scrvalue->overs;
																}else{
																	$data['teamBInn2'] = '0';
																}

																$data['matchStatus'] = '1';

								          			$matchLookupModel->updateInningsStatus($data);
								          			unset($data);

						          				}
						          			}
						          		}


						          		//- Update Player Batting Status To Notout
						          		$data['matchId'] = $ldvalue->matchId;
						          		$data['innings'] = '4';
						          		if ($irvalue->team_id == $ldvalue->teamaKey) {
						          			$data['teamId'] = $ldvalue->teamaId;
						          		}else if ($irvalue->team_id == $ldvalue->teambKey) {
						          			$data['teamId'] = $ldvalue->teambId;
					          			}

					          			$playScorsModel->updateOldPlayerScore($data);
					          			unset($data);

					          		}else{

					          			$data['lookId'] = $ldvalue->id;

						          		if (count($scorCard) > 0) {
						          			foreach ($scorCard as $scrkey => $scrvalue) {
						          				if ($scrvalue->inning == '3') {

						          					if ($teamAInn1) {
						          						$data['teamAInn1Score'] = $scrvalue->score;
																	$data['teamAInn1Wickets'] = $scrvalue->wickets;
																	$data['teamAInn1Overs'] = $scrvalue->overs;

						          					}

											    			if ($teamAInn2) {
																	$data['teamAInn2Score'] = $scrvalue->score;
																	$data['teamAInn2Wickets'] = $scrvalue->wickets;
																	$data['teamAInn2Overs'] = $scrvalue->overs;
											    			}

						          					if ($teamBInn1) {
																	$data['teamBInn1Score'] = $scrvalue->score;
																	$data['teamBInn1Wickets'] = $scrvalue->wickets;
																	$data['teamBInn1Overs'] = $scrvalue->overs;
						          					}

											    			if ($teamBInn2) {
																	$data['teamBInn2Score'] = $scrvalue->score;
																	$data['teamBInn2Wickets'] = $scrvalue->wickets;
																	$data['teamBInn2Overs'] = $scrvalue->overs;
											    			}

								          			$matchLookupModel->updateInningsStatus($data);
								          			unset($data);

						          				}
						          			}
						          		}

					          		}

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
		          $data['text'] = 'Unable To Fetch Live Scores - '.$ldvalue->crickmId;
		          $data['disc'] = $error;
		          CreateErrorLog($data);
		          unset($data);

		        }else{

		          $data['userId'] = '0';
		          $data['text'] = 'Unable To Fetch Live Scores - '.$ldvalue->crickmId;
		          $data['disc'] = 'Cricbuzz API not responding need manual inspection';
		          CreateErrorLog($data);
		          unset($data);
		        }

		        
		        //- Send Message To Admin
		        $data['subject'] = 'Unable To Fetch Live Scores - '.$ldvalue->crickmId;
		        $data['message'] = 'International Matches Api error. Please run it manually';
		        SendErrorEmail($data);
		        unset($data);

		      }

      	}
      }

    }


    //- Update Game Winner
    public function updateGameWinners(){

    	$contModel = new ContestModel();
      $matchLookupModel = new MatchesLookupModel();
      $conFinPlayer = new ContestFinalPlayers();
      $playerScores = new MatchPlayerScores();
      $userModel = new UserModel();
      $teamModel = new TeamsModel();
      $fishData = $matchLookupModel->getFinishedMatches();

      if (count($fishData) > 0) {
      	foreach ($fishData as $fdkey => $fdvalue) {
      	
    			//- Get All Games
    			$allGames = $contModel->getAllGameForResults($fdvalue->matchId);

    			if (count($allGames) > 0) {
    				foreach ($allGames as $agkey => $agvalue) {

    					//- Get User One Pl6 Scores
    					$totalOScore = 0;
    					$userOnePlaye = $conFinPlayer->getFinalGamePlayers($agvalue->matchId, $agvalue->pairId, $agvalue->userOne);
    					if (count($userOnePlaye) > 0) {
    						foreach ($userOnePlaye as $uopkey => $uopvalue) {
    							
    							$playerScore = $playerScores->getMatchPlayerTotalScores($agvalue->matchId, $uopvalue->playerId);

    							$tmpOScore = 0;
    							if (count($playerScore) > 0) {
    								$tmpOScore = intval($playerScore[0]->runs);
    							}

    							if ($uopvalue->isCap == '1') {
    								$tmpOScore = intval($tmpOScore) * 2;
    							}

    							if ($uopvalue->isvCap == '1') {
    								$tmpOScore = intval($tmpOScore) * 1.5;
    							}

    							$totalOScore = floatval($totalOScore) + $tmpOScore;

    						}
    					}
    					
    					$totalOScore = round($totalOScore, 2);

    					//- Get User Two Pl6 Scores
    					$totalTScore = 0;
    					$userTwoPlaye = $conFinPlayer->getFinalGamePlayers($agvalue->matchId, $agvalue->pairId, $agvalue->userTwo);
    					if (count($userTwoPlaye) > 0) {
    						foreach ($userTwoPlaye as $utpkey => $utpvalue) {
    							
    							$playerScore = $playerScores->getMatchPlayerTotalScores($agvalue->matchId, $utpvalue->playerId);

    							$tmpTScore = 0;
    							if (count($playerScore) > 0) {
    								$tmpTScore = intval($playerScore[0]->runs);
    							}

    							if ($utpvalue->isCap == '1') {
    								$tmpTScore = intval($tmpTScore) * 2;
    							}

    							if ($utpvalue->isvCap == '1') {
    								$tmpTScore = intval($tmpTScore) * 1.5;
    							}

    							$totalTScore = floatval($totalTScore) + $tmpTScore;

    						}
    					}

    					$totalTScore = round($totalTScore, 2);

    					//- Find Winner, Diff amount
    					$gameWinnerId = '';
    					$winnStatus = 0;
    					$totDiff = 0;
    					if ($totalOScore > $totalTScore) {
    						$gameWinnerId = $agvalue->userOne;
    						$totDiff = floatval($totalOScore) - floatval($totalTScore);
    						$totDiff = round($totDiff, 2);
    						$winnStatus = 1;
    					}else if ($totalTScore > $totalOScore) {
    						$gameWinnerId = $agvalue->userTwo;
    						$totDiff = floatval($totalTScore) - floatval($totalOScore);
    						$totDiff = round($totDiff, 2);
    						$winnStatus = 1;
    					}

    					if ($winnStatus == 1) {
    						
    						//- Winning Price
    						$winningPrice = 0;
    						$priceDetails = $contModel->getCampPrices($agvalue->contestId,$fdvalue->matchType);

	    					$tmpDiff = 0;
	    					if ($totDiff > floatval($priceDetails[0]->maxDiff)) {
	    						$tmpDiff = floatval($priceDetails[0]->maxDiff);
	    					}else{
	    						$tmpDiff = floatval($totDiff);
	    					}    						

	    					$contPrice = 0;
	    					if ($agvalue->contestId == '1') {
	    						$contPrice = 1;
	    					}else if ($agvalue->contestId == '2') {
	    						$contPrice = 5;
	    					}else if ($agvalue->contestId == '3') {
	    						$contPrice = 10;
	    					}else if ($agvalue->contestId == '4') {
	    						$contPrice = 20;
	    					}

	    					//- Winner 
	    					$winnPrice = ($tmpDiff * $contPrice);
	    					$winnPrice = round($winnPrice, 2);
	    					$winrRefnd = $priceDetails[0]->price;

	    					$pl6fee = (8 / 100 ) * $winnPrice;
	    					$pl6fee = round($pl6fee, 2);  

	    					$finWinAmount = floatval($winnPrice) - floatval($pl6fee);
	    					$finWinAmount = round($finWinAmount, 2);

	    					//- Looser
	    					$lostAmount = $winnPrice;
	    					$loosRefnd = floatval($priceDetails[0]->price) - floatval($lostAmount);

	    					//- Update Details
	    					$data['totalOScore'] = $totalOScore;
	    					$data['totalTScore'] = $totalTScore;
	    					$data['gameWinnerId'] = $gameWinnerId;
	    					$data['winnStatus'] = $winnStatus;

	    					if ($gameWinnerId == $agvalue->userOne) {
	    						
	    						$data['oneRefundAmount'] = $winrRefnd;
	    						$data['oneWinningPrice'] = $finWinAmount;
	    						$data['oneLostPrice'] = '0';

	    						$data['twoRefundAmount'] = $loosRefnd;
	    						$data['twoWinningPrice'] = '0';
	    						$data['twoLostPrice'] = $lostAmount;				

	    					}

	    					if ($gameWinnerId == $agvalue->userTwo) {

	    						$data['twoRefundAmount'] = $winrRefnd;
	    						$data['twoWinningPrice'] = $finWinAmount;
	    						$data['twoLostPrice'] = '0';

	    						$data['oneRefundAmount'] = $loosRefnd;
	    						$data['oneWinningPrice'] = '0';
	    						$data['oneLostPrice'] = $lostAmount;

	    					}
	    					$data['platform'] = $pl6fee;

	    					//- Update Contest
	    					$contModel->updateContestResults($agvalue->pairId,$data);

	    					$userWalletModel = new UserWalletModel();

	    					//- Refund Winner Fee
	              $tmpWall['userId'] = $gameWinnerId;
	              $tmpWall['amount'] = $priceDetails[0]->price;
	              $tmpWall['contestId'] = $agvalue->pairId;
	              $tmpWall['text'] = 'Contest fee refund';
	              $tmpWall['status'] = '3';
	              $userWalletModel->creditWallet($tmpWall);
	              unset($tmpWall);	    					

	    					//- Deposit Winner Amount
	              $tmpWall['userId'] = $gameWinnerId;
	              $tmpWall['amount'] = $finWinAmount;
	              $tmpWall['contestId'] = $agvalue->pairId;
	              $tmpWall['text'] = 'Contest win deposit';
	              $tmpWall['status'] = '4';
	              $userWalletModel->creditWallet($tmpWall);
	              unset($tmpWall);

	              //- Refund Lossr Fee
	              $looserId = '';
	              if ($gameWinnerId == $agvalue->userOne) {
	              	$looserId = $agvalue->userTwo;
	              }else if ($gameWinnerId == $agvalue->userTwo) {
	              	$looserId = $agvalue->userOne;
	              }

	              if (floatval($loosRefnd) > 0) {
		              $tmpWall['userId'] = $looserId;
		              $tmpWall['amount'] = $loosRefnd;
		              $tmpWall['contestId'] = $agvalue->pairId;
		              $tmpWall['text'] = 'Contest fee refund';
		              $tmpWall['status'] = '3';
		              $userWalletModel->creditWallet($tmpWall);
		              unset($tmpWall);
	              }    					

	    					unset($data);

	    					//- Send Notification For Winner
	    					$userDetails = $userModel->getUserToken($gameWinnerId);
	    					if (count($userDetails) > 0) {
	    						if (!empty($userDetails[0]->token)) {

										$team1 = $teamModel->getTeamById($agvalue->userOneTeam);
										$team2 = $teamModel->getTeamById($agvalue->userTwoTeam);

										$title = $team1[0]->shortName." v ".$team2[0]->shortName." ₹".$contPrice;

				            $body = "Congratulation! You won ₹".$finWinAmount."!";

				            $notifi = array("title" => $title, "body" => $body);
				            $tmpBody = array('notification' => $notifi, 'to' => $userDetails[0]->token);
				            $tmpBody = json_encode($tmpBody);

				            sendPushNotifi($tmpBody);  	    							

	    						}
	    					}

	    					//- Send Notification For Looser
	    					$userDetails = $userModel->getUserToken($looserId);
	    					if (count($userDetails) > 0) {
	    						if (!empty($userDetails[0]->token)) {

										$team1 = $teamModel->getTeamById($agvalue->userOneTeam);
										$team2 = $teamModel->getTeamById($agvalue->userTwoTeam);

										$title = $team1[0]->shortName." v ".$team2[0]->shortName." ₹".$contPrice;

				            $body = "You lost ₹".$lostAmount.". Try an another game";

				            $notifi = array("title" => $title, "body" => $body);
				            $tmpBody = array('notification' => $notifi, 'to' => $userDetails[0]->token);
				            $tmpBody = json_encode($tmpBody);

				            sendPushNotifi($tmpBody);						

	    						}
	    					}

    					}else{

    						$priceDetails = $contModel->getCampPrices($agvalue->contestId,$fdvalue->matchType);

	    					$data['totalOScore'] = $totalOScore;
	    					$data['totalTScore'] = $totalTScore;
	    					$data['gameWinnerId'] = $gameWinnerId;
	    					$data['winnStatus'] = $winnStatus;
	    					$data['platform'] = '0';

    						$data['oneRefundAmount'] = $priceDetails[0]->price;
    						$data['oneWinningPrice'] = '0';
    						$data['oneLostPrice'] = '0';
    						$data['twoRefundAmount'] = $priceDetails[0]->price;
    						$data['twoWinningPrice'] = '0';
    						$data['twoLostPrice'] = '0';

    						//- Update Contest
	    					$contModel->updateContestResults($agvalue->pairId,$data);
	    					unset($data);

	    					$userWalletModel = new UserWalletModel();

	    					//- Refund User One Fee
	              $tmpWall['userId'] = $agvalue->userOne;
	              $tmpWall['amount'] = $priceDetails[0]->price;
	              $tmpWall['contestId'] = $agvalue->pairId;
	              $tmpWall['text'] = 'Contest fee refund';
	              $tmpWall['status'] = '3';
	              $userWalletModel->creditWallet($tmpWall);
	              unset($tmpWall);		    					

	    					//- Refund User One Fee
	              $tmpWall['userId'] = $agvalue->userTwo;
	              $tmpWall['amount'] = $priceDetails[0]->price;
	              $tmpWall['contestId'] = $agvalue->pairId;
	              $tmpWall['text'] = 'Contest fee refund';
	              $tmpWall['status'] = '3';
	              $userWalletModel->creditWallet($tmpWall);
	              unset($tmpWall);

    					}

    				}
    			}

    			$matchLookupModel->updateCompleteStatus($fdvalue->id);
    			
      	}
      }
   
    }


    public function clearLogs(){
    	clearSselog();
    }


}
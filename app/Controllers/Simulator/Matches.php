<?php

namespace App\Controllers\Simulator;
use App\Controllers\BaseController;
use App\Models\SeriesModel;
use App\Models\MatchesModel;
use App\Models\PlayersModel;
use App\Models\MatchesLookupModel;
use Config\Services;

class Matches extends BaseController
{

    public function __construct(){
      helper('Common');
    }


    public function create()
    {
      
    	if (!$this->request->is('post')) {

    		return view('matchCreate');

    	}else{

        $matchesModel = new MatchesModel();
        $playersModel = new PlayersModel();
        $lookupModel = new MatchesLookupModel();
      
        $matchId = $this->request->getPost('matchId');
        $matchDate = $this->request->getPost('matchDate');

        //- Copy matches table
        $matchDtails = $matchesModel->getMatcheById($matchId);

        if (count($matchDtails) > 0) {

          $sdate = date('Y-m-d H:i:s', strtotime($matchDate));
          if ($matchDtails[0]->matchType == '1') {
            $edate = date('Y-m-d H:i:s', strtotime($sdate." +480 minutes"));
          }else if ($matchDtails[0]->matchType == '3') {
            $edate = date('Y-m-d H:i:s', strtotime($sdate." +5 days"));
          }else if ($matchDtails[0]->matchType == '2') {
            $edate = date('Y-m-d H:i:s', strtotime($sdate." +90 minutes"));
          }else{          
            $edate = date('Y-m-d H:i:s', strtotime($sdate." +90 minutes"));
          }

          $data['seriesId'] = $matchDtails[0]->seriesId;
          $data['cricksId'] = $matchDtails[0]->cricksId; 
          $data['crickmId'] = $matchDtails[0]->crickmId;
          $data['teamaKey'] = $matchDtails[0]->teamaKey;
          $data['teambKey'] = $matchDtails[0]->teambKey;
          $data['title'] = $matchDtails[0]->title;
          $data['teamaId'] = $matchDtails[0]->teamaId;
          $data['teambId'] = $matchDtails[0]->teambId;
          $data['starttime'] = $sdate;
          $data['endtime'] = $edate;
          $data['status'] = '1';
          $data['matchType'] = $matchDtails[0]->matchType;
          $data['name'] = $matchDtails[0]->name;
          $data['seasonId'] = $matchDtails[0]->seasonId;

          $matchesModel->createMatch($data);
          unset($data);

          //- Get New Match Id
          $oldMatch = $matchesModel->getLastMatchExist($matchDtails[0]->crickmId);

          //- Copy players table
          $teamADtl = $playersModel->getTeamPlayes($matchDtails[0]->matchId, $matchDtails[0]->teamaId);
          if (count($teamADtl) > 0) {
            foreach ($teamADtl as $takey => $tavalue) {
                  
              $data['matchId'] = $oldMatch[0]->id;
              $data['playerId'] = $tavalue->playerId;
              $data['teamId'] = $tavalue->teamId;
              $data['orderId'] = $tavalue->orderId;
              $playersModel->createMatchPlayer($data);
              unset($data);

            }
          }


          $teamBDtl = $playersModel->getTeamPlayes($matchDtails[0]->matchId, $matchDtails[0]->teambId);
          if (count($teamBDtl) > 0) {
            foreach ($teamBDtl as $tbkey => $tbvalue) {
                  
              $data['matchId'] = $oldMatch[0]->id;
              $data['playerId'] = $tbvalue->playerId;
              $data['teamId'] = $tbvalue->teamId;
              $data['orderId'] = $tbvalue->orderId;
              $playersModel->createMatchPlayer($data);
              unset($data);

            }
          }

          $matchesModel->updateMatchHasPlayers($oldMatch[0]->id);

          //- Copy lookup table 
          $data['matchId'] = $oldMatch[0]->id;
          $data['cricksId'] = $matchDtails[0]->cricksId;
          $data['crickmId'] = $matchDtails[0]->crickmId;
          $data['teamaKey'] = $matchDtails[0]->teamaKey;
          $data['teambKey'] = $matchDtails[0]->teambKey;
          $data['teamaId'] = $matchDtails[0]->teamaId;
          $data['teambId'] = $matchDtails[0]->teambId;
          $data['matchType'] = $matchDtails[0]->matchType;
          $data['starttime'] = $oldMatch[0]->startTime;
          $data['endtime'] = $oldMatch[0]->endTime;

          $lookupModel->createMatchLookup($data);
          unset($data);

          //- Redirect to success
          return redirect()->to('/simulator/success');

        }


    	}

    }


    public function success()
    {
      return view('matchSuccess');
    }


}

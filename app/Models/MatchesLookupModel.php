<?php

namespace App\Models;
use CodeIgniter\Model;
use CodeIgniter\Database\RawSql;

class MatchesLookupModel extends Model
{

    protected $db;
    private string $matchesTbl;
    public function __construct() {
      $this->db = \Config\Database::connect();
      $this->matchesTbl = 'pl6_matches_lookup';
    }


    //- Create Matches Lookup
    public function createMatchLookup($data)
    {

      $builder = $this->db->table($this->matchesTbl);

      $builder->set('match_id', $data['matchId']);
      $builder->set('cricks_id', $data['cricksId']);
      $builder->set('crickm_id', $data['crickmId']);
      $builder->set('teama_key', $data['teamaKey']);
      $builder->set('teamb_key', $data['teambKey']);
      $builder->set('teama_id', $data['teamaId']);
      $builder->set('teamb_id', $data['teambId']);
      $builder->set('match_type', $data['matchType']);
      $builder->set('utc_starttime', $data['starttime']);
      $builder->set('utc_endtime', $data['endtime']);
       

      $builder->set('date_created', gmdate("Y-m-d H:i:s"));
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      return $builder->insert();

    }


    //- Update Matches Lookup
    public function updateMatchLookup($matchLookUpId,$data)
    {
      $builder = $this->db->table($this->matchesTbl);

      if (!empty($data['teamaKey'])) {
        $builder->set('teama_key', $data['teamaKey']);
      }

      if (!empty($data['teambKey'])) {
        $builder->set('teamb_key', $data['teambKey']);
      }
      
      if (!empty($data['teamaId'])) {
        $builder->set('teama_id', $data['teamaId']);
      }

      if (!empty($data['teambId'])) {
        $builder->set('teamb_id', $data['teambId']);
      }      

      if (!empty($data['matchType'])) {
        $builder->set('match_type', $data['matchType']);
      }

      if (!empty($data['starttime'])) {
        $builder->set('utc_starttime', $data['starttime']);
      }
      
      if (!empty($data['endtime'])) {
        $builder->set('utc_endtime', $data['endtime']);
      }
                  
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('match_id', $matchLookUpId);
      return $builder->update();
    }


    //- Check Matches Lookup
    public function checkMatchLookup($matchId)
    {
      
      $builder = $this->db->table($this->matchesTbl);
      
      $builder->select('match_id as matchId');
      $builder->select('cricks_id as cricksId');
      $builder->select('crickm_id as crickmId');
      $builder->select('teama_key as teamaKey');
      $builder->select('teamb_key as teambKey');
      $builder->select('teama_id as teamaId');	
      $builder->select('teamb_id as teambId');	
      $builder->select('utc_starttime as startTime');
      $builder->select('utc_endtime as endTime');	

      $builder->select('toss_status as tossStatus');
      $builder->select('match_status as matchStatus');

      $builder->select('teama_inn1_score_card as teamAInn1');
      $builder->select('teama_inn1_score as teamAInn1Score');
      $builder->select('teama_inn1_wickets as teamAInn1Wick');
      $builder->select('teama_inn1_overs as teamAInn1Overs');
      
      $builder->select('teama_inn2_score_card as teamAInn2');
      $builder->select('teama_inn2_score as teamAInn2Score');
      $builder->select('teama_inn2_wickets as teamAInn2Wick');
      $builder->select('teama_inn2_overs as teamAInn2Overs');

      $builder->select('teamb_inn1_score_card as teamBInn1');
      $builder->select('teamb_inn1_score as teamBInn1Score');
      $builder->select('teamb_inn1_wickets as teamBInn1Wick');
      $builder->select('teamb_inn1_overs as teamBInn1Overs');

      $builder->select('teamb_inn2_score_card as teamBInn2');
      $builder->select('teamb_inn2_score as teamBInn2Score');
      $builder->select('teamb_inn2_wickets as teamBInn2Wick');
      $builder->select('teamb_inn2_overs as teamBInn2Overs');

      $builder->select('player_list as player11');
      $builder->select('player_list_time as player11ST');
      
      $builder->where('match_id', $matchId);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }


    //- Get Matches About To Start For Toss
    public function getLiveTossMatches($startTime, $endTime)
    {

      $builder = $this->db->table($this->matchesTbl);
      
      $builder->select('id as id');
      $builder->select('match_id as matchId');
      $builder->select('cricks_id as cricksId');
      $builder->select('crickm_id as crickmId');
      $builder->select('teama_key as teamaKey');
      $builder->select('teamb_key as teambKey');
      $builder->select('teama_id as teamaId');  
      $builder->select('teamb_id as teambId');  
      $builder->select('utc_starttime as startTime');
      $builder->select('utc_endtime as endTime');
      $builder->select('notifi_send as notifiSend');
      
      $builder->where('toss_status', '0');

      $where = 'utc_starttime BETWEEN "'.$startTime. '" and "'.$endTime.'"'; 
      $builder->where(new RawSql($where));

      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }


    //- Get Matches Delayed Toss  
    public function getLiveTossDelayedMatches($startTime)
    {

      $builder = $this->db->table($this->matchesTbl);
      
      $builder->select('id as id');
      $builder->select('match_id as matchId');
      $builder->select('cricks_id as cricksId');
      $builder->select('crickm_id as crickmId');
      $builder->select('teama_key as teamaKey');
      $builder->select('teamb_key as teambKey');
      $builder->select('teama_id as teamaId');  
      $builder->select('teamb_id as teambId');  
      $builder->select('utc_starttime as startTime');
      $builder->select('utc_endtime as endTime'); 
      
      $builder->where('toss_status', '0');
      $builder->where('utc_starttime <=', $startTime);
      
      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }


    //- Update Toss Decision
    public function updateTossDecision($data)
    {

      $builder = $this->db->table($this->matchesTbl);

      $builder->set('toss_winner', $data['winningTeamId']);
      $builder->set('toss_status', '1');
      $builder->set('toss_decision', $data['tossDecision']);
      $builder->set('toss_time', $data['tossTime']);
      $builder->set('match_status', '1');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $data['matchLookUpId']);
      return $builder->update();

    }


    //- Get Ongoing Matches 
    public function getAllOngoingMatches()
    {

      $builder = $this->db->table($this->matchesTbl);
      
      $builder->select('id as id');
      $builder->select('match_id as matchId');
      $builder->select('cricks_id as cricksId');
      $builder->select('crickm_id as crickmId');
      $builder->select('teama_key as teamaKey');
      $builder->select('teamb_key as teambKey');
      $builder->select('teama_id as teamaId');  
      $builder->select('teamb_id as teambId');  
      $builder->select('utc_starttime as startTime');
      $builder->select('utc_endtime as endTime');
      $builder->select('match_type as matchType');
      $builder->select('teama_inn1_score_card as teamAInn1');
      $builder->select('teama_inn2_score_card as teamAInn2');
      $builder->select('teamb_inn1_score_card as teamBInn1');
      $builder->select('teamb_inn2_score_card as teamBInn2');

      $builder->where('toss_status','1');
      $builder->where('match_status','1');
      
      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }


    //- Update Innings 1 Status
    public function updateInningsStatus($data)
    {

      $builder = $this->db->table($this->matchesTbl);

      if (array_key_exists('teamAInn1', $data)) {
        if ($data['teamAInn1'] == '1') {
          $builder->set('teama_inn1_score_card', $data['teamAInn1']);
        }
      }

      if (array_key_exists('teamAInn1Score', $data)) {
        $builder->set('teama_inn1_score', $data['teamAInn1Score']);
      }

      if (array_key_exists('teamAInn1Wickets', $data)) {
        $builder->set('teama_inn1_wickets', $data['teamAInn1Wickets']);
      }      

      if (array_key_exists('teamAInn1Overs', $data)) {
        $builder->set('teama_inn1_overs', $data['teamAInn1Overs']);
      }      


      if (array_key_exists('teamAInn2', $data)) {
        if ($data['teamAInn2'] == '1') {
          $builder->set('teama_inn2_score_card', $data['teamAInn2']);
        }
      }      

      if (array_key_exists('teamAInn2Score', $data)) {
        $builder->set('teama_inn2_score', $data['teamAInn2Score']);
      }

      if (array_key_exists('teamAInn2Wickets', $data)) {
        $builder->set('teama_inn2_wickets', $data['teamAInn2Wickets']);
      }

      if (array_key_exists('teamAInn2Overs', $data)) {
        $builder->set('teama_inn2_overs', $data['teamAInn2Overs']);
      }              

      if (array_key_exists('teamBInn1', $data)) {
        if ($data['teamBInn1'] == '1') {
          $builder->set('teamb_inn1_score_card', $data['teamBInn1']);
        }
      }

      if (array_key_exists('teamBInn1Score', $data)) {
        $builder->set('teamb_inn1_score', $data['teamBInn1Score']);
      }  

      if (array_key_exists('teamBInn1Wickets', $data)) {
        $builder->set('teamb_inn1_wickets', $data['teamBInn1Wickets']);
      }

      if (array_key_exists('teamBInn1Overs', $data)) {
        $builder->set('teamb_inn1_overs', $data['teamBInn1Overs']);
      }      

      if (array_key_exists('teamBInn2', $data)) {
        if ($data['teamBInn2'] == '1') {
          $builder->set('teamb_inn2_score_card', $data['teamBInn2']);
        }
      }
    
      if (array_key_exists('teamBInn2Score', $data)) {
        $builder->set('teamb_inn2_score', $data['teamBInn2Score']);
      }

      if (array_key_exists('teamBInn2Wickets', $data)) {
        $builder->set('teamb_inn2_wickets', $data['teamBInn2Wickets']);
      }

      if (array_key_exists('teamBInn2Overs', $data)) {
        $builder->set('teamb_inn2_overs', $data['teamBInn2Overs']);
      }

      if (array_key_exists('matchStatus', $data)) {
        $builder->set('match_status', '2');
      }

      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));
      
      $builder->where('id', $data['lookId']);
      return $builder->update();
    }


    //- Get Completed Matches
    public function getCompletedMatches()
    {

      $builder = $this->db->table($this->matchesTbl);
      
      $builder->select('id as id');
      $builder->select('match_id as matchId');
      $builder->select('cricks_id as cricksId');
      $builder->select('crickm_id as crickmId');
      $builder->select('teama_key as teamaKey');
      $builder->select('teamb_key as teambKey');
      $builder->select('teama_id as teamaId');  
      $builder->select('teamb_id as teambId');  
      $builder->select('utc_starttime as startTime');
      $builder->select('utc_endtime as endTime');
      $builder->select('match_type as matchType');

      $builder->select('teama_inn1_score_card as teamAInn1');
      $builder->select('teama_inn1_score as teamAScore');
      $builder->select('teama_inn1_wickets as teamAWick');
      $builder->select('teama_inn1_overs as teamAOvers');
      $builder->select('teamb_inn1_score_card as teamBInn1');
      $builder->select('teamb_inn1_score as teamBScore');
      $builder->select('teamb_inn1_wickets as teamBWick');
      $builder->select('teamb_inn1_overs as teamBOvers');  

      $builder->whereIn('match_status',['2','3']);
      $builder->whereIn('match_type',['1','2']);
      $builder->orderBy('id', 'DESC');
      $builder->limit(5);
      
      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }


    //- Get Completed Matches
    public function getRunningMatches()
    {

      $builder = $this->db->table($this->matchesTbl);
      
      $builder->select('id as id');
      $builder->select('match_id as matchId');
      $builder->select('cricks_id as cricksId');
      $builder->select('crickm_id as crickmId');
      $builder->select('teama_key as teamaKey');
      $builder->select('teamb_key as teambKey');
      $builder->select('teama_id as teamaId');  
      $builder->select('teamb_id as teambId');  
      $builder->select('utc_starttime as startTime');
      $builder->select('utc_endtime as endTime');
      $builder->select('match_type as matchType');

      $builder->select('teama_inn1_score_card as teamAInn1');
      $builder->select('teama_inn1_score as teamAScore');
      $builder->select('teama_inn1_wickets as teamAWick');
      $builder->select('teama_inn1_overs as teamAOvers');
      $builder->select('teamb_inn1_score_card as teamBInn1');
      $builder->select('teamb_inn1_score as teamBScore');
      $builder->select('teamb_inn1_wickets as teamBWick');
      $builder->select('teamb_inn1_overs as teamBOvers');  

      $builder->whereIn('match_status',['1']);
      $builder->whereIn('match_type',['1','2']);
      $builder->orderBy('id', 'DESC');
      
      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }


    //- Check Matches Lookup
    public function getMatchById($matchId)
    {
      
      $builder = $this->db->table($this->matchesTbl);
      
      $builder->select('match_id as matchId');
      $builder->select('cricks_id as cricksId');
      $builder->select('crickm_id as crickmId');
      $builder->select('teama_key as teamaKey');
      $builder->select('teamb_key as teambKey');
      $builder->select('teama_id as teamaId');  
      $builder->select('teamb_id as teambId');  
      $builder->select('utc_starttime as startTime');
      $builder->select('utc_endtime as endTime'); 

      $builder->select('teama_inn1_score_card as teamAInn1');
      $builder->select('teama_inn1_score as teamAInn1Score');
      $builder->select('teama_inn1_wickets as teamAInn1Wick');
      $builder->select('teama_inn1_overs as teamAInn1Overs');
      
      $builder->select('teama_inn2_score_card as teamAInn2');
      $builder->select('teama_inn2_score as teamAInn2Score');
      $builder->select('teama_inn2_wickets as teamAInn2Wick');
      $builder->select('teama_inn2_overs as teamAInn2Overs');

      $builder->select('teamb_inn1_score_card as teamBInn1');
      $builder->select('teamb_inn1_score as teamBInn1Score');
      $builder->select('teamb_inn1_wickets as teamBInn1Wick');
      $builder->select('teamb_inn1_overs as teamBInn1Overs');

      $builder->select('teamb_inn2_score_card as teamBInn2');
      $builder->select('teamb_inn2_score as teamBInn2Score');
      $builder->select('teamb_inn2_wickets as teamBInn2Wick');
      $builder->select('teamb_inn2_overs as teamBInn2Overs');

      $builder->where('id', $matchId);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }


    //- Get Matches Delayed Toss  
    public function getBoutToStartMatches()
    {

      $builder = $this->db->table($this->matchesTbl);
      
      $builder->select('id as id');
      $builder->select('match_id as matchId');
      $builder->select('cricks_id as cricksId');
      $builder->select('crickm_id as crickmId');
      $builder->select('teama_key as teamaKey');
      $builder->select('teamb_key as teambKey');
      $builder->select('teama_id as teamaId');  
      $builder->select('teamb_id as teambId');  
      $builder->select('utc_starttime as startTime');
      $builder->select('utc_endtime as endTime'); 
      
      $builder->where('toss_status', '1');
      $builder->where('player_list', '0');
      $builder->where('match_status !=', '4');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }


    //- Update Final Players
    public function updateFinalPlayers($matchLookUpId)
    {

      $builder = $this->db->table($this->matchesTbl);

      $builder->set('player_list', '1');
      $builder->set('player_list_time', gmdate("Y-m-d H:i:s"));
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $matchLookUpId);
      return $builder->update();

    }


    //- Get Matches Delayed Toss  
    public function getAllFinalDraftMatches()
    {

      $builder = $this->db->table($this->matchesTbl);
      
      $builder->select('id as id');
      $builder->select('match_id as matchId');
      $builder->select('cricks_id as cricksId');
      $builder->select('crickm_id as crickmId');
      $builder->select('teama_key as teamaKey');
      $builder->select('teamb_key as teambKey');
      $builder->select('teama_id as teamaId');  
      $builder->select('teamb_id as teambId');  
      $builder->select('utc_starttime as startTime');
      $builder->select('utc_endtime as endTime'); 
      
      $builder->where('toss_status', '1');
      $builder->where('player_list', '1');
      $builder->where('final_drafts', '0');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }

    //- Update Final Decision
    public function updateFinalDraft($matchLookUpId)
    {

      $builder = $this->db->table($this->matchesTbl);

      $builder->set('final_drafts', '1');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $matchLookUpId);
      return $builder->update();

    }


    public function getFinishedMatches()
    {

      $builder = $this->db->table($this->matchesTbl);
      
      $builder->select('id as id');
      $builder->select('match_id as matchId');
      $builder->select('cricks_id as cricksId');
      $builder->select('crickm_id as crickmId');
      $builder->select('teama_key as teamaKey');
      $builder->select('teamb_key as teambKey');
      $builder->select('teama_id as teamaId');  
      $builder->select('teamb_id as teambId');  
      $builder->select('utc_starttime as startTime');
      $builder->select('utc_endtime as endTime');
      $builder->select('match_type as matchType');

      $builder->select('teama_inn1_score_card as teamAInn1');
      $builder->select('teama_inn1_score as teamAScore');
      $builder->select('teama_inn1_wickets as teamAWick');
      $builder->select('teama_inn1_overs as teamAOvers');
      $builder->select('teamb_inn1_score_card as teamBInn1');
      $builder->select('teamb_inn1_score as teamBScore');
      $builder->select('teamb_inn1_wickets as teamBWick');
      $builder->select('teamb_inn1_overs as teamBOvers');  
      
      $builder->where('match_status', '2');
      
      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }


    //- Update Match Comaplete Status
    public function updateCompleteStatus($matchLookUpId)
    {

      $builder = $this->db->table($this->matchesTbl);

      $builder->set('match_status', '3');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $matchLookUpId);
      return $builder->update();

    }


    public function updateCloseStatus($matchLookUpId)
    {
      $builder = $this->db->table($this->matchesTbl);

      $builder->set('match_status', '4');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $matchLookUpId);
      return $builder->update();
    }


    public function updateNotifyStatus($matchLookUpId)
    {
      $builder = $this->db->table($this->matchesTbl);

      $builder->set('notifi_send', '1');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $matchLookUpId);
      return $builder->update();
    }


    //- TODO Need to be removed
    public function updateMatchTime($matchId,$date)
    {

      $builder = $this->db->table($this->matchesTbl);

      $builder->set('utc_starttime', $date);
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('match_id', $matchId);
      return $builder->update();

    }

}
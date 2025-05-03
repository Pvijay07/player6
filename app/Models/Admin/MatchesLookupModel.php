<?php

namespace App\Models\Admin;
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



    //- Check Matches Lookup
    public function checkMatchLookup($matchId)
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

      $builder->select('toss_winner as tossWinner');	
      $builder->select('toss_status as tossStatus');
      $builder->select('toss_decision as tossDecision');
      $builder->select('player_list as player11');
      $builder->select('player_list_time as player11ST');
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
      
      $builder->where('match_id', $matchId);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }


    public function updateCloseStatus($matchLookUpId)
    {
      $builder = $this->db->table($this->matchesTbl);

      $builder->set('match_status', '4');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $matchLookUpId);
      return $builder->update();
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


    public function closeMatch($matchId)
    {
      $builder = $this->db->table($this->matchesTbl);

      $builder->set('match_status', '4');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('match_id', $matchId);
      return $builder->update();
    }


    //- Get Total Complete Matches
    public function getTotalCompletedMatch($dur)
    {
      
      $builder = $this->db->table($this->matchesTbl);
      
      $builder->select('id');

      $builder->where('match_status', '2');
      $builder->orWhere('match_status', '3');
      if (isset($dur['fromdate']) && isset($dur['todate'])) {
        $builder->where('date_created >=', $dur['fromdate']);
        $builder->where('date_created <=', $dur['todate']);
      }

      $query = $builder->get();
      return $query->getNumRows();
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
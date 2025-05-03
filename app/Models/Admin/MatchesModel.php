<?php

namespace App\Models\Admin;
use CodeIgniter\Model;

class MatchesModel extends Model
{

    protected $db;
    private string $matchesTbl;
    public function __construct() {
      $this->db = \Config\Database::connect();
      $this->matchesTbl = 'pl6_matches';
    }


    public function getOnlyOnGoingSeries($startTime,$endTime)
    {

      $builder = $this->db->table($this->matchesTbl);

      $builder->select('series_id as seriesId');
      $builder->distinct();
      
      $builder->where('toss_winner', '0');
      $builder->where('utc_starttime >=', $startTime);
      $builder->where('utc_starttime <', $endTime);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;      
    
    }
    

    public function getOnlyOnGoingMatches($startTime,$endTime,$seriesId)
    {
      $builder = $this->db->table($this->matchesTbl);
      
      $builder->select('id as matchId');  
      $builder->select('series_id as seriesId');
      $builder->select('cricks_id as cricksId');
      $builder->select('crickm_id as crickmId');
      $builder->select('teama_key as teamaKey');
      $builder->select('teamb_key as teambKey');
      $builder->select('title as title');
      $builder->select('match_type as matchType');
      $builder->select('teama_id as teamaId');  
      $builder->select('teamb_id as teambId');
      $builder->select('has_players as hasPlayers');
      $builder->select('utc_starttime as startTime');
      $builder->select('utc_endtime as endTime'); 
      $builder->select('name as name');
      $builder->select('season_id as seasonId');  
      $builder->select('status as status');  
    	
      //- $builder->where('toss_winner', '0');
      $builder->where('utc_starttime >=', $startTime);
      $builder->where('utc_starttime <', $endTime);
      if (!empty($seriesId)) {
        $builder->where('series_id', $seriesId);
      }
      $builder->orderBy('utc_starttime', 'ASC');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    } 


    public function getOnlyRunningMatches($seriesId)
    {
      $builder = $this->db->table($this->matchesTbl);
      
      $builder->select('pl6_matches.id as matchId');  
      $builder->select('pl6_matches.series_id as seriesId');
      $builder->select('pl6_matches.cricks_id as cricksId');
      $builder->select('pl6_matches.crickm_id as crickmId');
      $builder->select('pl6_matches.teama_key as teamaKey');
      $builder->select('pl6_matches.teamb_key as teambKey');
      $builder->select('pl6_matches.title as title');
      $builder->select('pl6_matches.match_type as matchType');
      $builder->select('pl6_matches.teama_id as teamaId');  
      $builder->select('pl6_matches.teamb_id as teambId');
      $builder->select('pl6_matches.has_players as hasPlayers');
      $builder->select('pl6_matches.utc_starttime as startTime');
      $builder->select('pl6_matches.utc_endtime as endTime'); 
      $builder->select('pl6_matches.name as name');
      $builder->select('pl6_matches.season_id as seasonId');  
      $builder->select('pl6_matches.status as status');  
      
      
      $builder->join('pl6_matches_lookup', 'pl6_matches.id = pl6_matches_lookup.match_id');
      $builder->whereIn('pl6_matches_lookup.match_status', ['1','2']);
      if (!empty($seriesId)) {
        $builder->where('pl6_matches.series_id', $seriesId);
      }
      $builder->orderBy('pl6_matches.utc_starttime', 'ASC');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }     


    //- Get Match By Id
    public function getMatcheById($matchId)
    {
      $builder = $this->db->table($this->matchesTbl);
      
      $builder->select('id as matchId');  
      $builder->select('series_id as seriesId');
      $builder->select('cricks_id as cricksId');
      $builder->select('crickm_id as crickmId');
      $builder->select('teama_key as teamaKey');
      $builder->select('teamb_key as teambKey');
      $builder->select('title as title');
      $builder->select('name as name');
      $builder->select('season_id as seasonId');      
      $builder->select('match_type as matchType');
      $builder->select('teama_id as teamaId');
      $builder->select('teamb_id as teambId');  
      $builder->select('utc_starttime as startTime');
      $builder->select('utc_endtime as endTime'); 
      $builder->select('toss_winner as tossWinner');
    
      $builder->where('id', $matchId);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }

    //- Update Toss Decision
    public function updateTossDecision($data){

      $builder = $this->db->table($this->matchesTbl);

      $builder->set('toss_winner', $data['winningTeamId']);
      $builder->set('toss_decision', $data['tossDecision']);
      $builder->set('toss_time', $data['tossTime']);
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $data['matchId']);
      return $builder->update();

    }


    //- Suspend Match
    public function suspendMatch($matchId)
    {
      $builder = $this->db->table($this->matchesTbl);

      $builder->set('status', '0');

      $builder->where('id', $matchId);
      return $builder->update();
    }    
    

    public function getAllMatches($dur)
    {
      $builder = $this->db->table($this->matchesTbl);
      
      $builder->select('id as matchId');  
      $builder->select('series_id as seriesId');
      $builder->select('cricks_id as cricksId');
      $builder->select('crickm_id as crickmId');
      $builder->select('teama_key as teamaKey');
      $builder->select('teamb_key as teambKey');
      $builder->select('title as title');
      $builder->select('match_type as matchType');
      $builder->select('teama_id as teamaId');  
      $builder->select('teamb_id as teambId');
      $builder->select('has_players as hasPlayers');
      $builder->select('utc_starttime as startTime');
      $builder->select('utc_endtime as endTime'); 
      $builder->select('name as name');
      $builder->select('season_id as seasonId');  
      $builder->select('status as status');  
      
      if (isset($dur['fromdate']) && isset($dur['todate'])) {
        $builder->where('utc_starttime >=', $dur['fromdate'] );
        $builder->where('utc_starttime <=', $dur['todate']);
      } 

      $builder->orderBy('utc_starttime', 'DESC');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    } 


    //- TODO Need to be removed
    public function updateMatchTime($matchId,$date)
    {

      $builder = $this->db->table($this->matchesTbl);

      $builder->set('utc_starttime', $date);
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $matchId);
      return $builder->update();

    }


}
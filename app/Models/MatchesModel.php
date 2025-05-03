<?php

namespace App\Models;
use CodeIgniter\Model;

class MatchesModel extends Model
{

    protected $db;
    private string $matchesTbl;
    public function __construct() {
      $this->db = \Config\Database::connect();
      $this->matchesTbl = 'pl6_matches';
    }


    //- Create Matches
    public function createMatch($data)
    {

      $builder = $this->db->table($this->matchesTbl);

      $builder->set('series_id', $data['seriesId']);
      $builder->set('cricks_id', $data['cricksId']);
      $builder->set('crickm_id', $data['crickmId']);
      $builder->set('teama_key', $data['teamaKey']);
      $builder->set('teamb_key', $data['teambKey']);
      $builder->set('title', $data['title']);
      $builder->set('teama_id', $data['teamaId']);
      $builder->set('teamb_id', $data['teambId']);
      $builder->set('utc_starttime', $data['starttime']);
      $builder->set('utc_endtime', $data['endtime']);
      $builder->set('status', $data['status']);
      $builder->set('match_type', $data['matchType']);
      $builder->set('name', $data['name']);
      $builder->set('season_id', $data['seasonId']);

      $builder->set('date_created', gmdate("Y-m-d H:i:s"));
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      return $builder->insert();

    }


    //- Update Match Details
    public function updateMatch($matchId,$data)
    {

      $builder = $this->db->table($this->matchesTbl);  

      if (!empty($data['teamaKey'])) {
        $builder->set('teama_key', $data['teamaKey']);
      }

      if (!empty($data['teambKey'])) {
        $builder->set('teamb_key', $data['teambKey']);
      }

      if (!empty($data['title'])) {
        $builder->set('title', $data['title']);
      }

      if (!empty($data['teamaId'])) {
        $builder->set('teama_id', $data['teamaId']);
      }

      if (!empty($data['teambId'])) {
        $builder->set('teamb_id', $data['teambId']);
      }      

      if (!empty($data['starttime'])) {
        $builder->set('utc_starttime', $data['starttime']);
      }

      if (!empty($data['endtime'])) {
        $builder->set('utc_endtime', $data['endtime']);
      }

      if (!empty($data['name'])) {
        $builder->set('name', $data['name']);
      } 

      if (!empty($data['seasonId'])) {
        $builder->set('season_id', $data['seasonId']);
      } 

      if (!empty($data['matchType'])) {
        $builder->set('match_type', $data['matchType']);
      }                       

      $builder->where('id', $matchId);
      return $builder->update();
    }


    //- Check Match Exist Are Not Using Cricbuzz Team Key
    public function checkMatchExist($matchkey)
    {
      
      $builder = $this->db->table($this->matchesTbl);
      
      $builder->select('id as id');
      $builder->select('series_id as seriesId');
      $builder->select('cricks_id as cricksId');
      $builder->select('crickm_id as crickmId');
      $builder->select('teama_key as teamaKey');
      $builder->select('teamb_key as teambKey');
      $builder->select('title as title');
      $builder->select('teama_id as teamaId');	
      $builder->select('teamb_id as teambId');	
      $builder->select('utc_starttime as startTime');
      $builder->select('utc_endtime as endTime');	
      $builder->select('toss_time as tossTime');	
      $builder->select('status as status');
      $builder->select('name as name');
      $builder->select('season_id as seasonId');
      $builder->select('match_type as matchType');

      $builder->where('crickm_id', $matchkey);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    //- Show Matches From Active Series, Active Matches, Today, Tommorow, Dayafter Tommorow, Should Be befour 30 MIN
    public function getOnlyOnGoingMatches($startTime,$endTime)
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
    
      $builder->where('status', '1');
      $builder->where('toss_winner', '0');
      $builder->where('utc_starttime >=', $startTime);
      $builder->where('utc_starttime <', $endTime);
      $builder->orderBy('utc_starttime', 'ASC');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function getOnGoingMatches($startTime,$endTime)
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
      $builder->select('has_players as hasPlayers');
      $builder->select('utc_starttime as startTime');
      $builder->select('utc_endtime as endTime');
    
      $builder->where('status', '1');
      $builder->where('utc_starttime >=', $startTime);
      $builder->where('utc_starttime <', $endTime);
      $builder->orderBy('utc_starttime', 'ASC');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    //- Get Matches With No Players
    public function getNoPlayerMatches($startTime,$endTime)
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
      $builder->select('teama_id as teamaId');  
      $builder->select('teamb_id as teambId');  
      $builder->select('utc_starttime as startTime');
      $builder->select('utc_endtime as endTime'); 
    
      $builder->where('has_players', '0');
      $builder->where('utc_starttime >=', $startTime);
      $builder->where('utc_starttime <=', $endTime); 
      $builder->orderBy('utc_starttime', 'ASC');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;      
    }


    //- Update Match Has Palayers 
    public function updateMatchHasPlayers($matchId)
    {

      $builder = $this->db->table($this->matchesTbl);

      $builder->set('has_players', '1');

      $builder->where('id', $matchId);
      return $builder->update();
    }


    //- Check On Going Matche Exist
    public function checkOnGoingMatche($matchId,$startTime,$endTime)
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
      $builder->select('teama_id as teamaId');  
      $builder->select('teamb_id as teambId');  
      $builder->select('utc_starttime as startTime');
      $builder->select('utc_endtime as endTime'); 
      $builder->select('toss_winner as tossWinner');
    
      $builder->where('status', '1');
      $builder->where('utc_starttime >=', $startTime);
      $builder->where('utc_starttime <=', $endTime);
      $builder->where('id', $matchId);
      $builder->limit(1);

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


    //- Get All TBC Matches
    public function getTBCMatches($startTime,$endTime){

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
      $builder->select('teama_id as teamaId');  
      $builder->select('teamb_id as teambId');  
      $builder->select('utc_starttime as startTime');
      $builder->select('utc_endtime as endTime'); 
    
      $builder->where('utc_starttime >=', $startTime);
      $builder->where('utc_starttime <=', $endTime); 
      $builder->groupStart();
      $builder->where('teama_key', '106');
      $builder->orWhere('teamb_key', '106');
      $builder->groupEnd();      
     
      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }


    //- Update Match Has Palayers 
    public function updateMatchTeams($matchId,$data)
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

      $builder->where('id', $matchId);
      return $builder->update();
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


    public function getLastMatchExist($matchkey)
    {
      
      $builder = $this->db->table($this->matchesTbl);
      
      $builder->select('id as id');
      $builder->select('series_id as seriesId');
      $builder->select('cricks_id as cricksId');
      $builder->select('crickm_id as crickmId');
      $builder->select('teama_key as teamaKey');
      $builder->select('teamb_key as teambKey');
      $builder->select('title as title');
      $builder->select('teama_id as teamaId');  
      $builder->select('teamb_id as teambId');  
      $builder->select('utc_starttime as startTime');
      $builder->select('utc_endtime as endTime'); 
      $builder->select('toss_time as tossTime');  
      $builder->select('status as status');
      $builder->select('name as name');
      $builder->select('season_id as seasonId');
      $builder->select('match_type as matchType');

      $builder->where('crickm_id', $matchkey);
      $builder->orderBy('id', 'DESC');
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }

}
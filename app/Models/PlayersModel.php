<?php

namespace App\Models;
use CodeIgniter\Model;

class PlayersModel extends Model
{

    protected $db;
    private string $playerTbl;
    private string $playStatsTbl;
    private string $mPlayTbl;
    public function __construct() {
      $this->db = \Config\Database::connect();
      $this->playerTbl = 'pl6_player_details';
      $this->playStatsTbl = 'pl6_player_stats';
      $this->mPlayTbl = 'pl6_match_players';
    }


    //- Check Player Exist Are Not Using Cricbuzz Player Id
    public function checkPlayerExist($playerkey)
    {
      
      $builder = $this->db->table($this->playerTbl);
      
      $builder->select('id as playerId');
      $builder->select('crick_id as crickpId');
      $builder->select('nickName as nickName');
      $builder->select('name as name');
      $builder->select('faceImageId as faceImageId');
      $builder->select('role as role');
      $builder->select('batting_style as battingStyle');	
      $builder->select('bowling_style as bowlingStyle');

      $builder->where('crick_id', $playerkey);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }    


    //- Create Player
    public function createPlayer($data)
    {

      $builder = $this->db->table($this->playerTbl);

      $builder->set('crick_id', $data['playerKey']);
      $builder->set('nickName', $data['nickName']);
      $builder->set('name', $data['name']);
      $builder->set('faceImageId', $data['faceImageId']);
      $builder->set('role', $data['role']);
      $builder->set('batting_style', $data['battingStyle']);
      $builder->set('bowling_style', $data['bowlingStyle']);

      $builder->set('date_created', gmdate("Y-m-d H:i:s"));
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      return $builder->insert();

    }


    //- Create Player Stats
    public function createPlayerStats($playerId,$formatType,$data)
    {

      $builder = $this->db->table($this->playStatsTbl);

      $builder->set('player_id', $playerId);
      $builder->set('format_type', $formatType);
      $builder->set('matches', $data['matches']);
      $builder->set('inns', $data['innings']);
      $builder->set('runs', $data['runs']);
      $builder->set('hs', $data['highest']);
      $builder->set('avg', $data['average']);

      $builder->set('date_created', gmdate("Y-m-d H:i:s"));
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      return $builder->insert();

    }


    public function updatePlayerStats($playerId,$formatType,$data)
    {

      $builder = $this->db->table($this->playStatsTbl);

      $builder->set('matches', $data['matches']);
      $builder->set('inns', $data['innings']);
      $builder->set('runs', $data['runs']);
      $builder->set('hs', $data['highest']);
      $builder->set('avg', $data['average']);

      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('player_id', $playerId);
      $builder->where('format_type', $formatType);
      return $builder->update();

    }

    //- Create Match Players
    public function createMatchPlayer($data){

      $builder = $this->db->table($this->mPlayTbl);

      $builder->set('match_id', $data['matchId']);
      $builder->set('player_id', $data['playerId']);
      $builder->set('team_id', $data['teamId']);
      $builder->set('is_bench', '1');
      $builder->set('order_id', $data['orderId']);
      
      $builder->set('date_created', gmdate("Y-m-d H:i:s"));
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      return $builder->insert();    	
    }


    //- Create Final Match Players
    public function createFinalMatchPlayer($data){

      $builder = $this->db->table($this->mPlayTbl);

      $builder->set('match_id', $data['matchId']);
      $builder->set('player_id', $data['playerId']);
      $builder->set('team_id', $data['teamId']);
      $builder->set('order_id', $data['orderId']);
      $builder->set('is_bench', '0');
      
      $builder->set('date_created', gmdate("Y-m-d H:i:s"));
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      return $builder->insert();      
    }


    //- Check Player Exist Are Not 
    public function createMatchPlayerExist($matchId,$playerId,$teamId)
    {
      
      $builder = $this->db->table($this->mPlayTbl);
      
      $builder->select('id as mpId');
      $builder->select('match_id as matchId');
      $builder->select('player_id as playerId');
      $builder->select('team_id as teamId');
  
      $builder->where('match_id', $matchId);
      $builder->where('player_id', $playerId);
      $builder->where('team_id', $teamId);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }    


    //- Get Single Team Players
    public function getTeamPlayes($matchId,$teamId)
    {
      
      $builder = $this->db->table($this->mPlayTbl);
      
      $builder->select('id as mpId');
      $builder->select('match_id as matchId');
      $builder->select('team_id as teamId');
      $builder->select('player_id as playerId');
      $builder->select('order_id as orderId');
  
      $builder->where('match_id', $matchId);
      $builder->where('team_id', $teamId);
    
      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    //- Get Player By Id
    public function getPlayerById($playerId)
    {
      
      $builder = $this->db->table($this->playerTbl);
      
      $builder->select('id as playerId');
      $builder->select('crick_id as crickpId');
      $builder->select('nickName as nickName');
      $builder->select('name as name');
      $builder->select('faceImageId as faceImageId');
      $builder->select('role as role');
      $builder->select('batting_style as battingStyle');  
      $builder->select('bowling_style as bowlingStyle');

      $builder->where('id', $playerId);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }  


    //- Get Player Stats
    public function getPlayerStats($playerId)
    {

      $builder = $this->db->table($this->playStatsTbl);
      
      $builder->select('format_type as formatType');
      $builder->select('matches as matches');
      $builder->select('inns as inns');
      $builder->select('runs as runs');
      $builder->select('hs as hs');
      $builder->select('avg as avg');
  
      $builder->where('player_id', $playerId);
    
      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    //- Get Match Player
    public function getMatchPlayer($matchId,$playerId)
    {
      
      $builder = $this->db->table($this->mPlayTbl);
      
      $builder->select('id as mpId');
      $builder->select('match_id as matchId');
      $builder->select('player_id as playerId');
      $builder->select('team_id as teamId');
  
      $builder->where('match_id', $matchId);
      $builder->where('player_id', $playerId);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    //- Get All Match Players
    public function getAllMatchPlayers($matchId)
    {
      
      $builder = $this->db->table($this->mPlayTbl);
      
      $builder->select('id as mpId');
      $builder->select('match_id as matchId');
      $builder->select('player_id as playerId');
      $builder->select('team_id as teamId');
  
      $builder->where('match_id', $matchId);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function getPlayersDetails($playerId,$formatType){
      $builder = $this->db->table($this->playerTbl);
      
      $builder->select('pl6_player_details.id as playerId');
      $builder->select('pl6_player_details.crick_id as crickpId');
      $builder->select('pl6_player_details.nickName as nickName');
      $builder->select('pl6_player_details.name as name');
      $builder->select('pl6_player_details.faceImageId as faceImageId');
      $builder->select('pl6_player_stats.avg as avg');
      $builder->select('pl6_player_stats.hs as hs');

      $builder->join($this->playStatsTbl, 'pl6_player_stats.player_id = pl6_player_details.id', 'left');
      $builder->where('pl6_player_details.id', $playerId);
      $builder->where('pl6_player_stats.format_type', $formatType);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }

    //- Update As Playing 11
    public function updateAsPlayer11($matchId,$teamId,$playerId)
    {

      $builder = $this->db->table($this->mPlayTbl);

      $builder->set('is_bench', '0');

      $builder->where('match_id', $matchId);
      $builder->where('team_id', $teamId);
      $builder->where('player_id', $playerId);

      return $builder->update();
    }


    public function getTeamAllPlaye11($matchId,$teamId)
    {
      
      $builder = $this->db->table($this->mPlayTbl);
      
      $builder->select('id as mpId');
      $builder->select('match_id as matchId');
      $builder->select('team_id as teamId');
      $builder->select('player_id as playerId');
      $builder->select('is_bench as isBench');
      $builder->select('order_id as orderId');
  
      $builder->where('match_id', $matchId);
      $builder->where('team_id', $teamId);
      $builder->orderBy('is_bench', 'ASC');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function getPlayersDetailsNoScore($playerId){
      $builder = $this->db->table($this->playerTbl);
      
      $builder->select('pl6_player_details.id as playerId');
      $builder->select('pl6_player_details.crick_id as crickpId');
      $builder->select('pl6_player_details.nickName as nickName');
      $builder->select('pl6_player_details.name as name');
      $builder->select('pl6_player_details.faceImageId as faceImageId');
      $builder->select('pl6_player_stats.avg as avg');
      $builder->select('pl6_player_stats.hs as hs');

      $builder->join($this->playStatsTbl, 'pl6_player_stats.player_id = pl6_player_details.id', 'left');
      $builder->where('pl6_player_details.id', $playerId);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }

}
<?php

namespace App\Models\Admin;
use CodeIgniter\Model;

class PlayersModel extends Model
{

    protected $db;
    private string $playerTbl;
    private string $mPlayTbl;
    public function __construct() {
      $this->db = \Config\Database::connect();
      $this->playerTbl = 'pl6_player_details';
      $this->mPlayTbl = 'pl6_match_players';
    }


    //- Get Single Team Players
    public function getTeamPlayes($matchId,$teamId)
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
      $builder->orderBy('order_id', 'ASC');
    
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

    //- Update As Playing 11
    public function updateAsBanchPl($matchId,$teamId,$playerId)
    {

      $builder = $this->db->table($this->mPlayTbl);

      $builder->set('is_bench', '1');

      $builder->where('match_id', $matchId);
      $builder->where('team_id', $teamId);
      $builder->where('player_id', $playerId);

      return $builder->update();
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

    //- Get Single Team Players
    public function getTeamPlayes11($matchId,$teamId)
    {
      
      $builder = $this->db->table($this->mPlayTbl);
      
      $builder->select('id as mpId');
      $builder->select('match_id as matchId');
      $builder->select('team_id as teamId');
      $builder->select('player_id as playerId');

      $builder->select('is_bench as isBench');
      $builder->select('order_id as orderId');
    
      $builder->where('is_bench', '0');
      $builder->where('match_id', $matchId);
      $builder->where('team_id', $teamId);
      $builder->orderBy('order_id', 'ASC');
    
      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }
}


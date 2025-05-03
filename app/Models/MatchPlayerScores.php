<?php

namespace App\Models;
use CodeIgniter\Model;

class MatchPlayerScores extends Model
{

    protected $db;
    private string $playScoresTbl;
    public function __construct() {
      $this->db = \Config\Database::connect();
      $this->playScoresTbl = 'pl6_match_player_scores';
    }


    //- Create Player Scores
    public function createPlayerScore($data)
    {

      $builder = $this->db->table($this->playScoresTbl);

      $builder->set('match_id', $data['matchId']);
      $builder->set('team_id', $data['teamId']);
      $builder->set('innings_id', $data['inningsId']);
      $builder->set('player_id', $data['playerId']);
      $builder->set('player_key', $data['playerKey']);
      $builder->set('runs', $data['runs']);
      $builder->set('balls', $data['balls']);
      $builder->set('fours', $data['fours']);
      $builder->set('sixes', $data['sixes']);
      $builder->set('strikeRate', $data['strikeRate']);
      $builder->set('outDesc', $data['outDesc']);
      $builder->set('status', $data['status']);
      $builder->set('player_order', $data['orderId']);

      $builder->set('date_created', gmdate("Y-m-d H:i:s"));
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      return $builder->insert();
    }


    //- Check Player Scores
    public function checkPlayerScore($matchId,$teamId,$inningsId,$playerKey)
    {
      $builder = $this->db->table($this->playScoresTbl);
      
      $builder->select('id as id');
      $builder->select('match_id as matchId');
      $builder->select('team_id as teamId');
      $builder->select('innings_id as inningsId');
      $builder->select('player_id as playerId');
      $builder->select('runs as runs');
      $builder->select('balls as balls');
      $builder->select('fours as fours');
      $builder->select('sixes as sixes');
      $builder->select('strikeRate as strikeRate');
      $builder->select('outDesc as outDesc');
      $builder->select('player_key as playerKey');
      $builder->select('status as status');

      $builder->where('match_id', $matchId);
      $builder->where('team_id', $teamId);
      $builder->where('innings_id', $inningsId);
      $builder->where('player_key', $playerKey);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;      
    }


    //- Update Player Scores
    public function updatePlayerScore($recordId,$data)
    {

      $builder = $this->db->table($this->playScoresTbl);

      $builder->set('runs', $data['runs']);
      $builder->set('balls', $data['balls']);
      $builder->set('fours', $data['fours']);
      $builder->set('sixes', $data['sixes']);
      $builder->set('strikeRate', $data['strikeRate']);
      $builder->set('outDesc', $data['outDesc']);
      $builder->set('status', $data['status']);
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $recordId);
      return $builder->update();
    }


    //- All Match Innings
    public function getMatchInnings($matchId)
    {

      $builder = $this->db->table($this->playScoresTbl);
      
      $builder->select('innings_id as inningsId');
      $builder->select('team_id as teamId');

      $builder->where('match_id', $matchId);
      $builder->distinct('innings_id as inningsId');
      $builder->orderBy('innings_id', 'DESC');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }
    

    //- All Match Innings
    public function getMatchInningScores($matchId,$innings)
    {

      $builder = $this->db->table($this->playScoresTbl);
      
      $builder->select('id as id');
      $builder->select('match_id as matchId');
      $builder->select('team_id as teamId');
      $builder->select('innings_id as inningsId');
      $builder->select('player_id as playerId');
      $builder->select('runs as runs');
      $builder->select('balls as balls');
      $builder->select('fours as fours');
      $builder->select('sixes as sixes');
      $builder->select('strikeRate as strikeRate');
      $builder->select('outDesc as outDesc');
      $builder->select('player_key as playerKey');
      $builder->select('player_order as playerOrder');
      $builder->select('status as status');
      
      $builder->where('match_id', $matchId);
      $builder->where('innings_id', $innings);
      $builder->orderBy('player_order', 'ASC');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    //- All Match Innings
    public function getMatchPlayerTotalScores($matchId,$playerId)
    {

      $builder = $this->db->table($this->playScoresTbl);
      
      $builder->selectSum('runs');
      
      $builder->where('match_id', $matchId);
      $builder->where('player_id', $playerId);
      $builder->limit(1);
      
      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }

    //- Update Player Status
    public function updateOldPlayerScore($data)
    {

      $builder = $this->db->table($this->playScoresTbl);

      $builder->set('outDesc', 'Not out');
      $builder->set('status', '1');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('match_id', $data['matchId']);
      $builder->where('team_id', $data['teamId']);
      $builder->where('innings_id', $data['innings']);
      $builder->where('status', '0');
      return $builder->update();
    }

}
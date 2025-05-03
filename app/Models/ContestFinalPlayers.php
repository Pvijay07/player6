<?php

namespace App\Models;
use CodeIgniter\Model;

class ContestFinalPlayers extends Model
{

    protected $db;
    private string $errorTbl;
    public function __construct() {
      $this->db = \Config\Database::connect();
      $this->finPlayTbl = 'pl6_match_contest_final_select';
    }


    //- Create Final Players
    public function createFinalPlayers($data){

      $builder = $this->db->table($this->finPlayTbl);

      $builder->set('match_id', $data['matchId']);
      $builder->set('contest_id', $data['contestId']);
      $builder->set('user_id', $data['userId']);
      $builder->set('player_id', $data['playerId']);
      $builder->set('player_order', $data['order']);
      if (array_key_exists('endTime', $data)) {
        $builder->set('end_time', $data['endTime']);
      }
      if (array_key_exists('startTime', $data)) {
        $builder->set('start_time', $data['startTime']);
      }
      $builder->set('date_created', gmdate("Y-m-d H:i:s"));
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      return $builder->insert();

    }


    //- Get Final Players
    public function getFinalGamePlayers($matchId,$contestId,$userId){
      $builder = $this->db->table($this->finPlayTbl);
      
      $builder->select('id as fpId');
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('user_id as userId');
      $builder->select('player_id as playerId');
      $builder->select('player_order as order');
      $builder->select('status as status');
      $builder->select('is_cap as isCap');
      $builder->select('is_vcap as isvCap');
      $builder->select('end_time as endTime');
      
      $builder->where('match_id', $matchId);
      $builder->where('contest_id', $contestId);
      $builder->where('user_id', $userId);
      $builder->orderBy('player_order', 'ASC');
      $builder->limit(6);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }  


    //- Get Final Players
    public function checkPlayerExist($matchId,$contestId,$userId,$playerId){
      $builder = $this->db->table($this->finPlayTbl);
      
      $builder->select('id as fpId');
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('user_id as userId');
      $builder->select('player_id as playerId');
      $builder->select('player_order as order');
      $builder->select('is_cap as isCap');
      $builder->select('is_vcap as isvCap');
      $builder->select('end_time as endTime');

      $builder->where('match_id', $matchId);
      $builder->where('contest_id', $contestId);
      $builder->where('user_id', $userId);
      $builder->where('player_id', $playerId);
      $builder->limit(1);
      
      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function updateCaptain($id)
    {

      $builder = $this->db->table($this->finPlayTbl);

      $builder->set('is_cap', '1');
      $builder->set('is_vcap', '0');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      //- $builder->where('match_id', $matchId);
      //- $builder->where('contest_id', $contestId);
      //- $builder->where('user_id', $userId);
      //- $builder->where('player_id', $playerId);

      $builder->where('id', $id);

      return $builder->update();
    }


    public function updateViceCaptain($id)
    {

      $builder = $this->db->table($this->finPlayTbl);

      $builder->set('is_cap', '0');
      $builder->set('is_vcap', '1');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      //- $builder->where('match_id', $matchId);
      //- $builder->where('contest_id', $contestId);
      //- $builder->where('user_id', $userId);
      //- $builder->where('player_id', $playerId);

      $builder->where('id', $id);

      return $builder->update();
    }


    //- Get Final Players
    public function getSelGamePlayers($matchId,$contestId,$userId){
      $builder = $this->db->table($this->finPlayTbl);
      
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('user_id as userId');
      $builder->select('player_id as playerId');
      $builder->select('player_order as order');
      $builder->select('is_cap as isCap');
      $builder->select('is_vcap as isvCap');
      $builder->select('end_time as endTime');
      
      $builder->where('match_id', $matchId);
      $builder->where('contest_id', $contestId);
      $builder->where('user_id', $userId);
      $builder->where('status', '1');
      $builder->orderBy('player_order', 'ASC');
      

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    //- Remove Player Draft With Status 0 
    public function removePlayerDrafts($matchId,$contestId,$userId)
    {
      $builder = $this->db->table($this->finPlayTbl);
      $builder->delete(['match_id' => $matchId, 'contest_id' => $contestId, 'user_id' => $userId, 'status' => '0']);
    }


    //- Create Final Players
    public function createPlayersFinal($data){

      $builder = $this->db->table($this->finPlayTbl);

      $builder->set('match_id', $data['matchId']);
      $builder->set('contest_id', $data['contestId']);
      $builder->set('user_id', $data['userId']);
      $builder->set('player_id', $data['playerId']);
      $builder->set('player_order', $data['order']);
      if (array_key_exists('endTime', $data)) {
        $builder->set('end_time', $data['endTime']);
      }
      if (array_key_exists('startTime', $data)) {
        $builder->set('start_time', $data['startTime']);
      }      
      $builder->set('status', '1');

      $builder->set('date_created', gmdate("Y-m-d H:i:s"));
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      return $builder->insert();

    }


    public function updatePlayerStatus($matchId,$contestId,$userId,$playerId)
    {

      $builder = $this->db->table($this->finPlayTbl);

      $builder->set('status', '1');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('match_id', $matchId);
      $builder->where('contest_id', $contestId);
      $builder->where('user_id', $userId);
      $builder->where('player_id', $playerId);
      return $builder->update();
    }


    public function getLatestPlayers($matchId,$contestId,$userId,$startTime){
      $builder = $this->db->table($this->finPlayTbl);
      
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('user_id as userId');
      $builder->select('player_id as playerId');
      $builder->select('player_order as order');
      $builder->select('is_cap as isCap');
      $builder->select('is_vcap as isvCap');
      $builder->select('start_time as startTime');
      $builder->select('end_time as endTime');
      
      $builder->where('match_id', $matchId);
      $builder->where('contest_id', $contestId);
      $builder->where('user_id', $userId);
      $builder->where('end_time >', $startTime);
      $builder->where('status', '0');
      $builder->orderBy('player_order', 'ASC');
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }

    public function checkPlayerSelected($matchId,$contestId,$playerId){ 

      $builder = $this->db->table($this->finPlayTbl);
      
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('user_id as userId');
      $builder->select('player_id as playerId');
      $builder->select('player_order as order');
      $builder->select('is_cap as isCap');
      $builder->select('is_vcap as isvCap');
      $builder->select('end_time as endTime');

      $builder->where('match_id', $matchId);
      $builder->where('contest_id', $contestId);
      $builder->where('player_id', $playerId);
      $builder->where('status', '1');
      $builder->limit(1);
      
      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }


    public function updateFinPlayerStatus($ids)
    {

      $builder = $this->db->table($this->finPlayTbl);

      $builder->set('status', '1');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->whereIn('id', $ids);
      return $builder->update();
    }

}
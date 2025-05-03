<?php

namespace App\Models\Admin;
use CodeIgniter\Model;

class ContestModel extends Model
{

    protected $db;
    private string $campPriceTbl;
    private string $matchContTbl;
    private string $matchDrafTbl;
    public function __construct() {
      $this->db = \Config\Database::connect();
      $this->campPriceTbl = 'pl6_contest_pricing';
      $this->matchContTbl = 'pl6_match_contest';
      $this->matchDrafTbl = 'pl6_match_contest_drafts';
    }


    public function updateToss($matchId,$win)
    {

      $builder = $this->db->table($this->matchContTbl);

      $builder->set('status', '4');
      $builder->set('toss_winning_team', $win);
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('match_id', $matchId);
      $builder->whereIn('status', array('2', '3'));
      return $builder->update();
    }


    public function updateAutoAssignTossDecision($matchId,$win,$loss)
    {

      $builder = $this->db->table($this->matchContTbl);

      $builder->set('user_one_team', $loss);
      $builder->set('user_two_team', $win);
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('match_id', $matchId);
      $builder->where('user_one_team', 0);
      $builder->where('user_two_team', 0);
      return $builder->update();
    }    


    //- Close after unpaired Contests Status 6
    public function closeUnPairedContest($matchId){
      $builder = $this->db->table($this->matchContTbl);

      $builder->set('status', '6');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('match_id',$matchId);
      $builder->where('user_one !=',0);
      $builder->where('user_two',0);

      return $builder->update();
    }


    public function getContestTossWinUsersOnes($matchId,$win)
    {
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('pl6_users.device_token as token');
      
      $builder->join('pl6_users', 'pl6_users.id = pl6_match_contest.user_one');
      $builder->where('pl6_match_contest.match_id', $matchId);
      $builder->where('pl6_match_contest.user_one_team', $win);
      $builder->where('pl6_match_contest.user_two_team !=', $win);
      $builder->where('pl6_users.device_token !=', '');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function getContestTossWinUsersTwo($matchId,$win)
    {
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('pl6_users.device_token as token');
      
      $builder->join('pl6_users', 'pl6_users.id = pl6_match_contest.user_two');
      $builder->where('pl6_match_contest.match_id', $matchId);
      $builder->where('pl6_match_contest.user_two_team', $win);
      $builder->where('pl6_match_contest.user_one_team !=', $win);
      $builder->where('pl6_users.device_token !=', '');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function getContestTossLooseUsersOnes($matchId,$loss)
    {
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('pl6_users.device_token as token');

      $builder->join('pl6_users', 'pl6_users.id = pl6_match_contest.user_one');
      $builder->where('pl6_match_contest.match_id', $matchId);
      $builder->where('pl6_match_contest.user_one_team', $loss);
      $builder->where('pl6_match_contest.user_two_team !=', $loss);
      $builder->where('pl6_users.device_token !=', '');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function getContestTossLoosUsersTwo($matchId,$loss)
    {
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('pl6_users.device_token as token');
      
      $builder->join('pl6_users', 'pl6_users.id = pl6_match_contest.user_two');
      $builder->where('pl6_match_contest.match_id', $matchId);
      $builder->where('pl6_match_contest.user_two_team', $loss);
      $builder->where('pl6_match_contest.user_one_team !=', $loss);
      $builder->where('pl6_users.device_token !=', '');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }   


    //- Close All Match Contests Status 8
    public function closeAllMatchContest($matchId){
      $builder = $this->db->table($this->matchContTbl);

      $builder->set('status', '8');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('match_id',$matchId);
      return $builder->update();
    } 


    public function getContestUsersOnes($matchId)
    {
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('pl6_users.device_token as token');
      
      $builder->join('pl6_users', 'pl6_users.id = pl6_match_contest.user_one');
      $builder->where('pl6_match_contest.match_id', $matchId);
      $builder->where('pl6_match_contest.status', '4');
      $builder->where('pl6_match_contest.user_one !=', 0);
      $builder->where('pl6_users.device_token !=', '');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function getContestUsersTwo($matchId)
    {
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('pl6_users.device_token as token');
      
      $builder->join('pl6_users', 'pl6_users.id = pl6_match_contest.user_two');
      $builder->where('pl6_match_contest.match_id', $matchId);
      $builder->where('pl6_match_contest.status', '4');
      $builder->where('pl6_match_contest.user_two !=', 0);
      $builder->where('pl6_users.device_token !=', '');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }
    

    //- 
    public function getMatchContests($matchId)
    {
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id as pairId');
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('contest_fee as fee');
      $builder->select('user_one as userOne');
      $builder->select('user_two as userTwo');

      $builder->where('match_id', $matchId);
      
      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }    


    //- Get Total Complete Matches
    public function getGameRooms($dur)
    {
      
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id');

      $builder->where('status', '5');
      if (isset($dur['fromdate']) && isset($dur['todate'])) {
        $builder->where('date_created >=', $dur['fromdate']);
        $builder->where('date_created <=', $dur['todate']);
      }
     
      $query = $builder->get(); 
      return $query->getNumRows();
    } 


    public function getPlatFormFee($dur)
    {
      
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->selectSum('platform');
     
      $builder->where('status', '5');
      if (isset($dur['fromdate']) && isset($dur['todate'])) {
        $builder->where('date_created >=', $dur['fromdate']);
        $builder->where('date_created <=', $dur['todate']);
      }
           
      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    } 


    public function getRunningMatches()
    {
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('match_id as matchId');
      $builder->distinct();
      $builder->whereIn('status', array('1','2','3','4'));
      
      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    //- Get Total Complete Matches
    public function getTotalMatchRooms($matchid)
    {
      
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id');

      $builder->whereIn('status', array('2','3','4'));
      $builder->where('match_id', $matchid);
     
      $query = $builder->get();
      return $query->getNumRows();
    }


    public function getTotal1Rooms($matchid)
    {
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id');

      $builder->where('match_id', $matchid);
      $builder->where('contest_id', '1');
      $builder->whereIn('status', array('2','3','4'));

      $query = $builder->get();
      return $query->getNumRows();
    }


    public function getTotal2Rooms($matchid)
    {
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id');

      $builder->where('match_id', $matchid);
      $builder->where('contest_id', '2');
      $builder->whereIn('status', array('2','3','4'));

      $query = $builder->get();
      return $query->getNumRows();
    }


    public function getTotal3Rooms($matchid)
    {
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id');

      $builder->where('match_id', $matchid);
      $builder->where('contest_id', '3');
      $builder->whereIn('status', array('2','3','4'));

      $query = $builder->get();
      return $query->getNumRows();
    }


    public function getTotal4Rooms($matchid)
    {
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id');

      $builder->where('match_id', $matchid);
      $builder->where('contest_id', '4');
      $builder->whereIn('status', array('2','3','4'));

      $query = $builder->get();
      return $query->getNumRows();
    }


    public function getTotalWaitingRooms($matchid)
    {
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id');

      $builder->where('match_id', $matchid);
      $builder->where('user_two', '0');
      $builder->whereIn('status', array('1'));

      $query = $builder->get();
      return $query->getNumRows();
    }


    //- Get User Game Rooms
    public function getUserGameRooms($userId)
    {
      
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id');

      $builder->where('status', '5');
      $builder->groupStart();
      $builder->where('user_one', $userId);
      $builder->orWhere('user_two', $userId);
      $builder->groupEnd();   
           
      $query = $builder->get(); 
      return $query->getNumRows();
    }


    public function getWinningAmount($userId)
    {

      $builder = $this->db->table($this->matchContTbl);
      
      $builder->selectSum('one_winning_price');
      $builder->selectSum('two_winning_price');
            
      $builder->where('status', '5');
      $builder->where('winner_id', $userId);
      $builder->groupStart();
      $builder->where('user_one', $userId);
      $builder->orWhere('user_two', $userId);
      $builder->groupEnd();     
      
      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function getLossAmount($userId)
    {

      $builder = $this->db->table($this->matchContTbl);
      
      $builder->selectSum('one_lost_price');
      $builder->selectSum('two_lost_price');
            
      $builder->where('status', '5');
      $builder->where('winner_id !=', $userId);
      $builder->groupStart();
      $builder->where('user_one', $userId);
      $builder->orWhere('user_two', $userId);
      $builder->groupEnd();
      
      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    //- Get All Contests
    public function getAllMatchContests($matchId)
    {
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id as pairId');
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('contest_fee as fee');
      $builder->select('user_one as userOne');
      $builder->select('user_two as userTwo');
      $builder->select('user_one_team as userOneTeam');
      $builder->select('user_two_team as userTemTeam');
      $builder->select('user_one_drafted as userOneDrft');
      $builder->select('user_two_drafted as userTwoDrft');
      $builder->select('toss_winning_team as tossWinTeam');
      $builder->select('final_drafts as finalDrafts');
      $builder->select('toss_winner as tossWinner');
      $builder->select('platform as platform');
      $builder->select('one_winning_price as oneWinPrice');
      $builder->select('two_winning_price as twoWinPrice');
      $builder->select('one_lost_price as oneLostPrice');
      $builder->select('two_lost_price as twoLostPrice');
      $builder->select('user_one_score as userOneScore');
      $builder->select('user_two_score as userTwoScore');
      $builder->select('winner_id as winnerId');
      $builder->select('winn_status as winnStatus');
      $builder->select('status as status');
      

      $builder->where('match_id', $matchId);
      
      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }    

    //- Get Prices
    public function getCampPrices($contestId,$matchTypeId)
    {
      
      $builder = $this->db->table($this->campPriceTbl);
      
      $builder->select('id as priceId');
      $builder->select('contest_type as contType');
      $builder->select('match_type as matchType');
      $builder->select('price as price');
      $builder->select('max_diff as maxDiff');

      $builder->where('contest_type', $contestId);
      $builder->where('match_type', $matchTypeId);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    //- Get Total Complete Matches
    public function getTotalAllMatchRooms($matchid)
    {
      
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id');

      $builder->where('match_id', $matchid);
     
      $query = $builder->get();
      return $query->getNumRows();
    }


}
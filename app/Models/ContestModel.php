<?php

namespace App\Models;
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


    //- Get All Prices
    public function getAllContestPrices()
    {
      
      $builder = $this->db->table($this->campPriceTbl);
      
      $builder->select('id as priceId');
      $builder->select('contest_type as contType');
      $builder->select('match_type as matchType');
      $builder->select('price as price');
      $builder->select('max_diff as maxDiff');

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


    //- Create Contest
    public function createContest($data)
    {

      $builder = $this->db->table($this->matchContTbl);

      $builder->set('match_id', $data['matchId']);
      $builder->set('contest_id', $data['contestId']);
      $builder->set('contest_fee', $data['contestFee']);
      $builder->set('user_one', $data['userOne']);
      $builder->set('status', '1');

      $builder->set('date_created', gmdate("Y-m-d H:i:s"));
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      return $builder->insert();

    }


    public function getUserOneLastContest($userId){
      
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id as pairId');
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('contest_fee as fee');
      $builder->select('user_one as userOne');
      $builder->select('user_two as userTwo');

      $builder->where('user_one', $userId);
      $builder->orderBy('id', 'DESC');
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }


    //- Check Contest Exist
    public function checkUserContestExist($userId,$matchId,$contest)
    {
      
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id as pairId');
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('contest_fee as fee');
      $builder->select('user_one as userOne');
      $builder->select('user_two as userTwo');
      $builder->select('status as status');

      $builder->where('match_id', $matchId);
      $builder->where('contest_id', $contest);

      $builder->groupStart();
      $builder->where('user_one', $userId);
      $builder->orWhere('user_two', $userId);
      $builder->groupEnd();

      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    //- Check Contest Exist
    public function checkUserUnParedContest($userId,$matchId,$contest)
    {
      
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id as pairId');
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('contest_fee as fee');
      $builder->select('user_one as userOne');
      $builder->select('user_two as userTwo');
      $builder->select('status as status');

      $builder->where('match_id', $matchId);
      $builder->where('contest_id', $contest);
      $builder->where('user_one', $userId);
      $builder->where('user_two', '0');
      $builder->where('status', '1');
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    //- Get Un Paired Contest
    public function getUnPairedContest($matchId,$contest)
    {
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id as pairId');
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('contest_fee as fee');
      $builder->select('user_one as userOne');
      $builder->select('user_two as userTwo');

      $builder->where('match_id', $matchId);
      $builder->where('contest_id', $contest);
      $builder->where('user_one !=', '0');
      $builder->where('user_two', '0');
      $builder->where('status', '1');
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }


    public function updateContestPair($data)
    {

      $builder = $this->db->table($this->matchContTbl);

      $builder->set('user_two', $data['userTwo']);
      $builder->set('status', '2');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $data['pairId']);
      return $builder->update();
    }


    //- Create Drafts
    public function createDrafts($data)
    {

      $builder = $this->db->table($this->matchDrafTbl);

      $builder->set('match_id', $data['matchId']);
      $builder->set('contest_id', $data['contestId']);
      $builder->set('user_id', $data['userId']);
      $builder->set('player_id', $data['playerId']);
      $builder->set('player_order', $data['playerOrder']);
      

      $builder->set('date_created', gmdate("Y-m-d H:i:s"));
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      return $builder->insert();

    }


    //- Get Game Details By Id
    public function getGameDetailsById($gameId,$userId)
    {

      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id as pairId');
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('contest_fee as fee');
      $builder->select('user_one as userOne');
      $builder->select('user_two as userTwo');
      $builder->select('status as status');
      $builder->select('user_one_drafted as oneDraft');
      $builder->select('user_two_drafted as twoDraft');
      $builder->select('user_one_team as userOneTeam');
      $builder->select('user_two_team as userTwoTeam');
      $builder->select('sel_startTm as selStartTm');
      $builder->select('toss_winner as tossWinner');
      $builder->select('user_one_isCapSel as oneCapSel');
      $builder->select('user_two_isCapSel as twoCapSel');
      $builder->select('toss_winning_team as tossWinnerTeam');
      
      $builder->where('id', $gameId);
      $builder->groupStart();
      $builder->where('user_one', $userId);
      $builder->orWhere('user_two', $userId);
      $builder->groupEnd();
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }


    //- Get All User Games 
    public function getAllGameById($userId)
    {

      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id as pairId');
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('contest_fee as fee');
      $builder->select('user_one as userOne');
      $builder->select('user_two as userTwo');
      $builder->select('status as status');
      $builder->select('user_one_team as userOneTeam');
      $builder->select('user_two_team as userTwoTeam');
      $builder->select('toss_winner as tossWinner');
      $builder->select('final_drafts as finalPlayers');
      $builder->select('sel_startTm as selStartTm');
      $builder->select('user_one_isCapSel as uoneisCapSel'); 
      $builder->select('user_two_isCapSel as utwoisCapSel');
      $builder->select('toss_winning_team as tossWinnerTeam');  

      $builder->whereIn('status', array('1', '2', '3', '4'));
      $builder->groupStart();
      $builder->where('user_one', $userId);
      $builder->orWhere('user_two', $userId);
      $builder->groupEnd();

      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }


    //- Update Contest Teams
    public function updateContestTeams($data)
    {

      $builder = $this->db->table($this->matchContTbl);

      $builder->set('user_one_team', $data['userOneTeam']);
      $builder->set('user_two_team', $data['userTwoTeam']);
      $builder->set('status', '3');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $data['pairId']);
      return $builder->update();
    }


    //- Get Contest Under Draft
    public function getDeaftContest($userId,$gameId)
    {
      
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id as pairId');
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('contest_fee as fee');
      $builder->select('user_one as userOne');
      $builder->select('user_two as userTwo');

      $builder->where('id', $gameId);
      $builder->whereIn('status', array('2', '3'));
      $builder->where('user_one !=', '');
      $builder->where('user_two !=', '');

      $builder->groupStart();
      $builder->where('user_one', $userId);
      $builder->orWhere('user_two', $userId);
      $builder->groupEnd();

      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    //- Get My Game Draft Players
    public function getGameDraftsById($matchId,$gameId,$userId){
      $builder = $this->db->table($this->matchDrafTbl);
      
      $builder->select('id as draftId');
      $builder->select('player_id as playerId');
      $builder->select('player_order as playerOrder');
      
      $builder->where('match_id', $matchId);
      $builder->where('contest_id', $gameId);
      $builder->where('user_id', $userId);
      $builder->orderBy('player_order', 'ASC');
      
      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    //- Check Draft Player 
    public function getGameDraftPlaye($userId,$matchId,$gameId,$player){
      $builder = $this->db->table($this->matchDrafTbl);
      
      $builder->select('id as draftId');
      $builder->select('player_id as playerId');
      
      $builder->where('user_id', $userId);
      $builder->where('match_id', $matchId);
      $builder->where('contest_id', $gameId);
      $builder->where('player_id', $player);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    //- Remove Draft Player
    public function removeDraftPlayer($id)
    {
      
      $builder = $this->db->table($this->matchDrafTbl);

      $builder->delete(['id' => $id]);
      
    }


    //- Remove User Match Contest Draft
    public function removeUserMatchContestDraft($matchId, $contestId, $userId)
    {
      
      $builder = $this->db->table($this->matchDrafTbl);

      $builder->delete(['user_id' => $userId, 'contest_id' => $contestId ,'match_id' => $matchId]);
    }    


    //- Update Player Draft Order
    public function updateDraftPlayerOrder($draftId,$playerId,$orderId){
      $builder = $this->db->table($this->matchDrafTbl);

      $builder->set('player_order', $orderId);
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $draftId);
      $builder->where('player_id', $playerId);
      return $builder->update();      
    }


    //- Update User Draft Status
    public function updateUserDraftStatus($gameId,$user)
    {

      $builder = $this->db->table($this->matchContTbl);

      if ($user == 'one') {
        $builder->set('user_one_drafted', '1');
      }else if ($user == 'two') {
        $builder->set('user_two_drafted', '1');
      }
  
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $gameId);
      return $builder->update();
    }


    //- Get Un Paired Contest For Down Grade
    public function getDownGradeContests($matchId,$contest,$userId)
    {
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id as pairId');
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('contest_fee as fee');
      $builder->select('user_one as userOne');
      $builder->select('user_two as userTwo');

      $builder->where('match_id', $matchId);
      $builder->where('contest_id <', (int)$contest);
      $builder->where('user_one !=', $userId);
      $builder->where('user_two !=', $userId);
      $builder->where('status', '1');

      $builder->groupBy('contest_id');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    //- Get All DownGrade Options
    public function getAllDownGradeContests($matchId)
    {
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id as pairId');
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('contest_fee as fee');
      $builder->select('user_one as userOne');
      $builder->select('user_two as userTwo');

      $builder->where('match_id', $matchId);
      $builder->where('status', '1');

      $builder->groupBy('contest_id');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    //- 
    public function getMyMatchContests($matchId,$userId)
    {
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id as pairId');
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('contest_fee as fee');
      $builder->select('user_one as userOne');
      $builder->select('user_two as userTwo');

      $builder->where('match_id', $matchId);
      $builder->groupStart();
      $builder->where('user_one', $userId);
      $builder->orWhere('user_two', $userId);
      $builder->groupEnd();

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }

    //- Suspend Contests (Close after Downgrad) Status 7
    public function suspendContest($matchId,$contestId,$userId){
      $builder = $this->db->table($this->matchContTbl);

      $builder->set('status', '7');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('match_id',$matchId);
      $builder->where('contest_id',$contestId);
      $builder->where('user_one',$userId);

      return $builder->update();

    }


    public function activeContest($matchId,$contestId,$userId){
      $builder = $this->db->table($this->matchContTbl);

      $builder->set('status', '1');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('match_id',$matchId);
      $builder->where('contest_id',$contestId);
      $builder->where('user_one',$userId);

      return $builder->update();
    }


    public function getUserSuspendContest($matchId,$contestId,$userId){
      
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id as pairId');
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('contest_fee as fee');
      $builder->select('user_one as userOne');
      $builder->select('user_two as userTwo');

      $builder->where('match_id',$matchId);
      $builder->where('contest_id',$contestId);
      $builder->where('user_one',$userId);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;

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


    public function getUnPairedContestUsers($matchId)
    {
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('pl6_users.device_token as token');
      
      $builder->join('pl6_users', 'pl6_users.id = pl6_match_contest.user_one');
      $builder->where('pl6_match_contest.match_id', $matchId);
      $builder->where('pl6_match_contest.status', '1');
      $builder->where('pl6_match_contest.user_one !=', 0);
      $builder->where('pl6_users.device_token !=', '');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }    


    public function getAllMatchContestsForDrafts($matchId)
    {
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id as pairId');
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('contest_fee as fee');
      $builder->select('user_one as userOne');
      $builder->select('user_two as userTwo');
      $builder->select('user_one_team as userOneTeam');
      $builder->select('user_two_team as userTwoTeam');
      $builder->select('toss_winning_team as winningTeam');
      $builder->select('final_drafts as finalDrafts');

      $builder->select('toss_winner as tossWinner');
      $builder->select('sel_startTm as selStartTm');
      
      $builder->where('match_id', $matchId);
      $builder->where('status', '4');
      $builder->where('user_one !=', '0');
      $builder->where('user_two !=', '0');
      $builder->where('final_drafts', '0');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function updateFinalDraftStatus($pairId,$tosWinUser,$seleStartTime)
    {

      $builder = $this->db->table($this->matchContTbl);

      $builder->set('toss_winner', $tosWinUser);
      $builder->set('sel_startTm', $seleStartTime);
      $builder->set('final_drafts', '1');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $pairId);
      return $builder->update();
    }


    public function getGameForFinalSelById($gameId,$userId)
    {

      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id as pairId');
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('contest_fee as fee');
      $builder->select('user_one as userOne');
      $builder->select('user_two as userTwo');
      $builder->select('status as status');
      $builder->select('user_one_drafted as oneDraft');
      $builder->select('user_two_drafted as twoDraft');
      $builder->select('user_one_team as userOneTeam');
      $builder->select('user_two_team as userTwoTeam');
      $builder->select('toss_winner as tossWinner');
      $builder->select('sel_startTm as selStartTime');

      $builder->where('id', $gameId);
      $builder->where('final_drafts', '1');
      $builder->groupStart();
      $builder->where('user_one', $userId);
      $builder->orWhere('user_two', $userId);
      $builder->groupEnd();
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }


    public function updateOneCapSel($pairId,$userId)
    {

      $builder = $this->db->table($this->matchContTbl);

      $builder->set('user_one_isCapSel', '1');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $pairId);
      $builder->where('user_one', $userId);
      return $builder->update();
    }


    public function updateTwoCapSel($pairId,$userId)
    {

      $builder = $this->db->table($this->matchContTbl);

      $builder->set('user_two_isCapSel', '1');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $pairId);
      $builder->where('user_two', $userId);
      return $builder->update();
    }


    //- Get All User Games 
    public function getAllGameForResults($matchId)
    {

      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id as pairId');
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('contest_fee as fee');
      $builder->select('user_one as userOne');
      $builder->select('user_two as userTwo');
      $builder->select('status as status');
      $builder->select('user_one_team as userOneTeam');
      $builder->select('user_two_team as userTwoTeam');
      $builder->select('toss_winner as tossWinner');
      $builder->select('final_drafts as finalPlayers');
      $builder->select('sel_startTm as selStartTm');
      $builder->select('user_one_isCapSel as uoneisCapSel'); 
      $builder->select('user_two_isCapSel as utwoisCapSel');      

      $builder->where('match_id', $matchId);
      $builder->where('user_one !=', '0');
      $builder->where('user_two !=', '0');
      $builder->where('status', '4');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }


    public function updateContestResults($pairId,$data)
    {

      $builder = $this->db->table($this->matchContTbl);

      $builder->set('user_one_score', $data['totalOScore']);
      $builder->set('user_two_score', $data['totalTScore']);
      $builder->set('winner_id', $data['gameWinnerId']);
      $builder->set('winn_status', $data['winnStatus']);
      $builder->set('platform', $data['platform']);
      
      if (array_key_exists('oneRefundAmount', $data)) {
        $builder->set('one_refund_amount', $data['oneRefundAmount']);
      }
      if (array_key_exists('twoRefundAmount', $data)) {
        $builder->set('two_refund_amount', $data['twoRefundAmount']);
      }     

      if (array_key_exists('oneWinningPrice', $data)) {
        $builder->set('one_winning_price', $data['oneWinningPrice']);
      }
      if (array_key_exists('twoWinningPrice', $data)) {
        $builder->set('two_winning_price', $data['twoWinningPrice']);
      }

      if (array_key_exists('oneLostPrice', $data)) {
        $builder->set('one_lost_price', $data['oneLostPrice']);
      }
      if (array_key_exists('twoLostPrice', $data)) {
        $builder->set('two_lost_price', $data['twoLostPrice']);
      }

      $builder->set('status', '5');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $pairId);
      return $builder->update();
    }


    //- Get All User Games 
    public function getAllCompletedGame($userId)
    {

      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id as pairId');
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('contest_fee as fee');
      $builder->select('user_one as userOne');
      $builder->select('user_two as userTwo');

      $builder->select('status as status');
      $builder->select('user_one_team as userOneTeam');
      $builder->select('user_two_team as userTwoTeam');

      $builder->select('user_one_score as userOneScore');
      $builder->select('user_two_score as userTwoScore');

      $builder->select('one_winning_price as oneWinningPrice');
      $builder->select('two_winning_price as twoWinningPrice');

      $builder->select('one_lost_price as oneLostPrice');
      $builder->select('two_lost_price as twoLostPrice');   

      $builder->select('winn_status as winnStatus');
      $builder->select('winner_id as winnerId');
            
      $builder->where('status', '5');
      $builder->groupStart();
      $builder->where('user_one', $userId);
      $builder->orWhere('user_two', $userId);
      $builder->groupEnd();
      $builder->orderBy('id', 'DESC');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;

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

    //- Close All Match Contests Status 8
    public function closeAllMatchContest($matchId){
      $builder = $this->db->table($this->matchContTbl);

      $builder->set('status', '8');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('match_id',$matchId);
      return $builder->update();
    } 


    public function getAllActivMatchContests($matchId)
    {
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id as pairId');
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('contest_fee as fee');
      $builder->select('user_one as userOne');
      $builder->select('user_two as userTwo');

      $builder->where('match_id', $matchId);
      $builder->where('status', '8');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function getAllUnpairedContests($matchId)
    {
      $builder = $this->db->table($this->matchContTbl);
      
      $builder->select('id as pairId');
      $builder->select('match_id as matchId');
      $builder->select('contest_id as contestId');
      $builder->select('contest_fee as fee');
      $builder->select('user_one as userOne');
      $builder->select('user_two as userTwo');

      $builder->where('match_id',$matchId);
      $builder->where('status', '6');
      $builder->where('user_one !=',0);
      $builder->where('user_two',0);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }

    //- Active User Contests
    public function activeUserContests($userId)
    {
      
      $builder = $this->db->table($this->matchContTbl);
       
      $builder->selectSum('contest_fee');

      $builder->whereIn('status', array('1', '2', '3', '4'));
      $builder->groupStart();
      $builder->where('user_one', $userId);
      $builder->orWhere('user_two', $userId);
      $builder->groupEnd();

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }

}
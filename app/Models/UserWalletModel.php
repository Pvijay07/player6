<?php

namespace App\Models;
use CodeIgniter\Model;

class UserWalletModel extends Model
{

    protected $db;
    private string $tranTbl;
    private string $walletTbl;
    public function __construct() {
      $this->db = \Config\Database::connect();
      $this->tranTbl = 'pl6_transactions';
      $this->walletTbl = 'pl6_user_wallet';
    }


    public function createTransac($data)
    {

      $builder = $this->db->table($this->tranTbl);

      $builder->set('merchant_trans_id', $data['transId']);
      $builder->set('user_id', $data['userId']);
      $builder->set('amount', $data['amount']);
      $builder->set('lat', $data['lat']);
      $builder->set('lon', $data['long']);
      $builder->set('date_created', gmdate("Y-m-d H:i:s"));
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      return $builder->insert();

    }

    public function updateTransac($transId,$data)
    {

      $builder = $this->db->table($this->tranTbl);

      $builder->set('transaction_id', $data['transId']);
      $builder->set('state', $data['state']);
      $builder->set('response_code', $data['respCode']);
      $builder->set('amount', $data['amount']);
      $builder->set('status', $data['status']);

      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('merchant_trans_id', $transId);
      return $builder->update();
    }


    public function getTransactionDetails($transId){
    	$builder = $this->db->table($this->tranTbl);
      
      $builder->select('id as id');
      $builder->select('merchant_trans_id as mtransId');
      $builder->select('user_id as userId');
      $builder->select('transaction_id as transId');
      $builder->select('amount as amount');
      $builder->select('state as state');
      $builder->select('response_code as responseCode');
      
      $builder->where('merchant_trans_id', $transId);
      $builder->limit(1);
  
      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }


    public function creditWallet($data)
    {

      $builder = $this->db->table($this->walletTbl);

      $builder->set('user_id', $data['userId']);
      $builder->set('credit', $data['amount']);

      if (array_key_exists('transId', $data)) {
        $builder->set('deposit_id', $data['transId']);
      }

      if (array_key_exists('contestId', $data)) {
        $builder->set('contest_id', $data['contestId']);
      }

      $builder->set('text', $data['text']);
      $builder->set('transaction_type', $data['status']);
      $builder->set('date_created', gmdate("Y-m-d H:i:s"));
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      return $builder->insert();

    }


    public function debitWallet($data)
    {

      $builder = $this->db->table($this->walletTbl);

      $builder->set('user_id', $data['userId']);
      $builder->set('debit', $data['amount']);
      
      if (array_key_exists('transId', $data)) {
        $builder->set('deposit_id', $data['transId']);
      }

      if (array_key_exists('contestId', $data)) {
        $builder->set('contest_id', $data['contestId']);
      }

      if (array_key_exists('payOutId', $data)) {
        $builder->set('pay_out_id', $data['payOutId']);
      }      
      
      $builder->set('text', $data['text']);
      $builder->set('transaction_type', $data['status']);
      $builder->set('date_created', gmdate("Y-m-d H:i:s"));
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      return $builder->insert();

    }


    public function getWalletRecords($userId){
      $builder = $this->db->table($this->walletTbl);
      
      $builder->select('id as id');
      $builder->select('user_id as userId');
      $builder->select('credit as credit');
      $builder->select('debit as debit');
      $builder->select('text as text');

      $builder->select('date_created as dateCreated');
      $builder->where('user_id', $userId);
      $builder->orderBy('id', 'DESC');
  
      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }


    public function getTotalCredits($userId){
      $builder = $this->db->table($this->walletTbl);
      
      $builder->selectSum('credit');
      $builder->selectSum('debit');

      $builder->where('user_id', $userId);
      $builder->orderBy('id', 'DESC');
  
      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function checkCreditTransaction($userId,$contestId,$status,$credit){
      $builder = $this->db->table($this->walletTbl);
      
      $builder->select('id as id');
      $builder->select('user_id as userId');
      $builder->select('credit as credit');
      $builder->select('debit as debit');
      $builder->select('contest_id as contestId');
      $builder->select('deposit_id as depositId');
      
      $builder->where('user_id', $userId);
      $builder->where('contest_id', $contestId);
      $builder->where('transaction_type', $status);
      $builder->where('credit', $credit);
  
      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }

    public function getTotalDeposits($userId){
      $builder = $this->db->table($this->walletTbl);
      
      $builder->selectSum('credit');

      $builder->where('user_id', $userId);
      $builder->where('transaction_type', '1');
  
      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function getTotalWithdrawals($userId){
      $builder = $this->db->table($this->walletTbl);
      
      $builder->selectSum('debit');

      $builder->where('user_id', $userId);
      $builder->whereIn('transaction_type', array('7', '8'));

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function getSignUpBonus($userId){
      $builder = $this->db->table($this->walletTbl);
      
      $builder->select('id as id');
      $builder->select('user_id as userId');
      $builder->select('credit as credit');
      $builder->select('debit as debit');
      $builder->select('text as text');
      $builder->select('date_created as dateCreated');

      $builder->where('user_id', $userId);
      $builder->where('transaction_type', '9');
      $builder->orderBy('id', 'DESC');
  
      $query = $builder->get();
      $result = $query->getResult();
      return $result;      
    }


    public function getTotalTDSWithdrawals($userId){
      $builder = $this->db->table($this->walletTbl);
      
      $builder->selectSum('debit');

      $builder->where('user_id', $userId);
      $builder->whereIn('transaction_type', array('7','8'));

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function getTotalOldTDS($userId){
      $builder = $this->db->table($this->walletTbl);
      
      $builder->selectSum('debit');

      $builder->where('user_id', $userId);
      $builder->where('transaction_type', '8');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }
    

}
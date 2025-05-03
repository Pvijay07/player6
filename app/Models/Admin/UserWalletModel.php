<?php

namespace App\Models\Admin;
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


    public function getAllDeposits($dur,$uname){
      $builder = $this->db->table($this->walletTbl);
      
      $builder->select('id as id');
      $builder->select('user_id as userId');
      $builder->select('credit as credit');
      $builder->select('debit as debit');
      $builder->select('text as text');
      $builder->select('contest_id as contestId');
      $builder->select('deposit_id as depositId');
      $builder->select('transaction_type as transType');
      $builder->select('date_created as dateCreated');

      if (!is_null($uname)) {
        $builder->where('user_id', $uname);
      }
      
      if (isset($dur['fromDate']) && isset($dur['toDate'])) {
        $builder->where('date_created >=', $dur['fromDate']);
        $builder->where('date_created <=', $dur['toDate']);
      }

      $builder->whereIn('transaction_type', ['1','2','5']);    
        
      $builder->orderBy('deposit_id', 'DESC');
  
      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }

    public function getAllWithdrawal($dur,$uname){
      $builder = $this->db->table($this->walletTbl);
      
      $builder->select('id as id');
      $builder->select('user_id as userId');
      $builder->select('credit as credit');
      $builder->select('debit as debit');
      $builder->select('text as text');
      $builder->select('contest_id as contestId');
      $builder->select('deposit_id as depositId');
      $builder->select('transaction_type as transType');
      $builder->select('date_created as dateCreated');
      $builder->select('pay_out_id as payOutId');

      if (!is_null($uname)) {
        $builder->where('user_id', $uname);
      }
      
      if (isset($dur['fromDate']) && isset($dur['toDate'])) {
        $builder->where('date_created >=', $dur['fromDate']);
        $builder->where('date_created <=', $dur['toDate']);
      }

      $builder->whereIn('transaction_type', ['7','8']);    
        
      $builder->orderBy('pay_out_id', 'DESC');
  
      $query = $builder->get();
      $result = $query->getResult();
      return $result;

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
      $builder->select('lat as lat');
      $builder->select('lon as lon');
      
      $builder->where('id', $transId);
      $builder->limit(1);
  
      $query = $builder->get();
      $result = $query->getResult();
      return $result;

    }


    //- Get Team Deposits
    public function getTotalDeposits($dur)
    {
      
      $builder = $this->db->table($this->walletTbl);
      
      $builder->selectSum('credit');

      $builder->where('transaction_type', '1');

      if (isset($dur['fromdate']) && isset($dur['todate'])) {
        $builder->where('date_created >=', $dur['fromdate']);
        $builder->where('date_created <=', $dur['todate']);
      }
     
      $query = $builder->get();
      return $query->getResult();
    } 


    //- Get Total Bonus
    public function getTotalBonus($dur)
    {
      
      $builder = $this->db->table($this->walletTbl);
      
      $builder->selectSum('credit');

      $builder->whereIn('transaction_type', ['2']);
      if (isset($dur['fromdate']) && isset($dur['todate'])) {
        $builder->where('date_created >=', $dur['fromdate']);
        $builder->where('date_created <=', $dur['todate']);
      }
     
      $query = $builder->get();
      return $query->getResult();
    }


    //- Get Total Bonus
    public function getTotalPromosionBonus($dur)
    {
      
      $builder = $this->db->table($this->walletTbl);
      
      $builder->selectSum('credit');

      $builder->whereIn('transaction_type', ['9','10']);
      if (isset($dur['fromdate']) && isset($dur['todate'])) {
        $builder->where('date_created >=', $dur['fromdate']);
        $builder->where('date_created <=', $dur['todate']);
      }     

      $query = $builder->get();
      return $query->getResult();
    }


    //- Get Total Gst
    public function getTotalgst($dur)
    {
      
      $builder = $this->db->table($this->walletTbl);
      
      $builder->selectSum('debit');

      $builder->where('transaction_type', '5');
      if (isset($dur['fromdate']) && isset($dur['todate'])) {
        $builder->where('date_created >=', $dur['fromdate']);
        $builder->where('date_created <=', $dur['todate']);
      }     

      $query = $builder->get();
      return $query->getResult();
    }


    public function getTotalTDS($dur)
    {
      
      $builder = $this->db->table($this->walletTbl);
      
      $builder->selectSum('debit');

      $builder->where('transaction_type', '8');
      if (isset($dur['fromdate']) && isset($dur['todate'])) {
        $builder->where('date_created >=', $dur['fromdate']);
        $builder->where('date_created <=', $dur['todate']);
      } 
     
      $query = $builder->get();
      return $query->getResult();
    }


    public function getTotalWithDraw($dur)
    {
      
      $builder = $this->db->table($this->walletTbl);
      
      $builder->selectSum('debit');

      $builder->where('transaction_type', '7');
      if (isset($dur['fromdate']) && isset($dur['todate'])) {
        $builder->where('date_created >=', $dur['fromdate']);
        $builder->where('date_created <=', $dur['todate']);
      }       
     
      $query = $builder->get();
      return $query->getResult();
    }
    

    //- Get Total Bonus
    public function getPromosionBonus($dur,$uname)
    {
      
      $builder = $this->db->table($this->walletTbl);
      
      $builder->selectSum('credit');

      $builder->whereIn('transaction_type', ['9','10']);

      if (!is_null($uname)) {
        $builder->where('user_id', $uname);
      }
      
      if (isset($dur['fromDate']) && isset($dur['toDate'])) {
        $builder->where('date_created >=', $dur['fromDate']);
        $builder->where('date_created <=', $dur['toDate']);
      }

      $builder->orderBy('deposit_id', 'DESC');

      $query = $builder->get();
      return $query->getResult();
    }    

}
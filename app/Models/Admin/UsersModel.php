<?php

namespace App\Models\Admin;
use CodeIgniter\Model;

class UsersModel extends Model
{

    protected $db;
    private string $usersTbl;
    public function __construct() {
      $this->db = \Config\Database::connect();
      $this->usersTbl = 'pl6_users';
    }

   	//- Get Team Sign Ups
    public function getTotalSignUps($dur)
    {
      
      $builder = $this->db->table($this->usersTbl);
      
      $builder->select('id');

      $builder->where('mobile_verify', '1');
      $builder->orWhere('email_verify', '1');

      if (isset($dur['fromdate']) && isset($dur['todate'])) {
        $builder->where('date_create >=', $dur['fromdate']);
        $builder->where('date_create <=', $dur['todate']);
      }
     
      $query = $builder->get();
      return $query->getNumRows();
    } 


    //- Get User Details
    public function getAllUsers($dur,$uname,$pagedata)
    {

      $builder = $this->db->table($this->usersTbl);
      
      $builder->select('id as userId');
      $builder->select('name as name');
      $builder->select('email as email');
      $builder->select('number as number');
      $builder->select('profile_img as profileImg');
      $builder->select('user_status as userStatus');

      //- $builder->where('id', $teamId);
      if (!is_null($uname)) {
        $builder->where('id', $uname);
      }   
      $builder->limit($pagedata['perPage'],$pagedata['listFrom']);
      
      if (isset($dur['fromdate']) && isset($dur['todate'])) {
        $builder->where('date_create >=', $dur['fromdate']);
        $builder->where('date_create <=', $dur['todate']);
      }   

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    
    }


    public function getTotalUsers($dur,$uname)
    {

      $builder = $this->db->table($this->usersTbl);
      
      $builder->select('id as userId');
     

      //- $builder->where('id', $teamId);
      if (!is_null($uname)) {
        $builder->where('id', $uname);
      }  
      
      if (isset($dur['fromdate']) && isset($dur['todate'])) {
        $builder->where('date_create >=', $dur['fromdate']);
        $builder->where('date_create <=', $dur['todate']);
      }   

      $query = $builder->get();
      return $query->getNumRows();

    }

    public function findUser($name)
    {
      
      $builder = $this->db->table($this->usersTbl);
      
      $builder->select('id as id');
      $builder->select('name');
      $builder->select('email');

      $builder->like('name', $name);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    } 


    public function updateUserToken($userId,$status)
    {

      $builder = $this->db->table($this->usersTbl);

      $builder->set('ref_token', '');
      $builder->set('user_status', $status);

      $builder->where('id', $userId);
      return $builder->update();
    }    


    public function getUserById($userId)
    {
      
      $builder = $this->db->table($this->usersTbl);
      
      $builder->select('id as id');
      $builder->select('name as name');
      $builder->select('number as number');
      $builder->select('profile_img as profileImg');
      $builder->select('ref_token as refToken');
      $builder->select('email as email');
      $builder->select('login_status as logStatus');
      $builder->select('ip_address as myIp');
      $builder->select('email_otp as emailOTP');
      $builder->select('mobile_otp as mobileOTP');
      $builder->select('aadhaar_verify as aadhaarVerify');
      $builder->select('pan_verify as panVerify');
      $builder->select('mobile_verify as mobileVerify');
      $builder->select('email_verify as emailVerify');
      $builder->select('bank_verify as bankVerify');
      $builder->select('last_login as lastLogin');
      $builder->select('date_create as createdOn');
      $builder->select('razorpay_contacts_id as rzpayConId');
    
      $builder->where('id', $userId);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }    

}
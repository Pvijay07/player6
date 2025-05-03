<?php

namespace App\Models;
use CodeIgniter\Model;

class AdminModel extends Model
{

    protected $db;
    private string $adminTbl;
    public function __construct() {
      $this->db = \Config\Database::connect();
      $this->adminTbl = 'pl6_admin_users';
    }


    public function checkUserCred($email,$password)
    {
      
      $builder = $this->db->table($this->adminTbl);
      
      $builder->select('id');
      $builder->select('email');
      $builder->select('password');
      $builder->select('roal');
      $builder->select('login_status');
      $builder->select('ip_address');
      $builder->select('last_login');

      $builder->where('email', $email);
      $builder->where('password', $password);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function updateLoginStatus($data,$userId)
    {

      $builder = $this->db->table($this->adminTbl);
      
      $builder->set('email_otp', $data['emailOtp']);
      $builder->set('login_status', '1');
      $builder->set('ip_address', $data['ip']);
      $builder->set('last_login', $data['loginTime']);
      $builder->set('date_updated', $data['loginTime']);

      $builder->where('id', $userId);
      return $builder->update();
    }


    public function getUserDetailsById($userId,$otp)
    {
      
      $builder = $this->db->table($this->adminTbl);
      
      $builder->select('id as id');
      $builder->select('email as email');
      $builder->select('roal as roal');
      $builder->select('email_otp as emailOtp');
      $builder->select('login_status as logStatus');
      $builder->select('ip_address as myIp');
      $builder->select('last_login as lastLogin');
      $builder->select('date_create as createdOn');

      $builder->where('id', $userId);
      $builder->where('email_otp', $otp);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function updateUserToken($userId,$token)
    {

      $builder = $this->db->table($this->adminTbl);

      $builder->set('ref_token', $token);
      $builder->set('email_otp', '');

      $builder->where('id', $userId);
      return $builder->update();
    }


    public function getUserById($userId)
    {
      
      $builder = $this->db->table($this->adminTbl);
      
      $builder->select('id as id');
      $builder->select('email as email');
      $builder->select('roal as roal');
      $builder->select('email_otp as emailOtp');
      $builder->select('login_status as logStatus');
      $builder->select('ip_address as myIp');
      $builder->select('last_login as lastLogin');
      $builder->select('date_create as createdOn');
      $builder->select('ref_token as refToken');

      $builder->where('id', $userId);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }    

}
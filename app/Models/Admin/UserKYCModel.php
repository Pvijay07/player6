<?php

namespace App\Models\Admin;
use CodeIgniter\Model;

class UserKYCModel extends Model
{

    protected $db;
    private string $kycTbl;
    public function __construct() {
      $this->db = \Config\Database::connect();
      $this->kycTbl = 'pl6_users_kyc';
    }

    public function getUserKyc($userId)
    {
      
      $builder = $this->db->table($this->kycTbl);
      
      $builder->select('id as id');
      $builder->select('user_id as userId');
      
      $builder->select('aadhaar_full_name as aadrFullName');
      $builder->select('aadhaar_number as aadrNumber');
      $builder->select('aadhaar_dob as aadrDob');
      $builder->select('aadhaar_gender as aadrGender');
      $builder->select('aadhaar_zip as aadrZip');
      $builder->select('aadhaar_state as aadState');
      $builder->select('aadhaar_address as aadrAddr');

      $builder->select('pan_full_name as panFullName');
      $builder->select('pan_number as panNumber');
      $builder->select('pan_dob as panDob');
        
      $builder->where('user_id', $userId);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }

    public function updateKycName($id,$name)
    {

      $builder = $this->db->table($this->kycTbl);

      $builder->set('aadhaar_full_name', $name);

      $builder->where('id', $id);
      return $builder->update();
    }

}
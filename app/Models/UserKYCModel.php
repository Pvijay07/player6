<?php

namespace App\Models;
use CodeIgniter\Model;

class UserKYCModel extends Model
{

    protected $db;
    private string $kycTbl;
    public function __construct() {
      $this->db = \Config\Database::connect();
      $this->kycTbl = 'pl6_users_kyc';
    }


    //- Create User KYC
    public function createUserKyc($data)
    {

      $builder = $this->db->table($this->kycTbl);

      $builder->set('user_id', $data['userId']);

      if (array_key_exists('aadFullName', $data)) {
        $builder->set('aadhaar_full_name', $data['aadFullName']);
      }
      if (array_key_exists('aadNumber', $data)) {
        $builder->set('aadhaar_number', $data['aadNumber']);
      }
      if (array_key_exists('aaddob', $data)) {
        $builder->set('aadhaar_dob', $data['aaddob']);
      }
      if (array_key_exists('aadGen', $data)) {
        $builder->set('aadhaar_gender', $data['aadGen']);
      }
      if (array_key_exists('aadZip', $data)) {
        $builder->set('aadhaar_zip', $data['aadZip']);
      }
      if (array_key_exists('aadAddr', $data)) {
        $builder->set('aadhaar_address', $data['aadAddr']);
      }
      if (array_key_exists('aadState', $data)) {
        $builder->set('aadhaar_state', $data['aadState']);
      }
      
      if (array_key_exists('panFullName', $data)) {
        $builder->set('pan_full_name', $data['panFullName']);
      }
      if (array_key_exists('panNumber', $data)) {
        $builder->set('pan_number', $data['panNumber']);
      }
      if (array_key_exists('panDob', $data)) {
        $builder->set('pan_dob', $data['panDob']);
      }
    

      $builder->set('date_created', $data['dateCreated']);
      $builder->set('date_updated', $data['dateUpdated']);

      return $builder->insert();

    }


    //- Update User KYC
    public function updateUserKyc($userId,$token)
    {

      $builder = $this->db->table($this->kycTbl);

      $builder->set('ref_token', $token);
      $builder->set('otp', '');

      $builder->where('id', $userId);
      return $builder->update();
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


    //- Update User KYC
    public function updateUserPan($userId,$data)
    {

      $builder = $this->db->table($this->kycTbl);

      $builder->set('pan_full_name', $data['panFullName']);
      $builder->set('pan_number', $data['panNumber']);
      $builder->set('pan_dob', $data['panDob']);

      $builder->where('user_id', $userId);
      return $builder->update();
    }


    public function checkAadharExist($data)
    {
      
      $builder = $this->db->table($this->kycTbl);
      
      $builder->select('id as id');
      $builder->select('user_id as userId');
      
      $builder->select('aadhaar_full_name as aadrFullName');
      $builder->select('aadhaar_number as aadrNumber');
      $builder->select('aadhaar_dob as aadrDob');
      $builder->select('aadhaar_gender as aadrGender');
      $builder->select('aadhaar_zip as aadrZip');
      $builder->select('aadhaar_address as aadrAddr');
      $builder->select('aadhaar_state as aadState');
      $builder->select('pan_full_name as panFullName');
      $builder->select('pan_number as panNumber');
      $builder->select('pan_dob as panDob');
        
      $builder->where('aadhaar_full_name', $data['aadFullName']);
      $builder->where('aadhaar_number', $data['aadNumber']);
      $builder->where('aadhaar_dob', $data['aaddob']);
      $builder->where('aadhaar_gender', $data['aadGen']);
      $builder->where('aadhaar_zip', $data['aadZip']);
      $builder->where('aadhaar_address', $data['aadAddr']);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


}
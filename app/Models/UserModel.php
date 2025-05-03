<?php

namespace App\Models;
use CodeIgniter\Model;

class UserModel extends Model
{

    protected $db;
    private string $userTbl;
    private string $userLocTbl;
    private string $bannerTbl;
    public function __construct() {
      $this->db = \Config\Database::connect();
      $this->userTbl = 'pl6_users';
      $this->userLocTbl = 'pl6_users_location';
      $this->bannerTbl = 'pl6_app_banners';
      $this->userBankAccoTbl = 'pl6_user_bank_accounts';
    }


    public function checkEmailExist($email)
    {
      
      $builder = $this->db->table($this->userTbl);
      
      $builder->select('id as id');
      $builder->select('number');
      $builder->select('email');

      $builder->where('email', $email);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function checkUniqueEmailExist($email,$id)
    {
      
      $builder = $this->db->table($this->userTbl);
      
      $builder->select('id as id');
      $builder->select('number');
      $builder->select('email');

      $builder->where('email', $email);
      $builder->where('id !=', $id);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function checkNumberExist($number)
    {
      
      $builder = $this->db->table($this->userTbl);
      
      $builder->select('id as id');
      $builder->select('number');
      $builder->select('email');

      $builder->where('number', $number);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function checkUniqueNumberExist($number,$id)
    {
      
      $builder = $this->db->table($this->userTbl);
      
      $builder->select('id as id');
      $builder->select('number');
      $builder->select('email');

      $builder->where('number', $number);
      $builder->where('id !=', $id);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function updateLoginStatus($data,$userId)
    {

      $builder = $this->db->table($this->userTbl);
      if (array_key_exists('mobileOtp', $data)) {
        $builder->set('mobile_otp', $data['mobileOtp']);
      }
      if (array_key_exists('emailOtp', $data)) {
        $builder->set('email_otp', $data['emailOtp']);
      }
      $builder->set('login_status', '1');
      $builder->set('ip_address', $data['ip']);
      $builder->set('last_login', $data['loginTime']);
      $builder->set('date_updated', $data['loginTime']);

      $builder->where('id', $userId);
      return $builder->update();
    }


    public function createUser($data)
    {

      $builder = $this->db->table($this->userTbl);

      $builder->set('name', $data['name']);
      $builder->set('number', $data['number']);
      $builder->set('email', $data['email']);
      $builder->set('mobile_otp', $data['mobileOtp']);
      $builder->set('email_otp', $data['emailOtp']);
      $builder->set('login_status', $data['loginStatus']);
      $builder->set('ip_address', $data['ip']);
      $builder->set('last_login', $data['loginTime']);
      $builder->set('date_create', $data['loginTime']);
      $builder->set('date_updated', $data['loginTime']);

      return $builder->insert();

    }


    public function getUserDetailsById($userId,$otp)
    {
      
      $builder = $this->db->table($this->userTbl);
      
      $builder->select('id as id');
      $builder->select('name as name');
      $builder->select('number as number');
      $builder->select('email as email');
      $builder->select('mobile_otp as mobileOtp');
      $builder->select('email_otp as emailOtp');
      $builder->select('login_status as logStatus');
      $builder->select('ip_address as myIp');
      $builder->select('last_login as lastLogin');
      $builder->select('date_create as createdOn');
      $builder->select('mobile_verify as mobileVerify');
      $builder->select('email_verify as emailVerify');
      $builder->select('aadhaar_verify as aadhaarVerify');
      $builder->select('pan_verify as panVerify');
      $builder->select('user_status as userStatus');
      
      $builder->where('id', $userId);
      $builder->groupStart();
      $builder->where('mobile_otp', $otp);
      $builder->orWhere('email_otp', $otp);
      $builder->groupEnd();
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function getUserDetailsByNumber($userNumber)
    {
      
      $builder = $this->db->table($this->userTbl);
      
      $builder->select('id as id');
      $builder->select('name as name');
      $builder->select('number as number');
      $builder->select('email as email');
      $builder->select('mobile_otp as mobileOtp');
      $builder->select('email_otp as emailOtp');
      $builder->select('login_status as logStatus');
      $builder->select('ip_address as myIp');
      $builder->select('last_login as lastLogin');
      $builder->select('date_create as createdOn');
      
      $builder->where('number', $userNumber);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }

    
    public function updateUserToken($userId,$token)
    {

      $builder = $this->db->table($this->userTbl);

      $builder->set('ref_token', $token);
      $builder->set('mobile_otp', '');
      $builder->set('email_otp', '');

      $builder->where('id', $userId);
      return $builder->update();
    }


    public function getUserById($userId)
    {
      
      $builder = $this->db->table($this->userTbl);
      
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


    public function deleteUserToken($userId)
    {

      $builder = $this->db->table($this->userTbl);

      $builder->set('ref_token', '');
      $builder->set('mobile_otp', '');
      $builder->set('email_otp', '');

      $builder->where('id', $userId);
      return $builder->update();
    }


    public function updateProfile($userId,$data)
    {

      $builder = $this->db->table($this->userTbl);

      if (array_key_exists('profileImg', $data)) {
        $builder->set('profile_img', $data['profileImg']);
      }

      if (array_key_exists('name', $data)) {
        $builder->set('name', $data['name']);
      }
    
      $builder->where('id', $userId);
      return $builder->update();
    }


    public function updateEmailOtp($userId,$otp)
    {

      $builder = $this->db->table($this->userTbl);
      $builder->set('email_otp', $otp);

      $builder->where('id', $userId);
      return $builder->update();
    }


    public function updateEmail($userId,$email)
    {

      $builder = $this->db->table($this->userTbl);

      $builder->set('email', $email);
      $builder->set('email_otp', '');
      $builder->set('email_verify', '1');


      $builder->where('id', $userId);
      return $builder->update();
    }


    public function updateNumberOtp($userId,$otp)
    {

      $builder = $this->db->table($this->userTbl);

      $builder->set('mobile_otp', $otp);

      $builder->where('id', $userId);
      return $builder->update();
    }


    public function updateNumber($userId,$number)
    {

      $builder = $this->db->table($this->userTbl);

      $builder->set('number', $number);
      $builder->set('mobile_otp', '');
      $builder->set('mobile_verify', '1');

      $builder->where('id', $userId);
      return $builder->update();
    }


    public function logUserLocations($data)
    {

      $builder = $this->db->table( $this->userLocTbl);

      $builder->set('user_id', $data['userId']);
      $builder->set('ip_address', $data['ip']);
      $builder->set('geo_lat', $data['lat']);
      $builder->set('geo_lng', $data['lng']);
      $builder->set('status', $data['status']);
      $builder->set('ip_address', $data['ip']);
      $builder->set('page', $data['page']);
      $builder->set('date_created', gmdate("Y-m-d H:i:s"));
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      return $builder->insert();

    }


    public function getBanners()
    {
      
      $builder = $this->db->table($this->bannerTbl);
      
      $builder->select('id as id');
      $builder->select('name');
      
      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function updateAadharVerify($userId)
    {

      $builder = $this->db->table($this->userTbl);

      $builder->set('aadhaar_verify', '1');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $userId);
      return $builder->update();
    }


    public function rejectAadharVerify($userId)
    {

      $builder = $this->db->table($this->userTbl);

      $builder->set('aadhaar_verify', '2');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $userId);
      return $builder->update();
    }


    public function updatePanVerify($userId)
    {

      $builder = $this->db->table($this->userTbl);

      $builder->set('pan_verify', '1');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $userId);
      return $builder->update();
    }


    public function updatePanReject($userId)
    {

      $builder = $this->db->table($this->userTbl);

      $builder->set('pan_verify', '2');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $userId);
      return $builder->update();
    }

    //- Update Device Token To Send Notificatoins
    public function updateDeviceToken($userId,$token)
    {

      $builder = $this->db->table($this->userTbl); 

      $builder->set('device_token', $token);
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $userId);
      return $builder->update();
    }


    public function getUserToken($userId)
    {
      
      $builder = $this->db->table($this->userTbl);
      
      $builder->select('device_token as token');

      $builder->where('id', $userId);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function getAllUserToken()
    {
      
      $builder = $this->db->table($this->userTbl);
      
      $builder->select('device_token as token');

      $builder->where('device_token !=', '');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function updateMobileVerify($userId)
    {

      $builder = $this->db->table($this->userTbl);

      $builder->set('mobile_verify', '1');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $userId);
      return $builder->update();
    }


    public function updateEmailVerify($userId)
    {

      $builder = $this->db->table($this->userTbl);

      $builder->set('email_verify', '1');
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      $builder->where('id', $userId);
      return $builder->update();
    }


    public function createUserBankAccount($data)
    {

      $builder = $this->db->table($this->userBankAccoTbl);

      $builder->set('user_id', $data['userId']);
      $builder->set('is_primary', $data['status']);
      $builder->set('fund_account_id', $data['fundAccountId']);

      $builder->set('date_created', gmdate("Y-m-d H:i:s"));
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      return $builder->insert();

    }


    public function getAllUserBankAccount($userId)
    {
      
      $builder = $this->db->table($this->userBankAccoTbl);
      
      $builder->select('id as id');
      $builder->select('user_id as userId');
      //- $builder->select('acc_number as accNumber');
      //- $builder->select('ifsc as ifsc');
      //- $builder->select('name as name');
      $builder->select('is_primary as isPrimary');
      $builder->select('fund_account_id as fcId');

      $builder->where('user_id', $userId);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function updateBankVerified($userId)
    {

      $builder = $this->db->table($this->userTbl);
      $builder->set('bank_verify', '1');

      $builder->where('id', $userId);
      return $builder->update();
    }

    public function updateBankPrimary($userId,$bankId)
    {

      $builder = $this->db->table($this->userBankAccoTbl);
      $builder->set('is_primary', '1');

      $builder->where('id', $bankId);
      $builder->where('user_id', $userId);
      return $builder->update();
    }

    public function removeBankPrimary($userId)
    {

      $builder = $this->db->table($this->userBankAccoTbl);
      $builder->set('is_primary', '0');

      $builder->where('user_id', $userId);
      return $builder->update();
    }


    public function updateRZPayCustomerId($userId,$cid)
    {

      $builder = $this->db->table($this->userTbl);
      $builder->set('razorpay_contacts_id', $cid);

      $builder->where('id', $userId);
      return $builder->update();
    }


    public function getUserBankAccount($userId,$bankId)
    {
      
      $builder = $this->db->table($this->userBankAccoTbl);
      
      $builder->select('id as id');
      $builder->select('user_id as userId');
      //- $builder->select('acc_number as accNumber');
      //- $builder->select('ifsc as ifsc');
      //- $builder->select('name as name');
      $builder->select('is_primary as isPrimary');
      $builder->select('fund_account_id as fundAccountId');

      $builder->where('user_id', $userId);
      $builder->where('id', $bankId);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function findUser($name)
    {
      
      $builder = $this->db->table($this->userTbl);
      
      $builder->select('id as id');
      $builder->select('name');
      $builder->select('email');

      $builder->like('name', $name);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }

}

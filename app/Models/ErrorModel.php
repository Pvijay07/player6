<?php

namespace App\Models;
use CodeIgniter\Model;

class ErrorModel extends Model
{

    protected $db;
    private string $errorTbl;
    public function __construct() {
      $this->db = \Config\Database::connect();
      $this->errorTbl = 'pl6_error_log';
    }


    //- Create Error Log
    public function createErrorLog($data){

      $builder = $this->db->table($this->errorTbl);

      $builder->set('user_id', $data['userId']);
      $builder->set('text', $data['text']);
      $builder->set('discription', $data['disc']);
      $builder->set('date_created', gmdate("Y-m-d H:i:s"));
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      return $builder->insert();

    }


}
<?php

namespace App\Models;
use CodeIgniter\Model;

class SselogModel extends Model
{

    protected $db;
    private string $logTbl;
    public function __construct() {
      $this->db = \Config\Database::connect();
      $this->logTbl = 'pl6_app_sse_log';
    }


    //- Create Log
    public function createLog($data){

      $builder = $this->db->table($this->logTbl);

      $builder->set('event_name', $data['eventName']);
      $builder->set('event_data', $data['eventData']);
      $builder->set('date_created', gmdate("Y-m-d H:i:s"));

      return $builder->insert();

    }


    public function getLog()
    {
      
      $builder = $this->db->table($this->logTbl);
      
      $builder->select('id as id');
      $builder->select('event_name as eventName');
      $builder->select('event_data as eventData');
      $builder->select('date_created as dateCreated');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function deleteLog($id)
    {
      
      $builder = $this->db->table($this->logTbl);

      $builder->delete(['id' => $id]);
      
    }


    public function deleteOldLog()
    {
      
      $builder = $this->db->table($this->logTbl);
      $builder->where('date_created <', gmdate("Y-m-d H:i:s", strtotime('-5 minutes')));
      $builder->delete();
      
    }
}
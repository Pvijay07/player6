<?php

namespace App\Models\Admin;
use CodeIgniter\Model;

class TeamsModel extends Model
{

    protected $db;
    private string $teamsTbl;
    public function __construct() {
      $this->db = \Config\Database::connect();
      $this->teamsTbl = 'pl6_teams';
    }


    //- Get Team Details By Id
    public function getTeamById($teamId)
    {
      
      $builder = $this->db->table($this->teamsTbl);
      
      $builder->select('id as teamId');
      $builder->select('crickt_id as crickTeamId');
      $builder->select('name as name');
      $builder->select('short_name as shortName');
      $builder->select('logo as logo');

      $builder->where('id', $teamId);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }

}
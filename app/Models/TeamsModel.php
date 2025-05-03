<?php

namespace App\Models;
use CodeIgniter\Model;

class TeamsModel extends Model
{

    protected $db;
    private string $teamsTbl;
    public function __construct() {
      $this->db = \Config\Database::connect();
      $this->teamsTbl = 'pl6_teams';
    }


    //- Create Team
    public function createTeam($data){

      $builder = $this->db->table($this->teamsTbl);

      $builder->set('crickt_id', $data['cricktId']);
      $builder->set('name', $data['name']);
      $builder->set('short_name', $data['shortName']);
      $builder->set('logo', $data['logo']);
      $builder->set('date_created', gmdate("Y-m-d H:i:s"));
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      return $builder->insert();

    }


    //- Check Team Exist Are Not Using Cricbuzz Team Key
    public function checkTeamExist($crickey)
    {
      
      $builder = $this->db->table($this->teamsTbl);
      
      $builder->select('id as teamId');
      $builder->select('crickt_id as crickTeamId');
      $builder->select('name as name');
      $builder->select('short_name as shortName');
      $builder->select('logo as logo');

      $builder->where('crickt_id', $crickey);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
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


    //- Get Multiple Team Details By (Array of Id)
    public function getMultipleTeams($teamIds)
    {
      
      $builder = $this->db->table($this->teamsTbl);
      
      $builder->select('id as teamId');
      $builder->select('crickt_id as crickTeamId');
      $builder->select('name as name');
      $builder->select('short_name as shortName');
      $builder->select('logo as logo');

      $builder->whereIn('id', $teamIds);
      $builder->limit(2);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


}
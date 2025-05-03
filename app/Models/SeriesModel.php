<?php

namespace App\Models;
use CodeIgniter\Model;

class SeriesModel extends Model
{

    protected $db;
    private string $seriesTbl;
    public function __construct() {
      $this->db = \Config\Database::connect();
      $this->seriesTbl = 'pl6_series';
    }


    //- Check Series Exist Are Not Using Cricbuzz Series Key
    public function checkSeriesExist($crickey)
    {
      
      $builder = $this->db->table($this->seriesTbl);
      
      $builder->select('id as seriesId');
      $builder->select('crick_id as crickSeriesId');
      $builder->select('name as name');
      $builder->select('short_name as SName');
      $builder->select('date as date');
      $builder->select('status as status');
      $builder->select('type as type');

      $builder->where('crick_id', $crickey);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    //- Create Series
    public function createSeries($data)
    {

      $builder = $this->db->table($this->seriesTbl);

      $builder->set('crick_id', $data['serId']);
      $builder->set('name', $data['name']);
      $builder->set('short_name', $data['sname']);
      $builder->set('date', $data['date']);
      $builder->set('status', '1');
      $builder->set('type', $data['type']);
      $builder->set('date_created', gmdate("Y-m-d H:i:s"));
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      return $builder->insert();

    }


    //- Get Series Name By Id
    public function getSeriesById($seriesId)
    {
      
      $builder = $this->db->table($this->seriesTbl);
      
      $builder->select('id as seriesId');
      $builder->select('crick_id as crickSeriesId');
      $builder->select('name as name');
      $builder->select('short_name as SName');
      $builder->select('date as date');
      $builder->select('status as status');
      $builder->select('type as type');

      $builder->where('id', $seriesId);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }
}
<?php

namespace App\Models\Admin;
use CodeIgniter\Model;

class SeriesModel extends Model
{

    protected $db;
    private string $seriesTbl;
    public function __construct() {
      $this->db = \Config\Database::connect();
      $this->seriesTbl = 'pl6_series';
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
<?php

namespace App\Models;
use CodeIgniter\Model;

class NewsModel extends Model
{

    protected $db;
    private string $newsTbl;
    public function __construct() {
      $this->db = \Config\Database::connect();
      $this->newsTbl = 'pl6_news';
    }


    //- Create News
    public function createNews($data)
    {

      $builder = $this->db->table($this->newsTbl);

      $builder->set('title', $data['title']);
      $builder->set('crickn_id', $data['cricknId']);
      $builder->set('short_desc', $data['sdesc']);
      $builder->set('pub_time', $data['pubTime']);
      $builder->set('image', $data['image']);

      $builder->set('date_created', gmdate("Y-m-d H:i:s"));
      $builder->set('date_updated', gmdate("Y-m-d H:i:s"));

      return $builder->insert();
    }


    //- Check News Exist Are Not Using Cricbuzz News Key
    public function checkMatchExist($newskey)
    {
      
      $builder = $this->db->table($this->newsTbl);
      
      $builder->select('id as newsId');
      $builder->select('pub_time as pubTime');
      $builder->select('title as title');
      $builder->select('crickn_id as cricknId');
      $builder->select('short_desc as shortDesc');
      $builder->select('description as description');
      $builder->select('image as image');
      $builder->select('date_created as dateCreated');
      $builder->select('date_updated as dateUpdated');
 
      $builder->where('crickn_id', $newskey);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    public function getNewsById($matchId)
    {
      
      $builder = $this->db->table($this->newsTbl);
      
      $builder->select('id as newsId');
      $builder->select('pub_time as pubTime');
      $builder->select('title as title');
      $builder->select('crickn_id as cricknId');
      $builder->select('short_desc as shortDesc');
      $builder->select('description as description');
      $builder->select('image as image');
      $builder->select('date_created as dateCreated');
      $builder->select('date_updated as dateUpdated');
 
      $builder->where('id', $matchId);
      $builder->limit(1);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    //- Get All News With No Description
    public function getAllNoDescriptionNews()
    {
      
      $builder = $this->db->table($this->newsTbl);
      
      $builder->select('id as newsId');
      $builder->select('pub_time as pubTime');
      $builder->select('title as title');
      $builder->select('crickn_id as cricknId');
      $builder->select('short_desc as shortDesc');
      $builder->select('description as description');
      $builder->select('image as image');
      $builder->select('date_created as dateCreated');
      $builder->select('date_updated as dateUpdated');
 
      $builder->where('status', '0');

      $query = $builder->get();
      $result = $query->getResult();
      return $result;
    }


    //- Update News Description
    public function updateNewsDescription($newsId,$desc)
    {

      $builder = $this->db->table($this->newsTbl);

      $builder->set('description', $desc);
      $builder->set('status', '1');

      $builder->where('id', $newsId);
      return $builder->update();
    }


    public function getLatestNews(){
      $builder = $this->db->table($this->newsTbl);
      
      $builder->select('id as newsId');
      $builder->select('pub_time as pubTime');
      $builder->select('title as title');
      $builder->select('crickn_id as cricknId');
      $builder->select('short_desc as shortDesc');
      $builder->select('description as description');
      $builder->select('image as image');
      $builder->select('date_created as dateCreated');
      $builder->select('date_updated as dateUpdated');
 
      $builder->where('status', '1');
      $builder->orderBy('id', 'DESC');
      $builder->limit(3);

      $query = $builder->get();
      $result = $query->getResult();
      return $result;      
    }


    public function getAllNews(){
      $builder = $this->db->table($this->newsTbl);
      
      $builder->select('id as newsId');
      $builder->select('pub_time as pubTime');
      $builder->select('title as title');
      $builder->select('crickn_id as cricknId');
      $builder->select('short_desc as shortDesc');
      $builder->select('description as description');
      $builder->select('image as image');
      $builder->select('date_created as dateCreated');
      $builder->select('date_updated as dateUpdated');
 
      $builder->where('status', '1');
      $builder->orderBy('id', 'DESC');
      
      $query = $builder->get();
      $result = $query->getResult();
      return $result;      
    }


}
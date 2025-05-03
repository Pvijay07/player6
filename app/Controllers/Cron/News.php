<?php

namespace App\Controllers\Cron;
use App\Controllers\BaseController;
use App\Models\NewsModel;
use Config\Services;

class News extends BaseController
{

    public function __construct(){
      helper('Common');
    }


    //- Get Latest 3 News
    public function getDailyNews()
    { 

      try {

        $curl = \Config\Services::curlrequest();
        $response = $curl->request('get', 'https://cricbuzz-cricket.p.rapidapi.com/news/v1/index', ['headers' => ['X-RapidAPI-Host' => getenv('cricbuzzHost'), 'X-RapidAPI-Key' => getenv('cricbuzzKey') ]]);
        $responce = $response->getStatusCode();
        $error = '';

      } catch (\Exception $e) {
        $error = $e->getMessage();
        $responce = '500';
      }

      if ($responce == 200) {

        if (!empty($response->getBody())) {

          $responce = json_decode($response->getBody());
          $responce = $responce->storyList;

          $newModel = new NewsModel();

          if (count($responce) > 0) {
            $i = 1;
            foreach ($responce as $rkey => $rvalue) {
              
              if (property_exists($rvalue, 'story')) {
                
                if ($i < 4) {
                  

                  $sdate = $rvalue->story->pubTime / 1000; // Convert milliseconds to seconds
                  $sdate = date('Y-m-d H:i:s', $sdate);

                  $data['title'] = $rvalue->story->hline;
                  $data['cricknId'] = $rvalue->story->id;
                  $data['sdesc'] = $rvalue->story->intro;
                  $data['pubTime'] = $sdate;
                  $data['image'] = $rvalue->story->imageId;

                  $newsDetails = $newModel->checkMatchExist($data['cricknId']);

                  if (count($newsDetails) == 0) {
                    $newModel->createNews($data);
                  }

                }

                $i = $i+1;

              }

            }
          }

        }

      }else{

        //- Update Error Log 
        if (!empty($error)) {
          
          $data['userId'] = '0';
          $data['text'] = 'Unable To Fetch News';
          $data['disc'] = $error;
          CreateErrorLog($data);
          unset($data);

        }else{

          $data['userId'] = '0';
          $data['text'] = 'Unable To Fetch News';
          $data['disc'] = 'Cricbuzz API not responding need manual inspection';
          CreateErrorLog($data);
          unset($data);

        }

        //- Send Message To Admin
        $data['subject'] = 'News Fetch Failed';
        $data['message'] = 'News Fetch error. Please run it manually';
        SendErrorEmail($data);
        unset($data);

      }

    }


    //- Get News Description
    public function getNewsDescription()
    {

      //- Get All News With Out Description
      
      $newModel = new NewsModel();
      $allNews = $newModel->getAllNoDescriptionNews();

      if (count($allNews) > 0) {
        foreach ($allNews as $nkey => $nvalue) {

          try {

            $curl = \Config\Services::curlrequest();
            $response = $curl->request('get', 'https://cricbuzz-cricket.p.rapidapi.com/news/v1/detail/'.$nvalue->cricknId, ['headers' => ['X-RapidAPI-Host' => getenv('cricbuzzHost'), 'X-RapidAPI-Key' => getenv('cricbuzzKey') ]]);
            $responce = $response->getStatusCode();
            $error = '';

          } catch (\Exception $e) {
            
            $error = $e->getMessage();
            $responce = '500';

          }

          if ($responce == 200) {

            if (!empty($response->getBody())) {

              $responce = json_decode($response->getBody());

              if (property_exists($responce, 'format')) {
                $format = $responce->format;
              }else{
                $format = array();
              }
              
              if (property_exists($responce, 'content')) {
                $responce = $responce->content;
              }else{
                $responce = array();
              }          

              $tmpBold = array();
              $tmpLinks = array();
              if (count($format) > 0) {
                foreach ($format as $fkey => $fvalue) {

                  if ($fvalue->type == 'bold') {
                    if (property_exists($fvalue, 'value')) {
                      if (count($fvalue->value) > 0) {
                        foreach ($fvalue->value as $fvkey => $fvvalue) {
                          if (property_exists($fvvalue, 'value')) {
                            array_push($tmpBold, array('id' => $fvvalue->id, 'value' => $fvvalue->value));
                          }
                        }
                      }
                    }
                  }

                  if ($fvalue->type == 'links') {
                    if (property_exists($fvalue, 'value')) {
                      if (count($fvalue->value) > 0) {
                        foreach ($fvalue->value as $fvkey => $fvvalue) {
                          if (property_exists($fvvalue, 'value')) {
                            array_push($tmpLinks, array('id' => $fvvalue->id, 'value' => $fvvalue->value));
                          }
                        }
                      }
                    }
                  }         

                }
              }

              if (count($responce) > 0) {

                $description = '';

                foreach ($responce as $rkey => $rvalue) {
                  if (property_exists($rvalue, 'content')) {
                    
                    $txt = updateBoldDescription($rvalue->content->contentValue,$tmpBold);
                    if (!empty($txt)) {

                      $txtnew = updateDescription($txt,$tmpLinks);
                      if (!empty($txtnew)) {
                        $tmpTxt = $txtnew;
                      }else{
                        $tmpTxt = $txt;
                      }
                      
                    }else{

                      $txtnew = updateDescription($rvalue->content->contentValue,$tmpLinks);
                      if (!empty($txtnew)) {
                        $tmpTxt = $txtnew;
                      }else{
                        $tmpTxt = $rvalue->content->contentValue;
                      }

                    }

                    $description = $description.'<p>'.$tmpTxt.'</p>';

                  }
                }

                //- Update Description
                $newModel->updateNewsDescription($nvalue->newsId,$description);

              }

            }

          }else{

            //- Update Error Log 
            if (!empty($error)) {
              
              $data['userId'] = '0';
              $data['text'] = 'Unable To Fetch News Description';
              $data['disc'] = $error;
              CreateErrorLog($data);
              unset($data);

            }else{

              $data['userId'] = '0';
              $data['text'] = 'Unable To Fetch News Description';
              $data['disc'] = 'Cricbuzz API not responding need manual inspection';
              CreateErrorLog($data);
              unset($data);

            }

            //- Send Message To Admin
            $data['subject'] = 'News Description Fetch Failed';
            $data['message'] = 'News Description Fetch error. Please run it manually';
            SendErrorEmail($data);
            unset($data);

          }
        
        }
      }

    }


    //- Get Latest News
    public function getLatestNews()
    {

      $newModel = new NewsModel();
      $allNews = $newModel->getLatestNews();

      $data = array();

      if (count($allNews) > 0) {
        foreach ($allNews as $akey => $avalue) {

          array_push($data, array('newsId' => $avalue->newsId, 'title' => $avalue->title, 'shortDesc' => $avalue->shortDesc, 'image' => $avalue->image, 'pubTime' => $avalue->pubTime ));
          
        }
      }

      $msg = array('status' => 200,'data' => $data);
      return $this->response->setStatusCode(200)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg); 

    }


    //- Get Latest News
    public function getAllNews()
    {

      $newModel = new NewsModel();
      $allNews = $newModel->getAllNews();

      $data = array();

      if (count($allNews) > 0) {
        foreach ($allNews as $akey => $avalue) {

          array_push($data, array('newsId' => $avalue->newsId, 'title' => $avalue->title, 'shortDesc' => $avalue->shortDesc, 'image' => $avalue->image, 'pubTime' => $avalue->pubTime ));
          
        }
      }

      $msg = array('status' => 200,'data' => $data);
      return $this->response->setStatusCode(200)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg); 

    }


    //- Get News Details
    public function getNewsDetails()
    {
      
      $uri = current_url(true);
      $newId = $uri->getSegment(2);

      $newModel = new NewsModel();
      $newDetails = $newModel->getNewsById($newId);

      $data = array();

      if (count($newDetails) > 0) {
        $data['pubTime'] = $newDetails[0]->pubTime;
        $data['title'] = $newDetails[0]->title;
        $data['description'] = $newDetails[0]->description;
        $data['image'] = $newDetails[0]->image;
      }

      $msg = array('status' => 200,'data' => $data);
      return $this->response->setStatusCode(200)
                  ->setHeader('Access-Control-Allow-Origin', '*')
                  ->setHeader('Access-Control-Allow-Headers', 'Origin')
                  ->setContentType('application/json', 'utf-8')
                  ->setJSON($msg); 

    }


}
<?php

namespace App\Controllers\Cron;
use App\Controllers\BaseController;
use App\Models\SeriesModel;
use Config\Services;

class Series extends BaseController
{

    public function __construct(){
      helper('Common');
    }

    //- Get All Series In a Month From Sportmonks Api
    public function getSeries()
    {
      
      //- Get
      try {
        
        $curl = \Config\Services::curlrequest();
        $response = $curl->request('get', getenv('leagues').'?api_token='.getenv('sportMonksKey'));
        $responce = $response->getStatusCode();
        $error = '';
      } catch (\Exception $e) {
        $error = $e->getMessage();
        $responce = '500';
      }


      if ($responce == '200') {

        $responce = json_decode($response->getBody());
        $responce = $responce->data;
        
        if (count($responce) > 0) {
          $seriesModel = new SeriesModel();
          foreach ($responce as $rekey => $revalue) {            

            //- Check Series Exist
            $seriesDeails = $seriesModel->checkSeriesExist($revalue->id);
            if (count($seriesDeails) == '0') {

              //- Creatre Series
              $data['serId'] = $revalue->id;
              $data['name'] = $revalue->name;
              $data['sname'] = $revalue->code;
              $data['date'] = date("Y-m-d H:i:s", strtotime($revalue->updated_at));
              $data['type'] = $revalue->type;
              CreateSeries($data);
              unset($data);

            }

          }
        }

      }else{

        //- Update Error Log 
        if (!empty($error)) {
          
          $data['userId'] = '0';
          $data['text'] = 'Unable To Fetch League';
          $data['disc'] = $error;
          CreateErrorLog($data);
          unset($data);

        }else{
 
          $data['userId'] = '0';
          $data['text'] = 'Unable To Fetch League';
          $data['disc'] = 'Sportmonks API not responding need manual inspection';
          CreateErrorLog($data);
          unset($data);

        }

        
        //- Send Message To Admin
        $data['subject'] = 'Leagues Failed'; 
        $data['message'] = 'Leagues Api error. Please run it manually';
        SendErrorEmail($data);
        unset($data);

      }

    }

}

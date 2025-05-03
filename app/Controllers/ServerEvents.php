<?php

namespace App\Controllers;
use App\Controllers\BaseController;
use App\Libraries\jwtLibrary;
use Config\Services;
use App\Models\SselogModel;

class ServerEvents extends BaseController
{


  public function watchEvents(){

    header("X-Accel-Buffering: no");
    header("Content-Type: text/event-stream");
    header("Cache-Control: no-cache");        

    while (1) {

      $curDate = gmdate("Y-m-d H:i:s");

      $logModel = new SselogModel();
      $logData = $logModel->getLog();
      if (count($logData) > 0) {
        foreach ($logData as $ldkey => $ldvalue) {

          $curdate = gmdate("Y-m-d H:i:s",strtotime("-30 seconds"));
          if ($curdate > $ldvalue->dateCreated) {
            $logModel->deleteLog($ldvalue->id);
          }

          /*
          if ($ldvalue->eventName == 'downGradOpt') {
            $logModel->deleteLog($ldvalue->id);
          }*/

          $curdate1 = gmdate("Y-m-d H:i:s",strtotime("-2 seconds"));
          if (($ldvalue->eventName == 'conFnalSel' || $ldvalue->eventName == 'playerScore') && $curdate1 > $ldvalue->dateCreated) {
            $logModel->deleteLog($ldvalue->id);
          }
     
          echo "event: $ldvalue->eventName" . PHP_EOL;
          echo "data: $ldvalue->eventData" . PHP_EOL;
          echo PHP_EOL;
          
        }
      }else{

        $msg = array('data' => $curDate);
        $msg = json_encode($msg);
        echo "event: ping" . PHP_EOL;
        echo "data: $msg" . PHP_EOL;
        echo PHP_EOL;

      }


      // flush the output buffer and send echoed messages to the browser
      while (ob_get_level() > 0) {
        ob_end_flush();
      }

      flush();

      // break the loop if the client aborted the connection (closed the page)
      if ( connection_aborted() ){
        break;
      }

      // sleep for 1 second before running the loop again
      sleep(1);
    }

  }

}
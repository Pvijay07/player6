<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): object
    {
        $msg = array('error' => 'Page not found');
        return $this->response->setStatusCode(404)
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'Origin')
                    ->setContentType('application/json', 'utf-8')
                    ->setJSON($msg);
    }

}

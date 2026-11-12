<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Admin.php';

class Admin_menu extends Admin
{
    public function menu_index()
    {
        $this->module('system', 'menu');
    }
}

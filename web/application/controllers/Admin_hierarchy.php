<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Admin.php';

class Admin_hierarchy extends Admin
{
    public function hierarchy_index()
    {
        $this->placeholder('hr', 'hierarchy');
    }
}

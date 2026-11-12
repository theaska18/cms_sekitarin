<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Admin.php';

class Admin_approval extends Admin
{
    public function approval_index()
    {
        $this->placeholder('system', 'approval');
    }
}

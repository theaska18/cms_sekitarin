<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Admin.php';

class Admin_approvalFlow extends Admin
{
    public function approvalFlow_index()
    {
        $this->placeholder('hr', 'approvalFlow');
    }
}

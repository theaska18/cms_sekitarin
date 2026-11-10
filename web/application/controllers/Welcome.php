<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller
{
    public function index()
    {
        $this->load->library('admin_identity');
        $admin_user = $this->admin_identity->current_user();

        $this->load->view('welcome_message', array(
            'web_name' => getenv('WEB_NAME') ?: 'CMS',
            'admin_user' => $admin_user,
        ));
    }
}

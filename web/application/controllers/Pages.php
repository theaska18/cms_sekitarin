<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pages extends CI_Controller
{
    private $web_name;
    private $admin_user;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('property_store');
        $this->web_name = $this->property_store->web_name('CMSNivo');
        $this->load->library('admin_identity');
        $this->admin_user = $this->admin_identity->current_user();
    }

    public function features()
    {
        if (!$this->redirect_if_public_landing_disabled()) {
            return;
        }
        $this->render_page('features', 'Features');
    }

    public function about()
    {
        if (!$this->redirect_if_public_landing_disabled()) {
            return;
        }
        $this->render_page('about', 'About');
    }

    public function contact()
    {
        if (!$this->redirect_if_public_landing_disabled()) {
            return;
        }
        $this->render_page('contact', 'Contact');
    }

    private function redirect_if_public_landing_disabled()
    {
        $this->load->library('property_store');
        if ($this->property_store->landing_page_enabled()) {
            return TRUE;
        }
        redirect('/admin', 'location', 303);
        return FALSE;
    }

    public function login()
    {
        header('Location: /login', TRUE, 303);
        exit;
    }

    public function admin()
    {
        $this->output->set_status_header(200);
        $this->output->set_output('');
    }

    private function render_page($page, $page_title)
    {
        $data = array(
            'web_name' => $this->web_name,
            'page_title' => $page_title,
            'current_page' => $page,
            'admin_user' => $this->admin_user,
        );

        $this->load->view('pages/header', $data);
        $this->load->view('pages/' . $page, $data);
        $this->load->view('pages/footer', $data);
    }
}

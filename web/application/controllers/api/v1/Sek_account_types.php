<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Sek_api_base.php';

/** Public reference data for account types. */
class Sek_account_types extends Sek_api_base
{
    public function index()
    {
        if (!$this->require_method('GET')) {
            return;
        }
        $data = array();
        foreach ($this->sek_account_model->types() as $type) {
            $data[] = array(
                'type_code' => (string) $type['type_code'],
                'label' => (string) $type['label_id'],
                'label_en' => (string) $type['label_en'],
                'icon' => (string) $type['icon'],
                'allows_presence' => (int) $type['allows_presence'] === 1,
                'allows_services' => (int) $type['allows_services'] === 1,
            );
        }
        $this->output->set_header('Cache-Control: public, max-age=3600');
        $this->api_response->ok(array('data' => $data));
    }
}

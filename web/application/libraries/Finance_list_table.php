<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Shared shell for Finance DataTable list panels. */
class Finance_list_table
{
    public function open($id, $options = array())
    {
        $panel = isset($options['panel_class']) ? $options['panel_class'] : 'overflow-hidden border border-slate-200 bg-white shadow-sm';
        $wrapper = isset($options['wrapper_class']) ? $options['wrapper_class'] : 'overflow-x-auto';
        $table = isset($options['table_class']) ? $options['table_class'] : 'finance-list-table min-w-full whitespace-nowrap text-sm';
        $attrs = isset($options['table_attrs']) ? (string) $options['table_attrs'] : '';
        return '<section class="' . html_escape($panel) . '"><div class="' . html_escape($wrapper) . '"><table id="' . html_escape($id) . '" class="' . html_escape($table) . '"' . ($attrs !== '' ? ' ' . $attrs : '') . '>';
    }

    public function close()
    {
        return '</table></div></section>';
    }
}

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
        return '<section class="' . html_escape($panel) . '"><div class="finance-table-scroll" data-table-scroll><div class="finance-table-scroll-viewport ' . html_escape($wrapper) . '"><table id="' . html_escape($id) . '" class="' . html_escape($table) . '"' . ($attrs !== '' ? ' ' . $attrs : '') . '>';
    }

    public function close()
    {
        return '</table></div>'
            . '<span class="finance-table-fade is-left" data-table-fade="left" aria-hidden="true"></span>'
            . '<span class="finance-table-fade is-right" data-table-fade="right" aria-hidden="true"></span>'
            . '</div></section>';
    }

    /**
     * Empty action-column header. The label stays accessible through aria-label
     * but is not rendered, and the column is skipped by CSV/PDF export.
     */
    public function action_header($label = '', $class = 'w-12')
    {
        $label = is_string($label) && trim($label) !== '' ? trim($label) : 'Actions';
        $class = is_string($class) && trim($class) !== '' ? trim($class) : 'w-12';
        return '<th scope="col" aria-label="' . html_escape($label) . '" data-export-ignore="1" class="' . html_escape($class) . '"></th>';
    }
}

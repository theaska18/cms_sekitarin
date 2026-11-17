<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$menu_access_rows = isset($menu_access_rows) && is_array($menu_access_rows) ? $menu_access_rows : array();
$menu_access_table_id = isset($menu_access_table_id) && $menu_access_table_id !== ''
    ? (string) $menu_access_table_id
    : 'menu-access-table';
$menu_access_write_available = !empty($menu_access_write_available);
$labels = isset($labels) && is_array($labels) ? $labels : array();
$menu_access_label = function ($key, $fallback) use ($labels) {
    return isset($labels[$key]) && $labels[$key] !== '' ? $labels[$key] : $fallback;
};
$menu_access_language = array(
    'emptyTable' => $menu_access_label('admin_dt_empty', 'No menus available.'),
    'info' => $menu_access_label('admin_dt_info', 'Showing _START_ to _END_ of _TOTAL_ entries'),
    'infoEmpty' => $menu_access_label('admin_dt_info_empty', 'Showing 0 to 0 of 0 entries'),
    'infoFiltered' => $menu_access_label('admin_dt_info_filtered', '(filtered from _MAX_ total entries)'),
    'lengthMenu' => $menu_access_label('admin_dt_length', 'Show _MENU_ entries'),
    'loadingRecords' => $menu_access_label('admin_dt_loading', 'Loading…'),
    'processing' => $menu_access_label('admin_dt_processing', 'Processing…'),
    'search' => $menu_access_label('admin_dt_search', 'Search:'),
    'zeroRecords' => $menu_access_label('admin_dt_zero', 'No matching menus found.'),
    'paginate' => array(
        'first' => $menu_access_label('admin_dt_first', 'First'),
        'last' => $menu_access_label('admin_dt_last', 'Last'),
        'next' => $menu_access_label('admin_dt_next', 'Next'),
        'previous' => $menu_access_label('admin_dt_previous', 'Previous'),
    ),
);
$menu_access_json_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<div class="overflow-x-auto rounded-b-2xl">
    <table id="<?= html_escape($menu_access_table_id) ?>" class="min-w-full divide-y divide-slate-200 text-left text-sm"
           data-menu-access-table
           data-menu-access-datatable="<?= html_escape(isset($menu_access_datatable_url) ? $menu_access_datatable_url : '') ?>"
           data-menu-access-toggle="<?= html_escape(isset($menu_access_toggle_url) ? $menu_access_toggle_url : '') ?>"
           data-menu-access-write="<?= $menu_access_write_available ? '1' : '0' ?>"
           data-menu-access-page-length="25"
           data-menu-access-saving="<?= html_escape($menu_access_label('admin_menu_access_saving', 'Saving…')) ?>"
           data-menu-access-saved="<?= html_escape($menu_access_label('admin_menu_access_saved', 'Saved')) ?>"
           data-menu-access-save-error="<?= html_escape($menu_access_label('admin_menu_access_save_error', 'Could not save the change.')) ?>"
           data-menu-access-language="<?= html_escape(json_encode($menu_access_language, $menu_access_json_flags)) ?>">
        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
            <tr>
                <th scope="col" class="min-w-[8rem] px-2 py-3.5 sm:px-6"><?= html_escape($menu_access_label('admin_menu_group', 'Group')) ?></th>
                <th scope="col" class="px-2 py-3.5 sm:px-6"><?= html_escape($menu_access_label('admin_menu', 'Menu')) ?></th>
                <th scope="col" class="min-w-[12rem] px-2 py-3.5 sm:px-6"><?= html_escape($menu_access_label('admin_menu_access', 'Access')) ?></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($menu_access_rows as $menu_access_row): ?>
                <?php
                $menu_access_row = is_array($menu_access_row) ? $menu_access_row : array();
                $menu_access_row_code = isset($menu_access_row['menu_code']) ? (string) $menu_access_row['menu_code'] : '';
                ?>
                <tr data-menu-row data-menu-code="<?= html_escape($menu_access_row_code) ?>" class="transition hover:bg-slate-50/80">
                    <td class="px-2 py-3 text-slate-700 sm:px-6 sm:py-4"><?= html_escape(isset($menu_access_row['group_label']) ? $menu_access_row['group_label'] : '') ?></td>
                    <td class="px-2 py-3 sm:px-6 sm:py-4">
                        <?php $this->load->view('admin/menu_access/_menu_cell', array(
                            'menu_access_row' => $menu_access_row,
                            'menu_access_write_available' => $menu_access_write_available,
                        )); ?>
                    </td>
                    <td class="px-2 py-3 sm:px-6 sm:py-4">
                        <?php $this->load->view('admin/menu_access/_access_cell', array(
                            'menu_access_row' => $menu_access_row,
                            'menu_access_write_available' => $menu_access_write_available,
                        )); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

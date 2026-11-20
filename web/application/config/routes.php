<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
| -------------------------------------------------------------------------
*/
$route['default_controller'] = 'welcome';
$route['features'] = 'pages/features';
$route['about'] = 'pages/about';
$route['contact'] = 'pages/contact';
$route['login'] = 'auth/index';
$route['logout'] = 'auth/logout';
$route['api/update/check'] = 'update_api/check';
$route['api/update/package/(:any)'] = 'update_api/package/$1';
$route['admin'] = 'admin/index';
$route['admin/csrf-token'] = 'admin/csrf_token';
$route['admin/notifications'] = 'admin_notifications/index';
$route['admin/notifications/mark-viewed'] = 'admin_notifications/mark_viewed';
$route['admin/notifications/delete'] = 'admin_notifications/delete';
$route['admin/notifications/(:num)'] = 'admin_notifications/index/$1';
$route['admin/setting'] = 'admin/setting';
$route['admin/profile'] = 'admin/profile';
$route['admin/settings'] = 'admin/placeholder/admin/settings';
$route['admin/system/sequence/menu-lookup'] = 'admin_sequence/sequence_menu_lookup';
$route['admin/hr/hierarchy/action'] = 'admin_hierarchy/action';
$route['admin/hr/hierarchy/csrf_token'] = 'admin_hierarchy/hierarchy_csrf_token';
$route['api/finance/sales-invoices'] = 'finance_api/sales_invoices';
$route['api/finance/sales-invoices/(:num)'] = 'finance_api/sales_invoices/$1';
$route['api/finance/purchase-bills'] = 'finance_api/purchase_bills';
$route['api/finance/purchase-bills/(:num)'] = 'finance_api/purchase_bills/$1';
$route['admin/finance/sales_invoice_inbox'] = 'admin_finance_integration/sales_invoice_index';
$route['admin/finance/sales_invoice_inbox/data'] = 'admin_finance_integration/sales_invoice_data';
$route['admin/finance/sales_invoice_inbox/draft/(:num)'] = 'admin_finance_integration/sales_invoice_draft/$1';
$route['admin/finance/purchase_bill_inbox'] = 'admin_finance_integration/purchase_bill_index';
$route['admin/finance/purchase_bill_inbox/data'] = 'admin_finance_integration/purchase_bill_data';
$route['admin/finance/purchase_bill_inbox/draft/(:num)'] = 'admin_finance_integration/purchase_bill_draft/$1';
$route['admin/finance/tax_configuration/export'] = 'admin_finance/tax_export';
$route['admin/finance/tax_configuration/import'] = 'admin_finance/tax_import';
$route['admin/finance/categories/export'] = 'admin_finance/category_export';
$route['admin/finance/categories/import'] = 'admin_finance/category_import';
$route['admin/finance/fiscal_year/generate'] = 'admin_finance/fiscal_year_generate';
$route['admin/finance/cancel_request'] = 'admin_finance/cancel_request';
$route['admin/finance/delete_draft/(:any)/(:num)'] = 'admin_finance/delete_draft/$1/$2';
$route['admin/finance/chart_of_accounts/export'] = 'admin_finance/chart_of_accounts_export';
$route['admin/finance/chart_of_accounts/import'] = 'admin_finance/chart_of_accounts_import';
$route['admin/finance/(:any)/export'] = 'admin_finance/report_export/$1';
$route['admin/finance/(:any)/view/(:num)'] = 'admin_finance/view/$1/$2';
$route['admin/finance/(:any)/edit/(:num)'] = 'admin_finance/edit/$1/$2';
$route['admin/finance/(:any)/post/(:num)'] = 'admin_finance/post/$1/$2';
$route['admin/finance/(:any)/submit_approval/(:num)'] = 'admin_finance/submit_approval/$1/$2';
$route['admin/finance/(:any)/lookup/(:any)'] = 'admin_finance/lookup/$1/$2';
$route['admin/finance/(:any)/data'] = 'admin_finance/data/$1';
$route['admin/finance/(:any)/new'] = 'admin_finance/new_record/$1';
$route['admin/finance/(:any)'] = 'admin_finance/index/$1';

/*
 * Generic menu/action routing. The group segment is deliberately opaque;
 * adding an item to a menu manifest does not require a router entry.
 * The menu slug selects Admin_{slug}; deeper segments select
 * {slug}_{action}, with trailing URL segments passed as parameters.
 */
$route['admin/(:any)/user/employees'] = 'admin_user/employee_lookup';
$route['admin/(:any)/user/roles/(:num)'] = 'admin_user/role_options/$2';
$route['admin/(:any)/tenant/menu/(:any)'] = 'admin_tenant/tenant_menu/$2';
$route['admin/(:any)/tenant/menu/data/(:any)'] = 'admin_tenant/tenant_menu_data/$2';
$route['admin/(:any)/tenant/menu/toggle/(:any)'] = 'admin_tenant/tenant_menu_toggle/$2';
$route['admin/(:any)/role/menu/(:any)/(:num)/data'] = 'admin_role/role_menu_data/$2/$3';
$route['admin/(:any)/role/menu/(:any)/(:num)/toggle'] = 'admin_role/role_menu_toggle/$2/$3';
$route['admin/(:any)/(:any)/(:any)/(:any)/(:any)'] = 'admin_$2/$2_$3/$4/$5';
$route['admin/(:any)/(:any)/(:any)/(:any)'] = 'admin_$2/$2_$3/$4';
$route['admin/(:any)/(:any)/(:any)'] = 'admin_$2/$2_$3';
$route['admin/(:any)/(:any)'] = 'admin_$2/$2_index';

$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

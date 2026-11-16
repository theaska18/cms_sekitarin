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
$route['admin'] = 'admin/index';
$route['admin/notifications'] = 'admin_notifications/index';
$route['admin/notifications/mark-viewed'] = 'admin_notifications/mark_viewed';
$route['admin/notifications/delete'] = 'admin_notifications/delete';
$route['admin/notifications/(:num)'] = 'admin_notifications/index/$1';
$route['admin/setting'] = 'admin/setting';
$route['admin/profile'] = 'admin/profile';
$route['admin/settings'] = 'admin/placeholder/admin/settings';
$route['admin/finance/(:any)/edit/(:num)'] = 'admin_finance/edit/$1/$2';
$route['admin/finance/(:any)/post/(:num)'] = 'admin_finance/post/$1/$2';
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
$route['admin/(:any)/(:any)/(:any)/(:any)/(:any)'] = 'admin_$2/$2_$3/$4/$5';
$route['admin/(:any)/(:any)/(:any)/(:any)'] = 'admin_$2/$2_$3/$4';
$route['admin/(:any)/(:any)/(:any)'] = 'admin_$2/$2_$3';
$route['admin/(:any)/(:any)'] = 'admin_$2/$2_index';

$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

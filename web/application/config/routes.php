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
$route['admin/setting'] = 'admin/setting';
$route['admin/profile/photo/(:any)'] = 'admin/profile_photo/$1';
$route['admin/profile'] = 'admin/profile';
$route['admin/settings'] = 'admin/placeholder/admin/settings';
$route['admin/hr/employee'] = 'admin_employee/employee_index';
$route['admin/hr/employee/new'] = 'admin_employee/employee_new';
$route['admin/hr/employee/change/(:num)'] = 'admin_employee/employee_change/$1';
$route['admin/hr/employee/photo/(:num)'] = 'admin_employee/employee_photo/$1';
$route['admin/hr/employee/profile-photo/(:any)'] = 'admin_employee/employee_profile_photo/$1';
$route['admin/hr/employee/delete'] = 'admin_employee/employee_delete';
$route['admin/system/approval'] = 'admin/placeholder/system/approval';
$route['admin/hr/hierarchy'] = 'admin/placeholder/hr/hierarchy';
$route['admin/hr/approvalFlow'] = 'admin/placeholder/hr/approvalFlow';
$route['admin/system/property'] = 'admin_property/property_index';
$route['admin/system/property/new'] = 'admin_property/property_new';
$route['admin/system/property/change/(:any)'] = 'admin_property/property_change/$1';
$route['admin/system/property/delete'] = 'admin_property/property_delete';
$route['admin/system/tenant'] = 'admin_tenant/tenant_index';
$route['admin/system/tenant/new'] = 'admin_tenant/tenant_new';
$route['admin/system/tenant/change/(:any)'] = 'admin_tenant/tenant_change/$1';
$route['admin/system/tenant/delete'] = 'admin_tenant/tenant_delete';
$route['admin/system/tenant/menu/(:any)'] = 'admin_tenant/tenant_menu/$1';
$route['admin/system/parameter'] = 'admin_parameter/parameter_index';
$route['admin/system/parameter/new'] = 'admin_parameter/parameter_new';
$route['admin/system/parameter/change/(:any)'] = 'admin_parameter/parameter_change/$1';
$route['admin/system/parameter/delete'] = 'admin_parameter/parameter_delete';
$route['admin/system/menu'] = 'admin/module/system/menu';
$route['admin/system/sequence'] = 'admin/module/system/sequence';
$route['admin/system/sequence/new'] = 'admin_sequence/sequence_new';
$route['admin/system/sequence/change/(:any)'] = 'admin_sequence/sequence_change/$1';
$route['admin/system/sequence/delete'] = 'admin_sequence/sequence_delete';
$route['admin/application/role'] = 'admin_role/role_index';
$route['admin/application/role/new'] = 'admin_role/role_new';
$route['admin/application/role/change/(:any)/(:num)'] = 'admin_role/role_change/$1/$2';
$route['admin/application/role/menu/(:any)/(:num)'] = 'admin_role/role_menu/$1/$2';
$route['admin/application/role/change/(:any)'] = 'admin_role/role_change/$1';
$route['admin/application/role/menu/(:any)'] = 'admin_role/role_menu/$1';
$route['admin/application/role/delete'] = 'admin_role/role_delete';
$route['admin/app/sequence'] = 'admin/module/app/sequence';
$route['admin/app/role'] = 'admin/module/app/role';
$route['admin/app/user'] = 'admin/module/app/user';
$route['admin/application/employee'] = 'admin/module/app/sequence';
$route['admin/application/sequence'] = 'admin/module/app/sequence';

$route['admin/application/user'] = 'admin_user/user_index';
$route['admin/application/user/new'] = 'admin_user/user_new';
$route['admin/application/user/change/(:num)'] = 'admin_user/user_change/$1';
$route['admin/application/user/password/(:num)'] = 'admin_user/user_password/$1';
$route['admin/application/user/block'] = 'admin_user/user_toggle_block';
$route['admin/application/user/roles/(:num)'] = 'admin_user/role_options/$1';
$route['admin/application/user/employees'] = 'admin_user/employee_lookup';
$route['admin/application/user/delete'] = 'admin_user/user_delete';
$route['admin/application/user/api-secret'] = 'admin_user/api_secret';
$route['system/property'] = 'admin/module/system/property';
$route['system/parameter'] = 'admin/module/system/parameter';
$route['system/menu'] = 'admin/module/system/menu';
$route['system/sequence'] = 'admin/module/system/sequence';
$route['app/sequence'] = 'admin/module/app/sequence';
$route['app/role'] = 'admin/module/app/role';
$route['app/user'] = 'admin/module/app/user';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

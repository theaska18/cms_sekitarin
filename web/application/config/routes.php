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

// Sekitar mobile API v1.
$route['api/v1/app/latest'] = 'api/v1/sek_app/latest';
$route['api/v1/app/download'] = 'api/v1/sek_app/download';
$route['api/v1/auth/register'] = 'api/v1/sek_auth/register';
$route['api/v1/auth/login'] = 'api/v1/sek_auth/login';
$route['api/v1/auth/refresh'] = 'api/v1/sek_auth/refresh';
$route['api/v1/auth/logout'] = 'api/v1/sek_auth/logout';
$route['api/v1/auth/email/verify'] = 'api/v1/sek_auth/verify_email';
$route['api/v1/auth/email/resend'] = 'api/v1/sek_auth/resend_verification';
$route['api/v1/auth/password/forgot'] = 'api/v1/sek_auth/forgot_password';
$route['api/v1/auth/password/reset'] = 'api/v1/sek_auth/reset_password';
$route['api/v1/me'] = 'api/v1/sek_me/index';
$route['api/v1/me/consents/(:any)'] = 'api/v1/sek_me/consent/$1';
$route['api/v1/me/consents'] = 'api/v1/sek_me/consents';
$route['api/v1/me/devices/(:num)'] = 'api/v1/sek_me/delete_device/$1';
$route['api/v1/me/devices'] = 'api/v1/sek_me/devices';
$route['api/v1/me/push-token'] = 'api/v1/sek_me/push_token';
$route['api/v1/notifications/read-chat'] = 'api/v1/sek_notifications/read_chat';
$route['api/v1/notifications/read-all'] = 'api/v1/sek_notifications/read_all';
$route['api/v1/notifications/(:num)/read'] = 'api/v1/sek_notifications/read/$1';
$route['api/v1/notifications'] = 'api/v1/sek_notifications/index';
$route['api/v1/account-types'] = 'api/v1/sek_account_types/index';
$route['api/v1/media'] = 'api/v1/sek_media/index';
$route['api/v1/media/(:num)'] = 'api/v1/sek_media/show/$1';
$route['api/v1/contacts'] = 'api/v1/sek_contacts/index';
$route['api/v1/contacts/(:num)'] = 'api/v1/sek_contacts/show/$1';
$route['api/v1/blocks'] = 'api/v1/sek_blocks/index';
$route['api/v1/blocks/(:num)'] = 'api/v1/sek_blocks/show/$1';
$route['api/v1/reports'] = 'api/v1/sek_reports/index';
$route['api/v1/conversations/(:num)/messages'] = 'api/v1/sek_messages/index/$1';
$route['api/v1/conversations/(:num)/read'] = 'api/v1/sek_conversations/read/$1';
$route['api/v1/conversations/(:num)/typing'] = 'api/v1/sek_conversations/typing/$1';
$route['api/v1/conversations/(:num)/clear'] = 'api/v1/sek_conversations/clear/$1';
$route['api/v1/conversations/(:num)/pin'] = 'api/v1/sek_conversations/pin/$1';
$route['api/v1/conversations/(:num)/mute'] = 'api/v1/sek_conversations/mute/$1';
$route['api/v1/conversations/(:num)/members/(:num)'] = 'api/v1/sek_conversations/remove_member/$1/$2';
$route['api/v1/conversations/(:num)/members'] = 'api/v1/sek_conversations/members/$1';
$route['api/v1/conversations/(:num)/live-location'] = 'api/v1/sek_conversations/live_location/$1';
$route['api/v1/conversations/(:num)'] = 'api/v1/sek_conversations/show/$1';
$route['api/v1/conversations'] = 'api/v1/sek_conversations/index';
$route['api/v1/messages/(:num)/reaction'] = 'api/v1/sek_messages/reaction/$1';
$route['api/v1/messages/(:num)'] = 'api/v1/sek_messages/show/$1';
$route['api/v1/accounts/handle-available'] = 'api/v1/sek_accounts/handle_available';
$route['api/v1/accounts/lookup'] = 'api/v1/sek_accounts/lookup';
$route['api/v1/nearby'] = 'api/v1/sek_places/nearby';
$route['api/v1/feed'] = 'api/v1/sek_posts/feed';
$route['api/v1/posts/(:num)/like'] = 'api/v1/sek_posts/like/$1';
$route['api/v1/posts/(:num)/comments'] = 'api/v1/sek_posts/comments/$1';
$route['api/v1/posts/(:num)/report'] = 'api/v1/sek_posts/report/$1';
$route['api/v1/posts/(:num)'] = 'api/v1/sek_posts/show/$1';
$route['api/v1/posts'] = 'api/v1/sek_posts/index';
$route['api/v1/comments/(:num)'] = 'api/v1/sek_posts/delete_comment/$1';
$route['api/v1/accounts/(:num)/posts'] = 'api/v1/sek_posts/profile/$1';
$route['api/v1/accounts/(:num)/services'] = 'api/v1/sek_services/index/$1';
$route['api/v1/services/(:num)'] = 'api/v1/sek_services/show/$1';
$route['api/v1/orders/(:num)/accept'] = 'api/v1/sek_orders/accept/$1';
$route['api/v1/orders/(:num)/progress'] = 'api/v1/sek_orders/progress/$1';
$route['api/v1/orders/(:num)/deliver'] = 'api/v1/sek_orders/deliver/$1';
$route['api/v1/orders/(:num)/complete'] = 'api/v1/sek_orders/complete/$1';
$route['api/v1/orders/(:num)/cancel'] = 'api/v1/sek_orders/cancel/$1';
$route['api/v1/orders/(:num)/payment'] = 'api/v1/sek_orders/payment/$1';
$route['api/v1/orders/(:num)'] = 'api/v1/sek_orders/show/$1';
$route['api/v1/orders'] = 'api/v1/sek_orders/index';
$route['api/v1/accounts/search'] = 'api/v1/sek_accounts/search';
$route['api/v1/accounts/(:num)/location'] = 'api/v1/sek_places/location/$1';
$route['api/v1/accounts/(:num)/presence'] = 'api/v1/sek_places/presence/$1';
$route['api/v1/accounts/(:num)/hours'] = 'api/v1/sek_accounts/hours/$1';
$route['api/v1/accounts/(:num)/members'] = 'api/v1/sek_members/index/$1';
$route['api/v1/accounts/(:num)/members/(:num)'] = 'api/v1/sek_members/show/$1/$2';
$route['api/v1/accounts/(:num)/activate'] = 'api/v1/sek_accounts/activate/$1';
$route['api/v1/accounts/(:num)'] = 'api/v1/sek_accounts/show/$1';
$route['api/v1/accounts'] = 'api/v1/sek_accounts/index';
$route['admin'] = 'admin/index';
$route['admin/csrf-token'] = 'admin/csrf_token';
$route['admin/notifications'] = 'admin_notifications/index';
$route['admin/notifications/mark-viewed'] = 'admin_notifications/mark_viewed';
$route['admin/notifications/feed'] = 'admin_notifications/feed';
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
$route['admin/finance/customers/template'] = 'admin_finance/customers_template';
$route['admin/finance/customers/import'] = 'admin_finance/customers_import';
$route['admin/finance/suppliers/template'] = 'admin_finance/suppliers_template';
$route['admin/finance/suppliers/import'] = 'admin_finance/suppliers_import';
$route['admin/finance/categories/export'] = 'admin_finance/category_export';
$route['admin/finance/categories/import'] = 'admin_finance/category_import';
$route['admin/finance/fiscal_year/generate'] = 'admin_finance/fiscal_year_generate';
$route['admin/finance/cancel_request'] = 'admin_finance/cancel_request';
$route['admin/finance/revise_request'] = 'admin_finance/revise_request';
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

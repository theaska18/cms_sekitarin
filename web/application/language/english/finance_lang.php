<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Finance module labels.
$lang['finance_column_base_currency'] = 'Base currency';
$lang['finance_column_tax_inclusive'] = 'Tax inclusive';

$lang['finance_field_base_currency'] = 'Base currency (ISO code)';
$lang['finance_field_base_currency_hint'] = '3-letter ISO code of the accounting currency, e.g. IDR or USD. All journals and reports convert to this currency.';
$lang['finance_field_tax_inclusive_default'] = 'Tax inclusive by default';
$lang['finance_field_tax_inclusive_default_hint'] = 'Determines whether sales/purchase document totals include tax by default.';
$lang['finance_field_receivable_account_id'] = 'Accounts receivable control account';
$lang['finance_field_receivable_account_id_hint'] = 'Asset account for customer receivable balances. Recommended: 1100 Piutang Usaha.';
$lang['finance_field_payable_account_id'] = 'Accounts payable control account';
$lang['finance_field_payable_account_id_hint'] = 'Liability account for supplier payable balances. Recommended: 2000 Utang Usaha.';
$lang['finance_field_fx_gain_account_id'] = 'Foreign exchange gain account';
$lang['finance_field_fx_gain_account_id_hint'] = 'Revenue account for exchange-rate gains. Recommended: 8000 Keuntungan Selisih Kurs.';
$lang['finance_field_fx_loss_account_id'] = 'Foreign exchange loss account';
$lang['finance_field_fx_loss_account_id_hint'] = 'Expense account for exchange-rate losses. Recommended: 8100 Kerugian Selisih Kurs.';

$lang['finance_option_tax_exclusive'] = 'No — tax exclusive';
$lang['finance_option_tax_inclusive'] = 'Yes — tax inclusive';


// Income/expense transaction pages.
$lang['finance_tx_breadcrumb'] = 'Breadcrumb';
$lang['finance_tx_intro'] = 'Drafts do not affect the ledger. Posting creates a balanced, tenant-scoped journal entry; posted transactions cannot be edited.';
$lang['finance_tx_intro_all'] = 'Combined feed of Finance documents and manual transactions; drafts are labeled and do not affect reports.';
$lang['finance_tx_add_draft'] = 'Add draft';
$lang['finance_tx_unavailable'] = 'Finance transaction storage is unavailable.';
$lang['finance_tx_writes_unavailable'] = 'Secure Finance writes are unavailable.';
$lang['finance_tx_column_number'] = 'Number';
$lang['finance_tx_column_type'] = 'Type';
$lang['finance_tx_column_date'] = 'Date';
$lang['finance_tx_column_amount'] = 'Amount';
$lang['finance_tx_column_currency'] = 'Currency';
$lang['finance_tx_column_status'] = 'Status';
$lang['finance_tx_action_post'] = 'Post';
$lang['finance_tx_type_income'] = 'Income';
$lang['finance_tx_type_expense'] = 'Expense';
$lang['finance_tx_state_draft'] = 'Draft';
$lang['finance_tx_state_posted'] = 'Posted';
$lang['finance_tx_dt_empty'] = 'No transactions yet';
$lang['finance_tx_dt_info_empty'] = 'No transactions';
$lang['finance_tx_dt_zero'] = 'No matching transactions';
$lang['finance_tx_status_draft_saved'] = 'Draft saved.';
$lang['finance_tx_status_posted'] = 'Transaction posted to the general ledger.';
$lang['finance_tx_status_period_closed'] = 'No open accounting period includes this transaction date.';
$lang['finance_tx_status_period_ambiguous'] = 'More than one accounting period matches this transaction date. Contact an administrator.';
$lang['finance_tx_status_tax_account_missing'] = 'Select a tax code linked to an active tax account before posting.';
$lang['finance_tx_status_already_posted'] = 'A journal entry already exists for this transaction.';
$lang['finance_tx_status_account_invalid'] = 'The selected account is invalid or inactive.';
$lang['finance_tx_status_not_postable'] = 'This transaction cannot be posted in its current state.';
$lang['finance_tx_status_unavailable'] = 'Finance transaction storage is unavailable.';
$lang['finance_tx_status_post_error'] = 'The transaction could not be posted.';
$lang['finance_tx_status_unknown'] = 'The requested action could not be completed (%s).';
$lang['finance_tx_new_draft'] = 'New draft';
$lang['finance_tx_edit_draft'] = 'Edit draft';
$lang['finance_tx_heading_new'] = 'New %s';
$lang['finance_tx_heading_edit'] = 'Edit draft %s';
$lang['finance_tx_intro_form'] = 'Enter one tax-exclusive line. Choose a currency-compatible cash account and an exchange rate to the tenant base currency. Saving creates a draft only.';
$lang['finance_tx_form_error'] = 'The draft could not be saved. Review the fields and try again.';
$lang['finance_tx_field_transaction_on'] = 'Transaction date';
$lang['finance_tx_field_cash_account'] = 'Cash or bank account';
$lang['finance_tx_field_revenue_account'] = 'Revenue account';
$lang['finance_tx_field_expense_account'] = 'Expense account';
$lang['finance_tx_field_category'] = 'Category';
$lang['finance_tx_field_tax'] = 'Tax code';
$lang['finance_tx_field_department'] = 'Department';
$lang['finance_tx_field_amount'] = 'Line amount (before tax)';
$lang['finance_tx_field_exchange_rate'] = 'Exchange rate to base currency';
$lang['finance_tx_field_description'] = 'Description';
$lang['finance_tx_hint_exchange_rate'] = 'For a base-currency transaction, use 1. For other currencies, enter base currency units per transaction currency unit.';
$lang['finance_tx_save_draft'] = 'Save draft';
$lang['finance_tx_error_cash_account_id_required'] = 'Select a cash or bank account.';
$lang['finance_tx_error_account_id_required'] = 'Select the related account.';
$lang['finance_tx_error_transaction_on_invalid'] = 'Enter a valid transaction date.';
$lang['finance_tx_error_amount_invalid'] = 'Enter a valid amount greater than zero.';
$lang['finance_tx_error_exchange_rate_invalid'] = 'Enter a valid exchange rate greater than zero.';
$lang['finance_tx_error_description_too_long'] = 'The description must be 240 characters or fewer.';
$lang['finance_tx_error_transaction_unavailable'] = 'Finance transaction storage is unavailable.';
$lang['finance_tx_error_cash_account_invalid'] = 'The selected cash or bank account is invalid or inactive.';
$lang['finance_tx_error_account_invalid'] = 'The selected account is invalid or inactive.';
$lang['finance_tx_error_category_invalid'] = 'The selected category is invalid or inactive.';
$lang['finance_tx_error_department_invalid'] = 'The selected department is invalid or inactive.';
$lang['finance_tx_error_tax_invalid'] = 'The selected tax code is invalid or inactive.';
$lang['finance_tx_error_not_found'] = 'The requested draft could not be found.';
$lang['finance_tx_error_not_editable'] = 'Only drafts can be edited.';
$lang['finance_tx_error_save_error'] = 'The draft could not be saved. Try again.';

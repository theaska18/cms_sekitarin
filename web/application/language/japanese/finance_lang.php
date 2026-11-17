<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// 財務モジュールのラベル。
$lang['finance_column_base_currency'] = '基準通貨';
$lang['finance_column_tax_inclusive'] = '税込';

$lang['finance_field_base_currency'] = '基準通貨（ISO コード）';
$lang['finance_field_base_currency_hint'] = '会計通貨の 3 文字の ISO コード（例：IDR、USD）。すべての仕訳とレポートはこの通貨に換算されます。';
$lang['finance_field_tax_inclusive_default'] = '既定で税込';
$lang['finance_field_tax_inclusive_default_hint'] = '販売/購買伝票の合計に既定で税を含めるかどうかを指定します。';
$lang['finance_field_receivable_account_id'] = '売掛金統制勘定';
$lang['finance_field_receivable_account_id_hint'] = '顧客の売掛残高用の資産勘定。推奨：1100 Piutang Usaha。';
$lang['finance_field_payable_account_id'] = '買掛金統制勘定';
$lang['finance_field_payable_account_id_hint'] = '仕入先の買掛残高用の負債勘定。推奨：2000 Utang Usaha。';
$lang['finance_field_fx_gain_account_id'] = '為替差益勘定';
$lang['finance_field_fx_gain_account_id_hint'] = '為替差益用の収益勘定。推奨：8000 Keuntungan Selisih Kurs。';
$lang['finance_field_fx_loss_account_id'] = '為替差損勘定';
$lang['finance_field_fx_loss_account_id_hint'] = '為替差損用の費用勘定。推奨：8100 Kerugian Selisih Kurs。';

$lang['finance_option_tax_exclusive'] = 'いいえ — 税別';
$lang['finance_option_tax_inclusive'] = 'はい — 税込';


// Income/expense transaction pages.
$lang['finance_tx_breadcrumb'] = 'パンくずリスト';
$lang['finance_tx_intro'] = '下書きは総勘定元帳に影響しません。転記すると、テナント単位で貸借が一致する仕訳が作成されます。転記済みの取引は編集できません。';
$lang['finance_tx_intro_all'] = '財務書類と手動取引をまとめた一覧です。下書きにはラベルが付き、レポートには影響しません。';
$lang['finance_tx_add_draft'] = '下書きを追加';
$lang['finance_tx_unavailable'] = '財務取引の保存領域を利用できません。';
$lang['finance_tx_writes_unavailable'] = '安全な財務書き込みを利用できません。';
$lang['finance_tx_column_number'] = '番号';
$lang['finance_tx_column_type'] = '種類';
$lang['finance_tx_column_date'] = '日付';
$lang['finance_tx_column_amount'] = '金額';
$lang['finance_tx_column_currency'] = '通貨';
$lang['finance_tx_column_status'] = 'ステータス';
$lang['finance_tx_action_post'] = '転記';
$lang['finance_tx_type_income'] = '収入';
$lang['finance_tx_type_expense'] = '支出';
$lang['finance_tx_state_draft'] = '下書き';
$lang['finance_tx_state_posted'] = '転記済み';
$lang['finance_tx_dt_empty'] = '取引はまだありません';
$lang['finance_tx_dt_info_empty'] = '取引はありません';
$lang['finance_tx_dt_zero'] = '一致する取引はありません';
$lang['finance_tx_status_draft_saved'] = '下書きを保存しました。';
$lang['finance_tx_status_posted'] = '取引を総勘定元帳に転記しました。';
$lang['finance_tx_status_period_closed'] = 'この取引日を含む未締めの会計期間がありません。';
$lang['finance_tx_status_period_ambiguous'] = 'この取引日に複数の会計期間が一致します。管理者に連絡してください。';
$lang['finance_tx_status_tax_account_missing'] = '転記する前に、有効な税務科目に紐づく税コードを選択してください。';
$lang['finance_tx_status_already_posted'] = 'この取引の仕訳はすでに存在します。';
$lang['finance_tx_status_account_invalid'] = '選択した科目は無効または停止中です。';
$lang['finance_tx_status_not_postable'] = 'この取引は現在の状態では転記できません。';
$lang['finance_tx_status_unavailable'] = '財務取引の保存領域を利用できません。';
$lang['finance_tx_status_post_error'] = '取引を転記できませんでした。';
$lang['finance_tx_status_unknown'] = '要求された操作を完了できませんでした（%s）。';
$lang['finance_tx_new_draft'] = '新規下書き';
$lang['finance_tx_edit_draft'] = '下書きを編集';
$lang['finance_tx_heading_new'] = '新規 %s';
$lang['finance_tx_heading_edit'] = '下書きを編集 %s';
$lang['finance_tx_intro_form'] = '税抜の明細を 1 行入力します。通貨に対応した現金口座と、テナントの基準通貨への為替レートを選択してください。保存すると下書きのみ作成されます。';
$lang['finance_tx_form_error'] = '下書きを保存できませんでした。項目を確認して再試行してください。';
$lang['finance_tx_field_transaction_on'] = '取引日';
$lang['finance_tx_field_cash_account'] = '現金または銀行口座';
$lang['finance_tx_field_revenue_account'] = '収益科目';
$lang['finance_tx_field_expense_account'] = '費用科目';
$lang['finance_tx_field_category'] = 'カテゴリ';
$lang['finance_tx_field_tax'] = '税コード';
$lang['finance_tx_field_department'] = '部門';
$lang['finance_tx_field_amount'] = '明細金額（税抜）';
$lang['finance_tx_field_exchange_rate'] = '基準通貨への為替レート';
$lang['finance_tx_field_description'] = '説明';
$lang['finance_tx_hint_exchange_rate'] = '基準通貨の取引では 1 を使用します。その他の通貨では、取引通貨 1 単位あたりの基準通貨額を入力してください。';
$lang['finance_tx_save_draft'] = '下書きを保存';
$lang['finance_tx_error_cash_account_id_required'] = '現金または銀行口座を選択してください。';
$lang['finance_tx_error_account_id_required'] = '関連する科目を選択してください。';
$lang['finance_tx_error_transaction_on_invalid'] = '有効な取引日を入力してください。';
$lang['finance_tx_error_amount_invalid'] = '0 より大きい有効な金額を入力してください。';
$lang['finance_tx_error_exchange_rate_invalid'] = '0 より大きい有効な為替レートを入力してください。';
$lang['finance_tx_error_description_too_long'] = '説明は 240 文字以内にしてください。';
$lang['finance_tx_error_transaction_unavailable'] = '財務取引の保存領域を利用できません。';
$lang['finance_tx_error_cash_account_invalid'] = '選択した現金または銀行口座は無効または停止中です。';
$lang['finance_tx_error_account_invalid'] = '選択した科目は無効または停止中です。';
$lang['finance_tx_error_category_invalid'] = '選択したカテゴリは無効または停止中です。';
$lang['finance_tx_error_department_invalid'] = '選択した部門は無効または停止中です。';
$lang['finance_tx_error_tax_invalid'] = '選択した税コードは無効または停止中です。';
$lang['finance_tx_error_not_found'] = '要求された下書きが見つかりません。';
$lang['finance_tx_error_not_editable'] = '編集できるのは下書きのみです。';
$lang['finance_tx_error_save_error'] = '下書きを保存できませんでした。再試行してください。';

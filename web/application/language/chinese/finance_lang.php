<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// 财务模块标签。
$lang['finance_column_base_currency'] = '本位币';
$lang['finance_column_tax_inclusive'] = '含税';

$lang['finance_field_base_currency'] = '本位币（ISO 代码）';
$lang['finance_field_base_currency_hint'] = '记账货币的三字母 ISO 代码，例如 IDR 或 USD。所有日记账和报表均折算为该货币。';
$lang['finance_field_tax_inclusive_default'] = '默认含税';
$lang['finance_field_tax_inclusive_default_hint'] = '决定销售/采购单据合计是否默认包含税费。';
$lang['finance_field_receivable_account_id'] = '应收账款统制科目';
$lang['finance_field_receivable_account_id_hint'] = '用于客户应收余额的资产科目。建议：1100 Piutang Usaha。';
$lang['finance_field_payable_account_id'] = '应付账款统制科目';
$lang['finance_field_payable_account_id_hint'] = '用于供应商应付余额的负债科目。建议：2000 Utang Usaha。';
$lang['finance_field_fx_gain_account_id'] = '汇兑收益科目';
$lang['finance_field_fx_gain_account_id_hint'] = '用于汇兑收益的收入科目。建议：8000 Keuntungan Selisih Kurs。';
$lang['finance_field_fx_loss_account_id'] = '汇兑损失科目';
$lang['finance_field_fx_loss_account_id_hint'] = '用于汇兑损失的费用科目。建议：8100 Kerugian Selisih Kurs。';

$lang['finance_option_tax_exclusive'] = '否 — 不含税';
$lang['finance_option_tax_inclusive'] = '是 — 含税';


// Income/expense transaction pages.
$lang['finance_tx_breadcrumb'] = '面包屑导航';
$lang['finance_tx_intro'] = '草稿不影响总账。过账会创建借贷平衡且限定租户的日记账分录；已过账的交易不可编辑。';
$lang['finance_tx_intro_all'] = '财务单据与手工交易的合并列表；草稿会标注，且不影响报表。';
$lang['finance_tx_add_draft'] = '添加草稿';
$lang['finance_tx_unavailable'] = '财务交易存储不可用。';
$lang['finance_tx_writes_unavailable'] = '安全的财务写入不可用。';
$lang['finance_tx_column_number'] = '编号';
$lang['finance_tx_column_type'] = '类型';
$lang['finance_tx_column_date'] = '日期';
$lang['finance_tx_column_amount'] = '金额';
$lang['finance_tx_column_currency'] = '币种';
$lang['finance_tx_column_status'] = '状态';
$lang['finance_tx_action_post'] = '过账';
$lang['finance_tx_type_income'] = '收入';
$lang['finance_tx_type_expense'] = '支出';
$lang['finance_tx_state_draft'] = '草稿';
$lang['finance_tx_state_posted'] = '已过账';
$lang['finance_tx_dt_empty'] = '暂无交易';
$lang['finance_tx_dt_info_empty'] = '无交易';
$lang['finance_tx_dt_zero'] = '无匹配交易';
$lang['finance_tx_status_draft_saved'] = '草稿已保存。';
$lang['finance_tx_status_posted'] = '交易已过账至总账。';
$lang['finance_tx_status_period_closed'] = '没有包含此交易日期的开放会计期间。';
$lang['finance_tx_status_period_ambiguous'] = '有多个会计期间与此交易日期匹配。请联系管理员。';
$lang['finance_tx_status_tax_account_missing'] = '过账前请选择关联到启用税务科目的税码。';
$lang['finance_tx_status_already_posted'] = '此交易已存在日记账分录。';
$lang['finance_tx_status_account_invalid'] = '所选科目无效或已停用。';
$lang['finance_tx_status_not_postable'] = '此交易在当前状态下无法过账。';
$lang['finance_tx_status_unavailable'] = '财务交易存储不可用。';
$lang['finance_tx_status_post_error'] = '交易无法过账。';
$lang['finance_tx_status_unknown'] = '无法完成请求的操作（%s）。';
$lang['finance_tx_new_draft'] = '新建草稿';
$lang['finance_tx_edit_draft'] = '编辑草稿';
$lang['finance_tx_heading_new'] = '新建 %s';
$lang['finance_tx_heading_edit'] = '编辑草稿 %s';
$lang['finance_tx_intro_form'] = '请输入一行不含税金额。选择与币种兼容的现金账户以及到租户本位币的汇率。保存仅创建草稿。';
$lang['finance_tx_form_error'] = '草稿无法保存。请检查各字段后重试。';
$lang['finance_tx_field_transaction_on'] = '交易日期';
$lang['finance_tx_field_cash_account'] = '现金或银行账户';
$lang['finance_tx_field_revenue_account'] = '收入科目';
$lang['finance_tx_field_expense_account'] = '费用科目';
$lang['finance_tx_field_category'] = '类别';
$lang['finance_tx_field_tax'] = '税码';
$lang['finance_tx_field_department'] = '部门';
$lang['finance_tx_field_amount'] = '行金额（不含税）';
$lang['finance_tx_field_exchange_rate'] = '到本位币的汇率';
$lang['finance_tx_field_description'] = '描述';
$lang['finance_tx_hint_exchange_rate'] = '本位币交易请填 1。其他币种请输入每单位交易币种对应的本位币金额。';
$lang['finance_tx_save_draft'] = '保存草稿';
$lang['finance_tx_error_cash_account_id_required'] = '请选择现金或银行账户。';
$lang['finance_tx_error_account_id_required'] = '请选择相关科目。';
$lang['finance_tx_error_transaction_on_invalid'] = '请输入有效的交易日期。';
$lang['finance_tx_error_amount_invalid'] = '请输入大于零的有效金额。';
$lang['finance_tx_error_exchange_rate_invalid'] = '请输入大于零的有效汇率。';
$lang['finance_tx_error_description_too_long'] = '描述不得超过 240 个字符。';
$lang['finance_tx_error_transaction_unavailable'] = '财务交易存储不可用。';
$lang['finance_tx_error_cash_account_invalid'] = '所选现金或银行账户无效或已停用。';
$lang['finance_tx_error_account_invalid'] = '所选科目无效或已停用。';
$lang['finance_tx_error_category_invalid'] = '所选类别无效或已停用。';
$lang['finance_tx_error_department_invalid'] = '所选部门无效或已停用。';
$lang['finance_tx_error_tax_invalid'] = '所选税码无效或已停用。';
$lang['finance_tx_error_not_found'] = '找不到请求的草稿。';
$lang['finance_tx_error_not_editable'] = '只有草稿可以编辑。';
$lang['finance_tx_error_save_error'] = '草稿无法保存。请重试。';

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// 재무 모듈 라벨.
$lang['finance_column_base_currency'] = '기준 통화';
$lang['finance_column_tax_inclusive'] = '세금 포함';

$lang['finance_field_base_currency'] = '기준 통화(ISO 코드)';
$lang['finance_field_base_currency_hint'] = '회계 통화의 3자리 ISO 코드(예: IDR 또는 USD). 모든 분개와 보고서는 이 통화로 환산됩니다.';
$lang['finance_field_tax_inclusive_default'] = '기본적으로 세금 포함';
$lang['finance_field_tax_inclusive_default_hint'] = '판매/구매 문서 합계에 기본적으로 세금을 포함할지 결정합니다.';
$lang['finance_field_receivable_account_id'] = '매출채권 통제 계정';
$lang['finance_field_receivable_account_id_hint'] = '고객 미수금 잔액을 위한 자산 계정입니다. 권장: 1100 Piutang Usaha.';
$lang['finance_field_payable_account_id'] = '매입채무 통제 계정';
$lang['finance_field_payable_account_id_hint'] = '공급업체 미지급금 잔액을 위한 부채 계정입니다. 권장: 2000 Utang Usaha.';
$lang['finance_field_fx_gain_account_id'] = '외환차익 계정';
$lang['finance_field_fx_gain_account_id_hint'] = '환율 변동 이익을 위한 수익 계정입니다. 권장: 8000 Keuntungan Selisih Kurs.';
$lang['finance_field_fx_loss_account_id'] = '외환차손 계정';
$lang['finance_field_fx_loss_account_id_hint'] = '환율 변동 손실을 위한 비용 계정입니다. 권장: 8100 Kerugian Selisih Kurs.';

$lang['finance_option_tax_exclusive'] = '아니요 — 세금 별도';
$lang['finance_option_tax_inclusive'] = '예 — 세금 포함';


// Income/expense transaction pages.
$lang['finance_tx_breadcrumb'] = '이동 경로';
$lang['finance_tx_intro'] = '초안은 총계정원장에 영향을 주지 않습니다. 전기는 테넌트 범위의 균형 잡힌 분개를 생성하며, 전기된 거래는 편집할 수 없습니다.';
$lang['finance_tx_intro_all'] = '재무 문서와 수동 거래를 함께 보여 줍니다. 초안에는 표시가 있으며 보고서에 영향을 주지 않습니다.';
$lang['finance_tx_add_draft'] = '초안 추가';
$lang['finance_tx_unavailable'] = '재무 거래 저장소를 사용할 수 없습니다.';
$lang['finance_tx_writes_unavailable'] = '안전한 재무 쓰기를 사용할 수 없습니다.';
$lang['finance_tx_column_number'] = '번호';
$lang['finance_tx_column_type'] = '유형';
$lang['finance_tx_column_date'] = '날짜';
$lang['finance_tx_column_amount'] = '금액';
$lang['finance_tx_column_currency'] = '통화';
$lang['finance_tx_column_status'] = '상태';
$lang['finance_tx_action_post'] = '전기';
$lang['finance_tx_type_income'] = '수익';
$lang['finance_tx_type_expense'] = '비용';
$lang['finance_tx_state_draft'] = '초안';
$lang['finance_tx_state_posted'] = '전기됨';
$lang['finance_tx_dt_empty'] = '아직 거래가 없습니다';
$lang['finance_tx_dt_info_empty'] = '거래가 없습니다';
$lang['finance_tx_dt_zero'] = '일치하는 거래가 없습니다';
$lang['finance_tx_status_draft_saved'] = '초안이 저장되었습니다.';
$lang['finance_tx_status_posted'] = '거래가 총계정원장에 전기되었습니다.';
$lang['finance_tx_status_period_closed'] = '이 거래 날짜를 포함하는 열린 회계 기간이 없습니다.';
$lang['finance_tx_status_period_ambiguous'] = '이 거래 날짜와 일치하는 회계 기간이 여러 개입니다. 관리자에게 문의하세요.';
$lang['finance_tx_status_tax_account_missing'] = '전기하기 전에 활성 세무 계정에 연결된 세금 코드를 선택하세요.';
$lang['finance_tx_status_already_posted'] = '이 거래에 대한 분개가 이미 존재합니다.';
$lang['finance_tx_status_account_invalid'] = '선택한 계정이 유효하지 않거나 비활성 상태입니다.';
$lang['finance_tx_status_not_postable'] = '이 거래는 현재 상태에서 전기할 수 없습니다.';
$lang['finance_tx_status_unavailable'] = '재무 거래 저장소를 사용할 수 없습니다.';
$lang['finance_tx_status_post_error'] = '거래를 전기할 수 없습니다.';
$lang['finance_tx_status_unknown'] = '요청한 작업을 완료할 수 없습니다(%s).';
$lang['finance_tx_new_draft'] = '새 초안';
$lang['finance_tx_edit_draft'] = '초안 편집';
$lang['finance_tx_heading_new'] = '새 %s';
$lang['finance_tx_heading_edit'] = '초안 편집 %s';
$lang['finance_tx_intro_form'] = '세금 제외 항목 한 줄을 입력하세요. 통화와 호환되는 현금 계정과 테넌트 기준 통화에 대한 환율을 선택하세요. 저장하면 초안만 생성됩니다.';
$lang['finance_tx_form_error'] = '초안을 저장할 수 없습니다. 항목을 확인한 후 다시 시도하세요.';
$lang['finance_tx_field_transaction_on'] = '거래 날짜';
$lang['finance_tx_field_cash_account'] = '현금 또는 은행 계정';
$lang['finance_tx_field_revenue_account'] = '수익 계정';
$lang['finance_tx_field_expense_account'] = '비용 계정';
$lang['finance_tx_field_category'] = '범주';
$lang['finance_tx_field_tax'] = '세금 코드';
$lang['finance_tx_field_department'] = '부서';
$lang['finance_tx_field_amount'] = '항목 금액(세금 제외)';
$lang['finance_tx_field_exchange_rate'] = '기준 통화 환율';
$lang['finance_tx_field_description'] = '설명';
$lang['finance_tx_hint_exchange_rate'] = '기준 통화 거래는 1을 사용하세요. 다른 통화는 거래 통화 1단위당 기준 통화 금액을 입력하세요.';
$lang['finance_tx_save_draft'] = '초안 저장';
$lang['finance_tx_error_cash_account_id_required'] = '현금 또는 은행 계정을 선택하세요.';
$lang['finance_tx_error_account_id_required'] = '관련 계정을 선택하세요.';
$lang['finance_tx_error_transaction_on_invalid'] = '유효한 거래 날짜를 입력하세요.';
$lang['finance_tx_error_amount_invalid'] = '0보다 큰 유효한 금액을 입력하세요.';
$lang['finance_tx_error_exchange_rate_invalid'] = '0보다 큰 유효한 환율을 입력하세요.';
$lang['finance_tx_error_description_too_long'] = '설명은 240자 이하여야 합니다.';
$lang['finance_tx_error_transaction_unavailable'] = '재무 거래 저장소를 사용할 수 없습니다.';
$lang['finance_tx_error_cash_account_invalid'] = '선택한 현금 또는 은행 계정이 유효하지 않거나 비활성 상태입니다.';
$lang['finance_tx_error_account_invalid'] = '선택한 계정이 유효하지 않거나 비활성 상태입니다.';
$lang['finance_tx_error_category_invalid'] = '선택한 범주가 유효하지 않거나 비활성 상태입니다.';
$lang['finance_tx_error_department_invalid'] = '선택한 부서가 유효하지 않거나 비활성 상태입니다.';
$lang['finance_tx_error_tax_invalid'] = '선택한 세금 코드가 유효하지 않거나 비활성 상태입니다.';
$lang['finance_tx_error_not_found'] = '요청한 초안을 찾을 수 없습니다.';
$lang['finance_tx_error_not_editable'] = '초안만 편집할 수 있습니다.';
$lang['finance_tx_error_save_error'] = '초안을 저장할 수 없습니다. 다시 시도하세요.';

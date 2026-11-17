<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// تسميات وحدة المالية.
$lang['finance_column_base_currency'] = 'العملة الأساسية';
$lang['finance_column_tax_inclusive'] = 'شامل الضريبة';

$lang['finance_field_base_currency'] = 'العملة الأساسية (رمز ISO)';
$lang['finance_field_base_currency_hint'] = 'رمز ISO المكون من 3 أحرف لعملة المحاسبة، مثل IDR أو USD. تُحوَّل جميع القيود والتقارير إلى هذه العملة.';
$lang['finance_field_tax_inclusive_default'] = 'شامل الضريبة افتراضيًا';
$lang['finance_field_tax_inclusive_default_hint'] = 'يحدد ما إذا كانت إجماليات مستندات البيع/الشراء تتضمن الضريبة افتراضيًا.';
$lang['finance_field_receivable_account_id'] = 'حساب مراقبة الذمم المدينة';
$lang['finance_field_receivable_account_id_hint'] = 'حساب أصول لأرصدة الذمم المدينة للعملاء. الموصى به: 1100 Piutang Usaha.';
$lang['finance_field_payable_account_id'] = 'حساب مراقبة الذمم الدائنة';
$lang['finance_field_payable_account_id_hint'] = 'حساب التزامات لأرصدة الذمم الدائنة للموردين. الموصى به: 2000 Utang Usaha.';
$lang['finance_field_fx_gain_account_id'] = 'حساب أرباح فروق العملة';
$lang['finance_field_fx_gain_account_id_hint'] = 'حساب إيرادات لأرباح فروق أسعار الصرف. الموصى به: 8000 Keuntungan Selisih Kurs.';
$lang['finance_field_fx_loss_account_id'] = 'حساب خسائر فروق العملة';
$lang['finance_field_fx_loss_account_id_hint'] = 'حساب مصروفات لخسائر فروق أسعار الصرف. الموصى به: 8100 Kerugian Selisih Kurs.';

$lang['finance_option_tax_exclusive'] = 'لا — دون ضريبة';
$lang['finance_option_tax_inclusive'] = 'نعم — شامل الضريبة';


// Income/expense transaction pages.
$lang['finance_tx_breadcrumb'] = 'مسار التنقل';
$lang['finance_tx_intro'] = 'لا تؤثر المسودات على دفتر الأستاذ. يؤدي الترحيل إلى إنشاء قيد متوازن مقيّد بالمستأجر؛ ولا يمكن تعديل المعاملات المرحّلة.';
$lang['finance_tx_intro_all'] = 'قائمة موحدة لمستندات المالية والمعاملات اليدوية؛ المسودات موسومة ولا تؤثر على التقارير.';
$lang['finance_tx_add_draft'] = 'إضافة مسودة';
$lang['finance_tx_unavailable'] = 'تخزين معاملات المالية غير متاح.';
$lang['finance_tx_writes_unavailable'] = 'عمليات الكتابة الآمنة للمالية غير متاحة.';
$lang['finance_tx_column_number'] = 'الرقم';
$lang['finance_tx_column_type'] = 'النوع';
$lang['finance_tx_column_date'] = 'التاريخ';
$lang['finance_tx_column_amount'] = 'المبلغ';
$lang['finance_tx_column_currency'] = 'العملة';
$lang['finance_tx_column_status'] = 'الحالة';
$lang['finance_tx_action_post'] = 'ترحيل';
$lang['finance_tx_type_income'] = 'الإيرادات';
$lang['finance_tx_type_expense'] = 'المصروفات';
$lang['finance_tx_state_draft'] = 'مسودة';
$lang['finance_tx_state_posted'] = 'مرحّلة';
$lang['finance_tx_dt_empty'] = 'لا توجد معاملات بعد';
$lang['finance_tx_dt_info_empty'] = 'لا توجد معاملات';
$lang['finance_tx_dt_zero'] = 'لا توجد معاملات مطابقة';
$lang['finance_tx_status_draft_saved'] = 'تم حفظ المسودة.';
$lang['finance_tx_status_posted'] = 'تم ترحيل المعاملة إلى دفتر الأستاذ العام.';
$lang['finance_tx_status_period_closed'] = 'لا توجد فترة محاسبية مفتوحة تشمل تاريخ هذه المعاملة.';
$lang['finance_tx_status_period_ambiguous'] = 'أكثر من فترة محاسبية تطابق تاريخ هذه المعاملة. اتصل بالمسؤول.';
$lang['finance_tx_status_tax_account_missing'] = 'اختر رمز ضريبة مرتبطًا بحساب ضريبة نشط قبل الترحيل.';
$lang['finance_tx_status_already_posted'] = 'يوجد قيد يومية لهذه المعاملة بالفعل.';
$lang['finance_tx_status_account_invalid'] = 'الحساب المحدد غير صالح أو غير نشط.';
$lang['finance_tx_status_not_postable'] = 'لا يمكن ترحيل هذه المعاملة في حالتها الحالية.';
$lang['finance_tx_status_unavailable'] = 'تخزين معاملات المالية غير متاح.';
$lang['finance_tx_status_post_error'] = 'تعذر ترحيل المعاملة.';
$lang['finance_tx_status_unknown'] = 'تعذر إتمام الإجراء المطلوب (%s).';
$lang['finance_tx_new_draft'] = 'مسودة جديدة';
$lang['finance_tx_edit_draft'] = 'تعديل المسودة';
$lang['finance_tx_heading_new'] = 'جديد %s';
$lang['finance_tx_heading_edit'] = 'تعديل المسودة %s';
$lang['finance_tx_intro_form'] = 'أدخل بندًا واحدًا بدون ضريبة. اختر حساب نقدية متوافقًا مع العملة وسعر صرف إلى العملة الأساسية للمستأجر. الحفظ ينشئ مسودة فقط.';
$lang['finance_tx_form_error'] = 'تعذر حفظ المسودة. راجع الحقول وحاول مرة أخرى.';
$lang['finance_tx_field_transaction_on'] = 'تاريخ المعاملة';
$lang['finance_tx_field_cash_account'] = 'حساب النقدية أو البنك';
$lang['finance_tx_field_revenue_account'] = 'حساب الإيرادات';
$lang['finance_tx_field_expense_account'] = 'حساب المصروفات';
$lang['finance_tx_field_category'] = 'الفئة';
$lang['finance_tx_field_tax'] = 'رمز الضريبة';
$lang['finance_tx_field_department'] = 'القسم';
$lang['finance_tx_field_amount'] = 'مبلغ البند (قبل الضريبة)';
$lang['finance_tx_field_exchange_rate'] = 'سعر الصرف إلى العملة الأساسية';
$lang['finance_tx_field_description'] = 'الوصف';
$lang['finance_tx_hint_exchange_rate'] = 'استخدم 1 لمعاملة بالعملة الأساسية. للعملات الأخرى، أدخل وحدات العملة الأساسية لكل وحدة من عملة المعاملة.';
$lang['finance_tx_save_draft'] = 'حفظ المسودة';
$lang['finance_tx_error_cash_account_id_required'] = 'اختر حساب نقدية أو بنك.';
$lang['finance_tx_error_account_id_required'] = 'اختر الحساب ذي الصلة.';
$lang['finance_tx_error_transaction_on_invalid'] = 'أدخل تاريخ معاملة صالحًا.';
$lang['finance_tx_error_amount_invalid'] = 'أدخل مبلغًا صالحًا أكبر من صفر.';
$lang['finance_tx_error_exchange_rate_invalid'] = 'أدخل سعر صرف صالحًا أكبر من صفر.';
$lang['finance_tx_error_description_too_long'] = 'يجب ألا يتجاوز الوصف 240 حرفًا.';
$lang['finance_tx_error_transaction_unavailable'] = 'تخزين معاملات المالية غير متاح.';
$lang['finance_tx_error_cash_account_invalid'] = 'حساب النقدية أو البنك المحدد غير صالح أو غير نشط.';
$lang['finance_tx_error_account_invalid'] = 'الحساب المحدد غير صالح أو غير نشط.';
$lang['finance_tx_error_category_invalid'] = 'الفئة المحددة غير صالحة أو غير نشطة.';
$lang['finance_tx_error_department_invalid'] = 'القسم المحدد غير صالح أو غير نشط.';
$lang['finance_tx_error_tax_invalid'] = 'رمز الضريبة المحدد غير صالح أو غير نشط.';
$lang['finance_tx_error_not_found'] = 'تعذر العثور على المسودة المطلوبة.';
$lang['finance_tx_error_not_editable'] = 'يمكن تعديل المسودات فقط.';
$lang['finance_tx_error_save_error'] = 'تعذر حفظ المسودة. حاول مرة أخرى.';

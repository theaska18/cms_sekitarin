<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$title = isset($labels['finance_' . $finance_document_slug]) ? $labels['finance_' . $finance_document_slug] : ucfirst(str_replace('_', ' ', $finance_document_slug));
$party_label = $finance_document_kind === 'sales'
    ? (isset($labels['finance_customer']) ? $labels['finance_customer'] : 'Customer')
    : (isset($labels['finance_supplier']) ? $labels['finance_supplier'] : 'Supplier');
$account_label = $finance_document_kind === 'sales'
    ? (isset($labels['finance_revenue_account']) ? $labels['finance_revenue_account'] : 'Revenue account')
    : (isset($labels['finance_expense_account']) ? $labels['finance_expense_account'] : 'Expense account');
$status = isset($finance_document_record['status']) ? (string) $finance_document_record['status'] : '';
$status_labels = array(
    'draft' => isset($labels['finance_tx_state_draft']) ? $labels['finance_tx_state_draft'] : 'Draft',
    'revise' => isset($labels['finance_tx_state_revise']) ? $labels['finance_tx_state_revise'] : 'Revise',
    'approved' => isset($labels['finance_tx_state_approved']) ? $labels['finance_tx_state_approved'] : 'Approved',
    'posted' => isset($labels['finance_tx_state_posted']) ? $labels['finance_tx_state_posted'] : 'Posted',
    'cancelled' => isset($labels['finance_status_cancelled']) ? $labels['finance_status_cancelled'] : 'Cancelled',
);
$status_styles = array(
    'draft' => 'border-slate-200 bg-slate-50 text-slate-700',
    'revise' => 'border-amber-200 bg-amber-50 text-amber-900',
    'approved' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
    'posted' => 'border-slate-800 bg-slate-800 text-white',
    'cancelled' => 'border-rose-200 bg-rose-50 text-rose-800',
);
$status_style = isset($status_styles[$status]) ? $status_styles[$status] : $status_styles['draft'];
$financials = isset($finance_document_financials) && is_array($finance_document_financials) ? $finance_document_financials : NULL;
$payment_status = '';
$payment_style = '';
if ($status === 'posted' && is_array($financials)) {
    if ((float) $financials['outstanding_amount'] <= 0.000001) {
        $payment_status = isset($labels['finance_payment_status_paid']) ? $labels['finance_payment_status_paid'] : 'Paid';
        $payment_style = 'border-emerald-200 bg-emerald-50 text-emerald-800';
    } elseif ((float) $financials['paid_amount'] > 0.000001) {
        $payment_status = isset($labels['finance_payment_status_partial']) ? $labels['finance_payment_status_partial'] : 'Partially paid';
        $payment_style = 'border-amber-200 bg-amber-50 text-amber-900';
    } else {
        $payment_status = isset($labels['finance_payment_status_unpaid']) ? $labels['finance_payment_status_unpaid'] : 'Unpaid';
        $payment_style = 'border-slate-200 bg-slate-50 text-slate-700';
    }
}
$amount = static function ($value) {
    return number_format((float) $value, 2, '.', ',');
};
$document_label = $finance_document_kind === 'sales'
    ? (isset($labels['finance_invoice_date']) ? $labels['finance_invoice_date'] : 'Invoice date')
    : (isset($labels['finance_bill_date']) ? $labels['finance_bill_date'] : 'Bill date');
?>
<main class="mx-auto w-full max-w-4xl px-2 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="<?= html_escape(isset($labels['finance_breadcrumb']) ? $labels['finance_breadcrumb'] : 'Breadcrumb') ?>" class="mb-5 text-sm text-slate-500">
        <a href="/admin/finance/<?= html_escape($finance_document_slug) ?>" class="hover:text-indigo-700"><?= html_escape($title) ?></a>
        <span aria-hidden="true" class="mx-2">/</span>
        <span aria-current="page"><?= html_escape(isset($labels['finance_view_document']) ? $labels['finance_view_document'] : 'View Document') ?></span>
    </nav>
    <header class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($finance_document_number) ?></h1>
            <p class="mt-2 text-sm text-slate-600"><?= html_escape($title) ?></p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex w-fit items-center rounded-md border px-2.5 py-1 text-xs font-semibold <?= html_escape($status_style) ?>"><?= html_escape(isset($status_labels[$status]) ? $status_labels[$status] : $status) ?></span>
            <?php if ($payment_status !== ''): ?><span class="inline-flex w-fit items-center rounded-md border px-2.5 py-1 text-xs font-semibold <?= html_escape($payment_style) ?>"><?= html_escape($payment_status) ?></span><?php endif; ?>
        </div>
    </header>

    <?php if ($finance_document_integration_reference !== ''): ?>
    <section class="mb-5 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-950">
        <p class="font-semibold"><?= html_escape(isset($labels['finance_integration_source']) ? $labels['finance_integration_source'] : 'Integration data') ?></p>
        <p class="mt-1 font-mono text-xs"><?= html_escape($finance_document_integration_reference) ?></p>
        <?php if ($finance_document_integration_party !== ''): ?><p class="mt-1"><?= html_escape($finance_document_integration_party) ?></p><?php endif; ?>
    </section>
    <?php endif; ?>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <dl class="grid gap-5 sm:grid-cols-2">
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= html_escape($document_label) ?></dt><dd class="mt-1 text-sm font-medium text-slate-900"><?= html_escape($finance_document_record['document_date']) ?></dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_due_date']) ? $labels['finance_due_date'] : 'Due date') ?></dt><dd class="mt-1 text-sm font-medium text-slate-900"><?= html_escape($finance_document_record['due_date'] !== NULL && $finance_document_record['due_date'] !== '' ? $finance_document_record['due_date'] : '—') ?></dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= html_escape($party_label) ?></dt><dd class="mt-1 text-sm font-medium text-slate-900"><?= html_escape(isset($finance_document_lookup_labels['person_id']) ? $finance_document_lookup_labels['person_id'] : '—') ?></dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= html_escape($account_label) ?></dt><dd class="mt-1 text-sm font-medium text-slate-900"><?= html_escape(isset($finance_document_lookup_labels['account_id']) ? $finance_document_lookup_labels['account_id'] : '—') ?></dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_tax_code']) ? $labels['finance_tax_code'] : 'Tax code') ?></dt><dd class="mt-1 text-sm font-medium text-slate-900"><?= html_escape(isset($finance_document_lookup_labels['tax_id']) && $finance_document_lookup_labels['tax_id'] !== '' ? $finance_document_lookup_labels['tax_id'] : '—') ?></dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_column_currency']) ? $labels['finance_column_currency'] : 'Currency') ?></dt><dd class="mt-1 text-sm font-medium text-slate-900"><?= html_escape($finance_document_record['currency_code']) ?></dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_exchange_rate_base']) ? $labels['finance_exchange_rate_base'] : 'Exchange rate') ?></dt><dd class="mt-1 text-sm font-medium text-slate-900"><?= html_escape($amount($finance_document_record['exchange_rate'])) ?></dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_doc_line_amount']) ? $labels['finance_doc_line_amount'] : 'Amount before tax') ?></dt><dd class="mt-1 text-sm font-medium text-slate-900"><?= html_escape($amount($finance_document_record['amount'])) ?></dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_tax_amount']) ? $labels['finance_tax_amount'] : 'Tax') ?></dt><dd class="mt-1 text-sm font-medium text-slate-900"><?= html_escape($amount($finance_document_record['tax_total'])) ?></dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_column_total']) ? $labels['finance_column_total'] : 'Total') ?></dt><dd class="mt-1 text-sm font-semibold text-slate-950"><?= html_escape($finance_document_record['currency_code'] . ' ' . $amount($finance_document_record['total_amount'])) ?></dd></div>
            <?php if (is_array($financials)): ?>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_payment_paid']) ? $labels['finance_payment_paid'] : 'Paid') ?></dt><dd class="mt-1 text-sm font-medium text-emerald-800"><?= html_escape($financials['currency_code'] . ' ' . $amount($financials['paid_amount'])) ?></dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_payment_outstanding']) ? $labels['finance_payment_outstanding'] : 'Outstanding') ?></dt><dd class="mt-1 text-sm font-bold text-slate-950"><?= html_escape($financials['currency_code'] . ' ' . $amount($financials['outstanding_amount'])) ?></dd></div>
            <?php endif; ?>
            <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_doc_line_description']) ? $labels['finance_doc_line_description'] : 'Description') ?></dt><dd class="mt-1 text-sm text-slate-900"><?= html_escape($finance_document_record['description']) ?></dd></div>
            <?php if (!empty($finance_document_record['memo'])): ?><div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_memo']) ? $labels['finance_memo'] : 'Memo') ?></dt><dd class="mt-1 whitespace-pre-line text-sm text-slate-900"><?= html_escape($finance_document_record['memo']) ?></dd></div><?php endif; ?>
        </dl>
        <div class="mt-7 border-t border-slate-100 pt-5">
            <a href="/admin/finance/<?= html_escape($finance_document_slug) ?>" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"><?= html_escape(isset($labels['finance_back']) ? $labels['finance_back'] : 'Back') ?></a>
        </div>
    </section>
</main>

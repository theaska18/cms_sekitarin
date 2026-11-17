<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Etiquetas del módulo de Finanzas.
$lang['finance_column_base_currency'] = 'Moneda base';
$lang['finance_column_tax_inclusive'] = 'Impuesto incluido';

$lang['finance_field_base_currency'] = 'Moneda base (código ISO)';
$lang['finance_field_base_currency_hint'] = 'Código ISO de 3 letras de la moneda contable, p. ej. IDR o USD. Todos los asientos e informes se convierten a esta moneda.';
$lang['finance_field_tax_inclusive_default'] = 'Impuesto incluido por defecto';
$lang['finance_field_tax_inclusive_default_hint'] = 'Determina si los totales de los documentos de venta/compra incluyen impuestos de forma predeterminada.';
$lang['finance_field_receivable_account_id'] = 'Cuenta de control de cuentas por cobrar';
$lang['finance_field_receivable_account_id_hint'] = 'Cuenta de activo para los saldos pendientes de clientes. Recomendado: 1100 Piutang Usaha.';
$lang['finance_field_payable_account_id'] = 'Cuenta de control de cuentas por pagar';
$lang['finance_field_payable_account_id_hint'] = 'Cuenta de pasivo para los saldos pendientes de proveedores. Recomendado: 2000 Utang Usaha.';
$lang['finance_field_fx_gain_account_id'] = 'Cuenta de ganancias por tipo de cambio';
$lang['finance_field_fx_gain_account_id_hint'] = 'Cuenta de ingresos para ganancias por tipo de cambio. Recomendado: 8000 Keuntungan Selisih Kurs.';
$lang['finance_field_fx_loss_account_id'] = 'Cuenta de pérdidas por tipo de cambio';
$lang['finance_field_fx_loss_account_id_hint'] = 'Cuenta de gastos para pérdidas por tipo de cambio. Recomendado: 8100 Kerugian Selisih Kurs.';

$lang['finance_option_tax_exclusive'] = 'No — sin impuesto';
$lang['finance_option_tax_inclusive'] = 'Sí — impuesto incluido';


// Income/expense transaction pages.
$lang['finance_tx_breadcrumb'] = 'Ruta de navegación';
$lang['finance_tx_intro'] = 'Los borradores no afectan al libro mayor. Al contabilizar se crea un asiento equilibrado limitado al tenant; las transacciones contabilizadas no se pueden editar.';
$lang['finance_tx_intro_all'] = 'Flujo combinado de documentos de Finanzas y transacciones manuales; los borradores están señalados y no afectan a los informes.';
$lang['finance_tx_add_draft'] = 'Añadir borrador';
$lang['finance_tx_unavailable'] = 'El almacenamiento de transacciones de Finanzas no está disponible.';
$lang['finance_tx_writes_unavailable'] = 'Las escrituras seguras de Finanzas no están disponibles.';
$lang['finance_tx_column_number'] = 'Número';
$lang['finance_tx_column_type'] = 'Tipo';
$lang['finance_tx_column_date'] = 'Fecha';
$lang['finance_tx_column_amount'] = 'Importe';
$lang['finance_tx_column_currency'] = 'Moneda';
$lang['finance_tx_column_status'] = 'Estado';
$lang['finance_tx_action_post'] = 'Contabilizar';
$lang['finance_tx_type_income'] = 'Ingresos';
$lang['finance_tx_type_expense'] = 'Gastos';
$lang['finance_tx_state_draft'] = 'Borrador';
$lang['finance_tx_state_posted'] = 'Contabilizada';
$lang['finance_tx_dt_empty'] = 'Aún no hay transacciones';
$lang['finance_tx_dt_info_empty'] = 'No hay transacciones';
$lang['finance_tx_dt_zero'] = 'No hay transacciones coincidentes';
$lang['finance_tx_status_draft_saved'] = 'Borrador guardado.';
$lang['finance_tx_status_posted'] = 'Transacción contabilizada en el libro mayor.';
$lang['finance_tx_status_period_closed'] = 'Ningún periodo contable abierto incluye esta fecha de transacción.';
$lang['finance_tx_status_period_ambiguous'] = 'Más de un periodo contable coincide con esta fecha de transacción. Contacte con un administrador.';
$lang['finance_tx_status_tax_account_missing'] = 'Seleccione un código de impuesto vinculado a una cuenta de impuesto activa antes de contabilizar.';
$lang['finance_tx_status_already_posted'] = 'Ya existe un asiento para esta transacción.';
$lang['finance_tx_status_account_invalid'] = 'La cuenta seleccionada no es válida o está inactiva.';
$lang['finance_tx_status_not_postable'] = 'Esta transacción no se puede contabilizar en su estado actual.';
$lang['finance_tx_status_unavailable'] = 'El almacenamiento de transacciones de Finanzas no está disponible.';
$lang['finance_tx_status_post_error'] = 'No se pudo contabilizar la transacción.';
$lang['finance_tx_status_unknown'] = 'La acción solicitada no se pudo completar (%s).';
$lang['finance_tx_new_draft'] = 'Nuevo borrador';
$lang['finance_tx_edit_draft'] = 'Editar borrador';
$lang['finance_tx_heading_new'] = 'Nuevo %s';
$lang['finance_tx_heading_edit'] = 'Editar borrador %s';
$lang['finance_tx_intro_form'] = 'Introduzca una línea sin impuestos. Elija una cuenta de efectivo compatible con la moneda y un tipo de cambio a la moneda base del tenant. Guardar solo crea un borrador.';
$lang['finance_tx_form_error'] = 'No se pudo guardar el borrador. Revise los campos e inténtelo de nuevo.';
$lang['finance_tx_field_transaction_on'] = 'Fecha de la transacción';
$lang['finance_tx_field_cash_account'] = 'Cuenta de efectivo o banco';
$lang['finance_tx_field_revenue_account'] = 'Cuenta de ingresos';
$lang['finance_tx_field_expense_account'] = 'Cuenta de gastos';
$lang['finance_tx_field_category'] = 'Categoría';
$lang['finance_tx_field_tax'] = 'Código de impuesto';
$lang['finance_tx_field_department'] = 'Departamento';
$lang['finance_tx_field_amount'] = 'Importe de la línea (antes de impuestos)';
$lang['finance_tx_field_exchange_rate'] = 'Tipo de cambio a la moneda base';
$lang['finance_tx_field_description'] = 'Descripción';
$lang['finance_tx_hint_exchange_rate'] = 'Para una transacción en la moneda base, use 1. Para otras monedas, introduzca unidades de moneda base por unidad de moneda de la transacción.';
$lang['finance_tx_save_draft'] = 'Guardar borrador';
$lang['finance_tx_error_cash_account_id_required'] = 'Seleccione una cuenta de efectivo o banco.';
$lang['finance_tx_error_account_id_required'] = 'Seleccione la cuenta relacionada.';
$lang['finance_tx_error_transaction_on_invalid'] = 'Introduzca una fecha de transacción válida.';
$lang['finance_tx_error_amount_invalid'] = 'Introduzca un importe válido mayor que cero.';
$lang['finance_tx_error_exchange_rate_invalid'] = 'Introduzca un tipo de cambio válido mayor que cero.';
$lang['finance_tx_error_description_too_long'] = 'La descripción debe tener 240 caracteres o menos.';
$lang['finance_tx_error_transaction_unavailable'] = 'El almacenamiento de transacciones de Finanzas no está disponible.';
$lang['finance_tx_error_cash_account_invalid'] = 'La cuenta de efectivo o banco seleccionada no es válida o está inactiva.';
$lang['finance_tx_error_account_invalid'] = 'La cuenta seleccionada no es válida o está inactiva.';
$lang['finance_tx_error_category_invalid'] = 'La categoría seleccionada no es válida o está inactiva.';
$lang['finance_tx_error_department_invalid'] = 'El departamento seleccionado no es válido o está inactivo.';
$lang['finance_tx_error_tax_invalid'] = 'El código de impuesto seleccionado no es válido o está inactivo.';
$lang['finance_tx_error_not_found'] = 'No se encontró el borrador solicitado.';
$lang['finance_tx_error_not_editable'] = 'Solo se pueden editar los borradores.';
$lang['finance_tx_error_save_error'] = 'No se pudo guardar el borrador. Inténtelo de nuevo.';

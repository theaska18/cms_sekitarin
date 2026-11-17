<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Libellés du module Finance.
$lang['finance_column_base_currency'] = 'Devise de base';
$lang['finance_column_tax_inclusive'] = 'Taxes incluses';

$lang['finance_field_base_currency'] = 'Devise de base (code ISO)';
$lang['finance_field_base_currency_hint'] = 'Code ISO à 3 lettres de la devise comptable, par ex. IDR ou USD. Tous les journaux et rapports sont convertis dans cette devise.';
$lang['finance_field_tax_inclusive_default'] = 'Taxes incluses par défaut';
$lang['finance_field_tax_inclusive_default_hint'] = 'Détermine si les totaux des documents de vente/achat incluent les taxes par défaut.';
$lang['finance_field_receivable_account_id'] = 'Compte collectif des créances clients';
$lang['finance_field_receivable_account_id_hint'] = 'Compte d\'actif pour les soldes de créances clients. Recommandé : 1100 Piutang Usaha.';
$lang['finance_field_payable_account_id'] = 'Compte collectif des dettes fournisseurs';
$lang['finance_field_payable_account_id_hint'] = 'Compte de passif pour les soldes de dettes fournisseurs. Recommandé : 2000 Utang Usaha.';
$lang['finance_field_fx_gain_account_id'] = 'Compte de gains de change';
$lang['finance_field_fx_gain_account_id_hint'] = 'Compte de produits pour les gains de change. Recommandé : 8000 Keuntungan Selisih Kurs.';
$lang['finance_field_fx_loss_account_id'] = 'Compte de pertes de change';
$lang['finance_field_fx_loss_account_id_hint'] = 'Compte de charges pour les pertes de change. Recommandé : 8100 Kerugian Selisih Kurs.';

$lang['finance_option_tax_exclusive'] = 'Non — hors taxes';
$lang['finance_option_tax_inclusive'] = 'Oui — taxes incluses';


// Income/expense transaction pages.
$lang['finance_tx_breadcrumb'] = 'Fil d\'Ariane';
$lang['finance_tx_intro'] = 'Les brouillons n\'affectent pas le grand livre. La validation crée une écriture équilibrée limitée au tenant ; les transactions validées ne peuvent plus être modifiées.';
$lang['finance_tx_intro_all'] = 'Flux combiné des documents Finance et des transactions manuelles ; les brouillons sont signalés et n\'affectent pas les rapports.';
$lang['finance_tx_add_draft'] = 'Ajouter un brouillon';
$lang['finance_tx_unavailable'] = 'Le stockage des transactions Finance est indisponible.';
$lang['finance_tx_writes_unavailable'] = 'Les écritures Finance sécurisées sont indisponibles.';
$lang['finance_tx_column_number'] = 'Numéro';
$lang['finance_tx_column_type'] = 'Type';
$lang['finance_tx_column_date'] = 'Date';
$lang['finance_tx_column_amount'] = 'Montant';
$lang['finance_tx_column_currency'] = 'Devise';
$lang['finance_tx_column_status'] = 'Statut';
$lang['finance_tx_action_post'] = 'Valider';
$lang['finance_tx_type_income'] = 'Revenus';
$lang['finance_tx_type_expense'] = 'Dépenses';
$lang['finance_tx_state_draft'] = 'Brouillon';
$lang['finance_tx_state_posted'] = 'Validée';
$lang['finance_tx_dt_empty'] = 'Aucune transaction pour le moment';
$lang['finance_tx_dt_info_empty'] = 'Aucune transaction';
$lang['finance_tx_dt_zero'] = 'Aucune transaction correspondante';
$lang['finance_tx_status_draft_saved'] = 'Brouillon enregistré.';
$lang['finance_tx_status_posted'] = 'Transaction validée dans le grand livre.';
$lang['finance_tx_status_period_closed'] = 'Aucune période comptable ouverte ne couvre cette date de transaction.';
$lang['finance_tx_status_period_ambiguous'] = 'Plusieurs périodes comptables correspondent à cette date de transaction. Contactez un administrateur.';
$lang['finance_tx_status_tax_account_missing'] = 'Sélectionnez un code de taxe lié à un compte de taxe actif avant de valider.';
$lang['finance_tx_status_already_posted'] = 'Une écriture existe déjà pour cette transaction.';
$lang['finance_tx_status_account_invalid'] = 'Le compte sélectionné est invalide ou inactif.';
$lang['finance_tx_status_not_postable'] = 'Cette transaction ne peut pas être validée dans son état actuel.';
$lang['finance_tx_status_unavailable'] = 'Le stockage des transactions Finance est indisponible.';
$lang['finance_tx_status_post_error'] = 'La transaction n\'a pas pu être validée.';
$lang['finance_tx_status_unknown'] = 'L\'action demandée n\'a pas pu être effectuée (%s).';
$lang['finance_tx_new_draft'] = 'Nouveau brouillon';
$lang['finance_tx_edit_draft'] = 'Modifier le brouillon';
$lang['finance_tx_heading_new'] = 'Nouveau %s';
$lang['finance_tx_heading_edit'] = 'Modifier le brouillon %s';
$lang['finance_tx_intro_form'] = 'Saisissez une ligne hors taxes. Choisissez un compte de trésorerie compatible avec la devise et un taux de change vers la devise de base du tenant. L\'enregistrement crée uniquement un brouillon.';
$lang['finance_tx_form_error'] = 'Le brouillon n\'a pas pu être enregistré. Vérifiez les champs et réessayez.';
$lang['finance_tx_field_transaction_on'] = 'Date de la transaction';
$lang['finance_tx_field_cash_account'] = 'Compte de trésorerie ou bancaire';
$lang['finance_tx_field_revenue_account'] = 'Compte de revenus';
$lang['finance_tx_field_expense_account'] = 'Compte de dépenses';
$lang['finance_tx_field_category'] = 'Catégorie';
$lang['finance_tx_field_tax'] = 'Code de taxe';
$lang['finance_tx_field_department'] = 'Service';
$lang['finance_tx_field_amount'] = 'Montant de la ligne (hors taxes)';
$lang['finance_tx_field_exchange_rate'] = 'Taux de change vers la devise de base';
$lang['finance_tx_field_description'] = 'Description';
$lang['finance_tx_hint_exchange_rate'] = 'Pour une transaction en devise de base, utilisez 1. Pour les autres devises, indiquez les unités de devise de base par unité de devise de la transaction.';
$lang['finance_tx_save_draft'] = 'Enregistrer le brouillon';
$lang['finance_tx_error_cash_account_id_required'] = 'Sélectionnez un compte de trésorerie ou bancaire.';
$lang['finance_tx_error_account_id_required'] = 'Sélectionnez le compte associé.';
$lang['finance_tx_error_transaction_on_invalid'] = 'Saisissez une date de transaction valide.';
$lang['finance_tx_error_amount_invalid'] = 'Saisissez un montant valide supérieur à zéro.';
$lang['finance_tx_error_exchange_rate_invalid'] = 'Saisissez un taux de change valide supérieur à zéro.';
$lang['finance_tx_error_description_too_long'] = 'La description doit comporter 240 caractères ou moins.';
$lang['finance_tx_error_transaction_unavailable'] = 'Le stockage des transactions Finance est indisponible.';
$lang['finance_tx_error_cash_account_invalid'] = 'Le compte de trésorerie ou bancaire sélectionné est invalide ou inactif.';
$lang['finance_tx_error_account_invalid'] = 'Le compte sélectionné est invalide ou inactif.';
$lang['finance_tx_error_category_invalid'] = 'La catégorie sélectionnée est invalide ou inactive.';
$lang['finance_tx_error_department_invalid'] = 'Le service sélectionné est invalide ou inactif.';
$lang['finance_tx_error_tax_invalid'] = 'Le code de taxe sélectionné est invalide ou inactif.';
$lang['finance_tx_error_not_found'] = 'Le brouillon demandé est introuvable.';
$lang['finance_tx_error_not_editable'] = 'Seuls les brouillons peuvent être modifiés.';
$lang['finance_tx_error_save_error'] = 'Le brouillon n\'a pas pu être enregistré. Réessayez.';

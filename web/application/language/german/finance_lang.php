<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Beschriftungen des Finanzmoduls.
$lang['finance_column_base_currency'] = 'Basiswährung';
$lang['finance_column_tax_inclusive'] = 'Steuer inklusive';

$lang['finance_field_base_currency'] = 'Basiswährung (ISO-Code)';
$lang['finance_field_base_currency_hint'] = 'Dreistelliger ISO-Code der Buchhaltungswährung, z. B. IDR oder USD. Alle Buchungen und Berichte werden in diese Währung umgerechnet.';
$lang['finance_field_tax_inclusive_default'] = 'Steuer standardmäßig inklusive';
$lang['finance_field_tax_inclusive_default_hint'] = 'Legt fest, ob die Summen von Verkaufs-/Einkaufsbelegen standardmäßig Steuern enthalten.';
$lang['finance_field_receivable_account_id'] = 'Sammelkonto Forderungen aus Lieferungen und Leistungen';
$lang['finance_field_receivable_account_id_hint'] = 'Aktivkonto für offene Kundenforderungen. Empfohlen: 1100 Piutang Usaha.';
$lang['finance_field_payable_account_id'] = 'Sammelkonto Verbindlichkeiten aus Lieferungen und Leistungen';
$lang['finance_field_payable_account_id_hint'] = 'Passivkonto für offene Lieferantenverbindlichkeiten. Empfohlen: 2000 Utang Usaha.';
$lang['finance_field_fx_gain_account_id'] = 'Konto für Währungsgewinne';
$lang['finance_field_fx_gain_account_id_hint'] = 'Ertragskonto für Wechselkursgewinne. Empfohlen: 8000 Keuntungan Selisih Kurs.';
$lang['finance_field_fx_loss_account_id'] = 'Konto für Währungsverluste';
$lang['finance_field_fx_loss_account_id_hint'] = 'Aufwandskonto für Wechselkursverluste. Empfohlen: 8100 Kerugian Selisih Kurs.';

$lang['finance_option_tax_exclusive'] = 'Nein — ohne Steuer';
$lang['finance_option_tax_inclusive'] = 'Ja — inklusive Steuer';


// Income/expense transaction pages.
$lang['finance_tx_breadcrumb'] = 'Brotkrümelnavigation';
$lang['finance_tx_intro'] = 'Entwürfe wirken sich nicht auf das Hauptbuch aus. Beim Buchen wird ein ausgeglichener, mandantenbezogener Buchungssatz erstellt; gebuchte Transaktionen können nicht bearbeitet werden.';
$lang['finance_tx_intro_all'] = 'Kombinierte Übersicht aus Finanzdokumenten und manuellen Transaktionen; Entwürfe sind gekennzeichnet und wirken sich nicht auf Berichte aus.';
$lang['finance_tx_add_draft'] = 'Entwurf hinzufügen';
$lang['finance_tx_unavailable'] = 'Der Speicher für Finanztransaktionen ist nicht verfügbar.';
$lang['finance_tx_writes_unavailable'] = 'Sichere Finanzbuchungen sind nicht verfügbar.';
$lang['finance_tx_column_number'] = 'Nummer';
$lang['finance_tx_column_type'] = 'Typ';
$lang['finance_tx_column_date'] = 'Datum';
$lang['finance_tx_column_amount'] = 'Betrag';
$lang['finance_tx_column_currency'] = 'Währung';
$lang['finance_tx_column_status'] = 'Status';
$lang['finance_tx_action_post'] = 'Buchen';
$lang['finance_tx_type_income'] = 'Einnahmen';
$lang['finance_tx_type_expense'] = 'Ausgaben';
$lang['finance_tx_state_draft'] = 'Entwurf';
$lang['finance_tx_state_posted'] = 'Gebucht';
$lang['finance_tx_dt_empty'] = 'Noch keine Transaktionen';
$lang['finance_tx_dt_info_empty'] = 'Keine Transaktionen';
$lang['finance_tx_dt_zero'] = 'Keine passenden Transaktionen';
$lang['finance_tx_status_draft_saved'] = 'Entwurf gespeichert.';
$lang['finance_tx_status_posted'] = 'Transaktion ins Hauptbuch gebucht.';
$lang['finance_tx_status_period_closed'] = 'Keine offene Buchungsperiode umfasst dieses Transaktionsdatum.';
$lang['finance_tx_status_period_ambiguous'] = 'Mehrere Buchungsperioden passen zu diesem Transaktionsdatum. Wenden Sie sich an einen Administrator.';
$lang['finance_tx_status_tax_account_missing'] = 'Wählen Sie vor dem Buchen einen Steuercode, der mit einem aktiven Steuerkonto verknüpft ist.';
$lang['finance_tx_status_already_posted'] = 'Für diese Transaktion existiert bereits ein Buchungssatz.';
$lang['finance_tx_status_account_invalid'] = 'Das ausgewählte Konto ist ungültig oder inaktiv.';
$lang['finance_tx_status_not_postable'] = 'Diese Transaktion kann im aktuellen Status nicht gebucht werden.';
$lang['finance_tx_status_unavailable'] = 'Der Speicher für Finanztransaktionen ist nicht verfügbar.';
$lang['finance_tx_status_post_error'] = 'Die Transaktion konnte nicht gebucht werden.';
$lang['finance_tx_status_unknown'] = 'Die angeforderte Aktion konnte nicht abgeschlossen werden (%s).';
$lang['finance_tx_new_draft'] = 'Neuer Entwurf';
$lang['finance_tx_edit_draft'] = 'Entwurf bearbeiten';
$lang['finance_tx_heading_new'] = 'Neu: %s';
$lang['finance_tx_heading_edit'] = 'Entwurf bearbeiten: %s';
$lang['finance_tx_intro_form'] = 'Geben Sie eine steuerfreie Position ein. Wählen Sie ein währungskompatibles Kassenkonto und einen Wechselkurs zur Basiswährung des Mandanten. Beim Speichern wird nur ein Entwurf erstellt.';
$lang['finance_tx_form_error'] = 'Der Entwurf konnte nicht gespeichert werden. Prüfen Sie die Felder und versuchen Sie es erneut.';
$lang['finance_tx_field_transaction_on'] = 'Transaktionsdatum';
$lang['finance_tx_field_cash_account'] = 'Kassen- oder Bankkonto';
$lang['finance_tx_field_revenue_account'] = 'Ertragskonto';
$lang['finance_tx_field_expense_account'] = 'Aufwandskonto';
$lang['finance_tx_field_category'] = 'Kategorie';
$lang['finance_tx_field_tax'] = 'Steuercode';
$lang['finance_tx_field_department'] = 'Abteilung';
$lang['finance_tx_field_amount'] = 'Positionsbetrag (vor Steuer)';
$lang['finance_tx_field_exchange_rate'] = 'Wechselkurs zur Basiswährung';
$lang['finance_tx_field_description'] = 'Beschreibung';
$lang['finance_tx_hint_exchange_rate'] = 'Verwenden Sie für eine Transaktion in der Basiswährung 1. Geben Sie bei anderen Währungen Basiswährungseinheiten pro Einheit der Transaktionswährung ein.';
$lang['finance_tx_save_draft'] = 'Entwurf speichern';
$lang['finance_tx_error_cash_account_id_required'] = 'Wählen Sie ein Kassen- oder Bankkonto.';
$lang['finance_tx_error_account_id_required'] = 'Wählen Sie das zugehörige Konto.';
$lang['finance_tx_error_transaction_on_invalid'] = 'Geben Sie ein gültiges Transaktionsdatum ein.';
$lang['finance_tx_error_amount_invalid'] = 'Geben Sie einen gültigen Betrag größer als null ein.';
$lang['finance_tx_error_exchange_rate_invalid'] = 'Geben Sie einen gültigen Wechselkurs größer als null ein.';
$lang['finance_tx_error_description_too_long'] = 'Die Beschreibung darf höchstens 240 Zeichen enthalten.';
$lang['finance_tx_error_transaction_unavailable'] = 'Der Speicher für Finanztransaktionen ist nicht verfügbar.';
$lang['finance_tx_error_cash_account_invalid'] = 'Das ausgewählte Kassen- oder Bankkonto ist ungültig oder inaktiv.';
$lang['finance_tx_error_account_invalid'] = 'Das ausgewählte Konto ist ungültig oder inaktiv.';
$lang['finance_tx_error_category_invalid'] = 'Die ausgewählte Kategorie ist ungültig oder inaktiv.';
$lang['finance_tx_error_department_invalid'] = 'Die ausgewählte Abteilung ist ungültig oder inaktiv.';
$lang['finance_tx_error_tax_invalid'] = 'Der ausgewählte Steuercode ist ungültig oder inaktiv.';
$lang['finance_tx_error_not_found'] = 'Der angeforderte Entwurf wurde nicht gefunden.';
$lang['finance_tx_error_not_editable'] = 'Nur Entwürfe können bearbeitet werden.';
$lang['finance_tx_error_save_error'] = 'Der Entwurf konnte nicht gespeichert werden. Versuchen Sie es erneut.';

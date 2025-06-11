<?php
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    BESTLAB Ernest <contact@bestlab.pl>
 * @copyright 2007-2025 PrestaShop SA
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminBestProgramPartnerskiController extends ModuleAdminController
{
    private $commissionTiers;
    private $exceptionCodesForControlDisplay;
    private $ignoredCodes;
    private $excludedProductIdsFromSum;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->lang = false;
        $this->context = Context::getContext();
        $this->table = 'pshow_rp_partner';
        $this->className = 'BestProgramPartnerski';

        parent::__construct();

        $this->toolbar_title = $this->l('Raportowanie PP');

        $configFilePath = _PS_MODULE_DIR_ . $this->module->name . '/config/commission_tiers.php';
        if (file_exists($configFilePath)) {
            $config = require($configFilePath);
            $this->commissionTiers = $config['commissionTiers'];
            $this->exceptionCodesForControlDisplay = array_map('intval', $config['exception_codes_for_control_display']);
            $this->ignoredCodes = array_map('intval', $config['ignored_codes']);
            $this->excludedProductIdsFromSum = array_map('intval', $config['excluded_product_ids_from_sum']);
        } else {
            $this->commissionTiers = [
                ['threshold' => 5000.00,  'rate' => 0.05],
                ['threshold' => 7000.00,  'rate' => 0.06],
                ['threshold' => 9000.00,  'rate' => 0.07],
                ['threshold' => 11000.00, 'rate' => 0.08],
                ['threshold' => 13000.00, 'rate' => 0.09],
                ['threshold' => 15000.00, 'rate' => 0.10],
                ['threshold' => null,     'rate' => 0.12],
            ];
            $this->exceptionCodesForControlDisplay = [];
            $this->ignoredCodes = [];
            $this->excludedProductIdsFromSum = [];
        }
    }

    public function setMedia($is = false)
    {
        parent::setMedia($is);
        $this->addJqueryUI('jquery.ui.datepicker');
        $this->addJquery('hoverIntent');
        $this->addJquery('chosen');
        $this->addJqueryUI('ui.dialog');
        $this->addCSS(__PS_BASE_URI__ . 'modules/' . $this->module->name . '/views/css/admin.css');
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitFilter')) {
            // Logika przetwarzania formularza, jeśli jest potrzebna
        }

        return parent::postProcess();
    }

    // --- NOWA METODA AJAX DLA MODALA: SZCZEGÓŁY ZAMÓWIEŃ BL (ORDER_DETAIL) ---
    public function ajaxProcessGetOrderDetailsBlForModal()
    {
        header('Content-Type: text/html; charset=utf-8');

        $id_partner = (int)Tools::getValue('id_partner');
        $id_cart_rule = (int)Tools::getValue('id_cart_rule');
        $date_from = Tools::getValue('date_from');
        $date_to = Tools::getValue('date_to');

        if (!Validate::isDate($date_from) || !Validate::isDate($date_to)) {
            $date_from = date('Y-m-01 00:00:00');
            $date_to = date('Y-m-t 23:59:59');
        }

        $order_details_bl = [];
        if ($id_partner > 0 && $id_cart_rule > 0) {
            $db = Db::getInstance();
            $query = new DbQuery();
            $query->select('
                o.id_order,
                od.product_id,
                od.product_name,
                od.product_quantity,
                od.total_price_tax_excl,
                od.total_price_tax_incl,
                o.total_paid_tax_excl AS order_total_paid_tax_excl_overall
            ');
            $query->from('orders', 'o');
            $query->innerJoin('order_detail', 'od', 'o.id_order = od.id_order');
            $query->innerJoin('order_cart_rule', 'ocr', 'o.id_order = ocr.id_order');
            $query->innerJoin('cart_rule', 'cr', 'ocr.id_cart_rule = cr.id_cart_rule');
            $query->innerJoin('pshow_rp_partner', 'prt', 'cr.id_cart_rule = prt.id_voucher');

            $query->where('o.date_add >= \'' . pSQL($date_from) . '\' AND o.date_add <= \'' . pSQL($date_to) . '\'');
            $query->where('o.current_state = 4 AND o.valid = 1');
            $query->where('od.product_id NOT IN (' . implode(',', $this->excludedProductIdsFromSum) . ')');
            $query->where('prt.id_partner = ' . (int)$id_partner);
            $query->where('cr.id_cart_rule = ' . (int)$id_cart_rule);

            $query->groupBy('o.id_order, od.id_order_detail, od.product_id, od.product_name, od.product_quantity, od.total_price_tax_excl, od.total_price_tax_incl, o.total_paid_tax_excl');
            $query->orderBy('o.id_order ASC, od.product_name ASC');

            $results = $db->executeS($query);

            $grouped_orders = [];
            foreach ($results as $item) {
                $order_id = (int)$item['id_order'];
                if (!isset($grouped_orders[$order_id])) {
                    $grouped_orders[$order_id] = [
                        'id_order' => $order_id,
                        'order_total_paid_tax_excl_overall' => Tools::displayPrice($item['order_total_paid_tax_excl_overall'], $this->context->currency),
                        'products' => [],
                        'calculated_sum_from_detail' => 0.0,
                    ];
                }

                $product_line_net_after_discount = (float)$item['total_price_tax_excl'] * (1 - 0.15);

                $grouped_orders[$order_id]['products'][] = [
                    'name' => $item['product_name'],
                    'quantity' => (int)$item['product_quantity'],
                    'total_price_tax_excl_original' => Tools::displayPrice($item['total_price_tax_excl'], $this->context->currency),
                    'total_price_tax_excl_discounted' => Tools::displayPrice($product_line_net_after_discount, $this->context->currency),
                ];
                $grouped_orders[$order_id]['calculated_sum_from_detail'] += $product_line_net_after_discount;
            }

            foreach ($grouped_orders as &$order) {
                $order['calculated_sum_from_detail_formatted'] = Tools::displayPrice($order['calculated_sum_from_detail'], $this->context->currency);
            }

            $order_details_bl = array_values($grouped_orders);
        }

        // --- BUDOWANIE HTML W PHP DLA MODALA BL ---
        $html_output = '';
        if (empty($order_details_bl)) {
            $html_output = '<p>Brak szczegółowych danych dla tego okresu i kodu.</p>';
        } else {
            // Liczba koszyków
            $html_output .= '<p><strong>Koszyków: ' . count($order_details_bl) . '</strong></p>';

            $html_output .= '<table class="table table-bordered table-striped modal-report-table">';
            $html_output .= '<thead><tr><th>Koszyk ID</th><th>Wartość (całość)</th><th colspan="2">Produkty (Netto / Netto po -15%)</th></tr></thead><tbody>';
            foreach ($order_details_bl as $order) {
                $html_output .= '<tr>';
                $html_output .= '<td>' . $order['id_order'] . '</td>';
                $html_output .= '<td>' . $order['order_total_paid_tax_excl_overall'] . '</td>';
                $html_output .= '<td colspan="2"><ul class="list-unstyled">';
                foreach ($order['products'] as $product) {
                    $html_output .= '<li>' . $product['name'] . ' x ' . $product['quantity'] . ' (Netto: ' . $product['total_price_tax_excl_original'] . ') (Netto po -15%: ' . $product['total_price_tax_excl_discounted'] . ')</li>';
                }
                $html_output .= '</ul></td>';
                $html_output .= '</tr>';
                $html_output .= '<tr><td colspan="4" class="text-right"><strong>Suma koszyka BL: ' . $order['calculated_sum_from_detail_formatted'] . '</strong></td></tr>';
            }
            $html_output .= '</tbody></table>';
        }

        die($html_output);
    }

    public function ajaxProcessGetProvisionDetailsForModal()
    {
        header('Content-Type: text/html; charset=utf-8');

        $id_partner = (int)Tools::getValue('id_partner');
        $id_cart_rule = (int)Tools::getValue('id_cart_rule');
        $date_from = Tools::getValue('date_from');
        $date_to = Tools::getValue('date_to');

        if (!Validate::isDate($date_from) || !Validate::isDate($date_to)) {
            $date_from = date('Y-m-01 00:00:00');
            $date_to = date('Y-m-t 23:59:59');
        }

        $provision_details = [];
        if ($id_partner > 0 && $id_cart_rule > 0) {
            $db = Db::getInstance();
            $query = new DbQuery();
            $query->select('
                prv.id_order,
                prv.order_value,
                prv.provision_value
            ');
            $query->from('pshow_rp_provision', 'prv');
            $query->innerJoin('orders', 'o', 'prv.id_order = o.id_order');
            $query->innerJoin('order_cart_rule', 'ocr', 'o.id_order = ocr.id_order');

            $query->where('prv.date_add >= \'' . pSQL($date_from) . '\' AND prv.date_add <= \'' . pSQL($date_to) . '\'');
            $query->where('prv.confirmed = 1');
            $query->where('o.current_state = 4 AND o.valid = 1');
            $query->where('prv.id_partner = ' . (int)$id_partner);
            $query->where('ocr.id_cart_rule = ' . (int)$id_cart_rule);

            $query->groupBy('prv.id_order, prv.order_value, prv.provision_value');
            $query->orderBy('prv.id_order ASC');

            $results = $db->executeS($query);

            foreach ($results as $item) {
                $provision_details[] = [
                    'id_order' => (int)$item['id_order'],
                    'order_value' => Tools::displayPrice($item['order_value'], $this->context->currency),
                    'provision_value' => Tools::displayPrice($item['provision_value'], $this->context->currency),
                ];
            }
        }

        // --- BUDOWANIE HTML W PHP DLA MODALA PP ---
        $html_output = '';
        if (empty($provision_details)) {
            $html_output = '<p>Brak szczegółowych danych dla tego okresu i kodu.</p>';
        } else {
            // Liczba koszyków
            $html_output .= '<p><strong>Koszyków: ' . count($provision_details) . '</strong></p>';

            $html_output .= '<table class="table table-bordered table-striped modal-report-table">';
            $html_output .= '<thead><tr><th>Koszyk ID</th><th>Wartość koszyka</th><th>Prowizja (kwota)</th></tr></thead><tbody>';
            foreach ($provision_details as $item) {
                $html_output .= '<tr><td>' . $item['id_order'] . '</td><td>' . $item['order_value'] . '</td><td>' . $item['provision_value'] . '</td></tr>';
            }
            $html_output .= '</tbody></table>';
        }

        die($html_output);
    }


    public function initContent()
    {
        parent::initContent();

        $default_year = date('Y');
        $default_month = date('n');
        $default_date_from = date('Y-m-01 00:00:00');
        $default_date_to = date('Y-m-t 23:59:59');

        $selected_filter_mode = Tools::getValue('filter_mode', 'monthly');

        $selected_year = (int)Tools::getValue('filter_year', $default_year);
        $selected_month = (int)Tools::getValue('filter_month', $default_month);
        $selected_date_from = Tools::getValue('date_from', $default_date_from);
        $selected_date_to = Tools::getValue('date_to', $default_date_to);

        $sql_start_date = null;
        $sql_end_date = null;
        $report_display_start = '';
        $report_display_end = '';

        if ($selected_filter_mode === 'monthly') {
            $sql_start_date = (new DateTimeImmutable())->setDate($selected_year, $selected_month, 1)->setTime(0, 0, 0)->format('Y-m-d H:i:s');
            $sql_end_date = (new DateTimeImmutable())->setDate($selected_year, $selected_month, (int)date('t', mktime(0, 0, 0, $selected_month, 1, $selected_year)))->setTime(23, 59, 59)->format('Y-m-d H:i:s');

            $report_display_start = $this->getMonthsArray()[$selected_month];
            $report_display_end = $selected_year;

        } elseif ($selected_filter_mode === 'custom') {
            if (Validate::isDate($selected_date_from) && Validate::isDate($selected_date_to)) {
                $sql_start_date = $selected_date_from;
                $sql_end_date = $selected_date_to;

                $report_display_start = $selected_date_from;
                $report_display_end = $selected_date_to;
            } else {
                $selected_date_from = $default_date_from;
                $selected_date_to = $default_date_to;
                $sql_start_date = $default_date_from;
                $sql_end_date = $default_date_to;

                $report_display_start = $this->getMonthsArray()[$default_month];
                $report_display_end = $default_year;
                $selected_filter_mode = 'monthly';
            }
        } else {
            $selected_filter_mode = 'monthly';
            $sql_start_date = (new DateTimeImmutable())->setDate($default_year, $default_month, 1)->setTime(0, 0, 0)->format('Y-m-d H:i:s');
            $sql_end_date = (new DateTimeImmutable())->setDate($default_year, $default_month, (int)date('t', mktime(0, 0, 0, $default_month, 1, $default_year)))->setTime(23, 59, 59)->format('Y-m-d H:i:s');

            $report_display_start = $this->getMonthsArray()[$default_month];
            $report_display_end = $default_year;
        }

        $report_data = $this->getSimplifiedReportData($sql_start_date, $sql_end_date);

        $this->context->smarty->assign([
            'selected_filter_mode' => $selected_filter_mode,
            'selected_year' => $selected_year,
            'selected_month' => $selected_month,
            'selected_date_from' => $selected_date_from,
            'selected_date_to' => $selected_date_to,
            'months' => $this->getMonthsArray(),
            'years' => range(2025, 2030),
            'current_url' => $this->context->link->getAdminLink('AdminBestProgramPartnerski'),
            'report_data' => $report_data,
            'report_dates_info' => [
                'start_display' => $report_display_start,
                'end_display' => $report_display_end,
            ],
            'js_date_from' => $sql_start_date,
            'js_date_to' => $sql_end_date,
        ]);

        $this->setTemplate('report.tpl');
    }

    private function getMonthsArray()
    {
        return [
            1 => $this->l('Styczeń'), 2 => $this->l('Luty'), 3 => $this->l('Marzec'),
            4 => $this->l('Kwiecień'), 5 => $this->l('Maj'), 6 => $this->l('Czerwiec'),
            7 => $this->l('Lipiec'), 8 => $this->l('Sierpień'), 9 => $this->l('Wrzesień'),
            10 => $this->l('Październik'), 11 => $this->l('Listopad'), 12 => $this->l('Grudzień'),
        ];
    }

    /**
     * Zwraca procent prowizji na podstawie wartości zamówienia i progów.
     */
    private function getTieredCommissionRate($orderValue)
    {
        foreach ($this->commissionTiers as $tier) {
            if ($tier['threshold'] === null || $orderValue <= $tier['threshold']) {
                return $tier['rate'];
            }
        }
        return 0; // Domyślna wartość, jeśli nie znaleziono progu (np. wartość ujemna)
    }

    private function getSimplifiedReportData($start_date, $end_date)
    {
        if (!Validate::isDate($start_date) || !Validate::isDate($end_date)) {
            return [];
        }

        $db = Db::getInstance();

        // --- ZAPYTANIE 1: Podstawowe dane partnerów (bazowe wiersze raportu) ---
        $query_base = new DbQuery();
        $query_base->select('
            cr.code,
            cr.id_cart_rule,
            prt.id_partner,
            prt.id_customer,
            c.firstname,
            c.lastname,
            c.email,
            prt.provision
        ');
        $query_base->from('pshow_rp_partner', 'prt');
        $query_base->leftJoin('customer', 'c', 'prt.id_customer = c.id_customer');
        $query_base->innerJoin('cart_rule', 'cr', 'prt.id_voucher = cr.id_cart_rule');
        $query_base->where('prt.id_voucher IS NOT NULL');
        $query_base->groupBy('cr.code, cr.id_cart_rule, prt.id_partner, prt.id_customer, c.firstname, c.lastname, c.email, prt.provision');
        $query_base->orderBy('cr.code ASC, c.lastname ASC, c.firstname ASC, prt.id_customer ASC');

        $base_results = $db->executeS($query_base);

        if (empty($base_results)) {
            return [];
        }

        // Przygotowanie danych do połączenia w PHP i filtrowania ignorowanych kodów
        $report_data = [];
        $partner_ids = [];
        $cart_rule_ids = [];

        foreach ($base_results as $row) {
            // Filtracja: pomijamy kody, które są na liście $ignoredCodes
            if (in_array((int)$row['id_cart_rule'], $this->ignoredCodes)) {
                continue; // Pomijamy ten wiersz
            }

            $key = (int)$row['id_partner'] . '_' . (int)$row['id_cart_rule'];
            $report_data[$key] = $row;
            $report_data[$key]['total_orders_value_display'] = 0.0; // Inicjalizacja dla "Suma zamówień BL"
            $report_data[$key]['provision_order_value_for_tiers'] = 0.0; // Inicjalizacja dla "Suma zamówień PP" (wartość bazowa dla progów kontrolnych)

            $partner_ids[] = (int)$row['id_partner'];
            $cart_rule_ids[] = (int)$row['id_cart_rule'];
        }
        $partner_ids = array_unique($partner_ids);
        $cart_rule_ids = array_unique($cart_rule_ids);

        // Jeśli po filtracji nie ma danych, zwróć puste
        if (empty($report_data)) {
            return [];
        }


        // --- ZAPYTANIE 2: Suma obliczonej wartości zamówień BL (total_price_tax_excl * 0.85, bez ID 78) ---
        if (!empty($partner_ids) && !empty($cart_rule_ids)) {
            $query_calculated_orders_value = new DbQuery();
            $query_calculated_orders_value->select('
                prt.id_partner,
                cr.id_cart_rule,
                SUM(od.total_price_tax_excl * (1 - 0.15)) AS calculated_order_value_sum
            ');
            $query_calculated_orders_value->from('orders', 'o');
            $query_calculated_orders_value->innerJoin('order_detail', 'od', 'o.id_order = od.id_order');
            $query_calculated_orders_value->innerJoin('order_cart_rule', 'ocr', 'o.id_order = ocr.id_order');
            $query_calculated_orders_value->innerJoin('cart_rule', 'cr', 'ocr.id_cart_rule = cr.id_cart_rule');
            $query_calculated_orders_value->innerJoin('pshow_rp_partner', 'prt', 'cr.id_cart_rule = prt.id_voucher');

            $query_calculated_orders_value->where('o.date_add >= \'' . pSQL($start_date) . '\' AND o.date_add <= \'' . pSQL($end_date) . '\'');
            $query_calculated_orders_value->where('o.current_state = 4 AND o.valid = 1');
            $query_calculated_orders_value->where('od.product_id NOT IN (' . implode(',', $this->excludedProductIdsFromSum) . ')');
            $query_calculated_orders_value->where('prt.id_partner IN (' . implode(',', $partner_ids) . ')');
            $query_calculated_orders_value->where('cr.id_cart_rule IN (' . implode(',', $cart_rule_ids) . ')');
            $query_calculated_orders_value->groupBy('prt.id_partner, ocr.id_cart_rule');

            $calculated_orders_results = $db->executeS($query_calculated_orders_value);

            foreach ($calculated_orders_results as $row) {
                $key = $row['id_partner'] . '_' . $row['id_cart_rule'];
                if (isset($report_data[$key])) {
                    $report_data[$key]['total_orders_value_display'] = (float)$row['calculated_order_value_sum'];
                }
            }
        }

        // --- ZAPYTANIE 3: Suma order_value z ps_pshow_rp_provision (dla kolumny "Suma zamówień PP" / wartość bazowa dla kontroli) ---
        if (!empty($partner_ids) && !empty($cart_rule_ids)) {
            $query_provision_control_value = new DbQuery();
            $query_provision_control_value->select('
                prv.id_partner,
                ocr.id_cart_rule,
                SUM(prv.order_value) AS provision_control_sum
            ');
            $query_provision_control_value->from('pshow_rp_provision', 'prv');
            $query_provision_control_value->innerJoin('orders', 'o', 'prv.id_order = o.id_order');
            $query_provision_control_value->innerJoin('order_cart_rule', 'ocr', 'o.id_order = ocr.id_order');
            $query_provision_control_value->where('prv.date_add >= \'' . pSQL($start_date) . '\' AND prv.date_add <= \'' . pSQL($end_date) . '\'');
            $query_provision_control_value->where('prv.confirmed = 1');
            $query_provision_control_value->where('o.current_state = 4 AND o.valid = 1');
            $query_provision_control_value->where('prv.id_partner IN (' . implode(',', $partner_ids) . ')');
            $query_provision_control_value->where('ocr.id_cart_rule IN (' . implode(',', $cart_rule_ids) . ')');
            $query_provision_control_value->groupBy('prv.id_partner, ocr.id_cart_rule');

            $provision_control_results = $db->executeS($query_provision_control_value);

            foreach ($provision_control_results as $row) {
                $key = $row['id_partner'] . '_' . $row['id_cart_rule'];
                if (isset($report_data[$key])) {
                    $report_data[$key]['provision_order_value_for_tiers'] = (float)$row['provision_control_sum'];
                }
            }
        }


        // --- KOŃCOWA ITERACJA I FORMATOWANIE ---
        foreach ($report_data as $key => &$row) {
            // Formatowanie Kodu rabatowego
            $row['code_display_name'] = $row['code'];
            if (!empty($row['id_cart_rule'])) {
                $row['code_display_name'] .= ' (' . $row['id_cart_rule'] . ')';
            }

            // Formatowanie Nazwiska Partnera (id klienta) (PartnerID: id_partner)
            $row['partner_display_name'] = '';
            if (!empty($row['firstname']) || !empty($row['lastname'])) {
                $row['partner_display_name'] = trim($row['firstname'] . ' ' . $row['lastname']);
            }
            if (!empty($row['id_customer'])) {
                $row['partner_display_name'] .= ' (' . $row['id_customer'] . ')';
            }
            if (!empty($row['id_partner'])) {
                $row['partner_display_name'] .= ' (PartnerID: ' . $row['id_partner'] . ')';
            }
            if (empty($row['partner_display_name'])) {
                $row['partner_display_name'] = $this->l('Brak danych partnera');
            }

            // --- DEKLARACJA ZMIENNYCH DLA OBLICZEŃ ---
            $total_orders_value_display_float = (float)$row['total_orders_value_display']; // Suma zamówień BL (główna podstawa)
            $provision_order_value_for_tiers_float = (float)$row['provision_order_value_for_tiers']; // Suma zamówień PP (wartość dla kontroli)
            $provision_base_value_float = (float)$row['provision']; // Początkowy procent prowizji z konfiguracji

            // --- KOLUMNA "PODSTAWADla "Suma zamówień PP" (wartość kontrolna) ---
            $row['control_pp_value_formatted'] = Tools::displayPrice($provision_order_value_for_tiers_float, $this->context->currency);

            // --- KOLUMNA "PODSTAWA (%)" ---
            $row['provision_base_formatted'] = $provision_base_value_float . '%';

            // --- KOLUMNA "PROWIZJA" (Wyliczona kwota prowizji na podstawie Sumy zamówień BL) ---
            $calculated_provision_amount = 0;
            $calculated_provision_class = '';

            // KWOTA BAZOWA DLA PROGÓW I OBLICZEŃ PROWIZJI: Użyj 'total_orders_value_display_float' (Suma zamówień BL)
            if (round($provision_base_value_float, 2) == 5.00) {
                $actual_tiered_rate = $this->getTieredCommissionRate($total_orders_value_display_float); // Baza to BL!
                $calculated_provision_amount = $total_orders_value_display_float * $actual_tiered_rate; // Baza to BL!

                if ($total_orders_value_display_float > 5000.00) { // Próg porównywany z BL!
                    $calculated_provision_class = 'threshold-highlight';
                }
            } else {
                $calculated_provision_amount = $total_orders_value_display_float * ($provision_base_value_float / 100); // Baza to BL!
            }
            $calculated_provision_formatted = Tools::displayPrice($calculated_provision_amount, $this->context->currency);


            // Logika warunkowa dla PIERWSZEJ linii kolumny "Prowizja" (próg)
            $first_line_display = '';

            $percentage_value_for_first_line = $provision_base_value_float . '%';
            $percentage_value_for_first_line_html = '<strong class="prog-prowizji">' . $percentage_value_for_first_line . '</strong>';

            if (round($provision_base_value_float, 2) != 0.00) {
                $first_line_display .= $this->l('Próg: ') . $percentage_value_for_first_line_html;

                // Sprawdź, czy przekroczono próg dla 5% (w oparciu o kwotę z BL)
                if (round($provision_base_value_float, 2) == 5.00 && $total_orders_value_display_float > 5000.00) {
                    $first_line_display = preg_replace(
                        '/<strong class="prog-prowizji">([\d.,]+%)<\/strong>/',
                        '<strong class="prog-prowizji"><del>$1</del></strong>',
                        $first_line_display
                    );
                    $actual_tiered_rate_percentage = $this->getTieredCommissionRate($total_orders_value_display_float) * 100; // Baza to BL!
                    $first_line_display .= ' <strong class="tiered-rate-highlight">' . $actual_tiered_rate_percentage . '%</strong>';
                }
            } else {
                $first_line_display .= '<span class="warning-text">' . $this->l('UWAGA: 0.00%') . '</span>';
            }

            $row['final_provision_display'] = $first_line_display . '<br><span class="' . $calculated_provision_class . '">' . $calculated_provision_formatted . '</span>';

            // --- KOLUMNA "CONTROL" (wcześniej "KONTROLKA P.P. - WERYFIKACJA") ---
            // Użyj wartości z BL i PP do weryfikacji.
            $row['control_verification_status'] = '';
            $row['control_verification_class'] = '';

            // Weryfikacja kodów z listy wyjątków
            if (in_array((int)$row['id_cart_rule'], $this->exceptionCodesForControlDisplay)) {
                $row['control_verification_status'] = $this->l('WYJĄTEK');
                $row['control_verification_class'] = 'exception-highlight';
            } else {
                // Standardowa logika weryfikacji (z tolerancją)
                $tolerance = 0.05;
                if (abs($total_orders_value_display_float - $provision_order_value_for_tiers_float) < $tolerance) { // Porównanie BL vs PP!
                    $row['control_verification_status'] = $this->l('OK');
                    $row['control_verification_class'] = 'success';
                } else {
                    $row['control_verification_status'] = sprintf($this->l('Rozbieżność: %s vs %s'),
                        Tools::displayPrice($total_orders_value_display_float, $this->context->currency, false),
                        Tools::displayPrice($provision_order_value_for_tiers_float, $this->context->currency, false)
                    );
                    $row['control_verification_class'] = 'danger';
                }
            }

            // --- KOLUMNA "WARTOŚĆ ZAMÓWIEŃ BL" ---
            // Ta kolumna pozostaje bez zmian
            $row['total_orders_value_formatted'] = Tools::displayPrice($total_orders_value_display_float, $this->context->currency);

            // --- KOLUMNA "PROWIZJA NETTO" (TO COŚ W SUMIE JEST IDENTYCZNE Z OBLICZONĄ PROWIZJĄ, ALE ZOSTAWIAM JAK BYŁO) ---
            $row['net_commission_display_formatted'] = '<strong class="net-commission-highlight">' . $calculated_provision_formatted . '</strong>';
        }

        return array_values($report_data); // Resetujemy klucze
    }
}
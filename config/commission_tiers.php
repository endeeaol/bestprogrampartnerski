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

// Definicja progów prowizyjnych
// Kolejność jest WAŻNA: od najniższego progu do najwyższego.
// 'threshold' oznacza górną granicę przedziału (włącznie), 'null' oznacza "i więcej".
$commissionTiers = [
    ['threshold' => 5000.00,  'rate' => 0.05],  // 5% do 5.000,00 zł netto
    ['threshold' => 7000.00,  'rate' => 0.06],  // 6% 5.000,01 zł netto do 7000,00 zł netto
    ['threshold' => 9000.00,  'rate' => 0.07],  // 7% 7.000,01 zł netto do 9000,00 zł netto
    ['threshold' => 11000.00, 'rate' => 0.08],  // 8% 9.000,01 zł netto do 11.000,00 zł netto
    ['threshold' => 13000.00, 'rate' => 0.09],  // 9% 11.000,01 zł netto do 13.000,00 zł netto
    ['threshold' => 15000.00, 'rate' => 0.10],  // 10% 13.000,01 zł netto 15.000,00 zł netto
    ['threshold' => null,     'rate' => 0.12],  // 12% od 15.000,01 zł netto
];

// Kody rabatowe (ID), dla których wyświetla się "WYJĄTEK" w kolumnie "Control"
$exception_codes_for_control_display = [
    9,   // GRECH (ID: 9)
    112, // GOSIA15 (ID: 112)
    10,  // SEBASTIAN15 (ID: 10)
];

// Kody rabatowe (ID), które są całkowicie pomijane i nie wyświetlają się w raporcie
$ignored_codes = [
    11, // ferdas
    88, // AGATA
    7,  // ~del-AGATA
];

// ID produktów, które są pomijane w sumowaniu "Suma zamówień BL"
$excluded_product_ids_from_sum = [
    78, // Produkt o ID 78
];

return [
    'commissionTiers' => $commissionTiers,
    'exception_codes_for_control_display' => $exception_codes_for_control_display,
    'ignored_codes' => $ignored_codes,
    'excluded_product_ids_from_sum' => $excluded_product_ids_from_sum,
];
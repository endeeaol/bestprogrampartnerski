{*
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
 *}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> Raportowanie PP
    </div>

    <div>
        <ul>
            <li>5% do 5.000,00 zł netto;</li>
            <li>6% 5.000,01 zł netto do 7000,00 zł netto;</li>
            <li>7% 7.000,01 zł netto do 9000,00 zł netto;</li>
            <li>8% 9.000,01 zł netto do 11.000,00 zł netto;</li>
            <li>9% 11.000,01 zł netto do 13.000,00 zł netto;</li>
            <li>10% 13.000,01 zł netto 15.000,00 zł netto;</li>
            <li>12% od 15.000,01 zł netto</li>
        </ul>
        <p>Wysokość obliczanych progów zmienisz w config/commission_tiers.php</p>
    </div>

    <div id="filter_container" class="form-horizontal">
        <div class="form-group">
            <label class="control-label col-lg-1">Tryb filtrowania</label>
            <div class="col-lg-3">
                <div class="radio-inline">
                    <label>
                        <input type="radio" name="filter_mode" id="mode_monthly" value="monthly" {if $selected_filter_mode == 'monthly' || !$selected_filter_mode}checked="checked"{/if} onchange="toggleFilterMode()" />
                        Miesiącami
                    </label>
                </div>
                <div class="radio-inline">
                    <label>
                        <input type="radio" name="filter_mode" id="mode_custom" value="custom" {if $selected_filter_mode == 'custom'}checked="checked"{/if} onchange="toggleFilterMode()" />
                        Własny zakres
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group filter-mode-monthly">
            <label class="control-label col-lg-1">Rok</label>
            <div class="col-lg-2">
                <select id="filter_year_select" name="filter_year" class="form-control" onchange="reloadPageWithFilters()">
                    {foreach from=$years item='year_val'}
                        <option value="{$year_val|escape:'htmlall':'UTF-8'}" {if $selected_year == $year_val}selected="selected"{/if}>
                            {$year_val|escape:'htmlall':'UTF-8'}
                        </option>
                    {/foreach}
                </select>
            </div>
            <label class="control-label col-lg-1">Miesiąc</label>
            <div class="col-lg-1">
                <select id="filter_month_select" name="filter_month" class="form-control" onchange="reloadPageWithFilters()">
                    {foreach from=$months key='month_num' item='month_name'}
                        <option value="{$month_num|escape:'htmlall':'UTF-8'}" {if $selected_month == $month_num}selected="selected"{/if}>
                            {$month_name|escape:'htmlall':'UTF-8'}
                        </option>
                    {/foreach}
                </select>
            </div>
        </div>

        <div class="form-group filter-mode-custom" style="display:none;">
            <label class="control-label col-lg-1">Od</label>
            <div class="col-lg-3">
                <div class="input-group">
                    <input type="text" id="date_from_input" name="date_from" class="datepicker form-control" value="{$selected_date_from|escape:'htmlall':'UTF-8'}" data-original-title="" title="" autocomplete="off" />
                    <span class="input-group-addon"><i class="icon-calendar"></i></span>
                </div>
            </div>

            <label class="control-label col-lg-1">Do</label>
            <div class="col-lg-3">
                <div class="input-group">
                    <input type="text" id="date_to_input" name="date_to" class="datepicker form-control" value="{$selected_date_to|escape:'htmlall':'UTF-8'}" data-original-title="" title="" autocomplete="off" />
                    <span class="input-group-addon"><i class="icon-calendar"></i></span>
                </div>
            </div>
        </div>
    </div>

    <hr/>

    {if $report_data|@count > 0}
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-table"></i> Raport od {$report_dates_info.start_display} do {$report_dates_info.end_display}
            </div>
            <div class="table-responsive">
                <table class="table" id="report_table">
                    <thead>
                        <tr>
                            <th class="table-header-padding">Kod rabatowy (id kodu)</th>
                            <th class="table-header-padding">Partner (id klienta) (id partnera)</th>
                            <th class="table-header-padding">E-mail</th>
                            <th class="table-header-padding table-cell-right">Suma zamówień BL</th>
                            <th class="table-header-padding table-cell-right">Suma zamówień PP</th>
                            <th class="table-header-padding table-cell-right">Podstawa (%)</th>
                            <th class="table-header-padding table-cell-left">Prowizja</th>
                            <th class="table-header-padding table-cell-right">Prowizja netto</th>
                            <th class="table-header-padding">Control</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$report_data item='row'}
                            <tr>
                                <td class="table-cell-padding">{$row.code_display_name|escape:'htmlall':'UTF-8'}</td>
                                <td class="table-cell-padding">{$row.partner_display_name|escape:'htmlall':'UTF-8'}</td>
                                <td class="table-cell-padding">{$row.email|default:'Brak'|escape:'htmlall':'UTF-8'}</td>
                                <td class="table-cell-padding table-cell-right js-orders-bl-link"
                                    data-id-partner="{$row.id_partner|intval}"
                                    data-id-cart-rule="{$row.id_cart_rule|intval}"
                                    data-js-date-from="{$js_date_from|escape:'htmlall':'UTF-8'}"
                                    data-js-date-to="{$js_date_to|escape:'htmlall':'UTF-8'}">
                                    {$row.total_orders_value_formatted|escape:'htmlall':'UTF-8'}
                                </td>
                                <td class="table-cell-padding table-cell-right js-provision-sum-link"
                                    data-id-partner="{$row.id_partner|intval}"
                                    data-id-cart-rule="{$row.id_cart_rule|intval}"
                                    data-js-date-from="{$js_date_from|escape:'htmlall':'UTF-8'}"
                                    data-js-date-to="{$js_date_to|escape:'htmlall':'UTF-8'}">
                                    {$row.control_pp_value_formatted nofilter} {* Poprawne dane: Suma zamówień PP (kontrolka) *}
                                </td>
                                <td class="table-cell-padding table-cell-right">{$row.provision_base_formatted nofilter}</td>
                                <td class="table-cell-padding table-cell-left">{$row.final_provision_display nofilter}</td> {* Poprawne dane: Prowizja (próg + kwota) *}
                                <td class="table-cell-padding table-cell-right">{$row.net_commission_display_formatted nofilter}</td> {* Poprawne dane: Prowizja netto (kwota) *}
                                <td class="table-cell-padding {if $row.control_verification_class} {$row.control_verification_class}{/if}">{$row.control_verification_status|escape:'htmlall':'UTF-8'}</td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    {else}
        <div class="alert alert-warning">
            Brak danych dla wybranego okresu.
        </div>
    {/if}
</div>

{* Okno modalne (domyślnie ukryte) *}
<div id="modal-content-container" style="display:none;"></div>

{* Przekazujemy zmienne z PHP do JS w postaci atrybutów data, aby uniknąć konfliktu ze Smarty w bloku script *}
<div id="js-data-container"
    data-current-url="{$current_url|escape:'htmlall':'UTF-8'}"
    data-admin-controller="AdminBestProgramPartnerski"
    data-admin-token="{$smarty.get.token|escape:'htmlall':'UTF-8'}"
    data-selected-filter-mode="{$selected_filter_mode|escape:'htmlall':'UTF-8'}"
    data-js-date-from="{$js_date_from|escape:'htmlall':'UTF-8'}"
    data-js-date-to="{$js_date_to|escape:'htmlall':'UTF-8'}"></div>

<script type="text/javascript">
    {literal}
    var currentUrl;
    var currentController;
    var currentToken;
    var selectedFilterMode;
    var jsDateFrom;
    var jsDateTo;

    jQuery(document).ready(function() {
        // Inicjalizacja zmiennych JS z danych HTML
        var jsDataContainer = jQuery('#js-data-container');
        currentUrl = jsDataContainer.data('current-url');
        currentController = jsDataContainer.data('admin-controller');
        currentToken = jsDataContainer.data('admin-token');
        selectedFilterMode = jsDataContainer.data('selected-filter-mode');
        jsDateFrom = jsDataContainer.data('js-date-from');
        jsDateTo = jsDataContainer.data('js-date-to');

        // Fallback dla tokena, jeśli nie został poprawnie przekazany
        if (!currentToken && typeof admin_token !== 'undefined') {
            currentToken = admin_token;
        }

        // Inicjalizacja datepickerów
        var dateFromInput = jQuery('#date_from_input');
        var dateToInput = jQuery('#date_to_input');

        dateFromInput.datepicker({
            dateFormat: 'yy-mm-dd',
            showOtherMonths: true,
            selectOtherMonths: true,
            onSelect: function (selectedDate) {
                dateToInput.datepicker('option', 'minDate', selectedDate);
                reloadPageWithFilters();
            }
        });

        dateToInput.datepicker({
            dateFormat: 'yy-mm-dd',
            showOtherMonths: true,
            selectOtherMonths: true,
            onSelect: function (selectedDate) {
                dateFromInput.datepicker('option', 'maxDate', selectedDate);
                reloadPageWithFilters();
            }
        });

        // Wywołaj funkcję przełączającą tryb przy załadowaniu strony
        toggleFilterMode(false);


        // --- OBSŁUGA MODALA DLA "Suma zamówień BL" ---
        jQuery('.js-orders-bl-link').on('click', function() {
            var id_partner = jQuery(this).data('id-partner');
            var id_cart_rule = jQuery(this).data('id-cart-rule');
            var date_from = jQuery(this).data('js-date-from');
            var date_to = jQuery(this).data('js-date-to');

            // Tekst nagłówka modala
            var modalTitle = 'Szczegóły zamówień BL - ' + jQuery(this).closest('tr').find('td').eq(1).text() + ' - ' + jQuery(this).closest('tr').find('td').eq(0).text();

            jQuery.ajax({
                url: currentUrl + '&ajax=1&action=GetOrderDetailsBlForModal',
                type: 'POST',
                dataType: 'html',
                data: {
                    id_partner: id_partner,
                    id_cart_rule: id_cart_rule,
                    date_from: date_from,
                    date_to: date_to,
                    token: currentToken
                },
                beforeSend: function() {
                    jQuery('#modal-content-container').html('<p>Ładowanie danych...</p>');
                    jQuery('#modal-content-container').dialog('option', 'title', modalTitle);
                    jQuery('#modal-content-container').dialog('option', 'width', 800);
                    jQuery('#modal-content-container').dialog('option', 'height', 600);
                    jQuery('#modal-content-container').dialog('open');
                },
                success: function(response) {
                    jQuery('#modal-content-container').html(response);
                },
                error: function(xhr, status, error) {
                    jQuery('#modal-content-container').html('<p>Wystąpił błąd podczas ładowania danych.</p><p>' + xhr.responseText + '</p>');
                    console.error('AJAX Error BL:', status, error, xhr.responseText);
                }
            });
        });


        // --- OBSŁUGA MODALA DLA "Suma zamówień PP" ---
        jQuery('.js-provision-sum-link').on('click', function() {
            var id_partner = jQuery(this).data('id-partner');
            var id_cart_rule = jQuery(this).data('id-cart-rule');
            var date_from = jQuery(this).data('js-date-from');
            var date_to = jQuery(this).data('js-date-to');

            // Tekst nagłówka modala
            var modalTitle = 'Szczegóły wpisów prowizyjnych PP - ' + jQuery(this).closest('tr').find('td').eq(1).text() + ' - ' + jQuery(this).closest('tr').find('td').eq(0).text();

            jQuery.ajax({
                url: currentUrl + '&ajax=1&action=GetProvisionDetailsForModal',
                type: 'POST',
                dataType: 'html',
                data: {
                    id_partner: id_partner,
                    id_cart_rule: id_cart_rule,
                    date_from: date_from,
                    date_to: date_to,
                    token: currentToken
                },
                beforeSend: function() {
                    jQuery('#modal-content-container').html('<p>Ładowanie danych...</p>');
                    jQuery('#modal-content-container').dialog('option', 'title', modalTitle);
                    jQuery('#modal-content-container').dialog('option', 'width', 600);
                    jQuery('#modal-content-container').dialog('option', 'height', 600);
                    jQuery('#modal-content-container').dialog('open');
                },
                success: function(response) {
                    jQuery('#modal-content-container').html(response);
                },
                error: function(xhr, status, error) {
                    jQuery('#modal-content-container').html('<p>Wystąpił błąd podczas ładowania danych.</p><p>' + xhr.responseText + '</p>');
                    console.error('AJAX Error PP:', status, error, xhr.responseText);
                }
            });
        });

        // Inicjalizacja jQuery UI Dialog dla OBU modalów.
        jQuery('#modal-content-container').dialog({
            autoOpen: false,
            modal: true,
            height: 'auto',
            width: 800,
            closeText: 'Zamknij',
            buttons: [
                {
                    text: 'Zamknij',
                    click: function() {
                        jQuery(this).dialog('close');
                    }
                }
            ]
        });

    });

    // Funkcja przełączająca widoczność pól filtrów
    function toggleFilterMode(reload = true) {
        selectedFilterMode = jQuery('input[name="filter_mode"]:checked').val();

        if (selectedFilterMode === 'monthly') {
            jQuery('.filter-mode-monthly').show();
            jQuery('.filter-mode-custom').hide();
        } else if (selectedFilterMode === 'custom') {
            jQuery('.filter-mode-monthly').hide();
            jQuery('.filter-mode-custom').show();
            jQuery('#date_from_input').datepicker('refresh');
            jQuery('#date_to_input').datepicker('refresh');
        }

        if (reload) {
            reloadPageWithFilters();
        }
    }

    // Funkcja do budowania URL i przeładowania strony (globalna)
    function reloadPageWithFilters() {
        var newUrl = currentUrl;

        newUrl = newUrl.replace(/&filter_year=\d{4}/, '');
        newUrl = newUrl.replace(/&filter_month=\d{1,2}/, '');
        newUrl = newUrl.replace(/&date_from=[^&]*/, '');
        newUrl = newUrl.replace(/&date_to=[^&]*/, '');
        newUrl = newUrl.replace(/&filter_mode=[^&]*/, '');
        newUrl = newUrl.replace(/&submitFilter=1/, '');
        newUrl = newUrl.replace(/&token=[a-f0-9]{32}/, '');

        newUrl += '&filter_mode=' + selectedFilterMode;

        if (selectedFilterMode === 'monthly') {
            var selectedYear = jQuery('#filter_year_select').val();
            var selectedMonth = jQuery('#filter_month_select').val();
            newUrl += '&filter_year=' + selectedYear;
            newUrl += '&filter_month=' + selectedMonth;
        } else if (selectedFilterMode === 'custom') {
            var selectedDateFrom = jQuery('#date_from_input').val();
            var selectedDateTo = jQuery('#date_to_input').val();
            if (selectedDateFrom) {
                newUrl += '&date_from=' + selectedDateFrom;
            }
            if (selectedDateTo) {
                newUrl += '&date_to=' + selectedDateTo;
            }
        }

        newUrl += '&submitFilter=1';
        newUrl += '&token=' + currentToken;

        window.location.href = newUrl;
    }
    {/literal}
</script>
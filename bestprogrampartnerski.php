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

class BestProgramPartnerski extends Module
{
    public function __construct()
    {
        $this->name = 'bestprogrampartnerski';
        $this->tab = 'administration';
        $this->version = '1.4.0'; // Zmieniono wersję na 1.4.0
        $this->author = 'BESTLAB Ernest';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Raportowanie PP');
        $this->description = $this->l('Weryfikacja należnych wypłat prowizji z tytułu członkostwa w programie partnerskim.');

        $this->confirmUninstall = $this->l('Czy na pewno chcesz odinstalować ten moduł? Wszystkie dane zostaną usunięte.');
    }

    public function install()
    {
        // Rejestruj hook displayBackOfficeHeader podczas instalacji modułu
        // To jest ważne, aby PrestaShop wiedział, że ma wywołać tę metodę
        if (!parent::install() || !$this->installTabs() || !$this->registerHook('displayBackOfficeHeader')) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        // Pamiętaj, aby również wyrejestrować hook podczas odinstalowywania
        if (!parent::uninstall() || !$this->uninstallTabs() || !$this->unregisterHook('displayBackOfficeHeader')) {
            return false;
        }

        return true;
    }

    private function installTabs()
    {
        $bestlabTabId = 492; // Upewnij się, że to ID jest poprawne dla Twojej instalacji

        $tab = new Tab();
        $tab->class_name = 'AdminBestProgramPartnerski';
        $tab->module = $this->name;
        $tab->id_parent = $bestlabTabId;
        $tab->active = 1;

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $this->displayName;
        }

        return $tab->save();
    }

    private function uninstallTabs()
    {
        $tabId = (int) Tab::getIdFromClassName('AdminBestProgramPartnerski');
        if ($tabId) {
            $tab = new Tab($tabId);
            if (!$tab->delete()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Hook, który jest wywoływany w nagłówku w panelu administracyjnym.
     * Używamy go, aby zapewnić, że moduł jest aktywny w tle, gdy kontroler jest używany.
     * Załadowanie jQuery UI dialog pozostawiamy w kontrolerze.
     */
    public function hookDisplayBackOfficeHeader()
    {
        // Ta metoda jest wymagana, aby hook był aktywny,
        // ale załadowanie 'ui.dialog' odbywa się w kontrolerze AdminBestProgramPartnerskiController::setMedia()
        // Możesz tutaj dodać inne globalne zasoby JS/CSS dla całego back office,
        // jeśli Twój moduł ich potrzebuje niezależnie od konkretnego kontrolera.
    }
}
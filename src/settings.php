<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Settings for the ifthenpay payment gateway.
 *
 * @package    paygw_ifthenpay
 * @copyright  2025 ifthenpay <geral@ifthenpay.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // 0) Primeiros passos (bloco informativo com HTML).
    $settings->add(new \admin_setting_heading(
        'paygw_ifthenpay/onboarding',
        get_string('onboarding_title', 'paygw_ifthenpay'),
        get_string('onboarding_html', 'paygw_ifthenpay')
    ));

    // 1) Ligação à ifthenpay (API / Backoffice Key).
    $settings->add(new \admin_setting_heading(
        'paygw_ifthenpay/api_heading',
        get_string('api_heading', 'paygw_ifthenpay'),
        ''
    ));

    $settings->add(new \paygw_ifthenpay\adminsetting\backofficekey(
        'paygw_ifthenpay/backoffice_key',
        get_string('backoffice_key', 'paygw_ifthenpay'),
        get_string('backoffice_key_desc', 'paygw_ifthenpay'),
        ''
    ));

    // 2) Comportamento do pagamento (opções core: surcharge, instructions).
    $settings->add(new \admin_setting_heading(
        'paygw_ifthenpay/behavior_heading',
        get_string('behavior_heading', 'paygw_ifthenpay'),
        get_string('behavior_desc', 'paygw_ifthenpay')
    ));

    \core_payment\helper::add_common_gateway_settings($settings, 'paygw_ifthenpay');
}

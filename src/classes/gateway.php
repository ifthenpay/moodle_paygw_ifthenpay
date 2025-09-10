<?php
// This file is part of Moodle - https://moodle.org/.
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
 * Ifthenpay payment gateway – admin form integration.
 *
 * @package    paygw_ifthenpay
 * @copyright  2025 ifthenpay <geral@ifthenpay.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_ifthenpay;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

use core_payment\form\account_gateway;

/**
 * UI options (gateway keys, methods, accounts) are sourced from the live dataset
 * built by {@see \paygw_ifthenpay_build_full_admin_dataset()}, while the saved
 * state is used only to preselect defaults. A hidden JSON state mirrors the UI.
 *
 * @package   paygw_ifthenpay
 */
final class gateway extends \core_payment\gateway
{
    /**
     * Supported currencies.
     *
     * @return string[] List of ISO currency codes.
     */
    public static function get_supported_currencies(): array {
        return ['EUR'];
    }

    /**
     * Whether refunds are supported.
     *
     * @return bool False (not supported).
     */
    public static function supports_refunds(): bool {
        return false;
    }

    /**
     * Add configuration fields to the gateway form.
     *
     * Path: Site admin → Payments → Payment accounts → (Account) → Gateways → ifthenpay → Configure.
     *
     * @param account_gateway $form The account gateway form wrapper.
     * @return void
     */
    public static function add_configuration_to_gateway_form(account_gateway $form): void {
        global $PAGE;

        $mform = $form->get_mform();

        // 0) Require Backoffice Key (early exit).
        if (!self::has_backoffice_key()) {
            if ($mform->elementExists('enabled')) {
                $mform->setDefault('enabled', 0);
                $mform->freeze('enabled');
                $mform->getElement('enabled')->setPersistantFreeze(true);
            }
            $mform->addElement(
                'static',
                'ifthenpay_missing_bo',
                '',
                \html_writer::tag(
                    'small',
                    get_string(
                        'form:missing_backoffice_key_inline',
                        'paygw_ifthenpay',
                        (new \moodle_url('/admin/settings.php', ['section' => 'paymentgatewayifthenpay']))->out(false)
                    ),
                    ['class' => 'text-muted']
                )
            );
            return;
        }

        // 1) Live dataset (source of truth for options).
        $dataset  = \paygw_ifthenpay_build_full_admin_dataset();
        $gkmap    = $dataset['gatewaykeys'] ?? []; // GatewayKey => label.
        $methods  = $dataset['methods'] ?? [];     // MethodKey => meta(label,image,tooltip).
        $accounts = $dataset['accounts'] ?? [];    // GatewayKey => methodKey => (accountId => label).
        $ordered  = \paygw_ifthenpay_order_method_keys($methods);

        // 1.b) Require at least one Gateway Key (early exit).
        if (empty($gkmap)) {
            if ($mform->elementExists('enabled')) {
                $mform->setDefault('enabled', 0);
                $mform->freeze('enabled');
                $mform->getElement('enabled')->setPersistantFreeze(true);
            }
            $mform->addElement(
                'static',
                'ifthenpay_missing_gk',
                '',
                \html_writer::tag(
                    'small',
                    get_string('form:missing_gateway_keys_inline', 'paygw_ifthenpay'),
                    ['class' => 'text-muted']
                )
            );
            return;
        }

        // 2) Saved state (persisted) – ONLY for defaults, not for building options.
        $saved = self::get_saved_config($form);

        // 3) Determine current GK default from saved state if still valid, else first GK.
        $savedgk   = isset($saved['ifthenpay_gatewaykey']) ? (string) $saved['ifthenpay_gatewaykey'] : '';
        $currentgk = ($savedgk !== '' && isset($gkmap[$savedgk]))
            ? $savedgk
            : (string) array_key_first($gkmap);

        // 4) CSS (visual tweaks).
        $PAGE->requires->css(new \moodle_url('/payment/gateway/ifthenpay/styles.css'));

        // 5) Sections.
        self::render_gateway_section($mform, $gkmap, $currentgk);
        self::render_payment_section($mform, $methods, $ordered, $accounts, $currentgk, $saved);
        self::render_other_section($mform, $methods, $saved);

        // 6) Hidden JSON state (mirrors UI for submission; initialised from defaults).
        $state = self::build_initial_state($ordered, $saved, $currentgk);
        $mform->addElement('hidden', 'ifthenpay_state', json_encode($state));
        $mform->setType('ifthenpay_state', PARAM_RAW);

        // 7) JS dataset + wiring.
        $PAGE->requires->data_for_js('ifthenpay', (object) [
            'accounts' => $accounts, // GK => methodKey => (id => label).
            'methods'  => $methods, // MethodKey => meta.
        ]);

        $selectors = (object) [
            'gatewayKey'    => 'ifthenpay_gatewaykey',
            'accountPrefix' => 'ifthenpay_account_',
            'enablePrefix'  => 'ifthenpay_enable_',
            'state'         => 'ifthenpay_state',
            'defaultMethod' => 'ifthenpay_defaultmethod',
            'description'   => 'ifthenpay_description',
        ];
        $i18n = (object) [
            'noaccounts' => get_string('form:noaccounts', 'paygw_ifthenpay'),
        ];

        // Positional args per AMD contract.
        $PAGE->requires->js_call_amd('paygw_ifthenpay/admin_gateway_form', 'init', [$selectors, $i18n]);
    }

    /**
     * Gateway configuration — Gateway Key select.
     *
     * @param \MoodleQuickForm $mform     Form.
     * @param array            $gkmap     Map of gatewayKey => label.
     * @param string           $currentgk Current gateway key.
     * @return void
     */
    private static function render_gateway_section(\MoodleQuickForm $mform, array $gkmap, string $currentgk): void {
        $mform->addElement('header', 'ifp_gatecfg', get_string('form:gateway_configuration', 'paygw_ifthenpay'));

        $mform->addElement(
            'select',
            'ifthenpay_gatewaykey',
            get_string('form:gateway_key', 'paygw_ifthenpay'),
            $gkmap
        );
        $mform->setDefault('ifthenpay_gatewaykey', $currentgk);
        $mform->setType('ifthenpay_gatewaykey', PARAM_RAW_TRIMMED);
        $mform->addRule('ifthenpay_gatewaykey', null, 'required', null, 'server');
        $mform->addHelpButton('ifthenpay_gatewaykey', 'form:gateway_key', 'paygw_ifthenpay');
    }

    /**
     * Payment configuration — required section.
     * Renders rows: checkbox + logo + label + select.
     *
     * Options come from $accounts[$currentgk]; persisted state only sets defaults.
     * It is OK to disable selects; the hidden state mirrors the UI on submission.
     *
     * @param \MoodleQuickForm $mform     Form.
     * @param array            $methods   Methods meta (methodKey => meta).
     * @param array            $ordered   Ordered method keys.
     * @param array            $accounts  Accounts: GK => methodKey => (accountId => label).
     * @param string           $currentgk Current gateway key.
     * @param array            $saved     Saved config for defaults.
     * @return void
     */
    private static function render_payment_section(
        \MoodleQuickForm $mform,
        array $methods,
        array $ordered,
        array $accounts,
        string $currentgk,
        array $saved
    ): void {
        $mform->addElement('header', 'ifp_paycfg', get_string('form:payment_configuration', 'paygw_ifthenpay'));

        $mform->addElement(
            'static',
            'ifp_paycfg_note',
            '',
            \html_writer::tag('small', get_string('form:payment_configuration_reqnote', 'paygw_ifthenpay'))
        );

        $bymethod = $accounts[$currentgk] ?? [];
        $noaccountslabel = get_string('form:noaccounts', 'paygw_ifthenpay');

        foreach ($ordered as $methodkey) {
            $meta     = $methods[$methodkey] ?? [];
            $label    = $meta['label'] ?? $methodkey;
            $imageurl = $meta['image'] ?? '';
            $tooltip  = $meta['tooltip'] ?? '';

            $cbname  = "ifthenpay_enable_{$methodkey}";
            $selname = "ifthenpay_account_{$methodkey}";

            // Checkbox (default from saved config only).
            $cb = $mform->createElement('advcheckbox', $cbname, '', '');
            $mform->setDefault($cbname, !empty($saved[$cbname]) ? 1 : 0);

            // Logo + label.
            $logo = $imageurl
                ? $mform->createElement(
                    'static',
                    "ifp_logo_{$methodkey}",
                    '',
                    \html_writer::div(
                        \html_writer::empty_tag('img', ['src' => $imageurl, 'alt' => $label, 'title' => $tooltip]),
                        'ifp-logo'
                    )
                )
                : $mform->createElement('static', '', '', '');

            $lbl = $mform->createElement(
                'static',
                "ifp_lbl_{$methodkey}",
                '',
                \html_writer::span(s($label), 'ifp-label', ['title' => $tooltip])
            );

            // Select options from live dataset for the current GK.
            $opts = $bymethod[$methodkey] ?? [];
            if (empty($opts)) {
                $opts = ['' => $noaccountslabel];
            }

            $sel = $mform->createElement('select', $selname, '', $opts);
            $sel->updateAttributes(['class' => 'ifp-select', 'title' => $tooltip]);

            // Default selection from saved config (if still present in options).
            if (isset($saved[$selname]) && $saved[$selname] !== '' && isset($opts[$saved[$selname]])) {
                $mform->setDefault($selname, $saved[$selname]);
            } else if (isset($opts[''])) {
                $mform->setDefault($selname, ''); // Placeholder when no accounts.
            }

            // If this GK has no accounts for the method → disable both controls.
            $hasaccounts = !empty($bymethod[$methodkey]);
            if (!$hasaccounts) {
                $mform->setDefault($cbname, 0);
                $cb->updateAttributes(['disabled' => 'disabled']);
                $sel->updateAttributes(['disabled' => 'disabled']);
            }

            $group = $mform->addGroup(
                [
                    $mform->createElement('html', '<div class="ifp-left">'),
                    $cb,
                    $logo,
                    $lbl,
                    $mform->createElement('html', '</div>'),
                    $sel,
                ],
                "ifp_row_{$methodkey}",
                '',
                ' ',
                false
            );
            $group->setAttributes(['class' => 'ifp-row']);
        }
    }

    /**
     * Other configuration (default method + description).
     *
     * @param \MoodleQuickForm $mform   Form.
     * @param array            $methods Methods meta.
     * @param array            $saved   Saved config.
     * @return void
     */
    private static function render_other_section(\MoodleQuickForm $mform, array $methods, array $saved): void {
        $mform->addElement('header', 'ifp_othercfg', get_string('form:other_configuration', 'paygw_ifthenpay'));

        // Default method (optional) — options from live dataset.
        $methodopts = ['' => get_string('form:default_method_none', 'paygw_ifthenpay')];
        foreach ($methods as $key => $meta) {
            $methodopts[$key] = $meta['label'] ?? $key;
        }

        $mform->addElement(
            'select',
            'ifthenpay_defaultmethod',
            get_string('form:default_method', 'paygw_ifthenpay'),
            $methodopts
        );
        $mform->setType('ifthenpay_defaultmethod', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('ifthenpay_defaultmethod', 'form:default_method', 'paygw_ifthenpay');
        $defaultmethod = $saved['ifthenpay_defaultmethod'] ?? '';
        $mform->setDefault('ifthenpay_defaultmethod', array_key_exists($defaultmethod, $methodopts) ? $defaultmethod : '');

        // Optional description.
        $mform->addElement(
            'text',
            'ifthenpay_description',
            get_string('form:description', 'paygw_ifthenpay'),
            ['size' => 64, 'maxlength' => 150]
        );
        $mform->setType('ifthenpay_description', PARAM_TEXT);
        $mform->addRule(
            'ifthenpay_description',
            get_string('form:error_maxchars', 'paygw_ifthenpay', 150),
            'maxlength',
            150,
            'client'
        );
        if (!empty($saved['ifthenpay_description'])) {
            $mform->setDefault('ifthenpay_description', $saved['ifthenpay_description']);
        }
        $mform->addHelpButton('ifthenpay_description', 'form:description', 'paygw_ifthenpay');
    }

    /**
     * Validate the account gateway configuration form.
     *
     * Minimal validation logic:
     * 1) Confirm `ifthenpay_state` decodes to an array.
     * 2) Ensure at least one payment method is enabled.
     * 3) If a default method is set, verify it exists and is enabled.
     * 4) Description (optional) must be ≤ 150 chars.
     *
     * After successful validation, activates the callback for the selected gateway key.
     *
     * @param account_gateway $form   Configuration form wrapper.
     * @param \stdClass       $data   Raw form submission data.
     * @param array           $files  Uploaded files (unused).
     * @param array           $errors Errors to attach to form fields (by ref).
     * @return void
     */
    public static function validate_gateway_form(
        account_gateway $form,
        \stdClass $data,
        array $files,
        array &$errors
    ): void {
        // 1) Must have a decodable ifthenpay_state.
        $state = is_string($data->ifthenpay_state ?? '') ? json_decode($data->ifthenpay_state, true) : null;
        if (!is_array($state)) {
            $errors['ifp_paycfg_note'] = get_string('form:error_state_missing', 'paygw_ifthenpay');
            return;
        }

        // Extract minimal fields we need from state.
        $methods     = $state['methods'] ?? [];
        $default     = (string) ($state['defaultmethod'] ?? '');
        $description = (string) ($state['description'] ?? '');

        // 2) Require at least one enabled method.
        if (!array_filter($methods, fn($m) => !empty($m['enabled']))) {
            $errors['ifp_paycfg_note'] = get_string('form:error_no_methods_enabled', 'paygw_ifthenpay');
        }

        // 3) If a default is set, ensure it exists and is enabled.
        if ($default !== '') {
            if (!isset($methods[$default])) {
                $errors['ifthenpay_defaultmethod'] = get_string('form:error_default_unknown', 'paygw_ifthenpay', $default);
            } else if (empty($methods[$default]['enabled'])) {
                $errors['ifthenpay_defaultmethod'] = get_string('form:error_default_not_enabled', 'paygw_ifthenpay', $default);
            }
        }

        // 4) Ensure description is at most 150 characters.
        if (\core_text::strlen($description) > 150) {
            $errors['ifthenpay_description'] = get_string('form:error_maxchars', 'paygw_ifthenpay', 150);
        }

        // Bail out early if there are any validation errors.
        if (!empty($errors)) {
            return;
        }

        // All validations passed — activate the gateway callback.
        $client = \paygw_ifthenpay_api();
        try {
            $client->activate_callback_by_gateway_context((string) $state['gatewaykey']);
        } catch (\moodle_exception $e) {
            $errors['ifp_paycfg_note'] = get_string('form:error_callback_activation', 'paygw_ifthenpay', $e->getMessage());
        }
    }

    /**
     * Build initial JSON state from saved config (for defaults only).
     *
     * @param array  $ordered   Ordered method keys.
     * @param array  $saved     Saved config.
     * @param string $currentgk Current gateway key.
     * @return array State array to be JSON-encoded.
     */
    private static function build_initial_state(array $ordered, array $saved, string $currentgk): array {
        $state = [
            'gatewaykey'    => $currentgk,
            'defaultmethod' => isset($saved['ifthenpay_defaultmethod']) ? (string) $saved['ifthenpay_defaultmethod'] : '',
            'description'   => isset($saved['ifthenpay_description']) ? (string) $saved['ifthenpay_description'] : '',
            'methods'       => [],
        ];

        foreach ($ordered as $methodkey) {
            $cbname  = "ifthenpay_enable_{$methodkey}";
            $selname = "ifthenpay_account_{$methodkey}";
            $state['methods'][$methodkey] = [
                'enabled' => !empty($saved[$cbname]),
                'account' => isset($saved[$selname]) ? (string) $saved[$selname] : '',
            ];
        }
        return $state;
    }

    /**
     * Get saved persistent config as an array (safe).
     *
     * @param account_gateway $form The account gateway form wrapper.
     * @return array Saved config array (empty array if none/invalid).
     */
    private static function get_saved_config(account_gateway $form): array {
        $persist = $form->get_gateway_persistent();
        if (!$persist) {
            return [];
        }
        $json = $persist->get('config');
        if (!is_string($json) || $json === '') {
            return [];
        }
        $arr = json_decode($json, true);
        return is_array($arr) ? $arr : [];
    }

    /**
     * Whether a Backoffice Key is present in configuration.
     *
     * @return bool True if present, false otherwise.
     */
    private static function has_backoffice_key(): bool {
        $key = (string) get_config('paygw_ifthenpay', 'backoffice_key');
        return trim($key) !== '';
    }
}

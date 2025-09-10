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
 * Library of interface functions and constants for module ifthenpay.
 *
 * @package    paygw_ifthenpay
 * @copyright  2025 ifthenpay <geral@ifthenpay.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use paygw_ifthenpay\local\api_client;
use paygw_ifthenpay\local\data_formatter;

/**
 * Get the configured Ifthenpay Backoffice Key.
 *
 * @return string Backoffice key or empty string.
 */
function paygw_ifthenpay_get_backoffice_key(): string {
    return trim((string) get_config('paygw_ifthenpay', 'backoffice_key'));
}

/**
 * Build an API client using the stored Backoffice Key.
 *
 * @return api_client
 * @throws \moodle_exception If the Backoffice Key is missing.
 */
function paygw_ifthenpay_api(): api_client {
    $key = paygw_ifthenpay_get_backoffice_key();
    if ($key === '') {
        throw new \moodle_exception('missing_backoffice_key', 'paygw_ifthenpay');
    }
    return new api_client($key, 8);
}

/**
 * Detect preferred language (pt|en|es|fr) from Moodle environment.
 *
 * @return string Two-letter language code.
 */
function paygw_ifthenpay_detect_language(): string {
    // Try Moodle’s current language first.
    if (function_exists('current_language')) {
        $lang = (string) current_language();
    } else {
        global $USER, $CFG;
        $lang = (string) ($USER->lang ?? $CFG->lang ?? '');
    }
    $lang = substr($lang, 0, 2);
    return in_array($lang, ['pt', 'en', 'es', 'fr'], true) ? $lang : 'pt';
}

/**
 * Decode a serialized ifthenpay_state JSON string from gateway config.
 *
 * @param array $cfg Gateway configuration array (expects 'ifthenpay_state').
 * @return stdClass Decoded state object (empty object if absent/invalid).
 */
function paygw_ifthenpay_decode_state(array $cfg): stdClass {
    $raw = $cfg['ifthenpay_state'] ?? '';
    if (!is_string($raw) || trim($raw) === '') {
        return new stdClass();
    }
    $decoded = json_decode($raw);
    return (is_object($decoded) && json_last_error() === JSON_ERROR_NONE) ? $decoded : new stdClass();
}

/**
 * Convert a list of rows [{id,label}] into an associative map id => label.
 * If input is already a map, it is returned as-is.
 *
 * @param array $list  Input list or map.
 * @param string $key  Key field name in each row.
 * @param string $label Label field name in each row.
 * @return array Associative map.
 */
function paygw_ifthenpay_as_map(array $list, string $key = 'id', string $label = 'label'): array {
    if ($list === [] || array_keys($list) !== range(0, count($list) - 1)) {
        // Already associative.
        return $list;
    }
    $out = [];
    foreach ($list as $row) {
        if (is_array($row) && isset($row[$key]) && array_key_exists($label, $row)) {
            $out[(string) $row[$key]] = (string) $row[$label];
        }
    }
    return $out;
}

/**
 * Fetch Gateway Keys and return a map gatewaykey => alias.
 *
 * @return array Map of gateway keys to aliases.
 */
function paygw_ifthenpay_get_gateway_keys_map(): array {
    try {
        $api = paygw_ifthenpay_api();
        $raw = $api->get_gateway_keys();
        $fmt = data_formatter::format_gateway_keys($raw);
        return paygw_ifthenpay_as_map($fmt, 'gatewaykey', 'alias');
    } catch (\Throwable $e) {
        debugging('[ifthenpay] get_gateway_keys_map: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return [];
    }
}

/**
 * Fetch available methods as a map methodKey => meta.
 * Meta keys: position (int), image (string), tooltip (string), label (string).
 *
 * @return array Methods map.
 */
function paygw_ifthenpay_get_methods_rich(): array {
    try {
        $api = paygw_ifthenpay_api();
        $raw = $api->get_available_payment_methods();
        $fmt = data_formatter::format_available_payment_methods($raw);

        // If already associative, return as-is.
        if ($fmt !== [] && array_keys($fmt) !== range(0, count($fmt) - 1)) {
            return $fmt;
        }

        // Reindex by 'id' when formatted as a list.
        $out = [];
        foreach ($fmt as $row) {
            if (isset($row['id'])) {
                $out[(string) $row['id']] = $row;
            }
        }
        return $out;
    } catch (\Throwable $e) {
        debugging('[ifthenpay] get_methods_rich: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return [];
    }
}

/**
 * Fetch accounts per method for a Gateway Key.
 *
 * @param string $gatewaykey Gateway key.
 * @return array Map: methodKey => (accountId => label).
 */
function paygw_ifthenpay_get_accounts_by_gateway_map(string $gatewaykey): array {
    $gatewaykey = trim($gatewaykey);
    if ($gatewaykey === '') {
        return [];
    }
    try {
        $api = paygw_ifthenpay_api();
        $raw = $api->get_payment_accounts_by_gateway($gatewaykey);
        $fmt = data_formatter::format_payment_accounts($raw);
        $out = [];
        foreach (($fmt ?? []) as $method => $list) {
            $out[$method] = paygw_ifthenpay_as_map($list, 'id', 'label');
        }
        return $out;
    } catch (\Throwable $e) {
        debugging('[ifthenpay] get_accounts_by_gateway_map: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return [];
    }
}

/**
 * Order method keys by 'position' ascending.
 *
 * @param array $methodsrich Methods map (methodKey => meta).
 * @return array Ordered list of method keys.
 */
function paygw_ifthenpay_order_method_keys(array $methodsrich): array {
    uasort($methodsrich, fn($a, $b) => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));
    return array_keys($methodsrich);
}

/**
 * Build full dataset at once (no AJAX).
 *
 * @return array Dataset containing gatewaykeys, methods, accounts and meta.
 */
function paygw_ifthenpay_build_full_admin_dataset(): array {
    $gkmap   = paygw_ifthenpay_get_gateway_keys_map(); // GK => alias.
    $methods = paygw_ifthenpay_get_methods_rich();     // MethodKey => meta.

    // GK => (methodKey => (accountId => label)).
    $accounts = [];
    foreach (array_keys($gkmap) as $gk) {
        $accounts[$gk] = paygw_ifthenpay_get_accounts_by_gateway_map($gk);
    }

    return [
        'gatewaykeys' => $gkmap,
        'methods' => $methods,
        'accounts' => $accounts,
        'meta' => ['fetchedat' => time(), 'source' => 'api'],
    ];
}

/**
 * Build optgroups per method (groups = Gateway Keys) for use with 'selectgroups'.
 *
 * @param array $dataset Dataset from paygw_ifthenpay_build_full_admin_dataset().
 * @param string $method Method key.
 * @return array List of groups: [['text' => alias, 'options' => (id => label)], ...].
 */
function paygw_ifthenpay_build_optgroups_for_method(array $dataset, string $method): array {
    $groups = [];
    $gkmap  = $dataset['gatewaykeys'] ?? []; // GK => alias.
    $accs   = $dataset['accounts'] ?? [];    // GK => (method => (id => label)).

    foreach ($gkmap as $gk => $alias) {
        $opts = $accs[$gk][$method] ?? [];
        if (!empty($opts)) {
            $groups[] = ['text' => $alias, 'options' => $opts];
        }
    }
    return $groups;
}

/**
 * Process an Ifthenpay webhook event (idempotent).
 *
 * Contract (same as webhook.php GET):
 *  - $token  Internal order token (repository primary key).
 *  - $amount Amount as stored (string); must match exactly.
 *  - $apk    Base64 of the gateway key; must decode to rec->gateway_key.
 *
 * Behaviour:
 *  - Returns true when the payment is (or becomes) PAID.
 *  - Returns false on any validation failure or technical error.
 *
 * @param string $token  Payment token.
 * @param string $amount Amount string (already formatted).
 * @param string $apk    Base64-encoded gateway key.
 * @return bool True if processed/paid, false otherwise.
 */
function paygw_ifthenpay_process_webhook(string $token, string $amount, string $apk): bool {
    global $DB;

    $rec = $DB->get_record('paygw_ifthenpay_tx', ['token' => $token], '*', IGNORE_MISSING);
    if (!$rec) {
        return false;
    }

    // Validate anti-phishing (apk) and amount.
    $decoded = base64_decode($apk, true);
    if ($decoded === false || (string) $decoded !== (string) $rec->gateway_key) {
        return false;
    }
    if ($amount !== (string) $rec->amount) {
        return false;
    }

    // Idempotent: already paid?
    if ((string) $rec->state === 'PAID') {
        return true;
    }

    try {
        // Finalise Moodle payment and deliver order.
        $paymentid = \core_payment\helper::save_payment(
            (int) $rec->accountid,
            (string) $rec->component,
            (string) $rec->paymentarea,
            (int) $rec->itemid,
            (int) $rec->userid,
            (float) $rec->amount,
            (string) $rec->currency,
            'ifthenpay'
        );

        \core_payment\helper::deliver_order(
            $rec->component,
            $rec->paymentarea,
            $rec->itemid,
            $paymentid,
            $rec->userid
        );

        $rec->paymentid    = $paymentid;
        $rec->state        = 'PAID';
        $rec->timemodified = time();
        $DB->update_record('paygw_ifthenpay_tx', $rec);

        return true;
    } catch (\Throwable $e) {
        return false;
    }
}

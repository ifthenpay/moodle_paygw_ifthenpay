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
 * Helpers to format data for the ifthenpay integration.
 *
 * @package    paygw_ifthenpay
 * @copyright  2025 ifthenpay <geral@ifthenpay.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_ifthenpay\local;

use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Formatters and payload constructors for the ifthenpay integration.
 *
 * Provides static helpers to format gateway keys, payment methods, accounts,
 * and amounts for use within the plugin.
 */
class data_formatter
{
    /**
     * Format gateway keys into an associative array [GatewayKey => Alias].
     *
     * Input is the response from api_client::get_gateway_keys().
     *
     * @param array $raw Each row must include 'Alias' and 'GatewayKey'.
     * @return array Map of GatewayKey => Alias.
     */
    public static function format_gateway_keys(array $raw): array {
        $out = [];
        foreach ($raw as $row) {
            $alias = $row['Alias'] ?? null;
            $gk    = $row['GatewayKey'] ?? null;
            if ($alias && $gk) {
                $out[$gk] = $alias;
            }
        }
        return $out;
    }

    /**
     * Format available payment methods into [key => ['position','image','tooltip','label']].
     *
     * Input is the response from api_client::get_available_payment_methods().
     *
     * @param array $raw Method rows from the API.
     * @return array Map keyed by method entity.
     */
    public static function format_available_payment_methods(array $raw): array {
        $methods = [];
        foreach ($raw as $entry) {
            $key = $entry['Entity'] ?? '';
            if ($key === '') {
                continue;
            }
            $methods[$key] = [
                'position' => (int)($entry['Position'] ?? 0),
                'image'    => (string)($entry['SmallImageUrl'] ?? ''),
                'tooltip'  => (string)($entry['DescriptionEN'] ?? ''),
                'label'    => (string)($entry['Method'] ?? ''),
            ];
        }
        // Ensure stable ordering by position.
        uasort($methods, fn ($a, $b) => $a['position'] <=> $b['position']);
        return $methods;
    }

    /**
     * Convert payment accounts into a map: Entity => [Account => Alias].
     *
     * Numeric 'Entidade' values are bucketed under 'MB' (Multibanco).
     *
     * @param array $accounts Rows with 'Alias', 'Conta', 'Entidade', 'SubEntidade'.
     * @return array Map of Entity => (Account => Alias).
     */
    public static function format_payment_accounts(array $accounts): array {
        $result = [];
        foreach ($accounts as $acct) {
            $alias = $acct['Alias'] ?? '';
            $conta = $acct['Conta'] ?? '';
            if ($alias === '' || $conta === '') {
                continue;
            }
            $ent = $acct['Entidade'] ?? '';
            $bucket = is_numeric($ent) ? 'MB' : ($ent ?: 'OTHER');
            $result[$bucket] ??= [];
            $result[$bucket][$conta] = $alias;
        }
        return $result;
    }

    /**
     * Format a monetary amount with two decimals (e.g., "12.34").
     *
     * @param float $amount Amount to format.
     * @return string Amount formatted as "0.00".
     */
    public static function format_amount(float $amount): string {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Build the payload to create an ifthenpay Pay-by-Link.
     *
     * Notes:
     * - Options (methods/accounts) are constrained by the admin form.
     * - The default method is optional; when not set (“Noone”) we omit selected_method.
     *
     * @param float    $cost          Raw payment amount.
     * @param stdClass $state         Decoded ifthenpay_state (gateway form state).
     * @param string   $token         Unique token for this payment attempt.
     * @param string   $desc_checkout Optional checkout description.
     * @return array   Payload for api_client::create_pay_by_link().
     */
    public static function build_pay_by_link_payload(
        float $cost,
        stdClass $state,
        string $token,
        string $desccheckout
    ): array {
        // Return URLs (token used for correlation; keep txid placeholder literal).
        $success = (new \moodle_url('/payment/gateway/ifthenpay/return.php', [
            'token' => $token,
        ]))->out(false) . '&txid=[TRANSACTIONID]';

        $cancel = (new \moodle_url('/payment/gateway/ifthenpay/cancel.php', [
            'token' => $token,
            'type'  => 'CANCEL',
        ]))->out(false) . '&txid=[TRANSACTIONID]';

        $error = (new \moodle_url('/payment/gateway/ifthenpay/cancel.php', [
            'token' => $token,
            'type'  => 'ERROR',
        ]))->out(false) . '&txid=[TRANSACTIONID]';

        $payload = [
            'id'          => $token,
            'amount'      => self::format_amount($cost),
            'description' => self::make_description($state, $token, $desccheckout),
            'lang'        => \paygw_ifthenpay_detect_language(),
            'accounts'    => self::make_accounts($state),
            'success_url' => $success,
            'cancel_url'  => $cancel,
            'error_url'   => $error,
            'btnCloseUrl' => $cancel,
        ];

        // Only include selected_method when a default is chosen (not “Noone”).
        $index = self::compute_selected_method($state);
        if ($index !== null) {
            $payload['selected_method'] = $index; // 1-based index in canonical ordering.
        }

        return $payload;
    }

    /**
     * Compose a human-friendly description.
     *
     * Priority: state.description → checkout description → “Order #<token>”.
     *
     * @param stdClass $state         Decoded ifthenpay_state.
     * @param string   $token         Payment token.
     * @param string   $desc_checkout Optional checkout description.
     * @return string
     */
    private static function make_description(stdClass $state, string $token, string $desccheckout): string {
        $fromstate    = isset($state->description) ? trim((string)$state->description) : '';
        $fromcheckout = trim((string)$desccheckout);

        if ($fromstate !== '') {
            return 'Order #' . $token . ' - ' . $fromstate;
        }
        if ($fromcheckout !== '') {
            return 'Order #' . $token . ' - ' . $fromcheckout;
        }
        return 'Order #' . $token;
    }

    /**
     * Build the ifthenpay accounts string.
     *
     * Includes only enabled methods, preserving the JSON order.
     * Example: "MB|ADC-663833;MBWAY|MBW-123456".
     *
     * @param stdClass $state Decoded ifthenpay_state (methods as stdClass).
     * @return string
     */
    private static function make_accounts(stdClass $state): string {
        $parts = [];
        foreach ($state->methods as $method => $meta) {
            if (!empty($meta->enabled) && !empty($meta->account)) {
                // Normalize spaces around the pipe, e.g. "MB | ADC-663833" → "MB|ADC-663833".
                $parts[] = preg_replace('/\s*\|\s*/', '|', trim((string)$meta->account));
            }
        }
        return implode(';', $parts);
    }

    /**
     * Compute the 1-based position of the selected default method.
     *
     * Uses paygw_ifthenpay_get_methods_rich() as the canonical ordering
     * (methodKey => ['position' => int, ...]).
     *
     * Returns null when no default is set (“Noone”) or the key is unknown.
     *
     * @param stdClass $state Decoded ifthenpay_state.
     * @return int|null 1-based position or null when not applicable.
     */
    private static function compute_selected_method(stdClass $state): ?int {
        $target = isset($state->defaultmethod) ? (string)$state->defaultmethod : '';
        if ($target === '') {
            return null;
        }

        // Canonical map from lib.php.
        $methodsrich = paygw_ifthenpay_get_methods_rich();
        if (!isset($methodsrich[$target])) {
            return null;
        }

        return (int)$methodsrich[$target]['position'];
    }
}

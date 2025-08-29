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
 * Redirects to the ifthenpay checkout for payment
 *
 * @package    paygw_ifthenpay
 * @copyright  2025 ifthenpay <geral@ifthenpay.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;

require(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/lib.php');

require_login();

$component = required_param('component', PARAM_ALPHANUMEXT);
$paymentarea = required_param('paymentarea', PARAM_ALPHANUMEXT);
$itemid = required_param('itemid', PARAM_INT);
$description = urldecode(required_param('description', PARAM_TEXT));
$sessionid = optional_param('session_id', null, PARAM_TEXT);

// 1) Resolve amount/currency/account from the component (server-side, canonical).
$payable   = helper::get_payable($component, $paymentarea, $itemid);
$surcharge = helper::get_gateway_surcharge('ifthenpay');
$cost      = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge);

// 2) Get this account’s gateway config (from your gateway form).
$cfg = helper::get_gateway_configuration($component, $paymentarea, $itemid, 'ifthenpay');

// Check if $cfg exists, contains 'ifthenpay_state', and is not empty.
if (!isset($cfg) || !is_array($cfg) || !array_key_exists('ifthenpay_state', $cfg) || empty($cfg['ifthenpay_state'])) {
    throw new moodle_exception('missing_ifthenpay_state', 'paygw_ifthenpay');
}

// 3) Build transaction token (short, 8 chars, base64url)
$token = substr(rtrim(strtr(base64_encode(random_bytes(6)), '+/', '-_'), '='), 0, 8);

// 4) Build the PinPay payload
$state   = \paygw_ifthenpay_decode_state($cfg);
$payload = \paygw_ifthenpay\local\data_formatter::build_pay_by_link_payload($cost, $state, $token, $description);

// 5) Generate PinPay order
$client = paygw_ifthenpay_api();
$order  = $client->create_pay_by_link($state->gatewaykey, $payload);

// 6) (Optional, recommended) Persist for diagnostics/idempotency (minimal fields).
if ($DB->get_manager()->table_exists('paygw_ifthenpay_tx')) {
    $rec = (object)[
        'timecreated'   => time(),
        'timemodified'  => time(),
        'token'         => $token,
        'userid'        => $USER->id,
        'component'     => $component,
        'paymentarea'   => $paymentarea,
        'itemid'        => $itemid,
        'accountid'     => $payable->get_account_id(),
        'amount'        => $cost,
        'currency'      => $payable->get_currency(),
        'gateway_key'   => $state->gatewaykey,
        'redirect_url'  => $order->redirect_url ?? $order->RedirectUrl ?? '',
        'paymentid'     => null,
        'transaction_id' => null,
        'state'         => 'PENDING',
    ];
    $DB->insert_record('paygw_ifthenpay_tx', $rec);
}

// 7) Off you go to Ifthenpay checkout.
$redirect = $order->redirect_url ?? $order->RedirectUrl ?? '';
if ($redirect === '') {
    throw new moodle_exception('error_missing_redirect', 'paygw_ifthenpay');
}
redirect(new moodle_url($redirect));

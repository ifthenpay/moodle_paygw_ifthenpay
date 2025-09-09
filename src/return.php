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
 * Return page for ifthenpay payments. Handles both UI and AJAX verification.
 *
 * @package    paygw_ifthenpay
 * @copyright  2025 ifthenpay <geral@ifthenpay.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;

require(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/lib.php');

require_login();

$token  = required_param('token', PARAM_ALPHANUMEXT);
$txid   = required_param('txid', PARAM_RAW_TRIMMED);
$sk     = optional_param('sk', '', PARAM_RAW_TRIMMED);
$action = optional_param('action', '', PARAM_ALPHA);   // For AJAX polling.

global $DB, $PAGE, $OUTPUT;

// Fetch record or bail to courses.
$rec = $DB->get_record('paygw_ifthenpay_tx', ['token' => $token], '*', IGNORE_MISSING);
if (!$rec) {
    if ($action === 'verify') {
        @header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['paid' => false, 'error' => 'notfound']);
        exit;
    }
    redirect(new moodle_url('/my/courses.php'));
}

// Persist txid once.
if (empty($rec->transaction_id)) {
    $rec->transaction_id = $txid;
    $rec->timemodified   = time();
    $DB->update_record('paygw_ifthenpay_tx', $rec);
}

$successurl = helper::get_success_url($rec->component, $rec->paymentarea, $rec->itemid);

// AJAX verify endpoint (15s polling).
if ($action === 'verify') {
    @header('Content-Type: application/json; charset=utf-8');

    $client   = paygw_ifthenpay_api();
    $deadline = time() + 15;

    $ispaid = function () use ($DB, $token): bool {
        $fresh = $DB->get_record('paygw_ifthenpay_tx', ['token' => $token], 'id,state', IGNORE_MISSING);
        return $fresh && (string)$fresh->state === 'PAID';
    };

    if ($ispaid()) {
        echo json_encode(['paid' => true]);
        exit;
    }

    while (time() < $deadline) {
        try {
            if ($client->get_transaction_status($txid) === true) {
                // Directly call the shared processor (no self-HTTP).
                paygw_ifthenpay_process_webhook(
                    $token,
                    (string)$rec->amount,
                    base64_encode((string)$rec->gateway_key)
                );
            }
        } catch (\Throwable $e) {
            // Ignore API errors here, we'll just retry until deadline.
            debugging('[ifthenpay] Transaction status check error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        if ($ispaid()) {
            echo json_encode(['paid' => true]);
            exit;
        }

        usleep(1000000); // 1s.
    }

    echo json_encode(['paid' => false]);
    exit;
}

// Already paid? Go straight to success.
if ((string)$rec->state === 'PAID') {
    redirect($successurl);
}

// Normal page (UI).
$params = array_filter([
    'token' => $token,
    'txid'  => $txid,
    'sk'    => $sk !== '' ? $sk : null,
]);

$PAGE->set_url(new moodle_url('/payment/gateway/ifthenpay/return.php', $params));
$PAGE->set_context(context_system::instance());

// Strings.
$str = (object)[
    'title'   => get_string('process:return_title', 'paygw_ifthenpay'),
    'waiting' => get_string('process:waiting', 'paygw_ifthenpay'),
    'hint'    => get_string('process:waiting_hint', 'paygw_ifthenpay'),
    'ref'     => get_string('process:order_reference', 'paygw_ifthenpay'),
    'txid'    => get_string('process:transaction_id', 'paygw_ifthenpay'),
    'amount'  => get_string('process:amount', 'paygw_ifthenpay'),
    'retry'   => get_string('process:btn_retry', 'paygw_ifthenpay'),
    'courses' => get_string('process:btn_go_to_courses', 'paygw_ifthenpay'),
];

$PAGE->set_title($str->title);
$PAGE->set_heading(get_string('gatewayname', 'paygw_ifthenpay'));

// JS dataset + AMD boot (same pattern as admin form).
$verifyurl   = (new moodle_url('/payment/gateway/ifthenpay/return.php', $params + ['action' => 'verify']))->out(false);
$coursesurl  = (new moodle_url('/my/courses.php'))->out(false);
$successurls = $successurl->out(false);

$PAGE->requires->data_for_js('ifthenpay', (object)[
    'verifyUrl'  => $verifyurl,
    'successUrl' => $successurls,
    'coursesUrl' => $coursesurl,
]);

$selectors = (object)[
    'spinner' => 'ifp-spinner',
    'status'  => 'ifp-status',
    'retry'   => 'ifp-retry',
];
$i18n = (object)[
    'verifying' => $str->waiting,
];

$PAGE->requires->js_call_amd('paygw_ifthenpay/return', 'init', [$selectors, $i18n]);

echo $OUTPUT->header();

// Already formatted upstream.
$amount = s((string)$rec->amount) . ' ' . s($rec->currency);
$ref    = s($rec->token);
$txids  = s((string)$txid);

// UI (spinner shows only while verifying; retry is single-use via AMD).
echo html_writer::start_div('container my-5');
echo html_writer::start_div('row justify-content-center');
echo html_writer::start_div('col-md-8 col-lg-7');

echo html_writer::start_div('card shadow-sm rounded-3');
echo html_writer::start_div('card-body p-4 p-md-5');

echo html_writer::start_div('mb-3 d-flex align-items-center', ['style' => 'gap:1rem']);
echo html_writer::tag('span', '', [
    'id' => 'ifp-spinner',
    'class' => 'spinner-border spinner-border-sm',
    'role' => 'status',
    'aria-hidden' => 'true',
    'style' => 'display:none',
]);
echo html_writer::span($str->waiting, 'fw-semibold mb-0', ['id' => 'ifp-status']);
echo html_writer::end_div();

echo html_writer::tag('h2', $str->title, ['class' => 'h4 mb-2']);
echo html_writer::tag('p', $str->hint, ['class' => 'text-muted mb-4']);

$dl  = html_writer::start_tag('dl', ['class' => 'row small mb-4']);
$dl .= html_writer::tag('dt', $str->ref, ['class' => 'col-sm-4']) . html_writer::tag('dd', $ref, ['class' => 'col-sm-8']);
$dl .= html_writer::tag('dt', $str->txid, ['class' => 'col-sm-4']) . html_writer::tag('dd', $txids, ['class' => 'col-sm-8']);
$dl .= html_writer::tag('dt', $str->amount, ['class' => 'col-sm-4']) . html_writer::tag('dd', $amount, ['class' => 'col-sm-8']);
$dl .= html_writer::end_tag('dl');
echo $dl;

echo html_writer::start_div('d-flex gap-2');
echo html_writer::tag('button', $str->retry, ['class' => 'btn btn-primary', 'id' => 'ifp-retry', 'type' => 'button']);
echo html_writer::link(new moodle_url('/my/courses.php'), $str->courses, ['class' => 'btn btn-link']);
echo html_writer::end_div();

echo html_writer::end_div(); // Card-body.
echo html_writer::end_div(); // Card.
echo html_writer::end_div(); // Col.
echo html_writer::end_div(); // Row.
echo html_writer::end_div(); // Container.

echo $OUTPUT->footer();

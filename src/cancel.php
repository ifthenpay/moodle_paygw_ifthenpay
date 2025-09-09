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
 * Payment cancelled or failed page (redirect from ifthenpay).
 *
 * @package    paygw_ifthenpay
 * @copyright  2025 ifthenpay <geral@ifthenpay.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;

require(__DIR__ . '/../../../config.php');

require_login();

$token = required_param('token', PARAM_ALPHANUMEXT);
$type  = required_param('type', PARAM_ALPHA);        // CANCEL or ERROR.

global $DB, $CFG, $PAGE, $OUTPUT;

// Fetch transaction.
$rec = $DB->get_record('paygw_ifthenpay_tx', ['token' => $token], '*', IGNORE_MISSING);

// Strings (single fetch, then reuse).
$str = (object)[
    'title'        => get_string('process:cancel_title', 'paygw_ifthenpay'),
    'desc_cancel'  => get_string('process:cancel_desc_cancel', 'paygw_ifthenpay'),
    'desc_error'   => get_string('process:cancel_desc_error', 'paygw_ifthenpay'),
    'status_cancel' => get_string('process:status_canceled', 'paygw_ifthenpay'),
    'status_error' => get_string('process:status_error', 'paygw_ifthenpay'),
    'ref'          => get_string('process:order_reference', 'paygw_ifthenpay'),
    'amount'       => get_string('process:amount', 'paygw_ifthenpay'),
    'tryagain'     => get_string('process:btn_try_again', 'paygw_ifthenpay'),
    'support'      => get_string('process:btn_contact_support', 'paygw_ifthenpay'),
    'notfound'     => get_string('process:not_found', 'paygw_ifthenpay'),
];

// Page basics.
$params = ['token' => $token, 'type' => $type];
$PAGE->set_url(new moodle_url('/payment/gateway/ifthenpay/cancel.php', $params));
$PAGE->set_context(context_system::instance());
$PAGE->set_title($str->title);
$PAGE->set_heading(get_string('gatewayname', 'paygw_ifthenpay'));

echo $OUTPUT->header();

if (!$rec) {
    echo $OUTPUT->notification($str->notfound, 'notifyproblem');
    echo html_writer::div(
        html_writer::link(new moodle_url('/'), get_string('back'), ['class' => 'btn btn-secondary']),
        'mt-3'
    );
    echo $OUTPUT->footer();
    exit;
}

// Persist state (idempotent).
if ((string)$rec->state !== 'PAID') {
    $rec->state = (strtoupper($type) === 'ERROR') ? 'ERROR' : 'CANCELED';
}
$rec->timemodified = time();
$DB->update_record('paygw_ifthenpay_tx', $rec);

// Variants.
$iserror   = (strtoupper($type) === 'ERROR');
$badge     = $iserror ? $str->status_error : $str->status_cancel;
$desc      = $iserror ? $str->desc_error : $str->desc_cancel;
$badgecls  = $iserror ? 'bg-danger' : 'bg-warning';

$backurl = helper::get_success_url($rec->component, $rec->paymentarea, $rec->itemid);
$support = !empty($CFG->supportemail)
    ? html_writer::link(new moodle_url('mailto:' . $CFG->supportemail), $str->support, ['class' => 'btn btn-link'])
    : '';

// Already formatted upstream.
$amount = s((string)$rec->amount) . ' ' . s($rec->currency);
$ref    = s($rec->token);

// Render (core Bootstrap only).
echo html_writer::start_div('container my-5');
echo html_writer::start_div('row justify-content-center');
echo html_writer::start_div('col-md-8 col-lg-7');

echo html_writer::start_div('card shadow-sm rounded-3');
echo html_writer::start_div('card-body p-4 p-md-5');

echo html_writer::div(html_writer::span($badge, 'badge ' . $badgecls), 'mb-3');
echo html_writer::tag('h2', $str->title, ['class' => 'h4 mb-2']);
echo html_writer::tag('p', $desc, ['class' => 'text-muted mb-4']);

$dl  = html_writer::start_tag('dl', ['class' => 'row small mb-4']);
$dl .= html_writer::tag('dt', $str->ref, ['class' => 'col-sm-4']) . html_writer::tag('dd', $ref, ['class' => 'col-sm-8']);
$dl .= html_writer::tag('dt', $str->amount, ['class' => 'col-sm-4']) . html_writer::tag('dd', $amount, ['class' => 'col-sm-8']);
$dl .= html_writer::end_tag('dl');
echo $dl;

echo html_writer::start_div('d-flex gap-2');
echo html_writer::link($backurl, $str->tryagain, ['class' => 'btn btn-primary']);
echo $support;
echo html_writer::end_div(); // Actions.

echo html_writer::end_div(); // Card-body.
echo html_writer::end_div(); // Card.
echo html_writer::end_div(); // Col.
echo html_writer::end_div(); // Row.
echo html_writer::end_div(); // Container.

echo $OUTPUT->footer();

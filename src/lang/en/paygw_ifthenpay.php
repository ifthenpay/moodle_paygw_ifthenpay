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
 * Strings for component 'paygw_ifthenpay', language 'en'
 *
 * @package    paygw_ifthenpay
 * @copyright  2025 ifthenpay <geral@ifthenpay.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Default.
$string['pluginname'] = 'ifthenpay';
$string['gatewayname'] = 'ifthenpay | Payment Gateway';
$string['gatewaydescription'] = '
An authorised payment gateway provider for processing payments with
<strong>Credit Cards</strong>, <strong>Cofidis Pay</strong>, <strong>Apple Pay</strong>,
<strong>Google Pay</strong>, <strong>MB WAY</strong>, <strong>Bizum</strong>, <strong>Pix</strong>,
<strong>Multibanco</strong> and <strong>Payshop</strong>.
';


// Modal (moustache).
$string['modal:redirectingtoifthenpay'] = 'Redirecting to ifthenpay | Payment Gateway';
$string['modal:pleasewait'] = 'Please wait...';


// Settings / headings.
$string['onboarding_title'] = 'Free service subscription';
$string['api_heading'] = 'Connect your ifthenpay account';
$string['behavior_heading'] = 'Payment behaviour';
$string['behavior_desc'] = 'Optional settings affecting how this gateway is shown to users.';
$string['onboarding_html'] = '
  <ul>
    <li>Visit and <a href="https://ifthenpay.com/aderir/" target="_blank" rel="noopener">subscribe</a>.</li>
    <li>Download and fill in the contract.</li>
    <li>Attach the required documents.</li>
    <li>Request the creation of the Gateway Key.</li>
    <li>Send the documents to <a href="mailto:ifthenpay@ifthenpay.com">ifthenpay@ifthenpay.com</a>.</li>
  </ul>
  <p><strong>Note:</strong> If you already have a contract with ifthenpay, just request the Gateway Key.</p>
  <p>For more information visit <a href="https://ifthenpay.com" target="_blank" rel="noopener">ifthenpay.com</a>.</p>
';
$string['backoffice_key'] = 'Backoffice Key';
$string['backoffice_key_desc'] = 'Used to authenticate API calls and webhooks.';

// Validation / messages.
$string['error_invalidformat'] = 'Invalid format. Use 1234-5678-9012-3456.';
$string['error_invalid_backoffice_key'] = 'The Backoffice Key is not valid. Please check and try again.';


// Errors for API responses.
$string['api:nobackofficekey_error'] = 'API: No Backoffice Key configured.';
$string['api:error_invalid_pbl_response'] = 'Invalid response from Pay-by-Link API.';
$string['api:error_invalid_json_get'] = 'Invalid JSON on GET request: {$a}';
$string['api:error_invalid_json_post'] = 'Invalid JSON on POST request.';
$string['api:error_http_request_failed'] = 'HTTP request failed: {$a}';
$string['api:error_http_status'] = 'API HTTP error: {$a}';


// Form – sections & labels.
$string['form:gateway_configuration'] = 'Gateway settings';
$string['form:gateway_key'] = 'Gateway Key';
$string['form:gateway_key_help'] = 'Need another key? <a href="mailto:suporte@ifthenpay.com">Contact ifthenpay support</a>. New keys and accounts appear automatically after activation.';

$string['form:payment_configuration'] = 'Payment methods';
$string['form:payment_configuration_reqnote'] = '<strong>Required:</strong> Please enable at least one payment method.';
$string['form:noaccounts'] = 'No accounts available';

$string['form:other_configuration'] = 'Additional settings';
$string['form:default_method'] = 'Default method (Optional)';
$string['form:default_method_help'] =
    'Optional. If set, this method will be preselected at checkout when multiple methods are enabled. Leave as "Noone" to let the customer choose without a preset.';
$string['form:default_method_none'] = 'Noone';
$string['form:description'] = 'Checkout description (Optional)';
$string['form:description_help'] = 'Optional text, up to 150 characters, shown at checkout.';

$string['form:missing_backoffice_key_inline'] = 'Backoffice Key is not configured. <a href="{$a}">Open settings</a>.';
$string['form:missing_gateway_keys_inline'] =
    'No Gateway Key is configured for Moodle in your ifthenpay backoffice. Please <a href="mailto:suporte@ifthenpay.com">contact ifthenpay support</a> to create a Gateway Key for Moodle and assign the payment methods you intend to accept. After it’s created, return here and select it.';

// Validation / messages.
$string['form:error_state_missing'] = 'Configuration data is missing. Please try saving again.';
$string['form:error_no_methods_enabled'] = 'Please enable at least one payment method.';
$string['form:error_default_not_enabled'] = 'The default method "{$a}" must be enabled in Payment methods.';
$string['form:error_default_unknown'] = 'Selected default method "{$a}" is not recognized.';
$string['form:error_maxchars'] = 'Maximum {$a} characters.';
$string['form:error_callback_activation'] = 'Failed to activate payment notifications. Please check your Backoffice Key and internet connectivity, then save again. Error: {$a}';


// Cancel/error page (processing flow).
$string['process:cancel_title']        = 'Payment not completed';
$string['process:cancel_desc_cancel']  = 'You canceled the payment before it was completed. You can try again below.';
$string['process:cancel_desc_error']   = 'We could not confirm your payment due to an error. You can try again or contact support.';
$string['process:status_canceled']     = 'Canceled';
$string['process:status_error']        = 'Error';
$string['process:btn_try_again']       = 'Try again';
$string['process:btn_contact_support'] = 'Contact support';
$string['process:not_found']           = 'Payment attempt not found.';

// Processing / return page.
$string['process:return_title']        = 'Confirming your payment';
$string['process:waiting']             = 'Checking status…';
$string['process:waiting_hint']        = 'This may take a few seconds. You can try once again; if it still does not complete, return to your courses.';
$string['process:order_reference']     = 'Order reference';
$string['process:transaction_id']      = 'Transaction ID';
$string['process:amount']              = 'Amount';
$string['process:btn_retry']           = 'Retry validation';
$string['process:btn_go_to_courses']   = 'Go to My courses';

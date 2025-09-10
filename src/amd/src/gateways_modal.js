// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This module is responsible for ifthenpay content in the gateways modal.
 *
 * @module     paygw_ifthenpay/gateway_modal
 * @copyright  2025 ifthenpay <geral@ifthenpay.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* eslint-disable promise/always-return */
/* eslint-disable no-empty-function */

define(["core/templates", "core/modal_factory"], function(
  Templates,
  ModalFactory
) {
  const showModalWithPlaceholder = function() {
    return ModalFactory.create({
      body: Templates.render(
        "paygw_ifthenpay/ifthenpay_button_placeholder",
        {}
      ),
    }).then(function(modal) {
      modal.show();
    });
  };

  const process = function(component, paymentArea, itemId, description) {
    return showModalWithPlaceholder().then(function() {
      window.location.href =
        // eslint-disable-next-line no-undef
        M.cfg.wwwroot + "/payment/gateway/ifthenpay/pay.php?"
        + "component=" + component
        + "&paymentarea=" + paymentArea
        + "&itemid=" + itemId
        + "&description=" + description;
      // Keep promise pending to avoid UI race conditions.
      return new Promise(function() {});
    });
  };

  return {process: process};
});

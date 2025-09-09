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
 * This module is responsible for handling the interectativity of the return
 * page (post-checkout)
 *
 * @module     paygw_ifthenpay/return
 * @copyright  2025 ifthenpay <geral@ifthenpay.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function () {
  "use strict";

  function $(id) {
    return document.getElementById(id);
  }
  function disable(btn, yes) {
    if (!btn) return;
    if (yes) {
      btn.setAttribute("disabled", "disabled");
      btn.classList.add("disabled");
    } else {
      btn.removeAttribute("disabled");
      btn.classList.remove("disabled");
    }
  }

  class IfthenpayReturn {
    /**
     * @param {{selectors:Object, i18n:Object}} cfg
     * @param {{verifyUrl:string, successUrl:string, coursesUrl:string}} ds
     */
    constructor(cfg, ds) {
      this.s = cfg.selectors || {};
      this.t = cfg.i18n || {};
      this.ds = ds || {};

      this.spinner = $(this.s.spinner);
      this.status = $(this.s.status);
      this.retry = $(this.s.retry);

      this.busy = false;
      this.retried = false;
    }

    init() {
      if (!this.ds.verifyUrl || !this.retry || !this.spinner || !this.status) {
        return;
      }
      // Auto-verify once on load.
      this.verifyOnce();

      // Single-use retry.
      this.retry.addEventListener("click", (e) => {
        e.preventDefault();
        if (this.retried || this.busy) return;
        this.retried = true;
        this.verifyOnce();
      });
    }

    setBusy(on) {
      this.busy = !!on;
      if (this.spinner) this.spinner.style.display = on ? "" : "none";
      if (this.status && this.t.verifying)
        this.status.textContent = this.t.verifying;
      disable(this.retry, on || this.retried); // lock after first click
    }

    async verifyOnce() {
      this.setBusy(true);
      try {
        const res = await fetch(this.ds.verifyUrl, {
          credentials: "same-origin",
        });
        const data = await res.json();
        if (data && data.paid) {
          window.location.assign(this.ds.successUrl);
          return;
        }
      } catch (e) {
        /* ignore */
      }
      this.setBusy(false);

      // If this was the retry and still not paid, go to courses.
      if (this.retried) {
        window.location.assign(this.ds.coursesUrl);
      }
    }
  }

  // AMD entry point (same contract as admin module).
  function init(selectors, i18n) {
    const dataset = window.ifthenpay || {};
    const app = new IfthenpayReturn({ selectors, i18n }, dataset);
    app.init();
  }

  return { init };
});

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
 * This module is responsible for ifthenpay payment account Admin Form
 *
 * Key points:
 * - LIVE dataset (window.ifthenpay.accounts/methods) drives options.
 * - Persisted ifthenpay_state is used only to preselect previous choices on load.
 * - Hidden ifthenpay_state mirrors the current UI for submission/validation.
 * - It’s OK to DISABLE selects (UX); we still submit via the hidden state.
 *
 * PHP side:
 *  $PAGE->requires->data_for_js('ifthenpay', { accounts, methods });
 *  $PAGE->requires->js_call_amd('paygw_ifthenpay/admin_gateway_form', 'init', [selectors, i18n]);
 *
 * @module     paygw_ifthenpay/admin_gateway_form
 * @copyright  2025 ifthenpay <geral@ifthenpay.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery"], function($) {
  "use strict";

  // Tiny DOM helpers.
  const Dom = {
    byName(name, tag) {
      let $el = $("#id_" + name);
      if (!$el.length || (tag && $el[0].tagName !== tag)) {
        $el = $('[name="' + name + '"]');
      }
      return $el;
    },
    checkbox(name) {
      return this.byName(name, "INPUT");
    },
    select(name) {
      return this.byName(name, "SELECT");
    },
    parseJSON(str, fallback) {
      try {
        return JSON.parse(str);
      } catch {
        return fallback;
      }
    },
  };

  class IfthenpayAdminForm {
    /**
     * @param {{selectors:Object, i18n:Object}} cfg
     * @param {{accounts:Object, methods:Object}} dataset
     */
    constructor(cfg, dataset) {
      this.s = cfg.selectors || {};
      this.t = cfg.i18n || {};
      this.ds = dataset || {};

      this.methodKeys = Object.keys(this.ds.methods || {});

      // Cache top-level elements.
      this.$gk = Dom.select(this.s.gatewayKey);
      this.$def = this.s.defaultMethod ? Dom.select(this.s.defaultMethod) : $();
      this.$desc = this.s.description ? Dom.byName(this.s.description) : $();
      this.$state = this.s.state ? Dom.byName(this.s.state) : $();

      // Per-row caches.
      this._cb = new Map(); // MethodKey -> $checkbox
      this._sel = new Map(); // MethodKey -> $select
    }

    /**
     * Initialise the module (attach events, etc).
     */
    init() {
      if (!this.$gk.length) {return;}

      // Initial render pass: build from LIVE dataset,
      // defaults were applied by PHP; we just enforce row consistency and sync.
      const gk = this.$gk.val();
      this.methodKeys.forEach((m) =>
        this.applyRowCycle(m, gk, /* initial*/ true)
      );
      this.syncAllToState();

      // Wire interactions.
      this.bindGatewayChange();
      this.bindMethodRowChanges();
      this.bindOtherChanges();
      this.bindSubmitGuard();
    }


    /**
     * Cached accessors (methods checkboxes).
     *
     * @param methodKey
     */
    $cbOf(methodKey) {
      const key = this.s.enablePrefix + methodKey;
      if (this._cb.has(key)) {return this._cb.get(key);}
      const $el = Dom.checkbox(key);
      this._cb.set(key, $el);
      return $el;
    }
    /**
     * Cached accessors (methods dropdowns/selectors).
     *
     * @param methodKey
     */
    $selOf(methodKey) {
      const key = this.s.accountPrefix + methodKey;
      if (this._sel.has(key)) {return this._sel.get(key);}
      const $el = Dom.select(key);
      this._sel.set(key, $el);
      return $el;
    }

    /**
     * Ensure a row is consistent for a given GK:
     * - Rebuild select options from LIVE dataset
     * - Keep current selection if still valid, else pick first
     * - Lock controls if no accounts or if checkbox off
     *
     * @param methodKey
     * @param gatewayKey
     * @param initial
     */
    applyRowCycle(methodKey, gatewayKey, initial = false) {
      const $cb = this.$cbOf(methodKey);
      const $sel = this.$selOf(methodKey);
      if (!$cb.length || !$sel.length) {return;}

      const optionsByMethod =
        this.ds.accounts && this.ds.accounts[gatewayKey]
          ? this.ds.accounts[gatewayKey]
          : {};
      const hasAccounts = this.rebuildSelect($sel, optionsByMethod[methodKey]);

      // If there are no accounts for this method under this GK, force off + lock.
      if (!hasAccounts) {
        $cb.prop("checked", false).prop("disabled", true);
        this.lockSelect($sel, true, /* forceEmpty*/ true);
      } else {
        // Enable checkbox; select is enabled iff checkbox is checked.
        $cb.prop("disabled", false);
        this.lockSelect($sel, !$cb.is(":checked"));
      }
    }

    /**
     * Rebuilds a <select> from a map (id => label).
     * Keeps previous value if still present; otherwise selects the first value.
     * Inserts a placeholder ('' => i18n.noaccounts) if map is empty and selects it.
     *
     * @param $sel
     * @param map
     * @returns {boolean} true if it has real accounts; false if placeholder only.
     */
    rebuildSelect($sel, map) {
      const el = $sel[0];
      const prev = $sel.val();
      el.options.length = 0;

      const keys = map ? Object.keys(map) : [];
      if (!keys.length) {
        el.add(new Option(this.t.noaccounts || "No accounts", "", true, true));
        $sel.val("");
        return false;
      }

      for (let i = 0; i < keys.length; i++) {
        const v = keys[i];
        el.add(new Option(map[v], v, false, false));
      }

      const newVal =
        prev && Object.prototype.hasOwnProperty.call(map, prev)
          ? prev
          : keys[0];
      $sel.val(newVal);
      for (let i = 0; i < el.options.length; i++) {
        el.options[i].selected = el.options[i].value === newVal;
      }
      return true;
    }

    /**
     * Toggle disabled + subtle UI class for selects.
     *
     * @param $sel
     * @param locked
     * @param forceEmpty
     */
    lockSelect($sel, locked, forceEmpty) {
      $sel.toggleClass("ifp-ui-locked", !!locked);
      if (locked) {
        $sel.prop("disabled", true);
        if (forceEmpty === true) {
          $sel.val("");
          const el = $sel[0];
          if (el && el.options && el.options.length) {
            el.options[0].selected = true;
          }
        }
      } else {
        $sel.prop("disabled", false);
      }
    }

    /**
     * State sync (hidden input mirrors UI).
     */
    readState() {
      if (!this.$state.length) {return {gatewaykey: "", methods: {}};}
      const raw = this.$state.val() || "{}";
      const st = Dom.parseJSON(raw, {}) || {};
      if (!st.methods || typeof st.methods !== "object") {st.methods = {};}
      return st;
    }

    /**
     * Write state back to hidden input.
     *
     * @param st
     */
    writeState(st) {
      if (this.$state.length) {this.$state.val(JSON.stringify(st));}
    }

    /**
     * Sync one row (payment method div) from UI to state.
     *
     * @param st
     * @param methodKey
     */
    syncRow(st, methodKey) {
      const $cb = this.$cbOf(methodKey);
      const $sel = this.$selOf(methodKey);
      st.methods[methodKey] = {
        enabled: !!($cb.length && $cb.is(":checked") && !$cb.is(":disabled")),
        account: $sel.length ? $sel.val() || "" : "",
      };
    }

    /**
     * Sync all rows + top-level fields to state.
     */
    syncAllToState() {
      const st = this.readState();
      st.gatewaykey = this.$gk.val() || "";
      if (this.$def.length) {st.defaultmethod = this.$def.val() || "";}
      if (this.$desc.length) {st.description = this.$desc.val() || "";}
      this.methodKeys.forEach((m) => this.syncRow(st, m));
      this.writeState(st);
    }

    /**
     * Bind GK selector change.
     */
    bindGatewayChange() {
      this.$gk.on("change", () => {
        const gk = this.$gk.val();
        this.methodKeys.forEach((m) => this.applyRowCycle(m, gk));
        this.syncAllToState();
      });
    }

    /**
     * Bind method row changes (checkboxes, selects).
     */
    bindMethodRowChanges() {
      this.methodKeys.forEach((m) => {
        const $cb = this.$cbOf(m);
        const $sel = this.$selOf(m);

        if ($cb.length) {
          $cb.on("change", () => {
            this.lockSelect($sel, !$cb.is(":checked"));
            this.syncAllToState();
          });
        }
        if ($sel.length) {
          $sel.on("change", () => this.syncAllToState());
        }
      });
    }

    /**
     * Bind other changes (description, default method).
     */
    bindOtherChanges() {
      if (this.$def.length) {this.$def.on("change", () => this.syncAllToState());}
      if (this.$desc.length)
        {this.$desc.on("input change", () => this.syncAllToState());}
    }

    /**
     * Bind form submit guard (sync state before submit).
     */
    bindSubmitGuard() {
      const $form = this.$gk.closest("form");
      if ($form.length) {
        $form.on("submit", () => this.syncAllToState());
      }
    }
  }

  /**
   * AMD entry point.
   *
   * @param selectors
   * @param i18n
   */
  function init(selectors, i18n) {
    const dataset = window.ifthenpay || {};
    const app = new IfthenpayAdminForm({selectors, i18n}, dataset);
    app.init();
  }

  return {init};
});

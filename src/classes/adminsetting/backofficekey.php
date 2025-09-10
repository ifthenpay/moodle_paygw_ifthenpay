<?php
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
 * Admin setting for the Ifthenpay Backoffice Key.
 *
 * @package    paygw_ifthenpay
 * @copyright  2025 ifthenpay <geral@ifthenpay.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_ifthenpay\adminsetting;

/**
 * Behaviour:
 * - Accepts empty values (does not block installation or explicit clearing).
 * - Validates format locally (####-####-####-####).
 * - Performs remote validation only when the value changes.
 */
class backofficekey extends \admin_setting_configpasswordunmask
{
    /** @var int Remote validation timeout (seconds). */
    protected $apitimeout = 5;

    /**
     * Validate data before storage.
     *
     * Rules:
     *  - Allow empty (installation / explicit clear).
     *  - Enforce local format ####-####-####-####.
     *  - If changed, attempt remote validation via api_client (constructor).
     *    Only a recognised invalid-key error blocks save; technical failures do not.
     *
     * @param mixed $data Raw value from the settings form.
     * @return bool|string True if valid, or a language string for the error.
     */
    public function validate($data) {
        // Normalise.
        $data = is_string($data) ? trim($data) : $data;

        // 0) Allow empty (do not block installation / allow explicit clear).
        if ($data === '' || $data === null) {
            return true;
        }

        // 1) Local format validation: 1234-5678-9012-3456.
        if (!is_string($data) || !preg_match('/^\d{4}(?:-\d{4}){3}$/', $data)) {
            return get_string('error_invalidformat', 'paygw_ifthenpay');
        }

        // 2) Remote validation only if the value actually changed.
        $current = (string) get_config('paygw_ifthenpay', 'backoffice_key');
        if ($current === $data) {
            return true;
        }

        // 3) Remote validation (api_client validates in its constructor).
        try {
            new \paygw_ifthenpay\local\api_client($data, $this->apitimeout);
            // If we got here, the API recognised the key.
        } catch (\moodle_exception $e) {
            if (!empty($e->errorcode) && $e->errorcode === 'error_invalid_backoffice_key') {
                return get_string('error_invalid_backoffice_key', 'paygw_ifthenpay');
            }
            // Other moodle_exception cases (transport/JSON/etc.) do NOT block save.
        } catch (\Throwable $e) {
            // Technical failures must not block saving the setting.
            debugging('[ifthenpay] Backoffice Key validation error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        return true;
    }
}

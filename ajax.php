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
 * Process ajax requests
 *
 * @package local_gugrades
 * @copyright  2026 Howard Miller
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require('../../config.php');
require_once($CFG->libdir . '/externallib.php');

// Scary bodge (thanks ChatGPT).
$_POST['sesskey'] = sesskey();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$params = $data['args'];
$courseid = $params['courseid'];
$loginrequired = $data['loginrequired'];

if ($loginrequired) {
    // Flag login errors. Note that AJAX_SCRIPT makes sure an exception
    // is caused not a (failed) redirect.
    try {
        require_login($courseid);
    } catch (require_login_exception $e) {
        http_response_code(401);
        echo json_encode([
            'error' => true,
            'errorcode' => 'notloggedin',
            'message' => 'User is not logged in'
        ]);
        exit;
    }
}

// Close the session to allow concurrent requests
// This must be done BEFORE calling external functions
session_write_close();

$result = external_api::call_external_function(
    $data['methodname'],
    $params
);

// If the external function fails, send back the $result json encoded.
if (!empty($result['error'])) {
    http_response_code(500);
    echo json_encode($result);
    exit;
}

echo json_encode($result['data'] ?? $result);
exit;

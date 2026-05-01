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

require_login();

// Scary bodge (thanks ChatGPT).
$_POST['sesskey'] = sesskey();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$params = $data['args'];

$result = external_api::call_external_function(
    $data['methodname'],
    $params
);

if (!empty($result['error'])) {
    http_response_code(500);
    echo json_encode([
        'message' => $result['exception']->message ?? 'Unknown error'
    ]);
    exit;
}

echo json_encode($result['data'] ?? $result);
exit;

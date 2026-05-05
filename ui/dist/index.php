<?php

require_once(__DIR__ . '/../../../../config.php');

$courseid = required_param('courseid', PARAM_INT);
require_login($courseid);

redirect(new moodle_url('/local/gugrades/ui/dist/index.html', ['courseid' => $courseid]));
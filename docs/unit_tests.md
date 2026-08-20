# UNIT TESTS

Unit tests are provided for testing the PHP side of the plugin. This primarily means
testing the web services exported by the plugin.

Note that the tests ONLY test the web services. There are no tests for the Vue.js UI stuff. 

PLEASE MAKE SURE ALL TESTS PASS BEFORE CHECKING IN!!

## Configuring Unit Tests

Please see document https://moodledev.io/general/development/tools/phpunit

Currently tests can be run individually, using (for example)

    vendor/bin/phpunit local/gugrades/tests/external/get_add_grade_form_test.php

...or the complete set for the plugin can be executed using

    vendor/bin/phpunit --testsuite local_gugrades_testsuite

Additional useful flags are --stop-on-error and --stop-on-failue (as the complete suite can take some time to complete)

## Docker

If running PHP in docker, you're going to have to do something like...

Exec into the Docker container for PHP-FPM:

    docker exec -it moodle45-php-1 /bin/bash

CD to the base of the Moodle install:

    cd /app/html

Run the tests:

    vendor/bin/phpunit --testsuite local_gugrades_testsuite --stop-on-error --stop-on-failure

If you need to recreate the database, the above will tell you and supply the correct command. 

Other than that, follow the Moodle instructions for configuring Unit Tests. Composer doesn't need to be run in the Docker container. 

## Caching

PHPUNIT doesn't clear caches between tests. Be careful if you have caches in the code - this can result in results changing depending on the order
in which tests happen to be run. Currently, I check if unit tests are being run and do not cache anything throughout the MyGrades code. This, of course, 
has the downside that Caching is not tested in unit tests. 

## Test configuration

Web service tests, extend the class *gugrades_base_testcase*, *gugrades_advanced_testcase* and *gugrades_advanced_testcase*. T
his creates some basic structure for tests to use. Including...

* A course
* The 22-point scale
* A teacher
* Some students
* Grade categories (confirming to MyGrades requirements)
* Assignments
* Some grades for the Assignments
* gugrades_aggregation_testcase has functionality to load schemas and data from pre-prepared json files


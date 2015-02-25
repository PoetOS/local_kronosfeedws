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
 * Kronos feed web services.
 *
 * @package    local_kronosfeedws
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2015 Remote Learner.net Inc http://www.remote-learner.net
 */

$string['pluginname'] = 'Kronos Feed web services';
$string['ws_function_requires_elis'] = 'This webservices method requires ELIS. ELIS was not found. Cannot continue.';
$string['ws_userset_create_fail_invalid_parent'] = 'Userset parent: \'{$a->parent}\' is not a valid userset.';
$string['ws_userset_create_success_code'] = 'userset_created';
$string['ws_userset_create_success_msg'] = 'Userset created successfully';
$string['ws_userset_create_fail'] = 'Could not create userset';
$string['kronosfeedws_heading'] = 'Kronos Feed web services configuration';
$string['no_field_selected'] = 'No field selected';
$string['expiry_field'] = 'Expiry date';
$string['expiry_field_desc'] = 'Select the ELIS User Set profile field that represents the User Set Expiry date.  Only User Set custom fields that are defined as datetime fields will be shown.';
$string['extension_field'] = 'Extension date';
$string['extension_field_desc'] = 'Select the ELIS User Set profile field that represents the User Set Extension date.  Only User Set custom fields that are defined as datetime fields will be shown.';
$string['solutionid_field'] = 'User Set Solution ID';
$string['solutionid_field_desc'] = 'Select the User Set profile field used to store the Solution ID.';
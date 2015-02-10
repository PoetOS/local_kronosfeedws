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

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $plugin = 'local_kronosfeedws';

    $settings = new \admin_settingpage($plugin, get_string('pluginname', $plugin));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new \admin_setting_heading('kronosfeedws_heading', '', ''));

    // Add a single drop down for selecting the Course Description custom field that denotes a featured course.
    $fields = field::get_for_context_level('cluster');
    $customfieldsdatetime = array();
    $customfieldtext = array();
    $moodlecustomfields = array();
    $solmoodlecustomfield = array();

    if ($fields->valid() === true) {
        foreach ($fields as $field) {
            if ('datetime' == $field->datatype) {
                $customfieldsdatetime[$field->id] = $field->name;
            }
        }
    }

    // If the custom fields array is empty add a 'none' value.
    if (empty($customfieldsdatetime)) {
        $customfieldsdatetime['0'] = get_string('no_field_selected', $plugin);
    }

    $title = get_string('expiry_field', $plugin);
    $desc = get_string('expiry_field_desc', $plugin);
    $setting = new \admin_setting_configselect('expiry', $title, $desc, null, $customfieldsdatetime);
    $setting->plugin = $plugin;
    $settings->add($setting);

    $title = get_string('extension_field', $plugin);
    $desc = get_string('extension_field_desc', $plugin);
    $setting = new \admin_setting_configselect('extension', $title, $desc, null, $customfieldsdatetime);
    $setting->plugin = $plugin;
    $settings->add($setting);
}

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

/**
 * This class is bassed off of the datahub web service userset_create.  This class also adds parameters to 
 * set the auto-association fields for a User Set.
 */
class local_kronosfeedws_userset_create extends external_api {
    /**
     * Require ELIS dependencies if ELIS is installed, otherwise return false.
     * @return bool Whether ELIS dependencies were successfully required.
     */
    public static function require_elis_dependencies() {
        global $CFG;
        if (file_exists($CFG->dirroot.'/local/elisprogram/lib/setup.php')) {
            require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
            require_once(elispm::lib('data/userset.class.php'));
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets userset custom fields
     * @return array An array of custom userset fields
     */
    public static function get_userset_custom_fields() {
        global $DB;

        if (static::require_elis_dependencies() === true) {
            // Get custom fields.
            $sql = 'SELECT f.id, shortname, name, datatype, multivalued
                      FROM {'.field::TABLE.'} f
                      JOIN {'.field_contextlevel::TABLE.'} fctx ON f.id = fctx.fieldid AND fctx.contextlevel = ?';
            $sqlparams = array(CONTEXT_ELIS_USERSET);
            return $DB->get_records_sql($sql, $sqlparams);
        } else {
            return array();
        }
    }

    /**
     * Gets a description of the userset input object for use in the parameter and return functions.
     * @return array An array of external_value objects describing a user record in webservice terms.
     */
    public static function get_userset_input_object_description() {
        global $DB;
        $params = array(
            'name' => new external_value(PARAM_TEXT, 'Userset name', VALUE_REQUIRED),
            'display' => new external_value(PARAM_TEXT, 'Userset description', VALUE_OPTIONAL),
            'parent' => new external_value(PARAM_TEXT, 'Userset parent name', VALUE_OPTIONAL),
            'expiry' => new external_value(PARAM_TEXT, 'Customer User expiry date in the following format: YYYY-MM-DD hh:mm:ss', VALUE_OPTIONAL),
            'autoassociate1' => new external_value(PARAM_TEXT, 'First auto-association Moodle field shortname', VALUE_OPTIONAL),
            'autoassociate1_value' => new external_value(PARAM_TEXT, 'First auto-association Moodle field value', VALUE_OPTIONAL),
            'autoassociate2' => new external_value(PARAM_TEXT, 'Second auto-association Moodle field shortname', VALUE_OPTIONAL),
            'autoassociate2_value' => new external_value(PARAM_TEXT, 'Second auto-association Moodle field value', VALUE_OPTIONAL)
        );

        $fields = self::get_userset_custom_fields();
        foreach ($fields as $field) {
            // Generate name using custom field prefix.
            $fullfieldname = data_object_with_custom_fields::CUSTOM_FIELD_PREFIX.$field->shortname;

            if ($field->multivalued) {
                $paramtype = PARAM_TEXT;
            } else {
                // Convert datatype to param type.
                switch($field->datatype) {
                    case 'bool':
                        $paramtype = PARAM_BOOL;
                        break;
                    case 'int':
                        $paramtype = PARAM_INT;
                        break;
                    default:
                        $paramtype = PARAM_TEXT;
                }
            }

            // Assemble the parameter entry and add to array.
            $params[$fullfieldname] = new external_value($paramtype, $field->name, VALUE_OPTIONAL);
        }

        return $params;
    }

    /**
     * Gets a description of the userset output object for use in the parameter and return functions.
     * @return array An array of external_value objects describing a user record in webservice terms.
     */
    public static function get_userset_output_object_description() {
        global $DB;
        $params = array(
            'id' => new external_value(PARAM_INT, 'Userset DB id', VALUE_REQUIRED),
            'name' => new external_value(PARAM_TEXT, 'Userset name', VALUE_REQUIRED),
            'display' => new external_value(PARAM_TEXT, 'Userset description', VALUE_OPTIONAL),
            'parent' => new external_value(PARAM_INT, 'Userset parent DB id', VALUE_OPTIONAL),
            'expiry' => new external_value(PARAM_TEXT, 'Customer User expiry date expressed as a Unix timestamp', VALUE_OPTIONAL),
            'autoassociate1' => new external_value(PARAM_TEXT, 'First auto-association Moodle field shortname', VALUE_OPTIONAL),
            'autoassociate1_value' => new external_value(PARAM_TEXT, 'First auto-association Moodle field value', VALUE_OPTIONAL),
            'autoassociate2' => new external_value(PARAM_TEXT, 'Second auto-association Moodle field shortname', VALUE_OPTIONAL),
            'autoassociate2_value' => new external_value(PARAM_TEXT, 'Second auto-association Moodle field value', VALUE_OPTIONAL)
        );

        $fields = self::get_userset_custom_fields();
        foreach ($fields as $field) {
            // Generate name using custom field prefix.
            $fullfieldname = data_object_with_custom_fields::CUSTOM_FIELD_PREFIX.$field->shortname;

            if ($field->multivalued) {
                $paramtype = PARAM_TEXT;
            } else {
                // Convert datatype to param type.
                switch($field->datatype) {
                    case 'bool':
                        $paramtype = PARAM_BOOL;
                        break;
                    case 'int':
                        $paramtype = PARAM_INT;
                        break;
                    default:
                        $paramtype = PARAM_TEXT;
                }
            }

            // Assemble the parameter entry and add to array.
            $params[$fullfieldname] = new external_value($paramtype, $field->name, VALUE_OPTIONAL);
        }

        return $params;
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters The parameters object for this webservice method.
     */
    public static function userset_create_parameters() {
        $params = array('data' => new external_single_structure(static::get_userset_input_object_description()));
        return new external_function_parameters($params);
    }

    /**
     * Performs userset creation
     * @throws moodle_exception If there was an error in passed parameters.
     * @throws data_object_exception If there was an error creating the entity.
     * @param array $data The incoming data parameter.
     * @return array An array of parameters, if successful.
     */
    public static function userset_create(array $data) {
        global $USER, $DB;

        if (static::require_elis_dependencies() !== true) {
            throw new moodle_exception('ws_function_requires_elis', 'local_kronosfeedws');
        }

        // Parameter validation.
        $params = self::validate_parameters(self::userset_create_parameters(), array('data' => $data));
        $params = $params['data'];

        // Additional validation of the auto-association field names and their values.
        if (isset($params['autoassociate1']) && '' != $params['autoassociate1'] && isset($params['autoassociate1_value'])) {
            $result = self::validate_autoassociate_field($params['autoassociate1'], $params['autoassociate1_value']);

            if (isset($result['messagecode'])) {
                return $result;
            } else {
                $params['autoassociate1'] = $result[0];
                $params['autoassociate1_value'] = $result[1];
            }
        } else {
            $params['autoassociate1'] = 0;
            $params['autoassociate1_value'] = 0;
        }

        // Additional validation of the auto-association field names and their values.
        if (isset($params['autoassociate2']) && '' != $params['autoassociate2'] && isset($params['autoassociate2_value'])) {
            $result = self::validate_autoassociate_field($params['autoassociate2'], $params['autoassociate2_value']);

            if (isset($result['messagecode'])) {
                return $result;
            } else {
                $params['autoassociate2'] = $result[0];
                $params['autoassociate2_value'] = $result[1];
            }
        } else {
            $params['autoassociate2'] = 0;
            $params['autoassociate2_value'] = 0;
        }

        // Validate the expiry date format and field.
        $params['expiry'] = empty($params['expiry']) ? '2000-01-01 00:00:00' : $params['expiry'];
        $expirydate = self::validate_expiry_date($params['expiry']);

        if (empty($expirydate[0])) {
            return $expirydate[1];
        }

        // Add expiry date to parameters array.
        $params['field_'.$expirydate[0]] = $expirydate[1];

        // Context validation.
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        // Capability checking.
        require_capability('local/elisprogram:userset_create', context_system::instance());

        $data = (object)$params;
        $record = new stdClass;
        $record = $data;

        // Validate.
        $usid = 0;
        if (!empty($data->parent) && strtolower($data->parent) != 'top' && !($usid = $DB->get_field(userset::TABLE, 'id',
                array('name' => $data->parent)))) {
            throw new data_object_exception('ws_userset_create_fail_invalid_parent', 'local_kronosfeedws', '', $data);
        }
        $record->parent = $usid;

        if (empty($record->display)) {
            $record->display = '';
        }

        $userset = new userset();
        $userset->set_from_data($record);
        $userset->save();

        // Save auto-associate field values.
        self::set_auto_associate_field($userset->id, $data->autoassociate1, $data->autoassociate1_value, $data->autoassociate2, $data->autoassociate2_value);

        // Respond.
        if (!empty($userset->id)) {
            $usrec = (array)$DB->get_record(userset::TABLE, array('id' => $userset->id));
            $usrec['expiry'] = $expirydate[1];
            $usobj = $userset->to_array();
            // Convert multi-valued custom field arrays to comma-separated listing.
            $fields = self::get_userset_custom_fields();
            foreach ($fields as $field) {
                // Generate name using custom field prefix.
                $fullfieldname = data_object_with_custom_fields::CUSTOM_FIELD_PREFIX.$field->shortname;

                if ($field->multivalued && isset($usobj[$fullfieldname]) && is_array($usobj[$fullfieldname])) {
                    $usobj[$fullfieldname] = implode(',', $usobj[$fullfieldname]);
                }
            }
            return array(
                'messagecode' => 1,
                'message' => 'Userset created successfully',
                'record' => array_merge($usrec, $usobj)
            );
        } else {
            throw new data_object_exception('ws_userset_create_fail', 'local_kronosfeedws');
        }
    }

    /**
     * Returns description of method result value
     * @return external_single_structure Object describing return parameters for this webservice method.
     */
    public static function userset_create_returns() {
        return new external_single_structure(
                array(
                    'messagecode' => new external_value(PARAM_INT, 'Response Code'),
                    'message' => new external_value(PARAM_TEXT, 'Response'),
                    'record' => new external_single_structure(static::get_userset_output_object_description())
                )
        );
    }

    /**
     * Return true if the parameter matches the shortname of a Moodle custom profile field.
     * @param string $name A Moodle profile field shortname.
     * @return object|bool The profile field table record or false if not found.
     */
    public static function name_equals_moodle_field_shortname($name) {
        global $DB;

        $record = $DB->get_record('user_info_field', array('shortname' => $name));

        return empty($record) ? false : $record;
    }

    /**
     * Return true if the parameter matches the shortname of a Moodle custom profile field.
     * @param int $fieldid A Moodle profile field id
     * @return bool True if the field is a valid field type.  Otherwise false.
     */
    public static function autoassociation_field_is_valid_type($fieldid) {
        global $DB;
        $select = 'id = ? AND (datatype = ? OR datatype = ? OR datatype = ?)';
        return $DB->record_exists_select('user_info_field', $select, array($fieldid, 'text', 'checkbox', 'menu'));
    }

    /**
     * Returns true if the value being passed is a valid value.  This is only applicable to menu of choice fields.
     * @param object $field A mdl_user_info_field record object
     * @param string $value The value to validate.
     * @return bool True if the value is a valid type or False.
     */
    public static function autoassociation_menu_field_value_is_valid($field, $value) {
        if (empty($value) || 'menu' != $field->datatype) {
            return false;
        }

        $choices = explode("\n", $field->param1);
        $valid = false;

        foreach ($choices as $choice) {
            if (trim($value) === $choice) {
                $valid = true;
                break;
            }
        }

        return $valid;
    }

    /**
     * This function calls additional validation methods
     * @param string $autoassociatefield The auto-associate field shortname
     * @param string $autoassociatefieldvalue The auto-associate field value
     * @return array Returns an array with keys 'messagecode', 'message' and 'record' if there was a validation error.  Otherwise an empty array is returned.
     */
    public static function validate_autoassociate_field($autoassociatefield, $autoassociatefieldvalue) {
        $field = self::name_equals_moodle_field_shortname($autoassociatefield);
        $fieldvalue = $autoassociatefieldvalue;

        if (false === $field) {
            return array(
                'messagecode' => -1,
                'message' => 'Auto-associate field shortname does not exist.',
                'record' => array(
                    'id' => 0,
                    'name' => 'NULL')
            );
        }

        if (false === self::autoassociation_field_is_valid_type($field->id)) {
            return array(
                'messagecode' => -2,
                'message' => 'Auto-associate field is not a valid type.  Valid types are "text", "menu" and "checkbox".',
                'record' => array(
                    'id' => 0,
                    'name' => 'NULL')
            );
        }

        if (('menu' == $field->datatype || 'text' == $field->datatype) && empty($fieldvalue)) {
            return array(
                'messagecode' => -3,
                'message' => 'Auto-associate value field cannot be empty.',
                'record' => array(
                    'id' => 0,
                    'name' => 'NULL')
            );
        }

        if ('menu' == $field->datatype) {
            if (!self::autoassociation_menu_field_value_is_valid($field, $fieldvalue)) {
                return array(
                    'messagecode' => -5,
                    'message' => 'Auto-associate value is not a valid option for a menu field.',
                    'record' => array(
                        'id' => 0,
                        'name' => 'NULL')
                );
            }
        }

        // For checkbox fields, set the value to either a 1 or a 0.
        if ('checkbox' == $field->datatype) {
            $fieldvalue = empty($fieldvalue) ? 0 : 1;
        }

        return array($field->id, $fieldvalue);
    }

    /**
     * This code is refactored from @see userset_moodleprofile_update(). This function updates the ELIS user Set auto association record.
     * @param int $usersetid The User Set id.
     * @param int $firstaafieldid The Moodle profile field id for the first auto associate field.
     * @param string $firstaafieldvalue The Moodle profile field value for the first auto associate field.
     * @param int $secondaafieldid The Moodle profile field id for the first auto associate field.
     * @param string $secondaafieldvalue The Moodle profile field value for the first auto associate field.
     */
    public static function set_auto_associate_field($usersetid, $firstaafieldid = 0, $firstaafieldvalue = '', $secondaafieldid = 0, $secondaafieldvalue = '') {
        global $DB;

        // Get the "new" profile field assignment values.
        $new = array();

        if (!empty($firstaafieldid)) {
            $new[$firstaafieldid] = $firstaafieldvalue;
        }

        if (!empty($secondaafieldid)) {
            $new[$secondaafieldid] = $secondaafieldvalue;
        }

        // Get the "old" (existing) profile field assignment values.
        $old = userset_profile::find(new field_filter('clusterid', $usersetid), array(), 0, 2)->to_array();

        $updated = false;

        // Compare old values against new values.
        foreach ($old as $field) {
            if (!isset($new[$field->id])) {
                // Old field is no longer a field.
                $field->delete();
                unset($old[$field->id]);
                $updated = true;
            } else if ($new[$field->id] != $field->value) {
                // Value has changed.
                $field->value = $new[$field->id];
                $field->save();
                $updated = true;
            }
        }

        // Check for added fields.
        $added = array_diff_key($new, $old);
        foreach ($added as $fieldid => $value) {
            $record = new userset_profile();
            $record->clusterid = $usersetid;
            $record->fieldid = $fieldid;
            $record->value = $value;
            $record->save();
            $updated = true;
        }

        if ($updated) {
            // Re-assign users: remove previous cluster assignments.
            clusterassignment::delete_records(array(new field_filter('clusterid', $usersetid), new field_filter('plugin', 'moodleprofile')));

            // Create new cluster assignments.
            $join  = '';
            $joinparams = array();
            $whereclauses = array();
            $whereparams = array();
            $i = 1;

            foreach ($new as $fieldid => $value) {
                // Check if the desired field value is equal to the field's default value if so, we need to include users that don't have an associated entry in user_info_data.
                $defaultvalue = $DB->get_field('user_info_field', 'defaultdata', array('id' => $fieldid));
                $isdefault    = ($value == $defaultvalue);

                $join .= ($isdefault ? ' LEFT' : ' INNER')." JOIN {user_info_data} inf{$i} ON mu.id = inf{$i}.userid AND inf{$i}.fieldid = ?";
                $joinparams[] = $fieldid;
                $where = "(inf{$i}.data = ?";

                // If desired field is the default.
                if ($isdefault) {
                    $where .= " OR inf{$i}.userid IS NULL";
                }
                $where .= ')';
                $whereclauses[] = $where;
                $whereparams[] = $value;
                $i++;
            }

            // Use the clauses to construct a where condition.
            $whereclause = implode(' AND ', $whereclauses);

            if (!empty($join) && !empty($where)) {
                $sql = "INSERT INTO {".clusterassignment::TABLE."} (clusterid, userid, plugin)
                             SELECT ?, cu.id, 'moodleprofile'
                               FROM {" . user::TABLE . "} cu
                         INNER JOIN {user} mu ON mu.idnumber = cu.idnumber
                                    $join
                              WHERE $whereclause";
                $params = array_merge(array($usersetid), $joinparams, $whereparams);

                $DB->execute($sql, $params);
            }

            clusterassignment::update_enrolments(0, $usersetid);
        }
    }

    /**
     * This function validates whether expiry date, passed as a parameter, is in an ISO format.
     * @param $string $xpirydate The expiry date expressed in an ISO format.
     * @return array An array whose first value is the field short name, second value is the converted expiry date and third value is an empty array.
     * Or whose first value is false and second value is an array with an error code, if an error occured.
     */
    public static function validate_expiry_date($expirydate = '2000-01-01 00:00:00') {
        global $DB;

        // Check if the expiry date configuration setting for the plug-in has been set.
        $expiryfieldid = get_config('local_kronosfeedws', 'expiry');

        // Check if the configured field exists in the ELIS profile fields table, has a data type of datetime and belongs to the User Set context.
        $sql = 'SELECT f.id, shortname, name, datatype
                  FROM {'.field::TABLE.'} f
                  JOIN {'.field_contextlevel::TABLE.'} fctx ON f.id = fctx.fieldid AND fctx.contextlevel = ?
                 WHERE f.id = ?
                       AND f.datatype = "datetime"';
        $sqlparams = array(CONTEXT_ELIS_USERSET, $expiryfieldid);
        $field = $DB->get_record_sql($sql, $sqlparams);

        if (empty($field)) {
            return array(false, array(
                'messagecode' => -6,
                'message' => 'Expiry date field does not exist as a field in the User Set context.',
                'record' => array(
                    'id' => 0,
                    'name' => 'NULL')
                )
            );
        }

        $datetime = date_create_from_format('Y-m-d H:i:s', $expirydate);

        if (is_object($datetime)) {
            $timestamp = make_timestamp($datetime->format('Y'), $datetime->format('m'), $datetime->format('d'),
                    $datetime->format('H'), $datetime->format('i'), $datetime->format('s'));

            return array($field->shortname, $timestamp, array());
        }

        return array(false, array(
            'messagecode' => -7,
            'message' => 'Expiry date is an invalid format.  Expiry date format must be YYYY-MM-DD hh:mm:ss',
            'record' => array(
                    'id' => 0,
                    'name' => 'NULL')
            )
        );
    }
}
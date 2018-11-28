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

defined('MOODLE_INTERNAL') || die();

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
class local_kronosfeedws_userset extends external_api {
    /** The default date in ISO format. */
    const DEFAULTDATE = '2000-01-01 00:00:00';

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
            'solutionid' => new external_value(PARAM_TEXT, 'The User Set solution ID', VALUE_OPTIONAL),
            'display' => new external_value(PARAM_TEXT, 'Userset description', VALUE_OPTIONAL),
            'parent' => new external_value(PARAM_TEXT, 'Userset parent name', VALUE_OPTIONAL),
            'expiry' => new external_value(PARAM_TEXT, 'Customer User expiry date in the following format: YYYY-MM-DD hh:mm:ss',
                VALUE_OPTIONAL),
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

        $autoassociateone = null;
        $autoassociateonevalue = '';
        $autoassociatetwo = null;
        $autoassociatetwovalue = '';

        // If parameter was included and is a non empty value then validate the data and set the auto association field.
        if (isset($params['autoassociate1']) && '' != $params['autoassociate1'] && isset($params['autoassociate1_value'])) {
            $result = self::validate_autoassociate_field($params['autoassociate1'], $params['autoassociate1_value']);

            if (isset($result['messagecode'])) {
                return $result;
            } else {
                $autoassociateone = $result[0];
                $autoassociateonevalue = $result[1];
            }
        }

        // If parameter was included and is a non empty value then validate the data and set the auto association field.
        if (isset($params['autoassociate2']) && '' != $params['autoassociate2'] && isset($params['autoassociate2_value'])) {
            $result = self::validate_autoassociate_field($params['autoassociate2'], $params['autoassociate2_value']);

            if (isset($result['messagecode'])) {
                return $result;
            } else {
                $autoassociatetwo = $result[0];
                $autoassociatetwovalue = $result[1];
            }
        }

        // Validate the expiry date format and field.
        $params['expiry'] = empty($params['expiry']) ? self::DEFAULTDATE : $params['expiry'];
        $expirydate = self::validate_expiry_date($params['expiry']);

        if (empty($expirydate[0])) {
            return $expirydate[1];
        }

        // Add expiry date to parameters array.
        $params['field_'.$expirydate[0]] = $expirydate[1];

        // If the Solution Id parameter is passed and another User Set with the same Solution Id exists, then return an error.
        // If the Solution Id parameter is passed and no other User Set exists with the same Solution Id, then add the Solution Id
        // value to be created with the User Set.
        // Otherwise continue as usual.
        if (isset($params['solutionid']) && self::userset_second_level_solutionid_exists($params['solutionid'])) {
            // Return an error because we should not create a new User Set when another User Set exists with the same Solutoin Id.
            return self::create_error_structure(-10, 'Solution Id already exists.  Unable to create a new User Set with a ' .
                'matching Solution Id.');
        } else if (isset($params['solutionid'])) {
            // Retireve the solution id shortname.
            $usetsolutionfieldid = get_config('local_kronosfeedws', 'solutionid');
            $solutionidshortname = self::retireve_field_shortname($usetsolutionfieldid);
            // Set the Solution Id.
            $params['field_'.$solutionidshortname] = $params['solutionid'];
        }

        // Context validation.
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        // Capability checking.
        require_capability('local/elisprogram:userset_create', context_system::instance());

        $data = (object)$params;
        $data->autoassociate1 = $autoassociateone;
        $data->autoassociate1_value = $autoassociateonevalue;
        $data->autoassociate2 = $autoassociatetwo;
        $data->autoassociate2_value = $autoassociatetwovalue;

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

        // Save auto-associate field values.
        self::set_auto_associate_field($userset->id, $data->autoassociate1, $data->autoassociate1_value, $data->autoassociate2,
            $data->autoassociate2_value);

        $userset->save();

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
     * @return array Returns an array with keys 'messagecode', 'message' and 'record' if there was a validation error.
     * Otherwise an empty array is returned.
     */
    public static function validate_autoassociate_field($autoassociatefield, $autoassociatefieldvalue) {
        $field = self::name_equals_moodle_field_shortname($autoassociatefield);
        $fieldvalue = $autoassociatefieldvalue;

        if (false === $field) {
            return self::create_error_structure(-1, 'Auto-associate field shortname does not exist.');
        }

        if (false === self::autoassociation_field_is_valid_type($field->id)) {
            return self::create_error_structure(-2, 'Auto-associate field is not a valid type.  Valid types are "text", "menu" ' .
                'and "checkbox".');
        }

        if (('menu' == $field->datatype || 'text' == $field->datatype) && empty($fieldvalue)) {
            return self::create_error_structure(-3, 'Auto-associate value field cannot be empty.');
        }

        if ('menu' == $field->datatype) {
            if (!self::autoassociation_menu_field_value_is_valid($field, $fieldvalue)) {
                return self::create_error_structure(-5, 'Auto-associate value is not a valid option for a menu field.');
            }
        }

        // For checkbox fields, set the value to either a 1 or a 0.
        if ('checkbox' == $field->datatype) {
            $fieldvalue = empty($fieldvalue) ? 0 : 1;
        }

        return array($field->id, $fieldvalue);
    }

    /**
     * This function sets a key in the POST global so that @see userset_moodleprofile_update() doesn't overwrite the User Set
     * autoassociate values when a User Set is updated.  This was done to avoid having to customize ELIS's User Set, and
     * User Set moodle profile enrolment plug-in.
     * @param int $usersetid The User Set id.
     * @param int $firstaafieldid The Moodle profile field id for the first auto associate field.
     * @param string $firstaafieldvalue The Moodle profile field value for the first auto associate field.
     * @param int $secondaafieldid The Moodle profile field id for the first auto associate field.
     * @param string $secondaafieldvalue The Moodle profile field value for the first auto associate field.
     */
    public static function set_auto_associate_field($usersetid, $firstaafieldid = 0, $firstaafieldvalue = '', $secondaafieldid = 0,
                                                    $secondaafieldvalue = '') {
        global $DB;

        // Get the "old" (existing) profile field assignment values.
        $old = userset_profile::find(new field_filter('clusterid', $usersetid), array(), 0, 2)->to_array();

        if (empty($old)) {
            if (!empty($firstaafieldid)) {
                $_POST["profile_field1"] = $firstaafieldid;
                $_POST["profile_value1"] = $firstaafieldvalue;
            }

            if (!empty($secondaafieldid)) {
                $_POST["profile_field2"] = $secondaafieldid;
                $_POST["profile_value2"] = $secondaafieldvalue;
            }
        }

        $i = 1;
        foreach ($old as $field) {
            if (1 == $i) {
                $tempfieldid = $firstaafieldid;
                $tempfieldvalue = $firstaafieldvalue;
            } else if (2 == $i) {
                $tempfieldid = $secondaafieldid;
                $tempfieldvalue = $secondaafieldvalue;
            }

            // If the auto-associate field is empty OR auto-associate field is the same field and value as the current field
            // Initialize the post global key to the old value.  This will prevent ELIS from deleting the old field.
            if (empty($tempfieldid) || ($tempfieldid == $field->id && $tempfieldvalue == $field->value)) {
                $_POST["profile_field{$i}"] = $field->id;
                $_POST["profile_value{$i}"] = $field->value;
            } else {
                // If the auto-associate field is not empty or if the field id or value is different, then initialize the
                // post global to the new values.
                $_POST["profile_field{$i}"] = $tempfieldid;
                $_POST["profile_value{$i}"] = $tempfieldvalue;
            }

            $i++;
        }
    }

    /**
     * This function validates whether expiry date, passed as a parameter, is in an ISO format.
     * @param $string $xpirydate The expiry date expressed in an ISO format.
     * @return array An array whose first value is the field short name, second value is the converted expiry date and third value
     * is an empty array.
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
            return array(false, self::create_error_structure(-6, 'Expiry date field does not exist as a field in the User Set ' .
                'context'));
        }

        $datetime = date_create_from_format('Y-m-d H:i:s', $expirydate);

        if (is_object($datetime)) {
            $timestamp = make_timestamp($datetime->format('Y'), $datetime->format('m'), $datetime->format('d'),
                    $datetime->format('H'), $datetime->format('i'), $datetime->format('s'));

            return array($field->shortname, $timestamp, array());
        }

        return array(false, self::create_error_structure(-7, 'Expiry date is an invalid format.  ' .
            'Expiry date format must be YYYY-MM-DD hh:mm:ss'));
    }

    /**
     * Gets a description of the userset input object for use in the parameter and return functions.
     * @return array An array of external_value objects describing a user record in webservice terms.
     */
    public static function get_userset_update_input_object_description() {
        global $DB;
        $params = array(
            'solutionid' => new external_value(PARAM_TEXT, 'The User Set local_kronosfeedws_userset ID', VALUE_REQUIRED),
            'name' => new external_value(PARAM_TEXT, 'Userset name', VALUE_OPTIONAL),
            'display' => new external_value(PARAM_TEXT, 'Userset description', VALUE_OPTIONAL),
            'parent' => new external_value(PARAM_TEXT, 'Userset parent name. If the parent field is left empty, the User Set ' .
                'created will be a top level User Set', VALUE_OPTIONAL),
            'expiry' => new external_value(PARAM_TEXT, 'Customer User expiry date in the following format: YYYY-MM-DD hh:mm:ss',
                VALUE_OPTIONAL),
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
    public static function userset_update_parameters() {
        $params = array('data' => new external_single_structure(static::get_userset_update_input_object_description()));
        return new external_function_parameters($params);
    }

    /**
     * Returns description of method result value
     * @return external_single_structure Object describing return parameters for this webservice method.
     */
    public static function userset_update_returns() {
        return new external_single_structure(
                array(
                    'messagecode' => new external_value(PARAM_INT, 'Response Code'),
                    'message' => new external_value(PARAM_TEXT, 'Response'),
                    'record' => new external_single_structure(static::get_userset_output_object_description())
                )
        );
    }

    /**
     * This function searches for a User Set with a matching Solution ID.  The User Set Solution ID needs to be defined as
     * a custom field in the User Set conext.
     * @param string $usersetsolutionid The User Set Solution ID.
     * @return mixed An object ('id' -> Context id, 'name' -> User Set name).  Otherwise false.
     */
    public static function userset_second_level_solutionid_exists($usersetsolutionid) {
        global $DB;

        // Clean the parameter.
        $cleansolutionid = clean_param(trim($usersetsolutionid), PARAM_ALPHANUMEXT);
        // Check if the expiry date configuration setting for the plug-in has been set.
        $usetsolutionfieldid = get_config('local_kronosfeedws', 'solutionid');

        $sql = "SELECT ctx.id, uset.name, uset.id AS usersetid, uset.parent
                  FROM {local_elisprogram_uset} uset
                  JOIN {local_eliscore_field_clevels} fldctx on fldctx.fieldid = ?
                  JOIN {context} ctx ON ctx.instanceid = uset.id AND ctx.contextlevel = fldctx.contextlevel
                  JOIN {local_eliscore_fld_data_char} fldchar ON fldchar.contextid = ctx.id AND fldchar.fieldid = ?
                 WHERE uset.depth = 2
                       AND fldchar.data = ?";

        $usersetcontextandname = $DB->get_record_sql($sql, array($usetsolutionfieldid, $usetsolutionfieldid, $cleansolutionid));

        if (empty($usersetcontextandname)) {
            return false;
        }

        return $usersetcontextandname;
    }

    /**
     * Performs userset update.  This code is refactored code from local/datahub/ws/elis/userset_update.class.php
     * @see userset_update().
     * User Sets will be updated based on a matching User Set profile field value.
     * Kronos specific business logic will also bee applied for for changing a User set expiry date.  The logic works as follows.
     * 1. If the expiry date parameter is greater than the User Set eexpiry date and extension date, then set the User Set expiry
     * date to the prameter
     *    AND set the extension date to Janurary 1, 2000.
     * 2. If the expiry date parameter is greater than the User Set expiry date BUT less than the User Set extension date,
     * do not update the User Set expiry date.
     * 3. If the expiry date parameter is less than the User Set expiry date, do not update the User Set expiry date.
     * @throws moodle_exception If there was an error in passed parameters.
     * @throws data_object_exception If there was an error creating the entity.
     * @param array $data The incoming data parameter.
     * @return array An array of parameters, if successful.
     */
    public static function userset_update(array $data) {
        global $USER, $DB;

        if (static::require_elis_dependencies() !== true) {
            throw new moodle_exception('ws_function_requires_elis', 'local_kronosfeedws');
        }

        // Parameter validation.
        $params = self::validate_parameters(self::userset_update_parameters(), array('data' => $data));
        $params = $params['data'];

        $autoassociateone = null;
        $autoassociateonevalue = '';
        $autoassociatetwo = null;
        $autoassociatetwovalue = '';

        // If parameter was included and is a non empty value then validate the data and set the auto association field.
        if (isset($params['autoassociate1']) && '' != $params['autoassociate1'] && isset($params['autoassociate1_value'])) {
            $result = self::validate_autoassociate_field($params['autoassociate1'], $params['autoassociate1_value']);

            if (isset($result['messagecode'])) {
                return $result;
            } else {
                $autoassociateone = $result[0];
                $autoassociateonevalue = $result[1];
            }
        }

        // If parameter was included and is a non empty value then validate the data and set the auto association field.
        if (isset($params['autoassociate2']) && '' != $params['autoassociate2'] && isset($params['autoassociate2_value'])) {
            $result = self::validate_autoassociate_field($params['autoassociate2'], $params['autoassociate2_value']);

            if (isset($result['messagecode'])) {
                return $result;
            } else {
                $autoassociatetwo = $result[0];
                $autoassociatetwovalue = $result[1];
            }
        }

        // Validate the expiry date format and field.
        $params['expiry'] = empty($params['expiry']) ? self::DEFAULTDATE : $params['expiry'];
        $expirydate = self::validate_expiry_date($params['expiry']);

        if (empty($expirydate[0])) {
            return $expirydate[1];
        }

        // Add expiry date to parameters array.
        $params['field_'.$expirydate[0]] = $expirydate[1];

        // Context validation.
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        $data = (object) $params;
        $data->autoassociate1 = $autoassociateone;
        $data->autoassociate1_value = $autoassociateonevalue;
        $data->autoassociate2 = $autoassociatetwo;
        $data->autoassociate2_value = $autoassociatetwovalue;

        // Need all custom fields, etc.
        $record = $data;

        // Retrieve a User Set via a the solution id.
        $usersetobj = self::userset_second_level_solutionid_exists($data->solutionid);

        if (empty($usersetobj)) {
            return self::create_error_structure(-8, 'Unable to find a User Set with solution ID of '.$data->solutionid);
        }
        // Capability checking.
        require_capability('local/elisprogram:userset_edit', \local_elisprogram\context\userset::instance($usersetobj->usersetid));

        $usid = 0;

        if (!empty($data->parent) && strtolower($data->parent) != 'top' && !($usid = $DB->get_field(userset::TABLE, 'id',
                array('name' => $data->parent)))) {
            return self::create_error_structure(-9, 'Parent User Set name of '.$data->parent.' does not exist');
        }

        if (isset($data->parent) && ($usid || strtolower($data->parent) == 'top')) {
            $record->parent = $usid;
        }

        // Initialize the User Set object, setting the properties from the data object.
        $userset = new userset($usersetobj->usersetid);
        // Resolves KRONOSDEV-67, otherwise the depth will get set to 1 and will prevent the Solution ID search method from finding
        // a the User Set.
        $userset->parent;
        $userset->depth;

        $userset->set_from_data($record);

        // Apply the Kronos business logic for setting the expiry date.
        $userset = self::userset_set_new_expiry_date($userset, $expirydate[0], $expirydate[1]);

        // Save auto-associate field values.
        self::set_auto_associate_field($userset->id, $data->autoassociate1, $data->autoassociate1_value, $data->autoassociate2,
            $data->autoassociate2_value);

        $userset->save();

        // Respond.
        if (!empty($userset->id)) {
            $usrec = (array) $DB->get_record(userset::TABLE, array('id' => $userset->id));
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
                'message' => 'Userset updated successfully',
                'record' => array_merge($usrec, $usobj)
            );
        } else {
            throw new data_object_exception('ws_userset_update_fail', 'local_kronosfeedws');
        }
    }

    /**
     * This function returns field shortname.  Or returns false if the field was not found within the User Set context.
     * @return string The field shortname. Or false if the field does not exist in the User Set context.
     */
    public static function get_date_extension_field() {
        global $DB;

        // Check if the expiry date configuration setting for the plug-in has been set.
        $extensionfieldid = get_config('local_kronosfeedws', 'extension');

        // Check if the configured field exists in the ELIS profile fields table, has a data type of datetime and belongs to the
        // User Set context.
        $sql = 'SELECT f.shortname
                  FROM {'.field::TABLE.'} f
                  JOIN {'.field_contextlevel::TABLE.'} fctx ON f.id = fctx.fieldid AND fctx.contextlevel = ?
                 WHERE f.id = ?
                       AND f.datatype = "datetime"';
        $sqlparams = array(CONTEXT_ELIS_USERSET, $extensionfieldid);
        $field = $DB->get_record_sql($sql, $sqlparams);

        if (empty($field)) {
            return false;
        }

        return $field->shortname;
    }

    /**
     * Kronos specific business logic will also bee applied for for changing a User set expiry date.  The logic works as follows.
     * If the expiry date parameter is greater than the User Set eexpiry date and extension date, then set the User Set expiry date
     * to the prameter
     *    AND set the extension date to Janurary 1, 2000.
     * else do not update any of the User Set expiry or extension fields.
     * @param object $userset A user set object.
     * @param string $expiryfield the expiry field shortname
     * @param int $expiryvalue A Unix timestamp representing the new expiry date.
     * @return object the userset object.
     */
    public static function userset_set_new_expiry_date($userset, $expiryfield, $expiryvalue) {
        // Get the extendion date field name.
        $extensionfieldname = self::get_date_extension_field();
        // Retrieve the User Set and it's original data.  This will be used to determine if the expiry date needs to be updated.
        $olduserset = new userset($userset->id);
        $olduserset->load();

        $expfield = 'field_'.$expiryfield;

        // If the extension date field exists, then compare the expiry date against the User Set expiry and extension date values.
        if (!empty($extensionfieldname)) {
            $extfield = 'field_'.$extensionfieldname;

            // If the expiry date is greater than the User Set expiry and the User Set extension, set the User Set
            // expiry to the new value and set the extension date to the default value.
            if ($expiryvalue > $olduserset->$expfield && $expiryvalue > $olduserset->$extfield) {
                $userset->$expfield = $expiryvalue;
                $userset->$extfield = strtotime(self::DEFAULTDATE);
            } else {
                $userset->$expfield = $olduserset->$expfield;
                $userset->$extfield = $olduserset->$extfield;
            }
        } else {
            // Compare the expiry date against the User Set expiry date only.
            if ($expiryvalue > $olduserset->$expfield) {
                $userset->$expfield = $expiryvalue;
            } else {
                $userset->$expfield = $olduserset->expfield;
            }
        }

        return $userset;
    }

    /**
     * This function returns a error data structure.
     * @param int $code The error code.
     * @param string $message The error message
     * @return array An array with the error code and message.
     */
    public static function create_error_structure($code, $message) {
        return array(
            'messagecode' => $code,
            'message' => $message,
            'record' => array(
                'id' => 0,
                'name' => 'NULL')
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters The parameters object for this webservice method.
     */
    public static function userset_create_update_parameters() {
        $params = array('data' => new external_single_structure(static::get_userset_update_input_object_description()));
        return new external_function_parameters($params);
    }

    /**
     * Returns description of method result value
     * @return external_single_structure Object describing return parameters for this webservice method.
     */
    public static function userset_create_update_returns() {
        return new external_single_structure(
                array(
                    'messagecode' => new external_value(PARAM_INT, 'Response Code'),
                    'message' => new external_value(PARAM_TEXT, 'Response'),
                    'record' => new external_single_structure(static::get_userset_output_object_description())
                )
        );
    }

    /**
     * This function returns the shortname of a custom profile field.
     * @param int $fieldid The id of the field.
     * @return string The shortname of the field.
     */
    public static function retireve_field_shortname($fieldid) {
        global $DB;

        if (empty($fieldid)) {
            return 'field_does_not_exist';
        }

        $result = $DB->get_record('local_eliscore_field', array('id' => $fieldid));

        if (empty($result)) {
            return 'field_does_not_exist';
        }

        return $result->shortname;
    }

    /**
     * This function creates a User Set, if no User Set is found with a matching Solution Id.
     * If a matching Solution Id is found then the User Set is updated.
     * @throws moodle_exception If there was an error in passed parameters.
     * @throws data_object_exception If there was an error creating the entity.
     * @param array $data The incoming data parameter.
     * @return array An array of parameters, if successful.
     */
    public static function userset_create_update(array $data) {
        if (static::require_elis_dependencies() !== true) {
            throw new moodle_exception('ws_function_requires_elis', 'local_kronosfeedws');
        }

        // Parameter validation.
        $params = self::validate_parameters(self::userset_create_update_parameters(), array('data' => $data));
        $params = $params['data'];

        // Retrieve a User Set via a the solution id.
        $usersetobj = self::userset_second_level_solutionid_exists($params['solutionid']);

        if (empty($usersetobj)) {
            return self::userset_create($data);
        } else {
            return self::userset_update($data);
        }
    }
}
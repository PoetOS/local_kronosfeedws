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
 * Kronos feed web services PHPUnit tests.
 *
 * @package    local_kronosfeedws
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2015 Remote Learner.net Inc http://www.remote-learner.net
 */

defined('MOODLE_INTERNAL') || die();

class local_kronosfeedws_testcases extends advanced_testcase {
    /** @var object User Set field category object. */
    protected $usersetfieldcat = null;
    /** @var object User Set text field object. */
    protected $usersettextfield = null;
    /** @var object User Set date field (represents expiry) object. */
    protected $usersetdatefield = null;
    /** @var object User Set date field (represents extension) object. */
    protected $usersetdatefieldextension = null;

    /**
     * Tests set up.
     */
    public function setUp() {
        global $CFG;
        require_once($CFG->libdir.'/externallib.php');
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));
        require_once($CFG->dirroot.'/local/kronosfeedws/local_kronosfeedws_userset.class.php');

        // Create custom field.
        $fieldcat = new field_category;
        $fieldcat->name = 'Test';
        $fieldcat->save();
        $this->usersetfieldcat = $fieldcat;

        $field = new field;
        $field->categoryid = $fieldcat->id;
        $field->shortname = 'testfield';
        $field->name = 'Test Field';
        $field->datatype = 'char';
        $field->save();
        $this->usersettextfield = $field;

        $fieldctx = new field_contextlevel;
        $fieldctx->fieldid = $field->id;
        $fieldctx->contextlevel = CONTEXT_ELIS_USERSET;
        $fieldctx->save();

        // Create custom field.
        $field = new field;
        $field->categoryid = $fieldcat->id;
        $field->shortname = 'textfielddate';
        $field->name = 'Test Field Date';
        $field->datatype = 'datetime';
        $field->save();
        $this->usersetdatefield = $field;

        $fieldctx = new field_contextlevel;
        $fieldctx->fieldid = $field->id;
        $fieldctx->contextlevel = CONTEXT_ELIS_USERSET;
        $fieldctx->save();

        // Create another custom field.
        $field = new field;
        $field->categoryid = $fieldcat->id;
        $field->shortname = 'textfielddateextension';
        $field->name = 'Test Field Date';
        $field->datatype = 'datetime';
        $field->save();
        $this->usersetdatefieldextension = $field;

        $fieldctx = new field_contextlevel;
        $fieldctx->fieldid = $field->id;
        $fieldctx->contextlevel = CONTEXT_ELIS_USERSET;
        $fieldctx->save();

        $this->resetAfterTest();
    }

    /**
     * This functions loads data via the tests/fixtures/auth_swogportal.xml file
     * @return void
     */
    protected function setup_test_data_xml() {
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/profile_fields.xml'));
    }

    /**
     * Give permissions to the current user.
     * @param array $perms Array of permissions to grant.
     */
    protected function give_permissions(array $perms) {
        global $DB;

        accesslib_clear_all_caches(true);

        $syscontext = context_system::instance();

        // Create a user to set ourselves to.
        $assigninguser = new user(array(
            'idnumber' => 'assigninguserid',
            'username' => 'assigninguser',
            'firstname' => 'assigninguser',
            'lastname' => 'assigninguser',
            'email' => 'assigninguser@example.com',
            'country' => 'CA'
        ));
        $assigninguser->save();
        $assigningmuser = $DB->get_record('user', array('username' => 'assigninguser'));
        $this->setUser($assigningmuser);

        // Create duplicate user.
        $dupemailuser = new user(array(
            'idnumber' => 'dupemailuserid',
            'username' => 'dupemailuser',
            'firstname' => 'dupemailuserfirstname',
            'lastname' => 'dupemailuserlastname',
            'email' => 'assigninguser@example.com', // Dup email!
            'country' => 'CA'
        ));
        $dupemailuser->save();

        $roleid = create_role('testrole', 'testrole', 'testrole');
        foreach ($perms as $perm) {
            assign_capability($perm, CAP_ALLOW, $roleid, $syscontext->id);
        }

        role_assign($roleid, $assigningmuser->id, $syscontext->id);
    }

    /**
     * Test name_equals_moodle_field_shortname()
     */
    public function test_name_equals_moodle_field_shortname_nomatch() {
        $this->setup_test_data_xml();
        $result = local_kronosfeedws_userset::name_equals_moodle_field_shortname('nonexistentname');
        $this->assertFalse($result);
    }

    /**
     * Test name_equals_moodle_field_shortname()
     */
    public function test_name_equals_moodle_field_shortname_match() {
        $this->setup_test_data_xml();
        $result = local_kronosfeedws_userset::name_equals_moodle_field_shortname('testtext');
        $this->assertNotEquals(false, $result);
    }

    /**
     * Test autoassociation_field_is_valid_type()
     */
    public function test_autoassociation_field_is_valid_type_invalid_type() {
        $this->setup_test_data_xml();
        $result = local_kronosfeedws_userset::autoassociation_field_is_valid_type(5);
        $this->assertFalse($result);
        $result = local_kronosfeedws_userset::autoassociation_field_is_valid_type(6);
        $this->assertFalse($result);
        $result = local_kronosfeedws_userset::autoassociation_field_is_valid_type(7);
        $this->assertFalse($result);
    }

    /**
     * Test autoassociation_field_is_valid_type()
     */
    public function test_autoassociation_field_is_valid_type() {
        $this->setup_test_data_xml();
        $result = local_kronosfeedws_userset::autoassociation_field_is_valid_type(1);
        $this->assertTrue($result);
        $result = local_kronosfeedws_userset::autoassociation_field_is_valid_type(2);
        $this->assertTrue($result);
        $result = local_kronosfeedws_userset::autoassociation_field_is_valid_type(3);
        $this->assertTrue($result);
        $result = local_kronosfeedws_userset::autoassociation_field_is_valid_type(4);
        $this->assertTrue($result);
        $result = local_kronosfeedws_userset::autoassociation_field_is_valid_type(8);
        $this->assertTrue($result);
    }

    /**
     * Test autoassociation_field_is_valid_type()
     */
    public function test_autoassociation_field_value_is_valid_invalid() {
        $menu = new stdClass();
        $menu->datatype = 'menu';
        $menu->param1 = "one\ntwo\nthree";

        $nonmenu = new stdClass();
        $nonmenu->datatype = 'text';

        $result = local_kronosfeedws_userset::autoassociation_menu_field_value_is_valid($menu, '');
        $this->assertFalse($result);
        $result = local_kronosfeedws_userset::autoassociation_menu_field_value_is_valid($menu, 'twoo');
        $this->assertFalse($result);
        $result = local_kronosfeedws_userset::autoassociation_menu_field_value_is_valid($nonmenu, 'three');
        $this->assertFalse($result);
    }

    /**
     * Test autoassociation_field_is_valid_type()
     */
    public function test_autoassociation_field_value_is_valid() {
        $menu = new stdClass();
        $menu->datatype = 'menu';
        $menu->param1 = "one\ntwo\nthree";

        $result = local_kronosfeedws_userset::autoassociation_menu_field_value_is_valid($menu, 'one');
        $this->assertTrue($result);
        $result = local_kronosfeedws_userset::autoassociation_menu_field_value_is_valid($menu, 'two');
        $this->assertTrue($result);
        $result = local_kronosfeedws_userset::autoassociation_menu_field_value_is_valid($menu, 'three');
        $this->assertTrue($result);
    }

    /**
     * Test unsuccessful userset creation.
     */
    public function test_userset_create_non_existent_autoassociate_field_shortname() {
        $userset = array(
            'name' => 'invalidautoassociate',
            'display' => 'phpunit test description',
            'autoassociate1' => 'doesnotexist',
            'autoassociate1_value' => 'doesnotexist',
        );

        $this->give_permissions(array('local/elisprogram:userset_create'));
        $response = local_kronosfeedws_userset::userset_create($userset);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(-1, $response['messagecode']);
        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);
        $this->assertEquals(0, $response['record']['id']);
        $this->assertArrayHasKey('name', $response['record']);
        $this->assertEquals('NULL', $response['record']['name']);
    }

    /**
     * Test unsuccessful userset creation.
     */
    public function test_userset_create_wrong_datatype() {
        $this->setup_test_data_xml();

        $userset = array(
            'name' => 'invalidautoassociate',
            'display' => 'phpunit test description',
            'autoassociate1' => 'testdate',
            'autoassociate1_value' => 'doesnotexist',
        );

        $this->give_permissions(array('local/elisprogram:userset_create'));

        $response = local_kronosfeedws_userset::userset_create($userset);
        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(-2, $response['messagecode']);
        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);
        $this->assertEquals(0, $response['record']['id']);
        $this->assertArrayHasKey('name', $response['record']);
        $this->assertEquals('NULL', $response['record']['name']);

        $userset['autoassociate1'] = 'testdatetime';

        $response = local_kronosfeedws_userset::userset_create($userset);
        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(-2, $response['messagecode']);
        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);
        $this->assertEquals(0, $response['record']['id']);
        $this->assertArrayHasKey('name', $response['record']);
        $this->assertEquals('NULL', $response['record']['name']);

        $userset['autoassociate1'] = 'testtextarea';

        $response = local_kronosfeedws_userset::userset_create($userset);
        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(-2, $response['messagecode']);
        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);
        $this->assertEquals(0, $response['record']['id']);
        $this->assertArrayHasKey('name', $response['record']);
        $this->assertEquals('NULL', $response['record']['name']);
    }

    /**
     * Test unsuccessful userset creation.
     */
    public function test_userset_create_empty_autoassociate_value() {
        $this->setup_test_data_xml();

        // Test with an empty text field value.
        $userset = array(
            'name' => 'invalidautoassociate',
            'display' => 'phpunit test description',
            'autoassociate1' => 'testtext',
            'autoassociate1_value' => '',
        );

        $this->give_permissions(array('local/elisprogram:userset_create'));

        $response = local_kronosfeedws_userset::userset_create($userset);
        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(-3, $response['messagecode']);
        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);
        $this->assertEquals(0, $response['record']['id']);
        $this->assertArrayHasKey('name', $response['record']);
        $this->assertEquals('NULL', $response['record']['name']);

        // Test with an empy menu of choice value.
        $userset['autoassociate1'] = 'testdropdownwithdefault';

        $response = local_kronosfeedws_userset::userset_create($userset);
        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(-3, $response['messagecode']);
        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);
        $this->assertEquals(0, $response['record']['id']);
        $this->assertArrayHasKey('name', $response['record']);
        $this->assertEquals('NULL', $response['record']['name']);
    }

    /**
     * Test unsuccessful userset creation.
     */
    public function test_userset_create_invalid_menu_of_choices_value() {
        $this->setup_test_data_xml();

        $userset = array(
            'name' => 'invalidautoassociate',
            'display' => 'phpunit test description',
            'autoassociate1' => 'testdropdownwithdefault',
            'autoassociate1_value' => 'five',
        );

        $this->give_permissions(array('local/elisprogram:userset_create'));

        $response = local_kronosfeedws_userset::userset_create($userset);
        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(-5, $response['messagecode']);
        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);
        $this->assertEquals(0, $response['record']['id']);
        $this->assertArrayHasKey('name', $response['record']);
        $this->assertEquals('NULL', $response['record']['name']);
    }

    /**
     * Test successful userset creation.
     */
    public function test_userset_create_success() {
        $this->give_permissions(array('local/elisprogram:userset_create'));

        set_config('expiry', $this->usersetdatefield->id, 'local_kronosfeedws');
        $userset = array(
            'name' => 'testusersetname',
            'display' => 'Userset Description',
            'field_testfield' => 'Test field',
            'expiry' => '2015-01-01 12:00:05',
        );

        $response = local_kronosfeedws_userset::userset_create($userset);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(1, $response['messagecode']);
        $this->assertEquals('Userset created successfully', $response['message']);

        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);

        // Get Userset.
        $createdus = new userset($response['record']['id']);
        $createdus->load();
        $createdus = $createdus->to_array();
        $expectus = array(
            'id' => $response['record']['id'],
            'name' => 'testusersetname',
            'display' => 'Userset Description',
            'parent' => 0,
            'field_testfield' => 'Test field'
        );

        foreach ($expectus as $param => $val) {
            $this->assertArrayHasKey($param, $createdus);
            $this->assertEquals($val, $createdus[$param]);
        }
    }

    /**
     * Test successful userset creation setting auto-associate 1 to a drop down
     */
    public function test_userset_create_success_autoassociate_dropdown() {
        global $DB;

        $this->setup_test_data_xml();
        set_config('expiry', $this->usersetdatefield->id, 'local_kronosfeedws');
        $this->give_permissions(array('local/elisprogram:userset_create'));

        $userset = array(
            'name' => 'testusersetname',
            'display' => 'Userset Description',
            'field_testfield' => 'Test field',
            'autoassociate1' => 'testdropdownwithdefault',
            'autoassociate1_value' => 'two',
            'expiry' => '2015-01-01 12:00:05',
        );

        $response = local_kronosfeedws_userset::userset_create($userset);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(1, $response['messagecode']);
        $this->assertEquals('Userset created successfully', $response['message']);

        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);

        // Get Userset.
        $createdus = new userset($response['record']['id']);
        $createdus->load();
        $createdus = $createdus->to_array();
        $expectus = array(
            'id' => $response['record']['id'],
            'name' => 'testusersetname',
            'display' => 'Userset Description',
            'parent' => 0,
            'field_testfield' => 'Test field'
        );

        foreach ($expectus as $param => $val) {
            $this->assertArrayHasKey($param, $createdus);
            $this->assertEquals($val, $createdus[$param]);
        }

        $select = "clusterid = ".$response['record']['id']." AND fieldid = 1 AND ".$DB->sql_compare_text('value', 3)." = 'two'";
        $result = $DB->record_exists_select('local_elisprogram_uset_prfle', $select);
        $this->assertTrue($result);
    }

    /**
     * Test successful userset creation setting auto-associate 1 to a checkbox.
     */
    public function test_userset_create_success_autoassociate_checkbox() {
        global $DB;

        $this->setup_test_data_xml();
        set_config('expiry', $this->usersetdatefield->id, 'local_kronosfeedws');
        $this->give_permissions(array('local/elisprogram:userset_create'));

        $userset = array(
            'name' => 'testusersetname',
            'display' => 'Userset Description',
            'field_testfield' => 'Test field',
            'autoassociate1' => 'testcheckboxdefaultunchecked',
            'autoassociate1_value' => 'checked',
            'expiry' => '2015-01-01 12:00:05',
        );

        $response = local_kronosfeedws_userset::userset_create($userset);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(1, $response['messagecode']);
        $this->assertEquals('Userset created successfully', $response['message']);

        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);

        // Get Userset.
        $createdus = new userset($response['record']['id']);
        $createdus->load();
        $createdus = $createdus->to_array();
        $expectus = array(
            'id' => $response['record']['id'],
            'name' => 'testusersetname',
            'display' => 'Userset Description',
            'parent' => 0,
            'field_testfield' => 'Test field'
        );

        foreach ($expectus as $param => $val) {
            $this->assertArrayHasKey($param, $createdus);
            $this->assertEquals($val, $createdus[$param]);
        }

        $select = "clusterid = ".$response['record']['id']." AND fieldid = 3 AND ".$DB->sql_compare_text('value', 1)." = '1'";
        $result = $DB->record_exists_select('local_elisprogram_uset_prfle', $select);
        $this->assertTrue($result);
    }

    /**
     * Test successful userset creation setting auto-associate 1 to a checkbox unchecked.
     */
    public function test_userset_create_success_autoassociate_checkbox_unchecked() {
        global $DB;

        $this->setup_test_data_xml();
        set_config('expiry', $this->usersetdatefield->id, 'local_kronosfeedws');
        $this->give_permissions(array('local/elisprogram:userset_create'));

        $userset = array(
            'name' => 'testusersetname',
            'display' => 'Userset Description',
            'field_testfield' => 'Test field',
            'autoassociate1' => 'testcheckboxdefaultunchecked',
            'autoassociate1_value' => '',
            'expiry' => '2015-01-01 12:00:05',
        );

        $response = local_kronosfeedws_userset::userset_create($userset);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(1, $response['messagecode']);
        $this->assertEquals('Userset created successfully', $response['message']);

        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);

        // Get Userset.
        $createdus = new userset($response['record']['id']);
        $createdus->load();
        $createdus = $createdus->to_array();
        $expectus = array(
            'id' => $response['record']['id'],
            'name' => 'testusersetname',
            'display' => 'Userset Description',
            'parent' => 0,
            'field_testfield' => 'Test field'
        );

        foreach ($expectus as $param => $val) {
            $this->assertArrayHasKey($param, $createdus);
            $this->assertEquals($val, $createdus[$param]);
        }

        $select = "clusterid = ".$response['record']['id']." AND fieldid = 3 AND ".$DB->sql_compare_text('value', 1)." = '0'";
        $result = $DB->record_exists_select('local_elisprogram_uset_prfle', $select);
        $this->assertTrue($result);
    }

    /**
     * Test successful userset creation setting auto-associate 1 to a textbox
     */
    public function test_userset_create_success_autoassociate_textbox() {
        global $DB;

        $this->setup_test_data_xml();
        set_config('expiry', $this->usersetdatefield->id, 'local_kronosfeedws');
        $this->give_permissions(array('local/elisprogram:userset_create'));

        $userset = array(
            'name' => 'testusersetname',
            'display' => 'Userset Description',
            'field_testfield' => 'Test field',
            'autoassociate1' => 'testtext',
            'autoassociate1_value' => 'TEST1234',
            'expiry' => '2015-01-01 12:00:05',
        );

        $response = local_kronosfeedws_userset::userset_create($userset);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(1, $response['messagecode']);
        $this->assertEquals('Userset created successfully', $response['message']);

        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);

        // Get Userset.
        $createdus = new userset($response['record']['id']);
        $createdus->load();
        $createdus = $createdus->to_array();
        $expectus = array(
            'id' => $response['record']['id'],
            'name' => 'testusersetname',
            'display' => 'Userset Description',
            'parent' => 0,
            'field_testfield' => 'Test field'
        );

        foreach ($expectus as $param => $val) {
            $this->assertArrayHasKey($param, $createdus);
            $this->assertEquals($val, $createdus[$param]);
        }

        $select = "clusterid = ".$response['record']['id']." AND fieldid = 8 AND ".$DB->sql_compare_text('value', 8)." = 'TEST1234'";
        $result = $DB->record_exists_select('local_elisprogram_uset_prfle', $select);
        $this->assertTrue($result);
    }

    /**
     * Test successful userset creation setting auto-associate 1 to a checkbox and auto-associate 2 to a textfield
     */
    public function test_userset_create_success_setting_bothautoassociate_fields() {
        global $DB;

        $this->setup_test_data_xml();
        set_config('expiry', $this->usersetdatefield->id, 'local_kronosfeedws');
        $this->give_permissions(array('local/elisprogram:userset_create'));

        $userset = array(
            'name' => 'testusersetname',
            'display' => 'Userset Description',
            'field_testfield' => 'Test field',
            'autoassociate1' => 'testcheckboxdefaultunchecked',
            'autoassociate1_value' => 'checked',
            'autoassociate2' => 'testtext',
            'autoassociate2_value' => 'TEST1234',
            'expiry' => '2015-01-01 12:00:05',
        );

        $response = local_kronosfeedws_userset::userset_create($userset);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(1, $response['messagecode']);
        $this->assertEquals('Userset created successfully', $response['message']);

        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);

        // Get Userset.
        $createdus = new userset($response['record']['id']);
        $createdus->load();
        $createdus = $createdus->to_array();
        $expectus = array(
            'id' => $response['record']['id'],
            'name' => 'testusersetname',
            'display' => 'Userset Description',
            'parent' => 0,
            'field_testfield' => 'Test field'
        );

        foreach ($expectus as $param => $val) {
            $this->assertArrayHasKey($param, $createdus);
            $this->assertEquals($val, $createdus[$param]);
        }

        $select = "clusterid = ".$response['record']['id']." AND fieldid = 3 AND ".$DB->sql_compare_text('value', 1)." = '1'";
        $result = $DB->record_exists_select('local_elisprogram_uset_prfle', $select);
        $this->assertTrue($result);

        $select = "clusterid = ".$response['record']['id']." AND fieldid = 8 AND ".$DB->sql_compare_text('value', 8)." = 'TEST1234'";
        $result = $DB->record_exists_select('local_elisprogram_uset_prfle', $select);
    }

    /**
     * Test validate_expiry_date when expiry parameter is zero.
     */
    public function test_validate_expiry_date_not_parameter_empty() {
        $result = local_kronosfeedws_userset::validate_expiry_date(0);
        $this->assertInternalType('array', $result);
        $this->assertEquals(false, $result[0]);
        $result = local_kronosfeedws_userset::validate_expiry_date();
        $this->assertInternalType('array', $result);
        $this->assertEquals(false, $result[0]);
    }

    /**
     * Test validate_expiry_date when expiry date not configured.
     */
    public function test_validate_expiry_date_expiry_field_not_configured() {
        $result = local_kronosfeedws_userset::validate_expiry_date('2015-01-01T12:00:05');
        $this->assertInternalType('array', $result);
        $this->assertFalse($result[0]);
        $this->assertInternalType('array', $result[1]);
        $this->assertArrayHasKey('messagecode', $result[1]);
        $this->assertEquals(-6, $result[1]['messagecode']);
        $this->assertArrayHasKey('message', $result[1]);
        $this->assertArrayHasKey('record', $result[1]);
    }

    /**
     * Test validate_expiry_date when expiry field does not exist.
     */
    public function test_validate_expiry_date_expiry_field_does_not_exist() {
        set_config('expiry', '22', 'local_kronosfeedws');
        $result = local_kronosfeedws_userset::validate_expiry_date('2015-01-01T12:00:05');
        $this->assertInternalType('array', $result);
        $this->assertFalse($result[0]);
        $this->assertArrayHasKey('messagecode', $result[1]);
        $this->assertEquals(-6, $result[1]['messagecode']);
        $this->assertArrayHasKey('message', $result[1]);
        $this->assertArrayHasKey('record', $result[1]);
    }

    /**
     * Test validate_expiry_date when expiry date parameter is of the wrong format.
     */
    public function test_validate_expiry_date_expiry_field_wrong_format() {
        set_config('expiry', $this->usersetdatefield->id, 'local_kronosfeedws');
        $result = local_kronosfeedws_userset::validate_expiry_date('2015-01-01T12:00:05');
        $this->assertInternalType('array', $result);
        $this->assertFalse($result[0]);
        $this->assertArrayHasKey('messagecode', $result[1]);
        $this->assertEquals(-7, $result[1]['messagecode']);
        $this->assertArrayHasKey('message', $result[1]);
        $this->assertArrayHasKey('record', $result[1]);
    }

    /**
     * Test validate_expiry_date success
     */
    public function test_validate_expiry_date_success() {
        set_config('expiry', $this->usersetdatefield->id, 'local_kronosfeedws');
        $result = local_kronosfeedws_userset::validate_expiry_date('2015-01-01 12:00:05');
        $this->assertInternalType('array', $result);
        $this->assertNotEquals(false, $result[0]);
    }

    /**
     * Test userset_solutionid_exists.
     */
    public function test_userset_second_level_solutionid_exists_with_no_configured_solutionid() {
        $result = local_kronosfeedws_userset::userset_second_level_solutionid_exists(0);
        $this->assertFalse($result);
    }

    /**
     * Test userset_solutionid_exists.
     */
    public function test_userset_second_level_solutionid_exists_with_non_existent_fieldid() {
        set_config('solutionid', '99', 'local_kronosfeedws');
        $result = local_kronosfeedws_userset::userset_second_level_solutionid_exists(0);
        $this->assertFalse($result);
    }

    /**
     * Test userset_second_level_solutionid_exists.  NOTE: this tests using a second level User Set
     */
    public function test_userset_second_level_solutionid_exists_success() {
        $this->give_permissions(array('local/elisprogram:userset_create'));
        set_config('expiry', $this->usersetdatefield->id, 'local_kronosfeedws');
        set_config('solutionid', $this->usersettextfield->id, 'local_kronosfeedws');

        $userset = array(
            'name' => 'testparent',
            'display' => 'Userset Description',
            'expiry' => '2015-01-01 12:00:05',
        );

        // Create Parent User Set.
        $response = local_kronosfeedws_userset::userset_create($userset);

        $userset = array(
            'name' => 'testusersetname',
            'parent' => 'testparent',
            'display' => 'Userset Description',
            'expiry' => '2015-01-01 12:00:05',
            'solutionid' => 'TEST1234',
        );

        // Create a User Set with solution id of 'TEST1234'.
        $response = local_kronosfeedws_userset::userset_create($userset);

        $result = local_kronosfeedws_userset::userset_second_level_solutionid_exists('TEST1234');

        $this->assertNotEquals(false, $result);
    }

    /**
     * test retrieving an the extension field where the field does not exist.
     */
    public function test_get_date_extension_field_field_not_exist() {
        $result = local_kronosfeedws_userset::get_date_extension_field();
        $this->assertFalse($result);
    }

    /**
     * test retrieving an the extension field where the field does not exist.
     */
    public function test_get_date_extension_field_success() {
        set_config('extension', $this->usersetdatefieldextension->id, 'local_kronosfeedws');
        $result = local_kronosfeedws_userset::get_date_extension_field();
        $this->assertEquals($this->usersetdatefieldextension->shortname, $result);
    }

    /**
     * Test userset_set_new_expiry_date where expiry date is less than User Set expiry and extension date.
     */
    public function test_userset_set_new_expiry_date_less_than_expiry_and_extension() {
        $this->give_permissions(array('local/elisprogram:userset_create'));
        set_config('expiry', $this->usersetdatefield->id, 'local_kronosfeedws');
        set_config('solutionid', $this->usersettextfield->id, 'local_kronosfeedws');
        set_config('extension', $this->usersetdatefieldextension->id, 'local_kronosfeedws');

        $expiryfield = 'field_'.$this->usersetdatefield->shortname;
        $extensionfield = 'field_'.$this->usersetdatefieldextension->shortname;

        $userset = array(
            'name' => 'testaudiencetype',
            'display' => 'Userset Description',
        );

        // Create Parent User Set.
        local_kronosfeedws_userset::userset_create($userset);

        $userset = array(
            'name' => 'testusersetname',
            'parent' => 'testaudiencetype',
            'display' => 'Userset Description',
            'expiry' => '2015-05-01 12:00:00',
        );

        $originaluserset = local_kronosfeedws_userset::userset_create($userset);
        $originaluserset = new userset($originaluserset['record']['id']);

        $mockuserset = new stdClass();
        $mockuserset->id = $originaluserset->id;
        // Setting the expiry propery of the mock User Set to a value that is less than value used by User Set "testusersetname" above.
        $mockuserset->$expiryfield = $originaluserset->$expiryfield - 100;
        $mockuserset->$extensionfield = $originaluserset->$expiryfield;

        $result = local_kronosfeedws_userset::userset_set_new_expiry_date($mockuserset, $this->usersetdatefield->shortname, $mockuserset->$expiryfield);

        // Assert that the expiry and extention values are uncharnged from the originally set values.
        $this->assertEquals($originaluserset->$expiryfield, $result->$expiryfield);
        $this->assertEquals($originaluserset->$extensionfield, $result->$extensionfield);
    }

    /**
     * Test userset_set_new_expiry_date where expiry date is greater than the User Set expiry date but less than the extension date.
     */
    public function test_userset_set_new_expiry_date_greater_than_expiry_less_than_extension() {
        $this->give_permissions(array('local/elisprogram:userset_create'));
        set_config('expiry', $this->usersetdatefield->id, 'local_kronosfeedws');
        set_config('solutionid', $this->usersettextfield->id, 'local_kronosfeedws');
        set_config('extension', $this->usersetdatefieldextension->id, 'local_kronosfeedws');

        $expiryfield = 'field_'.$this->usersetdatefield->shortname;
        $extensionfield = 'field_'.$this->usersetdatefieldextension->shortname;

        $userset = array(
            'name' => 'testaudiencetype',
            'display' => 'Userset Description',
        );

        // Create Parent User Set.
        local_kronosfeedws_userset::userset_create($userset);

        $userset = array(
            'name' => 'testusersetname',
            'parent' => 'testaudiencetype',
            'display' => 'Userset Description',
            'expiry' => '2015-05-01 12:00:00',
            $extensionfield => strtotime('2015-06-01 12:00:00'),
        );

        $originaluserset = local_kronosfeedws_userset::userset_create($userset);
        $originaluserset = new userset($originaluserset['record']['id']);

        $mockuserset = new stdClass();
        $mockuserset->id = $originaluserset->id;
        // Setting the expiry propery of the mock User Set to a value that is greater than value used by User Set "testusersetname" above, but less than the extension date.
        $mockuserset->$expiryfield = strtotime('2015-05-02 12:00:00');

        $result = local_kronosfeedws_userset::userset_set_new_expiry_date($mockuserset, $this->usersetdatefield->shortname, $mockuserset->$expiryfield);

        // Assert that the expiry and extention values are uncharnged from the originally set values.
        $this->assertEquals($originaluserset->$expiryfield, $result->$expiryfield);
        $this->assertEquals($originaluserset->$extensionfield, $result->$extensionfield);
    }

    /**
     * Test userset_set_new_expiry_date where expiry date is greater than the User Set expiry date and the extension date.
     */
    public function test_userset_set_new_expiry_date_greater_than_expiry_and_extension() {
        $this->give_permissions(array('local/elisprogram:userset_create'));
        set_config('expiry', $this->usersetdatefield->id, 'local_kronosfeedws');
        set_config('solutionid', $this->usersettextfield->id, 'local_kronosfeedws');
        set_config('extension', $this->usersetdatefieldextension->id, 'local_kronosfeedws');

        $expiryfield = 'field_'.$this->usersetdatefield->shortname;
        $extensionfield = 'field_'.$this->usersetdatefieldextension->shortname;

        $userset = array(
            'name' => 'testaudiencetype',
            'display' => 'Userset Description',
        );

        // Create Parent User Set.
        local_kronosfeedws_userset::userset_create($userset);

        $userset = array(
            'name' => 'testusersetname',
            'parent' => 'testaudiencetype',
            'display' => 'Userset Description',
            'expiry' => '2015-05-01 12:00:00',
            $extensionfield => strtotime('2015-06-01 12:00:00'),
        );

        $originaluserset = local_kronosfeedws_userset::userset_create($userset);
        $originaluserset = new userset($originaluserset['record']['id']);

        $mockuserset = new stdClass();
        $mockuserset->id = $originaluserset->id;
        // Setting the expiry propery of the mock User Set to a value that is greater than value used by User Set "testusersetname" above and greater than extension date.
        $mockuserset->$expiryfield = strtotime('2015-06-02 12:00:00');

        $result = local_kronosfeedws_userset::userset_set_new_expiry_date($mockuserset, $this->usersetdatefield->shortname, $mockuserset->$expiryfield);

        // Assert that the expiry and extention values are uncharnged from the originally set values.
        $this->assertEquals(strtotime('2015-06-02 12:00:00'), $result->$expiryfield);
        $this->assertEquals(strtotime(local_kronosfeedws_userset::DEFAULTDATE), $result->$extensionfield);
    }

    /**
     * Test updating a User Set that does not exist.
     */
    public function test_userset_update_userset_does_not_exist() {
        $this->give_permissions(array('local/elisprogram:userset_create'));
        set_config('expiry', $this->usersetdatefield->id, 'local_kronosfeedws');
        set_config('solutionid', $this->usersettextfield->id, 'local_kronosfeedws');

        $userset = array(
            'name' => 'testaudiencetype',
            'display' => 'Userset Description',
        );

        // Create Parent User Set.
        local_kronosfeedws_userset::userset_create($userset);

        $userset = array(
            'name' => 'testusersetname',
            'parent' => 'testaudiencetype',
            'display' => 'Userset Description',
            'field_'.$this->usersettextfield->shortname => 'TEST1234'
        );

        // Create Solution ID User Set.
        local_kronosfeedws_userset::userset_create($userset);

        $userset = array(
            'solutionid' => 'TEST12344',
        );

        $response = local_kronosfeedws_userset::userset_update($userset);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(-8, $response['messagecode']);
        $this->assertEquals('Unable to find a User Set with solution ID of TEST12344', $response['message']);
    }

    /**
     * Test updating a User Set where the parent does not exist.
     */
    public function test_userset_update_userset_parent_not_exist() {
        $this->give_permissions(array('local/elisprogram:userset_create', 'local/elisprogram:userset_edit'));
        set_config('expiry', $this->usersetdatefield->id, 'local_kronosfeedws');
        set_config('solutionid', $this->usersettextfield->id, 'local_kronosfeedws');

        $userset = array(
            'name' => 'testaudiencetype',
            'display' => 'Userset Description',
        );

        // Create Parent User Set.
        local_kronosfeedws_userset::userset_create($userset);

        $userset = array(
            'name' => 'testusersetname',
            'parent' => 'testaudiencetype',
            'display' => 'Userset Description',
            'field_'.$this->usersettextfield->shortname => 'TEST1234'
        );

        // Create Solution ID User Set.
        local_kronosfeedws_userset::userset_create($userset);

        $userset = array(
            'solutionid' => 'TEST1234',
            'parent' => 'notexist'
        );

        $response = local_kronosfeedws_userset::userset_update($userset);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(-9, $response['messagecode']);
        $this->assertEquals('Parent User Set name of notexist does not exist', $response['message']);
    }

    /**
     * Test updating a User Set, with an expiry date that is less than the User Set expiry date.
     */
    public function test_userset_update_expiry_parameter_less_than_userset_expiry() {
        $this->give_permissions(array('local/elisprogram:userset_create', 'local/elisprogram:userset_edit'));
        set_config('expiry', $this->usersetdatefield->id, 'local_kronosfeedws');
        set_config('solutionid', $this->usersettextfield->id, 'local_kronosfeedws');
        set_config('extension', $this->usersetdatefieldextension->id, 'local_kronosfeedws');

        $expiryfield = 'field_'.$this->usersetdatefield->shortname;
        $extensionfield = 'field_'.$this->usersetdatefieldextension->shortname;
        $solutionidfield = 'field_'.$this->usersettextfield->shortname;

        $userset = array(
            'name' => 'testaudiencetype',
            'display' => 'Userset Description',
        );

        // Create Parent User Set.
        local_kronosfeedws_userset::userset_create($userset);

        // Set expiry date to May 5, 2015.
        $userset = array(
            'name' => 'testusersetname',
            'parent' => 'testaudiencetype',
            'display' => 'Userset Description',
            'expiry' => '2015-05-01 12:00:00',
            $extensionfield => strtotime('2015-06-01 12:00:00'),
            $solutionidfield => 'TEST1234'
        );

        // Create Solution ID User Set.
        $data = local_kronosfeedws_userset::userset_create($userset);

        // Set expiry date to Jan 1, 2015.
        $userset = array(
            'solutionid' => 'TEST1234',
            'expiry' => '2015-01-01 12:00:00',
        );

        $response = local_kronosfeedws_userset::userset_update($userset);

        // Assert that the original expiry value used to create the user set, remains the same after the call to update the User Set.
        $this->assertEquals($data['record'][$expiryfield], $response['record'][$expiryfield]);
    }

    /**
     * Test updating a User Set with an expiry date that is greater than the User Set expiry date, but less than the extension date.
     */
    public function test_userset_update_expiry_parameter_greater_than_userset_expiry_less_than_extension() {
        $this->give_permissions(array('local/elisprogram:userset_create', 'local/elisprogram:userset_edit'));
        set_config('expiry', $this->usersetdatefield->id, 'local_kronosfeedws');
        set_config('solutionid', $this->usersettextfield->id, 'local_kronosfeedws');
        set_config('extension', $this->usersetdatefieldextension->id, 'local_kronosfeedws');

        $expiryfield = 'field_'.$this->usersetdatefield->shortname;
        $extensionfield = 'field_'.$this->usersetdatefieldextension->shortname;
        $solutionidfield = 'field_'.$this->usersettextfield->shortname;

        $userset = array(
            'name' => 'testaudiencetype',
            'display' => 'Userset Description',
        );

        // Create Parent User Set.
        local_kronosfeedws_userset::userset_create($userset);

        // Set expiry date to May 1, 2015.
        $userset = array(
            'name' => 'testusersetname',
            'parent' => 'testaudiencetype',
            'display' => 'Userset Description',
            'expiry' => '2015-05-01 12:00:00',
            $extensionfield => strtotime('2015-06-01 12:00:00'),
            $solutionidfield => 'TEST1234'
        );

        // Create Solution ID User Set.
        $data = local_kronosfeedws_userset::userset_create($userset);

        // Set expiry date to May 2, 2015.
        $userset = array(
            'solutionid' => 'TEST1234',
            'expiry' => '2015-05-02 12:00:00',
        );

        $response = local_kronosfeedws_userset::userset_update($userset);

        // Assert that the original expiry value used to create the user set, remains the same after the call to update the User Set.
        $this->assertEquals($data['record'][$expiryfield], $response['record'][$expiryfield]);
        // Assert that the original extension value used to create the user set, remains the same after the call to update the User Set.
        $this->assertEquals($data['record'][$extensionfield], $response['record'][$extensionfield]);
    }

    /**
     * Test updating a User Set with an expiry date that is greater than the User Set expiry and extension date.
     */
    public function test_userset_update_expiry_parameter_greater_than_userset_expiry_and_extension() {
        $this->give_permissions(array('local/elisprogram:userset_create', 'local/elisprogram:userset_edit'));
        set_config('expiry', $this->usersetdatefield->id, 'local_kronosfeedws');
        set_config('solutionid', $this->usersettextfield->id, 'local_kronosfeedws');
        set_config('extension', $this->usersetdatefieldextension->id, 'local_kronosfeedws');

        $expiryfield = 'field_'.$this->usersetdatefield->shortname;
        $extensionfield = 'field_'.$this->usersetdatefieldextension->shortname;
        $solutionidfield = 'field_'.$this->usersettextfield->shortname;

        $userset = array(
            'name' => 'testaudiencetype',
            'display' => 'Userset Description',
        );

        // Create Parent User Set.
        local_kronosfeedws_userset::userset_create($userset);

        // Set expiry date to May 5, 2015.
        $userset = array(
            'name' => 'testusersetname',
            'parent' => 'testaudiencetype',
            'display' => 'Userset Description',
            'expiry' => '2015-05-01 12:00:00',
            $extensionfield => strtotime('2015-06-01 12:00:00'),
            $solutionidfield => 'TEST1234'
        );

        // Create Solution ID User Set.
        $data = local_kronosfeedws_userset::userset_create($userset);

        // Set expiry date to July 1, 2015.
        $userset = array(
            'solutionid' => 'TEST1234',
            'expiry' => '2015-07-01 12:00:00',
        );

        $response = local_kronosfeedws_userset::userset_update($userset);

        // Assert that the original expiry value used to create the user set, remains the same after the call to update the User Set.
        $this->assertEquals(strtotime('2015-07-01 12:00:00'), $response['record'][$expiryfield]);
        // Assert that the original extension value used to create the user set, remains the same after the call to update the User Set.
        $this->assertEquals(strtotime(local_kronosfeedws_userset::DEFAULTDATE), $response['record'][$extensionfield]);
    }

    /**
     * Test updating a User Set changing the auto-associate field.
     */
    public function test_userset_update_change_autoassociate_field() {
        global $DB;

        $this->setup_test_data_xml();
        $this->give_permissions(array('local/elisprogram:userset_create', 'local/elisprogram:userset_edit'));
        set_config('expiry', $this->usersetdatefield->id, 'local_kronosfeedws');
        set_config('solutionid', $this->usersettextfield->id, 'local_kronosfeedws');
        set_config('extension', $this->usersetdatefieldextension->id, 'local_kronosfeedws');

        $expiryfield = 'field_'.$this->usersetdatefield->shortname;
        $extensionfield = 'field_'.$this->usersetdatefieldextension->shortname;
        $solutionidfield = 'field_'.$this->usersettextfield->shortname;

        $userset = array(
            'name' => 'testaudiencetype',
            'display' => 'Userset Description',
        );

        // Create Parent User Set.
        $audiencetype = local_kronosfeedws_userset::userset_create($userset);

        $userset = array(
            'name' => 'testusersetname',
            'display' => 'Userset Description',
            'parent' => 'testaudiencetype',
            'field_testfield' => 'Test field',
            'display' => 'Userset Description',
            'autoassociate1' => 'testdropdownwithdefault',
            'autoassociate1_value' => 'two',
            'expiry' => '2015-01-01 12:00:05',
            $solutionidfield => 'TEST1234',
        );

        $response = local_kronosfeedws_userset::userset_create($userset);

        $userset = array(
            'solutionid' => 'TEST1234',
            'name' => 'new testusersetname',
            'display' => 'New Userset Description',
            'autoassociate1' => 'testtext',
            'autoassociate1_value' => 'TEST1234',
        );

        $response = local_kronosfeedws_userset::userset_update($userset);

        // Get Userset.
        $createdus = new userset($response['record']['id']);
        $createdus->load();
        $createdus = $createdus->to_array();
        $expectus = array(
            'id' => $response['record']['id'],
            'name' => 'new testusersetname',
            'display' => 'New Userset Description',
            'parent' => $audiencetype['record']['id'],
        );

        foreach ($expectus as $param => $val) {
            $this->assertArrayHasKey($param, $createdus);
            $this->assertEquals($val, $createdus[$param]);
        }

        $select = "clusterid = ".$response['record']['id']." AND fieldid = 8 AND ".$DB->sql_compare_text('value', 8)." = 'TEST1234'";
        $result = $DB->record_exists_select('local_elisprogram_uset_prfle', $select);
        $this->assertTrue($result);
    }

    /**
     * Test creating and updating a User Set.
     */
    public function test_userset_create_update_success() {
        $this->give_permissions(array('local/elisprogram:userset_create'));

        set_config('expiry', $this->usersetdatefield->id, 'local_kronosfeedws');
        set_config('solutionid', $this->usersettextfield->id, 'local_kronosfeedws');
        set_config('extension', $this->usersetdatefieldextension->id, 'local_kronosfeedws');

        $expiryfield = 'field_'.$this->usersetdatefield->shortname;
        $extensionfield = 'field_'.$this->usersetdatefieldextension->shortname;
        $solutionidfield = 'field_'.$this->usersettextfield->shortname;

        $userset = array(
            'name' => 'testusersetname',
            'display' => 'Userset Description',
            'field_testfield' => 'Test field',
            'expiry' => '2015-01-01 12:00:05',
            'solutionid' => 'TEST1234',
        );

        $response = local_kronosfeedws_userset::userset_create_update($userset);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('record', $response);
        $this->assertEquals(1, $response['messagecode']);
        $this->assertEquals('Userset created successfully', $response['message']);

        $this->assertInternalType('array', $response['record']);
        $this->assertArrayHasKey('id', $response['record']);

        // Get Userset.
        $createdus = new userset($response['record']['id']);
        $createdus->load();
        $createdus = $createdus->to_array();
        $expectus = array(
            'id' => $response['record']['id'],
            'name' => 'testusersetname',
            'display' => 'Userset Description',
            'parent' => 0,
            $solutionidfield => 'TEST1234'
        );

        foreach ($expectus as $param => $val) {
            $this->assertArrayHasKey($param, $createdus);
            $this->assertEquals($val, $createdus[$param]);
        }

        $userset = array(
            'name' => 'testusersetname_updated',
            'display' => 'Userset Description (Updated)',
            'field_testfield' => 'Test field (Updated)',
            'expiry' => '2015-01-01 12:00:05',
            'solutionid' => 'TEST1234',
        );

        $response = local_kronosfeedws_userset::userset_create_update($userset);

        // Get Userset.
        $createdus = new userset($response['record']['id']);
        $createdus->load();
        $createdus = $createdus->to_array();
        $expectus = array(
            'id' => $response['record']['id'],
            'name' => 'testusersetname_updated',
            'display' => 'Userset Description (Updated)',
            'field_testfield' => 'Test field (Updated)',
            'parent' => 0,
            $solutionidfield => 'TEST1234'
        );

        foreach ($expectus as $param => $val) {
            $this->assertArrayHasKey($param, $createdus);
            $this->assertEquals($val, $createdus[$param]);
        }
    }
}
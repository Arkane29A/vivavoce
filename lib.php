<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Library of interface functions and constants.
 *
 * @package     mod_vivavoce
 * @copyright   2024 Saadh Zahid<saadhzahidwork@.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function vivavoce_supports($feature) {
    switch ($feature) {
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_vivavoce into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_vivavoce_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function vivavoce_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();

    $id = $DB->insert_record('vivavoce', $moduleinstance);

    return $id;
}

/**
 * Updates an instance of the mod_vivavoce in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_vivavoce_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function vivavoce_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('vivavoce', $moduleinstance);
}

/**
 * Removes an instance of the mod_vivavoce from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function vivavoce_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('vivavoce', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('vivavoce', array('id' => $id));

    return true;
}

/**
 * Is a given scale used by the instance of mod_vivavoce?
 *
 * This function returns if a scale is being used by one mod_vivavoce
 * if it has support for grading and scales.
 *
 * @param int $moduleinstanceid ID of an instance of this module.
 * @param int $scaleid ID of the scale.
 * @return bool True if the scale is used by the given mod_vivavoce instance.
 */
function vivavoce_scale_used($moduleinstanceid, $scaleid) {
    global $DB;

    if ($scaleid && $DB->record_exists('vivavoce', array('id' => $moduleinstanceid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of mod_vivavoce.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale.
 * @return bool True if the scale is used by any mod_vivavoce instance.
 */
function vivavoce_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid && $DB->record_exists('vivavoce', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the given mod_vivavoce instance.
 *
 * Needed by {@see grade_update_mod_grades()}.
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param bool $reset Reset grades in the gradebook.
 * @return void.
 */
function vivavoce_grade_item_update($moduleinstance, $reset=false) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $item = array();
    $item['itemname'] = clean_param($moduleinstance->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;

    if ($moduleinstance->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax']  = $moduleinstance->grade;
        $item['grademin']  = 0;
    } else if ($moduleinstance->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid']   = -$moduleinstance->grade;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }
    if ($reset) {
        $item['reset'] = true;
    }

    grade_update('/mod/vivavoce', $moduleinstance->course, 'mod', 'mod_vivavoce', $moduleinstance->id, 0, null, $item);
}

/**
 * Delete grade item for given mod_vivavoce instance.
 *
 * @param stdClass $moduleinstance Instance object.
 * @return grade_item.
 */
function vivavoce_grade_item_delete($moduleinstance) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('/mod/vivavoce', $moduleinstance->course, 'mod', 'vivavoce',
                        $moduleinstance->id, 0, null, array('deleted' => 1));
}

/**
 * Update mod_vivavoce grades in the gradebook.
 *
 * Needed by {@see grade_update_mod_grades()}.
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param int $userid Update grade of specific user only, 0 means all participants.
 */
function vivavoce_update_grades($moduleinstance, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    // Populate array of grade objects indexed by userid.
    $grades = array();
    grade_update('/mod/vivavoce', $moduleinstance->course, 'mod', 'mod_vivavoce', $moduleinstance->id, 0, $grades);
}

function vivavoce_get_coursemodule_info() {
    return array(
        // Plugin name and version
        'name' => 'Your Plugin Name',
        'version' => 2024041100, // Replace with your version number

        // Component information
        'component' => 'mod_vivavoce', // Name of your plugin component

        // Additional information displayed when the info button is clicked
        'description' => 'This plugin provides functionality for...',
        'dependencies' => array(
            'mod_dependency' => 2019010100, // Replace with the version number of any required dependencies
        ),
        'features' => array(
            'feature1' => true, // Additional features provided by your plugin
            'feature2' => false,
        ),
        'lang' => array(
            'en' => array(
                'vivavoce' => 'Your Plugin Name', // Language strings
                'pluginname_help' => 'Helpful information about your plugin...',
            ),
        ),
    );

}


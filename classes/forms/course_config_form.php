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
 * @package    block_quickmail
 * @copyright  2008-2017 Louisiana State University
 * @copyright  2008-2017 Adam Zapletal, Chad Mazilly, Philip Cali, Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_quickmail\forms;

require_once $CFG->libdir . '/formslib.php';

use block_quickmail_plugin;

class course_config_form extends \moodleform {

    public $context;

    public $user;

    public $course;

    public $course_config;

    public function definition() {

        $mform =& $this->_form;

        // set the context
        $this->context = $this->_customdata['context'];
        
        // set the user
        $this->user = $this->_customdata['user'];

        // set the course
        $this->course = $this->_customdata['course'];

        // set this course config values
        $this->course_config = block_quickmail_plugin::_c('', $this->course->id);

        // restore flag
        $mform->addElement('hidden', 'restore_flag');
        $mform->setType('restore_flag', PARAM_INT);
        $mform->setDefault('restore_flag', 0);

        ////////////////////////////////////////////////////////////
        ///  allow students (select, based on global setting)
        ////////////////////////////////////////////////////////////
        if ($this->should_show_allow_students()) {
            $mform->addElement('select', 'allowstudents', block_quickmail_plugin::_s('allowstudents'), $this->get_yes_or_no_options());
            $mform->setDefault('allowstudents', $this->course_config['allowstudents']);
        } else {
            $mform->addElement('hidden', 'allowstudents');
            $mform->setType('allowstudents', PARAM_INT);
            $mform->setDefault('allowstudents', 0);
        }

        ////////////////////////////////////////////////////////////
        ///  role selection (select)
        ////////////////////////////////////////////////////////////
        $mform->addElement('select', 'roleselection', block_quickmail_plugin::_s('select_roles'), $this->get_all_available_roles())->setMultiple(true);
        $mform->getElement('roleselection')->setSelected($this->get_selected_role_ids_array());
        $mform->addRule('roleselection', null, 'required');

        ////////////////////////////////////////////////////////////
        ///  prepend class (select)
        ////////////////////////////////////////////////////////////
        $mform->addElement('select', 'prepend_class', block_quickmail_plugin::_s('prepend_class'), $this->get_prepend_class_options());
        $mform->setDefault('prepend_class', $this->course_config['prepend_class']);

        ////////////////////////////////////////////////////////////
        ///  receipt (select)
        ////////////////////////////////////////////////////////////
        $mform->addElement('select', 'receipt', block_quickmail_plugin::_s('receipt'), $this->get_yes_or_no_options());
        $mform->setDefault('receipt', $this->course_config['receipt']);

        ////////////////////////////////////////////////////////////
        ///  default output channel (based on global setting)
        ////////////////////////////////////////////////////////////
        if ($this->should_show_default_output_channel()) {
            $mform->addElement('select', 'default_output_channel', block_quickmail_plugin::_s('default_output_channel'), $this->get_default_output_channel_options());
            $mform->setDefault('default_output_channel', $this->course_config['default_output_channel']);
        } else {
            $mform->addElement('static', 'default_output_channel', block_quickmail_plugin::_s('default_output_channel'), $this->display_default_output_channel());
        }

        ////////////////////////////////////////////////////////////
        ///  buttons
        ////////////////////////////////////////////////////////////
        $buttons = [
            $mform->createElement('submit', 'save', block_quickmail_plugin::_s('save_configuration')),
            $mform->createElement('submit', 'reset', block_quickmail_plugin::_s('reset')),
            $mform->createElement('cancel', 'cancel', block_quickmail_plugin::_s('cancel'))
        ];
        
        $mform->addGroup($buttons, 'actions', '&nbsp;', array(' '), false);
    }

    /**
     * Reports whether or not the course configuration form should display "allow students" option (based on global configuration)
     * 
     * @return bool
     */
    private function should_show_allow_students()
    {
        return block_quickmail_plugin::_c('allowstudents') !== -1;
    }

    /**
     * Reports whether or not the course configuration form should display "default output channel" option (based on global configuration)
     * 
     * @return bool
     */
    private function should_show_default_output_channel()
    {
        return block_quickmail_plugin::_c('output_channels_available') == 'all';
    }

    /**
     * Returns a yes/no option selection array
     * 
     * @return array
     */
    private function get_yes_or_no_options()
    {
        return [
            0 => get_string('no'), 
            1 => get_string('yes')
        ];
    }

    /**
     * Returns all available roles for configuration options
     * 
     * @return array
     */
    private function get_all_available_roles()
    {
        return role_fix_names(get_all_roles($this->context), $this->context, ROLENAME_ALIAS, true);
    }

    /**
     * Returns the currently selected role ids as array
     * 
     * @return array
     */
    private function get_selected_role_ids_array()
    {
        return explode(',', $this->course_config['roleselection']);
    }

    /**
     * Returns the options for "default output channel" setting
     * 
     * @return array
     */
    private function get_default_output_channel_options()
    {
        return [
            'message' => block_quickmail_plugin::_s('output_channel_message'),
            'email' => block_quickmail_plugin::_s('output_channel_email')
        ];
    }

    /**
     * Returns the options for "prepend class" setting
     * 
     * @return array
     */
    private function get_prepend_class_options()
    {
        return [
            0 => get_string('none'),
            'idnumber' => get_string('idnumber'),
            'shortname' => get_string('shortname'),
            'fullname' => get_string ('fullname')
        ];
    }

    /**
     * Returns the string for current forced output channel
     * 
     * @return string
     */
    private function display_default_output_channel()
    {
        $key = block_quickmail_plugin::_c('output_channels_available');

        return block_quickmail_plugin::_s('output_channel_' . $key);
    }

}

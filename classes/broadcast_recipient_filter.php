<?php

require_once "$CFG->dirroot/user/filters/lib.php";

class block_quickmail_broadcast_recipient_filter {

    public $filter_params;
    public $extra_params;
    public $draft_message;
    public $ufilter;
    public $filter_result_sql;
    public $filter_result_params;
    public $result_users;
    public $display_users;
    public $result_user_count = null;
    
    public static $session_key = 'user_filtering';

    /*
     * Necessary filter navigation key and their default values
     */
    public static $default_filter_params = [
        'page' => 1,
        'per_page' => 20,
        'sort_by' => 'lastname',
        'sort_dir' => 'asc'
    ];

    public $supported_fields = [
        'realname'          => 1,
        'lastname'          => 1,
        'firstname'         => 1,
        'email'             => 1,
        'city'              => 1,
        'country'           => 1,
        'confirmed'         => 1,
        'suspended'         => 1,
        'profile'           => 1,
        'courserole'        => 0,
        'systemrole'        => 0,
        'username'          => 0,
        'cohort'            => 1,
        'firstaccess'       => 1,
        'lastaccess'        => 0,
        'neveraccessed'     => 1,
        'timemodified'      => 1,
        'nevermodified'     => 1,
        'auth'              => 1,
        'mnethostid'        => 1,
        'language'          => 1,
        'firstnamephonetic' => 1,
        'lastnamephonetic'  => 1,
        'middlename'        => 1,
        'alternatename'     => 1
    ];

    /**
     * Construct a wrapper instance for moodle's user_filtering class
     * 
     * @param array  $filter_params
     * @param array  $extra_params
     * @param mixed  $draft_message  optional draft message, defaults to null
     */
    public function __construct($filter_params, $extra_params, $draft_message = null) {
        $this->filter_params = $filter_params;
        $this->extra_params = $extra_params;
        $this->draft_message = $draft_message;
        $this->ufilter = new user_filtering($this->supported_fields, null, $this->extra_params);
        $this->set_filter_sql_results();
        $this->set_result_users();
        $this->set_display_users();
    }

    public static function make($page_params, $draft_message = null)
    {
        $filter_params = self::get_filter_params($page_params);
        $extra_params = self::get_extra_params($draft_message);

        return new self($filter_params, $extra_params, $draft_message);
    }

    /**
     * Gets normalized filter params necessary for navigation of this filter instance from a given array of params
     * 
     * @param  array  $params
     * @return array
     */
    public static function get_filter_params($params)
    {
        $filter_params = [];

        foreach (array_keys(self::$default_filter_params) as $key) {
            $filter_params[$key] = array_key_exists($key, $params)
                ? $params[$key]
                : self::$default_filter_params[$key];
        }

        return $filter_params;
    }

    /**
     * Gets additional query string params needed for external use outside of this filter instance
     * 
     * @param mixed  $draft_message  optional draft message, defaults to null
     * @return array
     */
    public static function get_extra_params($draft_message = null)
    {
        return ! empty($draft_message) 
            ? ['draftid' => $draft_message->get('id')] 
            : [];
    }

    /**
     * Sets ufilter sql results and params
     */
    private function set_filter_sql_results()
    {
        list($sql, $params) = $this->ufilter->get_sql_filter();

        $this->filter_result_sql = $sql;
        $this->filter_result_params = $params;
    }

    /**
     * Unsets any session data for this filter
     * 
     * @return void
     */
    public function clear_session()
    {
        global $SESSION;

        $key = self::$session_key;

        unset($SESSION->$key);
    }

    /**
     * Sets the filtered "result" users
     */
    public function set_result_users()
    {
        $this->result_users = empty($this->filter_result_sql) 
            ? []
            : get_users_listing($this->filter_params['sort_by'], $this->filter_params['sort_dir'], 0, 0, '', '', '', $this->filter_result_sql, $this->filter_result_params);
    }

    /**
     * Sets the filtered "result" users to display as per "page" and "per_page" settings
     */
    public function set_display_users()
    {
        if (empty($this->result_users)) {
            $this->display_users = [];
        } else {
            $offset = ($this->filter_params['page'] * $this->filter_params['per_page']) - $this->filter_params['per_page'];

            $this->display_users = array_slice($this->result_users, $offset, $this->filter_params['per_page'], true);
        }
    }

    /**
     * Returns the count of users in the current results, caching the result for later calls
     * 
     * @return int
     */
    public function get_result_user_count()
    {
        if (is_null($this->result_user_count)) {
            $this->result_user_count = count($this->result_users);
        }

        return $this->result_user_count;
    }

    /**
     * Returns the current draft id, if any, defaulting to 0
     * 
     * @return int
     */
    public function get_draft_id()
    {
        return ! empty($this->draft_message) 
            ? $this->draft_message->get('id')
            : 0;
    }

    /**
     * Renders the user_filtering "add" magic
     * 
     * @return string
     */
    public function render_add()
    {
        return $this->ufilter->display_add();
    }
    
    /**
     * Renders the user_filtering "active" magic
     * 
     * @return string
     */
    public function render_active()
    {
        return $this->ufilter->display_active();
    }

    /**
     * Renders a pagination bar for the result users
     * 
     * @return string
     */
    public function render_paging_bar()
    {
        global $OUTPUT;

        echo $OUTPUT->paging_bar($this->get_result_user_count(), $this->filter_params['page'], $this->filter_params['per_page'],
            new moodle_url('/blocks/quickmail/broadcast.php', [
                'draftid' => $this->get_draft_id(),
                'sort_by' => $this->filter_params['sort_by'],
                'sort_dir' => $this->filter_params['sort_dir'],
                'per_page' => $this->filter_params['per_page'],
            ]
        ));
    }

}
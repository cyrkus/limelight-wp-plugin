<?php

class LimelightAPI {

    /**
     * A list of actions that the Limelight API wil accept
     *
     * @var      array
     */
    public static $api_actions = array(
        'invite'  => 'Add + Send Invite',
        'create'  => 'Add Only',
        'confirm' => 'RSVP + Send Confirmation',
        'rsvp'    => 'RSVP Only',
    );

    /**
     * Make a request to the API
     *
     * @param   string $type    HTTP request type
     * @param   string $URL     API Endpoint path
     * @param   array $fields   Data to pass to the API
     * @param   array $options  API Connection options - endpoint, username, password
     *
     * @return  object          JSON Decoded API Response
     */
    public static function make_api_request($type, $URL, $fields=false, $options=false) {

        if ( !$options ) {
            $options = get_option('limelight_options');
            $options['password'] = Limelight::decrypt_string($options['password'], Limelight::$crypt_key);
        }

        if ( strtolower($type) == 'put' ) {
            $type = 'POST';
            $fields['_method'] = 'put';
        }

        // Create + Submit POST Request to API
        $ch = curl_init();

        if ($_SERVER['HTTP_HOST'] == 'local.wp') curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_USERPWD, $options['username'].':'.$options['password']);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);

        $cURL = $options['endpoint'].'/v1/'.$URL;
        if ( $fields !== false ) {
            $fields_string = '';

            // URL-ify the data for the POST
            foreach ($fields as $k => $val) { $fields_string .= $k.'='.$val.'&'; }
            rtrim($fields_string, '&');

            if (strtolower($type) == 'get' || strtolower($type) == 'delete') {
                $cURL .= '?' . $fields_string;
            } else {
                curl_setopt($ch, CURLOPT_POST, count($fields));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            }
        }

        curl_setopt($ch, CURLOPT_URL, $cURL);

        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result = curl_exec($ch);

        curl_close($ch);

        if ($result == 'Invalid credentials.') {
            return false;
            // trigger_error('Limelight API Credentials Invalid.', E_USER_ERROR);
        }

        return json_decode($result);
    }

    public static function get_events() {

        $res = self::make_api_request('GET', 'events');
        unset($res->client);

        if ($res->events) foreach ($res->events as $k => $v)
        {
            unset($res->events[$k]->attendees);
            unset($res->events[$k]->settings);
            unset($res->events[$k]->languages);
            unset($res->events[$k]->modes);
        }

        return $res->events;
    }

    public static function get_event($id) {

        $res = self::make_api_request('GET', 'events/'.$id);
        return isset($res->event) ? $res->event : false;
    }

    public static function get_event_inputs($id) {

        $inputs = array();
        $lang_id = 0;

        if ( is_numeric($id) && $res = self::make_api_request('GET', 'events/'.$id)) {

            // print '<pre>'; print_r($res); die();

            foreach ($res->event->languages as $lang)
                if ($lang->default == 1) $lang_id = $lang->id;

            foreach ($res->event->features as $feature)
                if ($feature->type == 'guest_list') $inputs = $feature->form->inputs;

            foreach ($inputs as $k => $input)
                $inputs[$k]->label = $inputs[$k]->label->{$lang_id};
        }

        return $inputs;
    }

    public static function get_attendee($id) {

        $res = self::make_api_request('GET', 'attendees/' . $id);

        return $res->attendee ? $res->attendee : false;
    }

    public static function add_attendee($fields) {

        $res = self::make_api_request('POST', 'attendees', $fields);

        return $res->attendee;
    }

    public static function update_attendee($id, $fields) {

        $res = self::make_api_request('PUT', 'attendees/'.$id, $fields);

        return $res->attendee;
    }

    public static function delete_attendee($id, $force=false) {

        $fields = $force ? array('force'=>1) : array();

        return self::make_api_request('DELETE', 'attendees/'.$id, $fields);
    }

    public static function restore_attendee($id) {

        return self::make_api_request('PUT', 'attendees/'.$id, array('restore'=>1));
    }

}

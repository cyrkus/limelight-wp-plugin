<?php

class LimelightModel {

    public static function get_form_settings_table_name() {

        global $wpdb;
        return $wpdb->prefix . Limelight::$prefix . "form_settings";
    }

    /**
     * Setup the database tables
     */
    public static function setup_database() {

        global $wpdb;

        require_once(ABSPATH . '/wp-admin/includes/upgrade.php');

        if (!empty($wpdb->charset))
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if (!empty($wpdb->collate))
            $charset_collate .= " COLLATE $wpdb->collate";

        //------ FORM -----------------------------------------------
        $form_settings_table_name = self::get_form_settings_table_name();
        $sql = "CREATE TABLE $form_settings_table_name (
                form_id mediumint(8) unsigned not null,
                settings longtext,
                PRIMARY KEY (form_id)
            ) $charset_collate;";
        dbDelta($sql);
    }

    public static function get_all_settings() {

        global $wpdb;

        $form_settings_table_name = self::get_form_settings_table_name();
        $sql = "SELECT * FROM $form_settings_table_name";

        $res = $wpdb->get_results($sql);

        $forms = array();
        foreach ($res as $r) {
            $form = GFAPI::get_form($r->form_id);
            $form['settings'] = json_decode($r->settings);
            $forms[] = $form;
        }

        return $forms;
    }

    public static function get_form_settings($id) {

        global $wpdb;

        $form_settings_table_name = self::get_form_settings_table_name();
        $sql = "SELECT *
                FROM $form_settings_table_name
                WHERE form_id = $id";

        $res = $wpdb->get_results($sql);

        // initialize form settings if none are found
        if (count($res) === 0) {
            $sql = "INSERT INTO $form_settings_table_name
                    VALUES ( $id, '{}' )";
            $wpdb->get_results($sql);
            self::get_form_settings($id);
        } else {
            $settings = json_decode($res[0]->settings);

            if (!isset($settings->event_id)) $settings->event_id = false;
            if (!isset($settings->action))   $settings->action   = '';

            return $settings;
        }
    }

    public static function get_unlinked_entries($form_id) {

        global $wpdb;

        $entries = array();

        $gf_lead_table_name = GFFormsModel::get_lead_table_name();
        $gf_meta_table_name = GFFormsModel::get_lead_meta_table_name();
        $id_key = Limelight::$prefix . 'attendee_id';
        $sql = "SELECT id
                FROM $gf_lead_table_name
                WHERE id NOT IN (
                    SELECT DISTINCT lead_id
                    FROM $gf_meta_table_name
                    WHERE meta_key = '$id_key'
                )";
        $res = $wpdb->get_results($sql);

        if (count($res)) foreach ($res as $r) $entries[] = GFAPI::get_entry($r->id);

        return $entries;
    }

    public static function get_attendee_meta($form_id) {

        global $wpdb;

        $gf_meta_table_name = GFFormsModel::get_lead_meta_table_name();
        $id_key = Limelight::$prefix . 'attendee_id';
        $sql = "SELECT *
                FROM $gf_meta_table_name
                WHERE meta_key = '$id_key'
                AND form_id = '$form_id'";
        $res = $wpdb->get_results($sql);

        return $res;
    }

    public static function update_form_settings($id, $settings) {

        global $wpdb;

        // ensure there is a form settings row in the table
        $s = self::get_form_settings($id);

        $settings = json_encode($settings);

        $form_settings_table_name = self::get_form_settings_table_name();
        $sql = "UPDATE $form_settings_table_name
                SET settings = '$settings'
                WHERE form_id = $id";
        $wpdb->get_results($sql);
    }

    public static function get_entries_by_attendee_id($id) {

        global $wpdb;

        $entries = false;

        $gf_meta_table_name = GFFormsModel::get_lead_meta_table_name();
        $id_key = Limelight::$prefix . 'attendee_id';
        $sql = "SELECT *
                FROM $gf_meta_table_name
                WHERE meta_value = '$id'
                AND meta_key = '$id_key'";
        $res = $wpdb->get_results($sql);

        if (count($res)) foreach ($res as $r) $entries[] = GFAPI::get_entry($r->lead_id);

        return $entries;
    }

    public static function get_entries_by_email($email) {

        global $wpdb;

        $entries = false;

        $gf_details_table_name = GFFormsModel::get_lead_details_table_name();
        $sql = "SELECT *
                FROM $gf_details_table_name
                WHERE value = '$email'";
        $res = $wpdb->get_results($sql);

        if (count($res)) foreach ($res as $r) $entries[] = GFAPI::get_entry($r->lead_id);

        return $entries;
    }


}

<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   limelight
 * @author    7/Apps <ryan@7apps.com>
 * @license   GPL-2.0+
 * @link      http://www.7apps.com
 * @copyright 7-30-2014 7/Apps
 */

$form_id = $_GET['id'];

$events = LimelightAPI::get_events();
$form = GFFormsModel::get_form_meta_by_id($form_id)[0];
// print '<pre>'; print_r($form); die();

$form_settings = LimelightModel::get_form_settings($form_id);
$inputs = LimelightAPI::get_event_inputs($form_settings->event_id);
// print '<pre>'; print_r($form_settings); die();

$event_opts = array();
foreach ($events as $event) {
    $selected = ($form_settings->event_id == $event->id) ? 'selected' : '';
    $event_opts[] = sprintf("<option value='%d' %s>%s</option>", $event->id, $selected, $event->name);
}

function gform_field_opts($fields, $input_id, $form_settings) {

    $gform_field_opts = array();
    foreach ($fields as $field) {
        $selected = (isset($form_settings->inputs) && $form_settings->inputs->{$input_id} == $field['id']) ? 'selected' : '';
        $gform_field_opts[] = sprintf("<option value='%d' %s>%s</option>", $field['id'], $selected, $field['label']);
    }

    return $gform_field_opts;
}
?>
<div class="wrap">

    <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

    <hr>

    <h3><?php _e('Select Event', Limelight::$plugin_slug); ?></h3>
    <p><?php _e('Select an event from the list below.', Limelight::$plugin_slug); ?></p>

    <form method="post">
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">Event <span class="required">*</span></th>
                    <td>
                        <select name="event_id">
                            <option value=""></option>
                            <?php print join($event_opts); ?>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>

    <?php if (isset($form_settings->event_id) && $form_settings->event_id > 0) : ?>

    <h3><?php _e('Connect Fields', Limelight::$plugin_slug); ?></h3>
    <p><?php _e('Match each of the following fields to the gravity form inputs.', Limelight::$plugin_slug); ?></p>

        <table class="form-table">
            <tbody>
                <?php foreach ($inputs as $i) : ?>
                <tr>
                    <th scope="row"><?php print ucwords($i->label); if ($i->settings->required == true) print ' <span class="required">*</span>'; ?></th>
                    <td>
                        <select name="inputs[<?php print $i->id; ?>]">
                            <option value=""></option>
                            <?php print join(gform_field_opts($form['fields'], $i->id, $form_settings)); ?>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>

        <p class="submit">
            <input name="submit" type="submit" class="button-primary" value="<?php _e('Save', Limelight::$plugin_slug ); ?>" />
        </p>
    </form>

</div>

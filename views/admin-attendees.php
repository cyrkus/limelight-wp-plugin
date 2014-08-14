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
?>
<div class="wrap">

    <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

    <hr>

    <form method="post">
        <?php settings_fields('limelight_options'); ?>
        <?php do_settings_sections('limelight'); ?>

        <p class="submit">
            <input name="submit" type="submit" class="button-primary" value="<?php _e('Login', Limelight::$plugin_slug); ?>" />
        </p>
    </form>

</div>

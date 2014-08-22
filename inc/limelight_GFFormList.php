<?php

if (!class_exists('GFForms')) {
    die();
}

class Limelight_GFFormList {

    public static function form_list_page() {

        global $wpdb;

        if(!GFCommon::ensure_wp_version())
            return;

        echo '<hr>';

        $sort_column = empty($_GET["sort"]) ? "title" : $_GET["sort"];
        $sort_direction = empty($_GET["dir"]) ? "ASC" : $_GET["dir"];
        $active = RGForms::get("active") == "" ? null : RGForms::get("active");
        $trash = RGForms::get("trash") == "" ? false : RGForms::get("trash");
        $trash = esc_attr($trash);
        $forms = RGFormsModel::get_forms($active, $sort_column, $sort_direction, $trash);

        $form_count = RGFormsModel::get_form_count();
        ?>

        <div class="wrap <?php echo GFCommon::get_browser_class() ?>">

            <h3><?php _e("Forms", Limelight::$plugin_slug); ?></h3>

            <?php if (isset($message)) { ?>
            <div class="updated below-h2" id="message"><p><?php echo $message; ?></p></div>
            <?php } ?>

            <form id="forms_form" method="post">
                <?php wp_nonce_field('gforms_update_forms', 'gforms_update_forms') ?>
                <input type="hidden" id="action" name="action"/>
                <input type="hidden" id="action_argument" name="action_argument"/>

                <ul class="subsubsub">
                    <li><a class="<?php echo ($active === null) ? "current" : "" ?>" href="?page=limelight"><?php _e("All", Limelight::$plugin_slug); ?> <span class="count">(<span id="all_count"><?php echo $form_count["total"] ?></span>)</span></a> | </li>
                    <li><a class="<?php echo $active == "1" ? "current" : ""?>" href="?page=limelight&active=1"><?php _e("Active", Limelight::$plugin_slug); ?> <span class="count">(<span id="active_count"><?php echo $form_count["active"] ?></span>)</span></a> | </li>
                    <li><a class="<?php echo $active == "0" ? "current" : ""?>" href="?page=limelight&active=0"><?php _e("Inactive", Limelight::$plugin_slug); ?> <span class="count">(<span id="inactive_count"><?php echo $form_count["inactive"] ?></span>)</span></a> | </li>
                    <li><a class="<?php echo $active == "0" ? "current" : ""?>" href="?page=limelight&trash=1"><?php _e("Trash", Limelight::$plugin_slug); ?> <span class="count">(<span id="trash_count"><?php echo $form_count["trash"] ?></span>)</span></a></li>
                </ul>

                <table class="widefat fixed" cellspacing="0">
                    <thead>
                        <tr>
                            <?php
                            $dir = $sort_column == "id" && $sort_direction == "ASC" ? "DESC" : "ASC";
                            $url_id = admin_url("admin.php?page=limelight&sort=id&dir=$dir&trash=$trash");
                            ?>
                            <th scope="col" id="id" class="manage-column" style="width:50px;cursor:pointer;" onclick="document.location='<?php echo $url_id; ?>'"><?php _e("Id", Limelight::$plugin_slug);?></th>
                            <?php
                            $dir = $sort_column == "title" && $sort_direction == "ASC" ? "DESC" : "ASC";
                            $url_title = admin_url("admin.php?page=limelight&sort=title&dir=$dir&trash=$trash");
                            ?>
                            <th scope="col" id="title" class="manage-column column-title" style="cursor:pointer;" onclick="document.location='<?php echo $url_title; ?>'"><?php _e("Title", Limelight::$plugin_slug); ?></th>
                            <th scope="col" id="event" class="manage-column" style=""><?php _e("Event", Limelight::$plugin_slug) ?></th>
                            <th scope="col" id="action" class="manage-column" style=""><?php _e("Action", Limelight::$plugin_slug) ?></th>
                        </tr>
                    </thead>

                    <tfoot>
                        <tr>
                            <th scope="col" id="id" class="manage-column" style="cursor:pointer;" onclick="document.location='<?php echo $url_id; ?>'"><?php _e("Id", Limelight::$plugin_slug) ?></th>
                            <th scope="col" id="title" style="cursor:pointer;" class="manage-column column-title" onclick="document.location='<?php echo $url_title; ?>'"><?php _e("Title", Limelight::$plugin_slug) ?></th>
                            <th scope="col" id="event" class="manage-column" style=""><?php _e("Event", Limelight::$plugin_slug) ?></th>
                            <th scope="col" id="action" class="manage-column" style=""><?php _e("Action", Limelight::$plugin_slug) ?></th>
                        </tr>
                    </tfoot>

                    <tbody class="list:user user-list">
                        <?php
                        if(sizeof($forms) > 0){
                            $alternate_row = false;
                            foreach($forms as $form){
                                $gf_form_locking = new GFFormLocking();
                                ?>
                                <tr class='author-self status-inherit <?php $gf_form_locking->list_row_class($form->id); ?> <?php echo ($alternate_row = !$alternate_row) ? 'alternate' : '' ?>' valign="top" data-id="<?php echo esc_attr($form->id) ?>">
                                    <td class="column-id"><?php echo $form->id ?></td>
                                    <td class="column-title">
                                        <?php
                                        if ($trash) :
                                            echo $form->title;
                                        else :
                                            ?>
                                            <strong><a class="row-title" disabled="<?php disabled(true, $trash); ?>"
                                                       href="admin.php?page=limelight&id=<?php echo $form->id ?>"
                                                       title="<?php _e("Edit", Limelight::$plugin_slug) ?>"><?php echo $form->title ?></a></strong>
                                            <?php $gf_form_locking->lock_info($form->id);
                                        endif
                                        ?>
                                        <div class="row-actions">

                                            <a class="" onclick="" title=<?php _e("Edit this form", Limelight::$plugin_slug); ?> href="?page=limelight&amp;id=2" target="">Edit</a>

                                        </div>
                                    </td>
                                    <td class="column-date"><strong><?php print Limelight::get_event_name($form->id) ?></strong></td>
                                    <td class="column-date"><strong><?php print Limelight::get_action_type($form->id) ?></strong></td>
                                </tr>
                                <?php
                            }
                        }
                        else{
                            ?>
                            <tr>
                                <td colspan="6" style="padding:20px;">
                                    <?php
                                    if($trash)
                                        echo __("There are no forms in the trash.", Limelight::$plugin_slug);
                                    else
                                        echo sprintf(__("You don't have any forms. Let's go %screate one%s!", Limelight::$plugin_slug), '<a href="admin.php?page=gf_new_form">', "</a>");

                                    ?>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>

            </form>
        </div>
        <?php
    }

}

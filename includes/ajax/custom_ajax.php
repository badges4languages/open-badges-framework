<?php
/**
 * This is the ajax file.
 *
 * @author     Nicolas TORION
 * @package    custom_ajax.php
 * @subpackage includes/ajax
 * @since      0.6.3
 */

require_once '../../../../../wp-load.php';

require_once plugin_dir_path(dirname(__FILE__)) . 'utils/functions.php';
//mimic the actuall admin-ajax
define('DOING_AJAX', true);

if (!isset($_POST['action'])) {
    die('-1');
}

//Typical headers
header('Content-Type: text/html');
send_nosniff_header();

//Disable caching
header('Cache-Control: no-cache');
header('Pragma: no-cache');

$action = esc_attr(trim($_POST['action']));

//A bit of security
$allowed_actions = array(
    'action_select_class',
    'action_select_badge',
    'action_save_metabox_students',
    'action_languages_form',
    'action_save_comment',
    'action_select_description_preview',
    'send_message_badge'
);

/**
 * AJAX action to save metabox of students in class job listing type.
 *
 * @author Nicolas TORION
 * @since  0.4.1
 */
add_action('CUSTOMAJAX_action_save_metabox_students', 'action_save_metabox_students');

function action_save_metabox_students() {
    $post_id = $_POST['post_id'];
    update_post_meta($post_id, '_class_students', $_POST['class_students']);
    echo $_POST['class_students'];
}


/**
 * AJAX action to load all languages in a select form
 *
 * @author Nicolas TORION
 * @since  0.6.1
 */
add_action('CUSTOMAJAX_action_languages_form', 'action_languages_form');
function action_languages_form() {
    show_all_the_language($category = $_POST['slug']);
}

/**
 * AJAX action to load a preview of the description selected in a select form
 *
 * @author Nicolas TORION
 * @since  0.6.2
 */
add_action('CUSTOMAJAX_action_select_description_preview', 'action_select_description_preview');
function action_select_description_preview() {
    $badges = get_all_badges();
    foreach ($badges as $badge) {
        if ($_POST['badge_name'] == $badge->post_name) {
            $badge_description = get_badge_descriptions($badge)[$_POST['language_description_selected']];
            echo str_replace("\n", "<br>", "<p>" . $badge_description . "</p><br>");
        }
    }
}

/**
 * AJAX action to load the classes corresponding to the level and the language selected
 *
 * @author Nicolas TORION
 * @since  0.6.3
 */
add_action('CUSTOMAJAX_action_select_class', 'action_select_class');
function action_select_class() {

    global $current_user;
    wp_get_current_user();

    if (in_array("administrator", $current_user->roles) || in_array("editor", $current_user->roles)) {
        $classes = get_all_classes_zero();
        if (is_plugin_active("wp-job-manager/wp-job-manager.php")) {
            $classes_job_listing = get_all_classes();
            $classes = array_merge($classes, $classes_job_listing);
        }
    } elseif (in_array("academy", $current_user->roles)) {
        if (is_plugin_active("wp-job-manager/wp-job-manager.php")) {
            $classes = get_classes_teacher($current_user->user_login);
        }
    }


    $settings_id_links = get_settings_links();

    if (empty($classes)) {
        if (in_array("teacher", $current_user->roles)) {
            _e('<a href="' . get_page_link($settings_id_links["link_not_academy"]) . '" target="_blank">You need an academy account in order to create your own classes.</a>', 'badges-issuer-for-wp');
        } elseif (in_array("academy", $current_user->roles)) {
            _e('<a href="' . get_page_link($settings_id_links["link_create_new_class"]) . '" target="_blank">Don\'t you want to create a specific class for that student(s) ?</a>', 'badges-issuer-for-wp');
        }
    } else {
        if (count($classes) > 1) {
            $input_type = "radio";
        } else {
            $input_type = "checkbox";
        }

        if (in_array("administrator", $current_user->roles) || in_array("editor", $current_user->roles)) {
            echo '</br><b>Default Class:</b><br>';
            foreach ($classes as $class) {
                if ($class->post_type == 'class') {
                    echo '<div class="rdi-tab">';
                    echo '<label  for="class_' . $class->ID . '">' . $class->post_title . ' </label><input name="class_for_student" id="class_' . $class->ID . '" type="' . $input_type . '" value="' . $class->ID . '"/>';
                    echo '</div> &nbsp;';
                }
            }
            echo '</br></br>';
        }
        if (in_array("academy", $current_user->roles) || in_array("administrator", $current_user->roles) || in_array("editor", $current_user->roles)) {
            echo '</br><b>Specific Class:</b>';
            foreach ($classes as $class) {
                if ($class->post_type == 'job_listing') {
                    $languages = get_the_terms($class->ID, 'job_listing_category');
                    if ((in_array("academy", $current_user->roles) && in_array($_POST['language_selected'], $languages)) || in_array("administrator", $current_user->roles) || in_array("editor", $current_user->roles)) {
                        echo '<span style="margin-left:20px;"></span>';
                        echo '<label for="class_' . $class->ID . '">' . $class->post_title . ' </label><input name="class_for_student" id="class_' . $class->ID . '" type="' . $input_type . '" value="' . $class->ID . '"/>';
                    }
                }
            }
        }
    }
}

/**
 * AJAX action to load the badges of the level given.
 *
 * @author Nicolas TORION
 * @since  0.6.2
 * @since  X.X.X recoded and made it easy
 */
add_action('CUSTOMAJAX_action_select_badge', 'action_select_badge');
function action_select_badge() {
$badges = get_all_badges();

global $current_user;
wp_get_current_user();

if (in_array("administrator", $current_user->roles) || in_array("academy", $current_user->roles) || in_array("editor", $current_user->roles)) {
    $badges_corresponding = get_all_badges_level($badges, $_POST['level_selected'], $certification = true);
} else {
    $badges_corresponding = get_all_badges_level($badges, $_POST['level_selected']);
}

usort($badges_corresponding, function ($a, $b) {
    return strcmp($a->post_title, $b->post_title);
});


$first_certified_badge = true;
foreach ($badges_corresponding as $badge) {
    if (get_post_meta($badge->ID, '_certification', true) == "not_certified") {
        echo '<input type="radio" name="input_badge_name" class="input-badge input-hidden" id="' . $_POST['form'] . $badge->post_title . '" value="' . $badge->post_name . '"/>';
        echo '<label for="' . $_POST['form'] . $badge->post_title . '">';
        echo '<img class="img-send-badge" src="';
        if (get_the_post_thumbnail_url($badge->ID)) {
            echo get_the_post_thumbnail_url($badge->ID, 'thumbnail');
            echo '" /></label>';
            echo '</br><b>' . $badge->post_title . '</b>';
        } else {
            echo plugins_url('../../assets/default-badge-thumbnail.png', __FILE__);
            echo '" width="40px" height="40px" /></label>';
        }
        echo "</div>";

    } elseif (get_post_meta($badge->ID, '_certification', true) == "certified") {
        echo '<div">';
        echo '<br><b>Certified Badges : </b><br>';
        if ($first_certified_badge) {
            $first_certified_badge = false;
        }

        echo '<input onclick="jQuery(showDesc());"  type="radio" name="input_badge_name" class="input-badge input-hidden" id="' . $_POST['form'] . $badge->post_title . '" value="' . $badge->post_name . '"/>
                    <label for="' . $_POST['form'] . $badge->post_title . '">
                    <img class="img-send-badge" src="';
        if (get_the_post_thumbnail_url($badge->ID)) {
            echo get_the_post_thumbnail_url($badge->ID, 'thumbnail');
            echo '" width="40px" height="40px" /></label>';
            echo '</br><b>' . $badge->post_title . '</b>';
        } else {
            echo plugins_url('../../assets/default-badge-thumbnail.png', __FILE__);
            echo '" width="40px" height="40px" /></label>';
        }
    }
}

?>
<script>
    <?php

    /**
     *
     *
     * @author Nicolas TORION
     * @since  0.6.2
     */
    $badges = get_all_badges();

    foreach ($badges as $badge) {
        $descriptions = get_badge_descriptions($badge);
        echo 'var _' . str_replace("-", "_", $badge->post_name) . '_description_languages = [';
        $i = 0;
        foreach ($descriptions as $lang => $description) {
            echo "'" . $lang . "'";
            if ($i != (sizeof($descriptions) - 1)) {
                echo ', ';
            }
            $i++;
        }
        echo "]; \n";
    }
    ?>

    <?php
    }

    /**
     * AJAX action to save the modifications made on a comment
     *
     * @author Nicolas TORION
     * @since  0.5.1
     */
    add_action('CUSTOMAJAX_action_save_comment', 'action_save_comment');

    function action_save_comment() {
        $comment_id = $_POST['comment_id'];
        $comment_text = $_POST['comment_text'];

        $comment_arr = array();
        $comment_arr['comment_ID'] = $comment_id;
        $comment_arr['comment_content'] = $comment_text;

        wp_update_comment($comment_arr);
    }

    /**
     * AJAX action to salve and send the badge.
     *
     * @author Alessandro RICCARDI
     * @since  0.5.1
     * @since  X.X.X
     */
    add_action('CUSTOMAJAX_send_message_badge', 'send_message_badge');
            
    function send_message_badge() {

        /* Variables */
        $language = $_POST['language'];
        $level = $_POST['level'];
        $badge_name = $_POST['badge_name'];
        $language_description = $_POST['language_description'];
        $class_student = $_POST['class_student'];
        $class_teacher = $_POST['class_teacher'];
        $mails = $_POST['mail'];
        $comment = $_POST['comment'];
        $sender = $_POST['sender'];
        $curForm = $_POST['curForm'];
        $class = null;
        $notsent = array();

        /* Get user */
        global $current_user;
        wp_get_current_user();


        /* JSON file */
        $url_json_files = content_url('uploads/badges-issuer/json/');
        $path_dir_json_files = plugin_dir_path(dirname(__FILE__)) . '../../../uploads/badges-issuer/json/';

        /* Check if there are sufficient param */
        if (!isset($language) || !isset($level) || !isset($badge_name) ||
            !isset($language_description) || !isset($comment) || !isset($sender)) {

            echo "No enough information";

        } else {

            /* Get badge CERTIFICATION */
            $badge_others_items = get_badge($badge_name, $language_description);
            $certification = get_post_meta($badge_others_items['id'], '_certification', true);

            /* Set the email(s) */
            if (isset($mails)) {
                $mails_list = explode("\n", $mails);
            } else {
                $mails_list[0] = $sender;
            }

            /* Set the right class */
            if (check_the_rules($current_user->roles, "academy", "teacher")) {
                $class = $class_student;
            } elseif (check_the_rules($current_user->roles, "teacher")) {
                $class = $class_teacher;
            }

            /* Creation of the badge */
            $badge = new Badge($badge_others_items['name'], level, $language, $certification, $comment,
                $badge_others_items['description'], $language_description, $badge_others_items['image'],
                $url_json_files, $path_dir_json_files);

            /* Sending all the email */
            foreach ($mails_list as $mail) {

                /* operation for system not unix */
                $mail = str_replace("\r", "", $mail);

                $badge->create_json_files($mail);

                //SENDING THE EMAIL

                if (!$badge->send_mail($mail, $class)) {
                    $notsent[] = $mail;
                } else {
                    if ($curForm == "a") {
                        $badge->add_student_to_class_zero($mail);
                    }

                    $badge->add_student_to_class($mail, $class);
                    $badge->add_badge_to_user_profile($mail, $_POST['sender'], $class);
                }
            }

            if (sizeof($notsent) > 0) {
                $message = "Badge not sent to these persons : ";
                foreach ($notsent as $notsent_mail) {
                    $message = $message . $notsent_mail . " ";
                }
                display_error_message($message);
            } else {
                display_success_message("Badge sent to all persons.");
            }
        }
    }


    if (in_array($action, $allowed_actions)) {
        if (is_user_logged_in()) {
            do_action('CUSTOMAJAX_' . $action);
        }
    } else {
        die('-1');
    }
?>
<?php
/*
Plugin Name: CF7 Quiz Email Templates
Description: A plugin to select CF7 forms and send email templates to users.
Version: 1.0
Author: Sahil Ahlawat
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

function cf7_quiz_email_templates_admin_menu() {
    add_menu_page(
        'CF7 Quiz Email Templates',
        'CF7 Quiz Email Templates',
        'manage_options',
        'cf7-quiz-email-templates',
        'cf7_quiz_email_templates_admin_page',
        'dashicons-email',
        6
    );
}

add_action('admin_menu', 'cf7_quiz_email_templates_admin_menu');
function sa_get_all_cf7_forms() {
    $args = array('post_type' => 'wpcf7_contact_form', 'posts_per_page' => -1);
    $cf7forms = get_posts( $args );
    return $cf7forms;
}
function cf7_quiz_email_templates_admin_page(){
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Check if form is submitted
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Sanitize and save the option values
        update_option('sa_cfe_tem_cf7_form', array_map('sanitize_text_field', $_POST['sa_cfe_tem_cf7_form']));
        update_option('sa_cfe_tem_template_area_1', sanitize_textarea_field($_POST['sa_cfe_tem_template_area_1']));
        update_option('sa_cfe_tem_template_area_2', sanitize_textarea_field($_POST['sa_cfe_tem_template_area_2']));
        update_option('sa_cfe_tem_yes_no_fields', sanitize_text_field($_POST['sa_cfe_tem_yes_no_fields']));
        update_option('sa_cfe_tem_noneofabove_fields', sanitize_text_field($_POST['sa_cfe_tem_noneofabove_fields']));
        update_option('sa_cfe_tem_user_email_field', sanitize_text_field($_POST['sa_cfe_tem_user_email_field']));
    }

    // Get the option values
    $cf7_form = get_option('sa_cfe_tem_cf7_form');
    if(empty($cf7_form)){
        $cf7_form = [];
    }
    $template_area_1 = get_option('sa_cfe_tem_template_area_1');
    $template_area_2 = get_option('sa_cfe_tem_template_area_2');
    $yes_no_fields = get_option('sa_cfe_tem_yes_no_fields');
    $noneofabove_fields = get_option('sa_cfe_tem_noneofabove_fields');
    $user_email_field = get_option('sa_cfe_tem_user_email_field');

    // Start output buffering
    ob_start();
    ?>
    <style>
        .wrap {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
        }
        h1 {
            color: #444;
            text-align: center;
        }
        form {
            padding: 20px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        select, textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        input[type="submit"] {
            padding: 10px 20px;
            background-color: #007cba;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #005a87;
        }
    </style>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="" method="post">
            <?php
            // Output security fields
            settings_fields('cf7_quiz_email_templates_options');
            $cf7_plugin = "contact-form-7/wp-contact-form-7.php";
            if(is_plugin_active( $cf7_plugin )){
                // Add your fields here
                // 1. Select field with multiple options to select capability
                $forms = sa_get_all_cf7_forms(); // Replace this with the function that gets all CF7 forms
                echo "<h2>Select quiz contact forms</h2>";
                if($forms){
                    echo '<select name="sa_cfe_tem_cf7_form[]" multiple>';
                    foreach ($forms as $form) {
                        echo '<option value="' . $form->ID . '"' . (in_array($form->ID, $cf7_form) ? ' selected' : '') . '>' . $form->post_title . '</option>';
                    }
                    echo '</select>';
                }
                else{
                    echo "<h2>No CF& form found</h2>";
                }

                // 2. Template areas
                echo "<h2>Template one</h2>";
                echo '<textarea name="sa_cfe_tem_template_area_1">' . esc_textarea($template_area_1) . '</textarea>';
                echo "<h2>Template two</h2>";
                echo '<textarea name="sa_cfe_tem_template_area_2">' . esc_textarea($template_area_2) . '</textarea>';

                // 3. Yes/No fields
                echo "<h2>Yes/No Fields</h2>";
                echo '<input type="text" name="sa_cfe_tem_yes_no_fields" value="' . esc_attr($yes_no_fields) . '">';

                // 4. None of the Above fields
                echo "<h2>None of the Above Fields</h2>";
                echo '<input type="text" name="sa_cfe_tem_noneofabove_fields" value="' . esc_attr($noneofabove_fields) . '">';

                // 5. User Email field
                echo "<h2>User Email Field</h2>";
                echo '<input type="text" name="sa_cfe_tem_user_email_field" value="' . esc_attr($user_email_field) . '">';

            } else {
                echo "<h2>Contact form 7 plugin is not active</h2>";
            }
            
            // Output setting sections and their fields
            do_settings_sections('cf7_quiz_email_templates');
            // Output save settings button
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
    echo ob_get_clean();
}







// More code to handle form submissions, email sending, etc.
// Hook into the wpcf7_before_send_mail action
add_action('wpcf7_before_send_mail', 'sa_cfe_send_email_on_form_submission');

function sa_cfe_send_email_on_form_submission($contact_form) {
    // Get the ID of the submitted form
    $form_id = $contact_form->id();

    // Get the IDs of the selected forms
    $selected_forms = get_option('sa_cfe_tem_cf7_form');

    // Check if the submitted form is one of the selected forms
    if (in_array($form_id, $selected_forms)) {
        // Get the submission
        $submission = WPCF7_Submission::get_instance();

        // Check if the submission exists
        if ($submission) {
            // Get the submitted data
            $data = $submission->get_posted_data();

            // Get the name of the user email field
            $user_email_field = get_option('sa_cfe_tem_user_email_field');

            // Prepare the email
            $to = $data[$user_email_field]; // Use the name of the email field stored in the text field
            $subject = 'A form was submitted';

            // Get the names of the Yes/No fields and None of the Above fields
            $yes_no_fields = explode(',', get_option('sa_cfe_tem_yes_no_fields'));
            $noneofabove_fields = explode(',', get_option('sa_cfe_tem_noneofabove_fields'));

            // Check the values of the Yes/No fields and None of the Above fields
            $use_template_2 = false;
            foreach ($yes_no_fields as $field) {
                if (strtolower($data[trim($field)]) == 'no') {
                    $use_template_2 = true;
                    break;
                }
            }
            if (!$use_template_2) {
                foreach ($noneofabove_fields as $field) {
                    if (strtolower($data[trim($field)]) != 'none of the above') {
                        $use_template_2 = true;
                        break;
                    }
                }
            }

            // Define your conditions and templates
            if ($use_template_2) {
                $message = get_option('sa_cfe_tem_template_area_2');
            } else {
                $message = get_option('sa_cfe_tem_template_area_1');
            }

            // Replace _name with the actual name in the message
            $message = str_replace('_name', $data['first_name'], $message); // Replace 'first_name' with the name of the name field in your form

            // Send the email
            wp_mail($to, $subject, $message);
        }
    }
}




?>

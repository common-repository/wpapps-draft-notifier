<?php
/**
 * Plugin Name: WPApps Draft Notifier
 * Plugin URI: https://wpapps.net
 * Description: A WordPress plugin that sends reminders to authors about their unpublished drafts.
 * Version: 1.0.0
 * Author: WPApps
 * Author URI: https://wpapps.net
 * License: GPL-2.0+
 * Text Domain: wpapps-draft-notifier
 * Domain Path: /languages/
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

// Admin menu for the plugin settings
add_action('admin_menu', 'wpapps_draft_notifier_admin_menu');
function wpapps_draft_notifier_admin_menu() {
    add_menu_page(
        esc_html__('Draft Notifier Settings', 'wpapps-draft-notifier'),
        esc_html__('Draft Notifier', 'wpapps-draft-notifier'),
        'manage_options',
        'wpapps-draft-notifier',
        'wpapps_draft_notifier_settings_page'
    );
}

function wpapps_draft_notifier_add_settings_link($links) {
    $settings_link = '<a href="admin.php?page=wpapps-draft-notifier">' . esc_html__('Settings', 'wpapps-draft-notifier') . '</a>';
    array_unshift($links, $settings_link); // Add the settings link to the beginning of the array
    return $links;
}
$plugin_basename = plugin_basename(__FILE__);
add_filter('plugin_action_links_' . $plugin_basename, 'wpapps_draft_notifier_add_settings_link');

function wpapps_draft_notifier_settings_page() {
    ?>
    <div class="wrap">
        <h2><?php esc_html_e('WPApps Draft Notifier Settings', 'wpapps-draft-notifier'); ?></h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('wpapps-draft-notifier');
            do_settings_sections('wpapps-draft-notifier');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings for reminder frequency
add_action('admin_init', 'wpapps_draft_notifier_register_settings');
function wpapps_draft_notifier_register_settings() {
    register_setting('wpapps-draft-notifier', 'wpapps_draft_notifier_frequency');
    register_setting('wpapps-draft-notifier', 'wpapps_draft_notifier_email_option');
    register_setting('wpapps-draft-notifier', 'wpapps_draft_notifier_email_cc');
    register_setting('wpapps-draft-notifier', 'wpapps_draft_notifier_email_template', 'wp_kses_post');
    register_setting('wpapps-draft-notifier', 'wpapps_draft_notifier_remove_cron');

    add_settings_section(
        'wpapps_draft_notifier_settings_section',
        esc_html__('Draft Notifier Settings', 'wpapps-draft-notifier'),
        null,
        'wpapps-draft-notifier'
    );

    add_settings_field(
        'wpapps_draft_notifier_frequency',
        esc_html__('Notify for Drafts Older Than', 'wpapps-draft-notifier'),
        'wpapps_draft_notifier_frequency_callback',
        'wpapps-draft-notifier',
        'wpapps_draft_notifier_settings_section'
    );

    add_settings_field(
        'wpapps_draft_notifier_email_option',
        esc_html__('Email Recipient Option', 'wpapps-draft-notifier'),
        'wpapps_draft_notifier_email_option_callback',
        'wpapps-draft-notifier',
        'wpapps_draft_notifier_settings_section'
    );

    add_settings_field(
        'wpapps_draft_notifier_email_cc',
        esc_html__('Additional Email CC', 'wpapps-draft-notifier'),
        'wpapps_draft_notifier_email_cc_callback',
        'wpapps-draft-notifier',
        'wpapps_draft_notifier_settings_section'
    );
      add_settings_section(
    'wpapps_custom_email_section', 
    esc_html__('Customize Email Template', 'wpapps-draft-notifier'), 
    'wpapps_draft_notifier_custom_email_description', 
    'wpapps-draft-notifier'
    );
    add_settings_section(
    'wpapps_draft_notifier_deactivation_section', 
    esc_html__('Plugin Deactivation Settings', 'wpapps-draft-notifier'), 
    'wpapps_draft_notifier_deactivation_section_description', 
    'wpapps-draft-notifier'
    );



    function wpapps_draft_notifier_custom_email_description() {
        esc_html_e('Customize the body of the email sent as a reminder. You can use the following placeholders:', 'wpapps-draft-notifier');
        echo '<ul>';
        echo '<li><code>[drafts_list]</code> - ' . esc_html__('For where the list of drafts will appear.', 'wpapps-draft-notifier') . '</li>';
        echo '<li><code>[site_name]</code> - ' . esc_html__('For the name of the site.', 'wpapps-draft-notifier') . '</li>';
        echo '<li><code>[drafts_count]</code> - ' . esc_html__('For the count of drafts.', 'wpapps-draft-notifier') . '</li>';
        echo '</ul>';
    }

add_settings_field(
    'wpapps_draft_notifier_email_template', 
    esc_html__('Email Template', 'wpapps-draft-notifier'), 
    'wpapps_draft_notifier_email_template_callback', 
    'wpapps-draft-notifier', 
    'wpapps_custom_email_section'
);
    add_settings_field(
    'wpapps_draft_notifier_remove_cron', 
    esc_html__('Remove scheduled tasks upon deactivation?', 'wpapps-draft-notifier'), 
    'wpapps_draft_notifier_remove_cron_render', 
    'wpapps-draft-notifier', 
    'wpapps_draft_notifier_deactivation_section' 
    );

    function wpapps_draft_notifier_deactivation_section_description() {
        esc_html_e('Settings related to actions taken upon plugin deactivation.', 'wpapps-draft-notifier');
    }
    


function wpapps_draft_notifier_remove_cron_render() { 
    $remove_cron = get_option('wpapps_draft_notifier_remove_cron');
    ?>
    <input type='checkbox' name='wpapps_draft_notifier_remove_cron' <?php checked($remove_cron, 1); ?> value='1'>
    <p class="description">
        By checking this option, any scheduled tasks related to the Draft Notifier (like automated draft reminders) will be removed when you deactivate the plugin. If you plan to reactivate the plugin in the future and want the tasks to continue without reconfiguration, leave this option unchecked.
    </p>
    <?php
}
register_deactivation_hook(__FILE__, 'wpapps_draft_notifier_deactivate');
function wpapps_draft_notifier_deactivate() {
    if (get_option('wpapps_draft_notifier_remove_cron')) {
        wp_clear_scheduled_hook('wpapps_draft_notifier_cron_hook');
    }
}


function wpapps_draft_notifier_email_template_callback() {
    $default_template = esc_html__(
        "Hello,\n\nYou have [drafts_count] drafts that have been unpublished for a while on [site_name].\n\nPlease review the following drafts:\n\n[drafts_list]\n\nRegards\n\nThe [site_name] Team",
        'wpapps-draft-notifier'
    );
    $value = get_option('wpapps_draft_notifier_email_template', $default_template);
    
    $editor_settings = array(
        'media_buttons' => false,  // Disable the media buttons
        'textarea_rows' => 10,    // Set the number of rows
        'teeny' => true           // Display a minimal editor
    );
    wp_editor($value, 'wpapps_draft_notifier_email_template', $editor_settings);
}      
}


function wpapps_draft_notifier_frequency_callback() {
    $frequency = get_option('wpapps_draft_notifier_frequency', 7);
    printf(
        '<input type="number" name="wpapps_draft_notifier_frequency" value="%s" /> (Days)',
        esc_attr($frequency)
    );
}

function wpapps_draft_notifier_email_option_callback() {
    $option = get_option('wpapps_draft_notifier_email_option', 'admin');
    $options = array(
        'admin' => __('Site Admin', 'wpapps-draft-notifier'),
        'author' => __('Post Author Only', 'wpapps-draft-notifier'),
        'both' => __('Both', 'wpapps-draft-notifier')
    );
    echo '<select name="wpapps_draft_notifier_email_option">';
    foreach ($options as $value => $label) {
        echo '<option value="' . esc_attr($value) . '"' . selected($option, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
}

function wpapps_draft_notifier_email_cc_callback() {
    $email_cc = get_option('wpapps_draft_notifier_email_cc', '');
    echo '<input type="email" name="wpapps_draft_notifier_email_cc" value="' . esc_attr($email_cc) . '" placeholder="example@example.com" />';
}


// Cron jobs for sending email notifications
register_activation_hook(__FILE__, 'wpapps_draft_notifier_cron_activation');
function wpapps_draft_notifier_cron_activation() {
    if (!wp_next_scheduled('wpapps_draft_notifier_event')) {
        wp_schedule_event(time(), 'daily', 'wpapps_draft_notifier_event');
    }
}
add_action('wpapps_draft_notifier_event', 'wpapps_draft_notifier_send_email');

register_deactivation_hook(__FILE__, 'wpapps_draft_notifier_cron_deactivation');
function wpapps_draft_notifier_cron_deactivation() {
    wp_clear_scheduled_hook('wpapps_draft_notifier_event');
}

function wpapps_draft_notifier_get_old_drafts() {
    $frequency = get_option('wpapps_draft_notifier_frequency', 7);
    $date_threshold = date('Y-m-d', strtotime("-{$frequency} days"));

    // Get all public post types
    $post_types = get_post_types(array('public' => true), 'names');
    
    // Exclude 'attachment' from the post types as it's not relevant here
    unset($post_types['attachment']);

    $args = array(
        'post_type' => $post_types,
        'post_status' => 'draft',
        'date_query' => array(
           array(
               'before' => $date_threshold
           )
        ),
        'posts_per_page' => -1
    );

    return get_posts($args);
}


function wpapps_draft_notifier_send_email() {
    $drafts = wpapps_draft_notifier_get_old_drafts();

    if (empty($drafts)) return;

    $subject = esc_html__('Reminder: Unpublished Drafts', 'wpapps-draft-notifier');

    // Fetch the custom template or use a default message if no custom template is set
    $template = get_option('wpapps_draft_notifier_email_template');
    if (!$template) {
        $template = esc_html__('You have drafts that have been unpublished for a while.<br> Please review the following drafts: [drafts_list]', 'wpapps-draft-notifier');
    }
    $template = nl2br(esc_html($template));
    // Replace global tags
    $template = str_replace('[site_name]', esc_html(get_bloginfo('name')), $template);
    $template = str_replace('[drafts_count]', esc_html(count($drafts)), $template);

    // Construct the list of drafts in an unordered list
    $drafts_list = "<ul>";
    foreach ($drafts as $draft) {
        $edit_link = esc_url(admin_url("post.php?post={$draft->ID}&action=edit"));
        $author_name = esc_html(get_the_author_meta('display_name', $draft->post_author));
        $draft_date = esc_html(get_the_date('F j, Y', $draft));
        
        $draft_info = "<a href='{$edit_link}'>" . esc_html($draft->post_title) . "</a> (" . esc_html__('Author', 'wpapps-draft-notifier') . ": {$author_name} - " . esc_html__('Date', 'wpapps-draft-notifier') . ": {$draft_date})";
        $drafts_list .= "<li>{$draft_info}</li>"; 
    }
    $drafts_list .= "</ul>";
        
    // Replace the [drafts_list] tag in the template with the actual drafts list
    $message = str_replace('[drafts_list]', $drafts_list, $template);
    
    $option = get_option('wpapps_draft_notifier_email_option', 'admin');
    $admin_email = esc_html(get_option('admin_email'));
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    $email_cc = esc_html(get_option('wpapps_draft_notifier_email_cc', ''));
    if (!empty($email_cc)) {
        $headers[] = 'Cc: ' . $email_cc;
    }

    $author_emails = [];
    foreach ($drafts as $draft) {
      $author_email = esc_html(get_the_author_meta('user_email', $draft->post_author));
        $author_emails[$author_email] = true;  // Using an associative array to keep unique emails
    }

switch ($option) {
    case 'admin':
        wp_mail($admin_email, $subject, $message, $headers);
        break;
    
    case 'author':
        foreach (array_keys($author_emails) as $author_email) {
            wp_mail($author_email, $subject, $message, $headers);
        }
        break;

    case 'both':
        // Send to admin
        wp_mail($admin_email, $subject, $message, $headers);
        
        // Remove admin email from the list of author emails to prevent duplicates
        unset($author_emails[$admin_email]);

        // Send to unique authors
        foreach (array_keys($author_emails) as $author_email) {
            wp_mail($author_email, $subject, $message, $headers);
        }
        break;
}
}
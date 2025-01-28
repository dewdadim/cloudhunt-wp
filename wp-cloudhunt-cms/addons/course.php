<?php

function register_course_post_type()
{
    $args = [
        'label'  => esc_html__('Course', 'text-domain'),
        'labels' => [
            'menu_name'          => esc_html__('Course', 'runcloud-docs'),
            'name_admin_bar'     => esc_html__('Course', 'runcloud-docs'),
            'add_new'            => esc_html__('Add Course', 'runcloud-docs'),
            'add_new_item'       => esc_html__('Add new course', 'runcloud-docs'),
            'new_item'           => esc_html__('New Course', 'runcloud-docs'),
            'edit_item'          => esc_html__('Edit Course', 'runcloud-docs'),
            'view_item'          => esc_html__('View Course', 'runcloud-docs'),
            'update_item'        => esc_html__('View Course', 'runcloud-docs'),
            'all_items'          => esc_html__('All Courses', 'runcloud-docs'),
            'search_items'       => esc_html__('Search Courses', 'runcloud-docs'),
            'parent_item_colon'  => esc_html__('Parent Course', 'runcloud-docs'),
            'not_found'          => esc_html__('No Courses found', 'runcloud-docs'),
            'not_found_in_trash' => esc_html__('No Courses found in Trash', 'runcloud-docs'),
            'name'               => esc_html__('Courses', 'runcloud-docs'),
            'singular_name'      => esc_html__('Course', 'runcloud-docs'),
        ],
        'public'              => false,
        'exclude_from_search' => false,
        'publicly_queryable'  => false,
        'show_ui'             => true,
        'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'show_in_rest'        => true,
        'capability_type'     => 'post',
        'hierarchical'        => true,
        'has_archive'         => false,
        'query_var'           => false,
        'can_export'          => true,
        'rewrite_no_front'    => false,
        'show_in_menu'        => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-format-aside',
        'supports' => [
            'title',
            'author',
            'thumbnail',
            'page_attribute'
        ],

        'rewrite' => true,

        'show_in_graphql'     => true,
        'graphql_single_name' => 'rccta',
        'graphql_plural_name' => 'rcctas',
    ];

    register_post_type('course', $args);
}
add_action('init', 'register_course_post_type');

// Add Meta Box
function add_course_meta_box()
{
    add_meta_box(
        'course_meta_box',
        'Course Details',
        'display_course_meta_box',
        'course',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_course_meta_box');

function display_course_meta_box($post)
{
    wp_nonce_field('course_meta_box', 'course_meta_box_nonce');

    $title = get_post_meta($post->ID, '_course_title', true);
    $description = get_post_meta($post->ID, '_course_description', true);

?>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">
                    <label for="course_title">Heading</label>
                </th>
                <td>
                    <input type="text" id="course_title" name="course_title" value="<?php echo esc_attr($title); ?>" class="regular-text" required />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="course_description">Description</label>
                </th>
                <td>
                    <textarea id="cta_content" name="course_description" rows="3" cols="50" class="regular-text" required><?php echo esc_textarea($description); ?></textarea>
                </td>
            </tr>
        </tbody>
    </table>
<?php
}

function save_course_meta_box($post_id)
{
    if (!isset($_POST['course_meta_box_nonce'])) {
        return;
    }
    if (!wp_verify_nonce($_POST['course_meta_box_nonce'], 'course_meta_box')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = [
        '_course_title',
        '_course_description',
    ];

    foreach ($fields as $field) {
        $value = $_POST[ltrim($field, '_')] ?? '';
        update_post_meta($post_id, $field, $value);
    }
}
add_action('save_post', 'save_course_meta_box');

function rc_cta_select_option($value, $none_text = '-- Select Course --')
{
    $courses = get_posts(['post_type' => 'course', 'numberposts' => -1]);
?>
    <select name="rc_cta">
        <option value=""><?php echo esc_html($none_text); ?></option>
        <?php foreach ($courses as $course): ?>
            <option value="<?php echo $course->ID; ?>" <?php selected($value, $course->ID); ?>><?php echo esc_html($course->post_title); ?> #<?php echo esc_html($course->ID); ?></option>
        <?php endforeach; ?>
    </select>
<?php
}

function add_course_category_field($term)
{
    $course = get_term_meta($term->term_id, 'course', true);
?>
    <tr class="form-field">
        <th scope="row"><label for="course">Course Box</label></th>
        <td>
            <?php rc_cta_select_option($course); ?>
        </td>
    </tr>
<?php
}
add_action('category_edit_form_fields', 'add_course_category_field');

function save_course_category_field($term_id)
{
    if (isset($_POST['course'])) {
        update_term_meta($term_id, 'course', $_POST['course']);
    }
}
add_action('edited_category', 'save_course_category_field');

function rc_cta_add_post_metabox()
{
    add_meta_box(
        'course_metabox',
        'Course Box',
        'display_course_post_metabox',
        'post',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'rc_cta_add_post_metabox');

function display_course_post_metabox($post)
{
    wp_nonce_field('course_metabox', 'course_metabox_nonce');

    $course = get_post_meta($post->ID, '_course', true);
    rc_cta_select_option($course);
}

function save_course_metabox($post_id)
{
    if (!isset($_POST['course_metabox_nonce'])) {
        return;
    }
    if (!wp_verify_nonce($_POST['course_metabox_nonce'], 'course_metabox')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['course'])) {
        update_post_meta($post_id, '_course', sanitize_text_field($_POST['course']));
    }
}
add_action('save_post', 'save_course_metabox');

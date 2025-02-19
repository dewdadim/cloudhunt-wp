<?php
// 1. Rename default "Post" type to "Module"
function rename_default_post_type_to_module() {
    global $wp_post_types;

    if (isset($wp_post_types['post'])) {
        $wp_post_types['post']->label = 'Modules';
        $wp_post_types['post']->labels->name = 'Modules';
        $wp_post_types['post']->labels->singular_name = 'Module';
        $wp_post_types['post']->labels->add_new = 'Add Module';
        $wp_post_types['post']->labels->add_new_item = 'Add New Module';
        $wp_post_types['post']->labels->edit_item = 'Edit Module';
        $wp_post_types['post']->labels->new_item = 'New Module';
        $wp_post_types['post']->labels->view_item = 'View Module';
        $wp_post_types['post']->labels->search_items = 'Search Modules';
    }
}
add_action('init', 'rename_default_post_type_to_module');

// 2. Register new "Course" post type
function register_course_post_type() {
    $args = [
        'label'  => esc_html__('Courses', 'text-domain'),
        'labels' => [
            'name'               => esc_html__('Courses', 'text-domain'),
            'singular_name'      => esc_html__('Course', 'text-domain'),
            'add_new'            => esc_html__('Add Course', 'text-domain'),
            'add_new_item'       => esc_html__('Add New Course', 'text-domain'),
            'edit_item'          => esc_html__('Edit Course', 'text-domain'),
            'new_item'           => esc_html__('New Course', 'text-domain'),
            'view_item'          => esc_html__('View Course', 'text-domain'),
            'search_items'       => esc_html__('Search Courses', 'text-domain'),
        ],
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-book',
        'public'              => true,
        'has_archive'         => true,
        'show_in_rest'        => true,
        'show_in_graphql'     => true,
        'graphql_single_name' => 'course',
        'graphql_plural_name' => 'courses',
        'supports'            => ['title', 'editor', 'thumbnail', 'revisions'],
    ];

    register_post_type('course', $args);
}
add_action('init', 'register_course_post_type');

// Remove default metaboxes for "Course"
function remove_default_course_metaboxes() {
    remove_post_type_support('course', 'editor'); // Remove the main editor
}
add_action('init', 'remove_default_course_metaboxes');

// Add a custom metabox for course description
function add_course_description_metabox() {
    add_meta_box(
        'course_description_metabox',
        'Course Description',
        'display_course_description_metabox',
        'course',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_course_description_metabox');

// Display the custom metabox
function display_course_description_metabox($post) {
    wp_nonce_field('save_course_description', 'course_description_nonce');

    $description = get_post_meta($post->ID, '_course_description', true);

    echo '<label for="course_description">Description:</label>';
    echo '<textarea id="course_description" name="course_description" rows="5" cols="50" class="large-text">' . esc_textarea($description) . '</textarea>';
}

// Save the course description
function save_course($post_id) {
    if (!isset($_POST['course_description_nonce']) || !wp_verify_nonce($_POST['course_description_nonce'], 'save_course_description')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['course'])) {
        update_post_meta($post_id, '_related_course', sanitize_text_field($_POST['course']));

        $description = sanitize_textarea_field($_POST['course_description'] ?? '');
        update_post_meta($post_id, '_course_description', $description);
    }
}
add_action('save_post', 'save_course');

function add_course_metabox_to_module() {
    add_meta_box(
        'module_course_metabox',
        'Assign Course',
        'display_module_course_metabox',
        'post',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'add_course_metabox_to_module');

function display_module_course_metabox($post) {
    wp_nonce_field('module_course_metabox', 'module_course_metabox_nonce');

    $selected_course = get_post_meta($post->ID, '_related_course', true);
    $courses = get_posts(['post_type' => 'course', 'numberposts' => -1]);

    echo '<select name="related_course" style="width:100%">';
    echo '<option value="">-- Select Course --</option>';
    foreach ($courses as $course) {
        echo '<option value="' . esc_attr($course->ID) . '" ' . selected($selected_course, $course->ID, false) . '>';
        echo esc_html($course->post_title);
        echo '</option>';
    }
    echo '</select>';
}

function save_module_course_relationship($post_id) {
    if (!isset($_POST['module_course_metabox_nonce']) || !wp_verify_nonce($_POST['module_course_metabox_nonce'], 'module_course_metabox')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $related_course = sanitize_text_field($_POST['related_course'] ?? '');
    update_post_meta($post_id, '_related_course', $related_course);
}
add_action('save_post', 'save_module_course_relationship');

add_action('graphql_register_types', function() {
    register_graphql_field('Post', 'relatedCourse', [
        'type' => 'Course', // Course post type
        'resolve' => function($module) {
            $related_course_id = get_post_meta($module->ID, '_related_course', true);

            if (!$related_course_id) {
                return null;
            }

            $course = get_post($related_course_id);
            return !empty($course) ? new WPGraphQL\Model\Post($course) : null;
        },
    ]);


    register_graphql_field('Course', 'modules', [
        'type' => ['list_of' => 'Post'],
        'resolve' => function($course) {
            $modules = get_posts([
                'post_type' => 'post',
                'meta_query' => [
                    [
                        'key' => '_related_course',
                        'value' => $course->ID,
                        'compare' => '='
                    ]
                ]
            ]);

            if (empty($modules)) {
                return [];
            }

            return array_map(fn($module) => new WPGraphQL\Model\Post($module), $modules);
        },
    ]);
});

<?php

function cloudhunt_sync_to_laravel($post_id, $post) {
    if ($post->post_type !== 'course' && $post->post_type !== 'module') {
        return;
    }

    $api_url = get_option('cloudhunt_api_url');
    $api_token = get_option('cloudhunt_api_token');

    $endpoint = $post->post_type === 'course' ? '/courses' : '/modules';
    $url = rtrim($api_url, '/') . $endpoint;

    $body = [
        'title' => $post->post_title,
        'slug' => $post->slug,
        'thumbnail' => get_the_post_thumbnail_url($post_id),
        'description' => get_post_meta($post_id, '_course_description', true),
    ];

    if ($post->post_type === 'module') {
        $body['course_id'] = get_post_meta($post_id, '_related_course', true);
        $body['content'] = get_rendered_post_content($post_id);
    }

    wp_remote_post($url, [
        'method'    => 'POST',
        'body'      => json_encode($body),
        'headers'   => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_token,
        ],
    ]);
}
add_action('save_post', 'cloudhunt_sync_to_laravel', 10, 2);

function cloudhunt_delete_from_laravel($post_id) {
    $api_url = get_option('cloudhunt_api_url');
    $api_token = get_option('cloudhunt_api_token');

    $post_type = get_post_type($post_id);
    if ($post_type !== 'course' && $post_type !== 'module') {
        return;
    }

    $endpoint = $post_type === 'course' ? "/courses/{$post_id}" : "/modules/{$post_id}";
    $url = rtrim($api_url, '/') . $endpoint;

    wp_remote_request($url, [
        'method'  => 'DELETE',
        'headers' => [
            'Authorization' => 'Bearer ' . $api_token,
        ],
    ]);
}
add_action('before_delete_post', 'cloudhunt_delete_from_laravel');

function cloudhunt_manual_sync() {
    error_log('CloudHunt manual sync started.');
    $laravel_api_url = get_option('cloudhunt_api_url') . '/api/sync';
    remove_all_filters('the_content');

    $courses = get_posts(['post_type' => 'course', 'numberposts' => -1]);
    $modules = get_posts(['post_type' => 'post', 'numberposts' => -1]);

    $data = [
        'courses' => [],
        'modules' => []
    ];

    foreach ($courses as $course) {
        $data['courses'][] = [
            'id' => $course->ID,
            'slug' => $course->post_name,
            'thumbnail' => get_the_post_thumbnail_url($course->ID),
            'title' => get_the_title($course),
            'description' => get_post_meta($course->ID, '_course_description', true),
        ];
    }
    
    foreach ($modules as $module) {
        $data['modules'][] = [
            'id' => $module->ID,
            'slug' => $module->post_name,
            'title' => get_the_title($module),
            'content' => get_rendered_post_content($module->ID),
            'course_id' => get_post_meta($module->ID, '_related_course', true), 
        ];
    }

    $response = wp_remote_post($laravel_api_url, [
        'method' => 'POST',
        'headers' => [
            'Authorization' => 'Bearer ' . get_option('cloudhunt_api_token'),
            'Content-Type'  => 'application/json',
        ],
        'body'    => json_encode($data),
    ]);

    if (is_wp_error($response)) {
        wp_send_json(['message' => 'Failed to sync.'], 500);
    } else {
        wp_send_json(['message' => 'Sync completed successfully!']);
    }
}
add_action('wp_ajax_cloudhunt_manual_sync', 'cloudhunt_manual_sync');

function get_rendered_post_content($post_id) {
    $post = get_post($post_id);
    if (!$post) {
        return '';
    }

    // Parse and render blocks
    $blocks = parse_blocks($post->post_content);
    $rendered_content = '';

    foreach ($blocks as $block) {
        if ($block['blockName'] === 'core/embed' && !empty($block['attrs']['url'])) {
            $embed_url = $block['attrs']['url'];
            $embed_html = wp_oembed_get($embed_url);

            if ($embed_html) {
                // Use WP function to get the embed class automatically
                $provider_class = wp_filter_oembed_result($embed_html, $embed_url, []);

                // Wrap it in a figure (WordPress-style)
                $rendered_content .= '<figure class="wp-block-embed">';
                $rendered_content .= '<div class="wp-block-embed__wrapper">';
                $rendered_content .= $provider_class;
                $rendered_content .= '</div></figure>';
                continue;
            }
        }

        // Render non-embed blocks normally
        $rendered_content .= render_block($block);
    }

    return $rendered_content;
}



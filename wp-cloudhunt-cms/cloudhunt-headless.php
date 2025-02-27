<?php
/*
Plugin Name: CloudHunt Headless CMS
Description: Collection of functions related to headless setup
Version: 1.3.2
Author: Nadim Hairi
*/

define('CLOUDHUNT_BASE_URL', 'https://dev.cloudhunt.guru/courses');

include_once('addons/course.php');
include_once('addons/module.php');
include_once('addons/settings.php');
include_once('addons/sync-cloudhunt-db.php');

add_filter('graphql_connection_max_query_amount', function (int $max_amount, $source, array $args, $context, $info) {
    return 300;
}, 10, 5);

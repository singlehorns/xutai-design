<?php
/**
 * Real-WordPress integration checks. Run ONLY in a disposable local WordPress.
 * WP-CLI: wp eval "define('T2_PUBLISH_TEST_ALLOW', true); require '/path/to/wordpress-publish.integration.php';"
 * Playground: define allow constant, require wp-load.php, then require this file.
 * All HTTP is intercepted. No real credential or external request is used.
 */
if (!defined('ABSPATH') || !defined('T2_PUBLISH_TEST_ALLOW') || T2_PUBLISH_TEST_ALLOW !== true) { throw new RuntimeException('Explicit local test opt-in required.'); }
if (!function_exists('t2_publish_flush')) { throw new RuntimeException('Activate T2 Portfolio 1.1.0 before testing.'); }
if (defined('T2_GITHUB_TOKEN')) { throw new RuntimeException('Run tests without a configured credential constant.'); }
require_once ABSPATH . 'wp-admin/includes/template.php';

$t2_test_options = array();
foreach (array('t2_publish_connection', 't2_publish_enabled', 't2_publish_state') as $key) { $t2_test_options[$key] = get_option($key, null); }
$t2_test_user = get_current_user_id();
$t2_test_posts = array();
$t2_test_terms = array();
$t2_test_requests = array();
$t2_test_response = 204;
$t2_test_assertions = 0;
$t2_test_watched = 0;
$t2_test_credential = 'github_pat_T2_LOCAL_FIXTURE_DO_NOT_USE_123456789';
$t2_test_prefix = 't2-publish-test-' . strtolower(wp_generate_password(8, false));
$t2_test_assert = function ($condition, $message) use (&$t2_test_assertions) {
    if (!$condition) { throw new RuntimeException('Assertion failed: ' . $message); }
    $t2_test_assertions++;
};
$t2_test_http = function ($pre, $args, $url) use (&$t2_test_requests, &$t2_test_response, &$t2_test_watched, $t2_test_credential) {
    if ($url !== 'https://api.github.com/repos/' . t2_publish_repository() . '/actions/workflows/deploy.yml/dispatches') { return new WP_Error('test_blocked', 'Unexpected external request blocked by local test.'); }
    $t2_test_requests[] = array(
        'url' => $url, 'method' => $args['method'] ?? '', 'body' => $args['body'] ?? '',
        'authorization_correct' => ($args['headers']['Authorization'] ?? '') === 'Bearer ' . $t2_test_credential,
        'related_label' => $t2_test_watched ? get_post_meta($t2_test_watched, '_t2_related_label', true) : '',
        'related_url' => $t2_test_watched ? get_post_meta($t2_test_watched, '_t2_related_url', true) : '',
        'term_count' => $t2_test_watched ? count(wp_get_object_terms($t2_test_watched, 't2_work_category')) : 0,
    );
    if ($t2_test_response === 'network') { return new WP_Error('network', 'Fixture error body that must never be persisted: ' . $t2_test_credential); }
    return array('headers' => array(), 'body' => 'Fixture response that must never be persisted: ' . $t2_test_credential, 'response' => array('code' => $t2_test_response, 'message' => 'Fixture'), 'cookies' => array());
};
add_filter('pre_http_request', $t2_test_http, PHP_INT_MAX, 3);

try {
    $admins = get_users(array('role' => 'administrator', 'number' => 1, 'fields' => 'ID'));
    if (!$admins) { throw new RuntimeException('Local test needs an existing administrator.'); }
    wp_set_current_user((int) $admins[0]);
    update_option('t2_publish_enabled', '0', false);
    delete_option('t2_publish_connection');
    $GLOBALS['t2_publish_request_changed'] = false;
    $nonce = wp_create_nonce('t2_connect_site');
    wp_set_current_user(0);
    $result = t2_publish_save_connection(array('_wpnonce' => $nonce, 'connection_credential' => $t2_test_credential, 'auto_publish' => '1'));
    $t2_test_assert(is_wp_error($result) && $result->get_error_code() === 'forbidden', 'unauthorized connection rejected');
    wp_set_current_user((int) $admins[0]);
    $result = t2_publish_save_connection(array('_wpnonce' => 'forged', 'connection_credential' => $t2_test_credential, 'auto_publish' => '1'));
    $t2_test_assert(is_wp_error($result) && $result->get_error_code() === 'invalid_nonce', 'forged nonce rejected');
    $t2_test_assert(count($t2_test_requests) === 0, 'unauthorized settings cause no network request');
    $result = t2_publish_save_connection(array('_wpnonce' => $nonce, 'connection_credential' => array('invalid')));
    $t2_test_assert(is_wp_error($result) && $result->get_error_code() === 'invalid_credential', 'malformed credential form rejected without TypeError');

    $result = t2_publish_save_connection(array('_wpnonce' => $nonce, 'connection_credential' => $t2_test_credential, 'auto_publish' => '1'));
    $t2_test_assert($result === true && t2_publish_enabled(), 'admin connection enables automatic publishing');
    $t2_test_assert(t2_publish_credential() === $t2_test_credential, 'credential decrypts on server');
    $t2_test_assert(strpos(get_option('t2_publish_connection'), $t2_test_credential) === false, 'database option contains encrypted data only');
    global $wpdb;
    $autoload = $wpdb->get_var($wpdb->prepare("SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", 't2_publish_connection'));
    $t2_test_assert(in_array($autoload, array('no', 'off', 'auto-off'), true), 'credential option does not autoload');
    $t2_test_assert(count($t2_test_requests) === 1 && $t2_test_requests[0]['authorization_correct'], 'connect uses dedicated credential once');
    $t2_test_assert($t2_test_requests[0]['method'] === 'POST' && json_decode($t2_test_requests[0]['body'], true) === array('ref' => 'main'), 'workflow dispatch targets main with no content or secrets in body');
    ob_start(); t2_portfolio_update_page(); $settings_html = ob_get_clean();
    $t2_test_assert(strpos($settings_html, $t2_test_credential) === false && strpos($settings_html, 'type="password"') !== false && strpos($settings_html, 'value=""') !== false, 'settings never echo saved credential');

    $baseline = count($t2_test_requests);
    foreach (array('draft', 'private') as $status) {
        $id = wp_insert_post(array('post_type' => 't2_work', 'post_status' => $status, 'post_title' => $t2_test_prefix . '-' . $status));
        $t2_test_posts[] = $id;
        update_post_meta($id, '_t2_related_label', 'Draft setting');
        wp_update_post(array('ID' => $id, 'post_content' => 'Updated but still hidden'));
    }
    $protected = wp_insert_post(array('post_type' => 't2_work', 'post_status' => 'publish', 'post_password' => 'local-fixture', 'post_title' => $t2_test_prefix . '-protected'));
    $t2_test_posts[] = $protected;
    wp_update_post(array('ID' => $protected, 'post_status' => 'private', 'post_password' => ''));
    t2_publish_flush();
    $t2_test_assert(count($t2_test_requests) === $baseline, 'drafts/private/password and nonpublic-to-nonpublic do not dispatch');

    $public = wp_insert_post(array('post_type' => 't2_work', 'post_status' => 'publish', 'post_title' => $t2_test_prefix . '-public'));
    $t2_test_posts[] = $public;
    $t2_test_watched = $public;
    $term = wp_insert_term($t2_test_prefix . '-category', 't2_work_category');
    $t2_test_terms[] = $term['term_id'];
    wp_set_object_terms($public, array((int) $term['term_id']), 't2_work_category');
    update_post_meta($public, '_t2_related_label', 'Final label');
    update_post_meta($public, '_t2_related_url', 'https://example.com/final');
    update_post_meta($public, '_t2_featured', '1');
    update_post_meta($public, '_thumbnail_id', 987654);
    $t2_test_assert(count($t2_test_requests) === $baseline, 'all writes wait until end of request');
    t2_publish_flush();
    $t2_test_assert(count($t2_test_requests) === $baseline + 1, 'many metadata/term writes coalesce into one request');
    $last = end($t2_test_requests);
    $t2_test_assert($last['related_label'] === 'Final label' && $last['related_url'] === 'https://example.com/final' && $last['term_count'] === 1, 'dispatch observes final metadata and category assignments');
    t2_publish_flush();
    $t2_test_assert(count($t2_test_requests) === $baseline + 1, 'repeated flush with no changes does not duplicate');

    $baseline = count($t2_test_requests);
    // Simulates the later Gutenberg legacy-metabox request after the REST save.
    update_post_meta($public, '_t2_related_label', 'Later metabox save');
    t2_publish_flush();
    $t2_test_assert(count($t2_test_requests) === $baseline + 1 && end($t2_test_requests)['related_label'] === 'Later metabox save', 'separate Gutenberg metabox save triggers latest content');
    $baseline = count($t2_test_requests);
    wp_update_post(array('ID' => $public, 'post_password' => 'hide-now'));
    t2_publish_flush();
    $t2_test_assert(count($t2_test_requests) === $baseline + 1, 'adding password removes formerly public work');
    wp_update_post(array('ID' => $public, 'post_password' => ''));
    t2_publish_flush();
    $t2_test_assert(count($t2_test_requests) === $baseline + 2, 'removing password publishes visible work');

    $baseline = count($t2_test_requests);
    wp_trash_post($public);
    t2_publish_flush();
    $t2_test_assert(count($t2_test_requests) === $baseline + 1, 'trashing published work updates website');
    $future = wp_insert_post(array('post_type' => 't2_work', 'post_status' => 'future', 'post_title' => $t2_test_prefix . '-future', 'post_date' => date('Y-m-d H:i:s', time() + 3600), 'post_date_gmt' => gmdate('Y-m-d H:i:s', time() + 3600)));
    $t2_test_posts[] = $future;
    t2_publish_flush();
    $t2_test_assert(count($t2_test_requests) === $baseline + 1, 'scheduled work waits until publication');
    wp_publish_post($future);
    t2_publish_flush();
    $t2_test_assert(count($t2_test_requests) === $baseline + 2, 'scheduled-to-publish dispatches');
    wp_delete_post($future, true);
    t2_publish_flush();
    $t2_test_assert(count($t2_test_requests) === $baseline + 3, 'permanently deleting published work dispatches');

    $t2_test_watched = 0;
    $baseline = count($t2_test_requests);
    $empty = wp_insert_term($t2_test_prefix . '-empty', 't2_work_category');
    $t2_test_terms[] = $empty['term_id'];
    t2_publish_flush();
    wp_update_term($empty['term_id'], 't2_work_category', array('name' => $t2_test_prefix . '-renamed'));
    t2_publish_flush();
    wp_delete_term($empty['term_id'], 't2_work_category');
    t2_publish_flush();
    $t2_test_assert(count($t2_test_requests) === $baseline + 3, 'creating/renaming/deleting empty categories dispatches');

    $t2_test_response = 'network';
    t2_publish_mark_changed(); t2_publish_flush();
    $state = get_option('t2_publish_state');
    $t2_test_assert($state['status'] === 'failed' && $state['error'] === 'network', 'network failures persist actionable safe state');
    $t2_test_assert(strpos(wp_json_encode($state), $t2_test_credential) === false, 'network error body never stored');
    $t2_test_response = 403;
    t2_publish_mark_changed(); t2_publish_flush();
    $state = get_option('t2_publish_state');
    $t2_test_assert($state['error'] === 'authorization' && strpos(wp_json_encode($state), 'Fixture') === false, 'HTTP failure response body never stored');
    $baseline = count($t2_test_requests);
    $t2_test_assert(is_wp_error(t2_publish_retry('forged')) && count($t2_test_requests) === $baseline, 'manual retry requires a valid nonce');
    $t2_test_response = 200;
    $t2_test_assert(t2_publish_retry(wp_create_nonce('t2_rebuild_site')) === true, 'manual retry accepts HTTP200');
    $t2_test_assert(get_option('t2_publish_state')['status'] === 'accepted', 'accepted does not claim deployed');

    $cipher = get_option('t2_publish_connection');
    $bad_cipher = json_decode($cipher, true);
    $bad_cipher['tag'] = base64_encode(str_repeat('x', 16));
    update_option('t2_publish_connection', wp_json_encode($bad_cipher), false);
    $t2_test_assert(t2_publish_credential() === '', 'tampered ciphertext is rejected');
    $bad_cipher['tag'] = array('invalid');
    update_option('t2_publish_connection', wp_json_encode($bad_cipher), false);
    $t2_test_assert(t2_publish_credential() === '', 'malformed encrypted fields rejected without TypeError');
    update_option('t2_publish_connection', $cipher, false);
    $result = t2_publish_save_connection(array('_wpnonce' => $nonce, 'connection_action' => 'disconnect'));
    $t2_test_assert($result === true && !t2_publish_enabled() && !get_option('t2_publish_connection'), 'disconnect removes encrypted credential and disables automatic requests');
    echo wp_json_encode(array('passed' => $t2_test_assertions, 'mock_requests' => count($t2_test_requests), 'external_requests' => 0)) . "\n";
} finally {
    update_option('t2_publish_enabled', '0', false);
    $GLOBALS['t2_publish_request_changed'] = false;
    foreach ($t2_test_posts as $id) { wp_delete_post($id, true); }
    foreach ($t2_test_terms as $id) { if (term_exists($id, 't2_work_category')) { wp_delete_term($id, 't2_work_category'); } }
    foreach ($t2_test_options as $key => $value) { if ($value === null) { delete_option($key); } else { update_option($key, $value, false); } }
    $GLOBALS['t2_publish_request_changed'] = false;
    wp_set_current_user($t2_test_user);
    remove_filter('pre_http_request', $t2_test_http, PHP_INT_MAX);
}

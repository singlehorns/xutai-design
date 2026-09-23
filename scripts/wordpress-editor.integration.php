<?php
/**
 * Execute ONLY in disposable local WordPress with T2 Portfolio 1.3.0 active.
 * wp eval "define('T2_EDITOR_TEST_ALLOW', true); require '/path/wordpress-editor.integration.php';"
 * Playground: require wp-load.php, define the same constant, then require this script.
 * Uses only its own temporary posts/media records. Every external HTTP call is intercepted.
 */
if (!defined('ABSPATH') || !defined('T2_EDITOR_TEST_ALLOW') || T2_EDITOR_TEST_ALLOW !== true) { throw new RuntimeException('Explicit local test opt-in required.'); }
if (!function_exists('t2_portfolio_work_groups')) { throw new RuntimeException('Activate T2 Portfolio 1.3.0 before testing.'); }
if (defined('T2_GITHUB_TOKEN')) { throw new RuntimeException('Run tests without real credential constants.'); }
require_once ABSPATH . 'wp-admin/includes/template.php';
require_once ABSPATH . 'wp-admin/includes/post.php';

$t2e_options = array();
foreach (array('t2_publish_enabled', 't2_publish_connection', 't2_publish_state') as $key) { $t2e_options[$key] = get_option($key, null); }
$t2e_user = get_current_user_id(); $t2e_post = $_POST; $t2e_posts = array(); $t2e_attachments = array(); $t2e_terms = array();
$t2e_count = 0; $t2e_requests = array(); $t2e_watch = 0;
$t2e_prefix = 't2-editor-test-' . strtolower(wp_generate_password(8, false));
$t2e_assert = function ($condition, $message) use (&$t2e_count) { if (!$condition) { throw new RuntimeException('Assertion failed: ' . $message); } $t2e_count++; };
$t2e_http = function ($pre, $args, $url) use (&$t2e_requests, &$t2e_watch) {
    if ($url !== 'https://api.github.com/repos/' . t2_publish_repository() . '/actions/workflows/deploy.yml/dispatches') { return new WP_Error('test_blocked', 'All external traffic is disabled in this test.'); }
    $t2e_requests[] = array('title' => get_post($t2e_watch)->post_title, 'content' => get_post($t2e_watch)->post_content, 'cover' => get_post_thumbnail_id($t2e_watch), 'link' => get_post_meta($t2e_watch, '_t2_related_url', true), 'terms' => wp_get_object_terms($t2e_watch, 't2_work_category', array('fields' => 'ids')), 'groups' => t2_portfolio_work_groups($t2e_watch));
    return array('headers' => array(), 'body' => '', 'response' => array('code' => 204, 'message' => 'Local mock'), 'cookies' => array());
};
add_filter('pre_http_request', $t2e_http, PHP_INT_MAX, 3);
$t2e_die = function () { return function ($message) { throw new RuntimeException('Expected guard: ' . wp_strip_all_tags($message)); }; };
add_filter('wp_die_handler', $t2e_die);

try {
    $_POST = array();
    $admins = get_users(array('role' => 'administrator', 'number' => 1, 'fields' => 'ID'));
    if (!$admins) { throw new RuntimeException('A local administrator is required.'); }
    wp_set_current_user((int) $admins[0]);
    update_option('t2_publish_enabled', '0', false);
    $GLOBALS['t2_publish_request_changed'] = false;
    $GLOBALS['t2_editor_validated'] = array();
    $t2e_assert(apply_filters('use_block_editor_for_post_type', true, 't2_work') === false && apply_filters('use_block_editor_for_post_type', true, 'post') === true, 'only portfolio uses the fixed editor');

    // Metadata-only fixture attachments; these paths do not exist and no media is downloaded.
    foreach (array('long', 'second') as $name) {
        $id = wp_insert_attachment(array('post_mime_type' => 'image/jpeg', 'post_title' => $t2e_prefix . '-' . $name, 'post_status' => 'inherit'), false);
        $t2e_attachments[] = $id;
        update_post_meta($id, '_wp_attached_file', $t2e_prefix . '/' . $name . '-scaled.jpg');
        wp_update_attachment_metadata($id, array('width' => 507, 'height' => 2560, 'file' => $t2e_prefix . '/' . $name . '-scaled.jpg', 'original_image' => $name . '.jpg', 'sizes' => array('large' => array('file' => $name . '-203x1024.jpg', 'width' => 203, 'height' => 1024, 'mime-type' => 'image/jpeg'))));
    }
    list($first, $second) = $t2e_attachments;
    $first_url = wp_get_original_image_url($first); $second_url = wp_get_original_image_url($second);
    $legacy_url = str_replace('long.jpg', 'long-203x1024.jpg', $first_url);
    $image_block = '<!-- wp:image {"id":' . $first . ',"sizeSlug":"large","linkDestination":"none"} --><figure class="wp-block-image size-large"><img src="' . $legacy_url . '" alt="Old alt" class="wp-image-' . $first . '"/></figure><!-- /wp:image -->';
    $gallery = '<!-- wp:gallery {"linkTo":"none"} --><figure class="wp-block-gallery has-nested-images columns-default is-cropped">' . $image_block . '</figure><!-- /wp:gallery -->';
    $heading = '<!-- wp:heading --><h2 class="wp-block-heading">作品畫面</h2><!-- /wp:heading -->';
    $paragraph = '<!-- wp:paragraph --><p>Original &amp; text</p><!-- /wp:paragraph -->';
    $special = '<!-- wp:group {"layout":{"type":"constrained"}} --><div class="wp-block-group"><!-- wp:paragraph --><p><strong>Original special</strong></p><!-- /wp:paragraph --></div><!-- /wp:group -->';

    $make_post = function ($content, $suffix = '') use (&$t2e_posts, $t2e_prefix) {
        $id = wp_insert_post(wp_slash(array('post_type' => 't2_work', 'post_status' => 'draft', 'post_title' => $t2e_prefix . $suffix, 'post_content' => $content, 'post_excerpt' => 'Original summary')), true);
        if (is_wp_error($id)) { throw new RuntimeException('Fixture creation failed.'); }
        $t2e_posts[] = $id; return $id;
    };
    $work = $make_post($heading . "\n\n" . $paragraph . "\n\n" . $image_block, '-primary');
    $other = $make_post('', '-other');
    update_post_meta($work, '_t2_service_ids', array('web'));
    $body_before = get_post($work)->post_content;
    $input_for = function ($id, $rows = null) {
        return array('post_ID' => (string) $id, 'post_title' => get_post($id)->post_title, 't2_editor_present' => '1', 't2_editor_ready' => '1', 't2_editor_nonce' => wp_create_nonce('t2_edit_work_' . $id), 't2_editor_snapshot' => t2_editor_snapshot($id), 't2_editor_rows' => wp_json_encode($rows === null ? t2_editor_rows(get_post($id)->post_content) : $rows), 'excerpt' => get_post($id)->post_excerpt, 't2_cover_id' => (string) get_post_thumbnail_id($id), 't2_service_groups_present' => '1', 't2_service_groups' => t2_portfolio_work_groups($id));
    };
    $t2e_assert(t2_portfolio_service_groups() === array('web' => '網站建置・維護管理', 'event' => '活動・教育推廣', 'social' => '社群・廣告推廣', 'print' => '商家印刷・製作'), 'only the four current service groups are available');
    $t2e_assert(t2_portfolio_work_groups($work) === array('web') && !metadata_exists('post', $work, '_t2_service_groups'), 'legacy mapping does not write or migrate records when read');
    update_post_meta($other, '_t2_service_ids', array('brand', 'motion', 'social'));
    $t2e_assert(t2_portfolio_work_groups($other) === array('social'), 'brand and motion are never guessed as event or social');
    $without_groups_meta = $input_for($other);
    update_post_meta($other, '_t2_service_groups', array());
    $t2e_assert(t2_portfolio_work_groups($other) === array(), 'explicit empty groups stay empty despite legacy services');
    $conflict = t2_editor_validate($other, $without_groups_meta);
    $t2e_assert(is_wp_error($conflict) && $conflict->get_error_code() === 'conflict', 'snapshot detects newly explicit empty groups');
    foreach (array('event', array('brand'), array('icon'), array('web', 'web'), array(array('web')), array('key' => 'web')) as $invalid_groups) {
        $candidate = $input_for($work); $candidate['t2_service_groups'] = $invalid_groups;
        $result = t2_editor_validate($work, $candidate);
        $t2e_assert(is_wp_error($result) && $result->get_error_code() === 'groups', 'unknown or malformed service group form is rejected');
    }
    $candidate = $input_for($work); unset($candidate['t2_service_groups_present']);
    $t2e_assert(is_wp_error(t2_editor_validate($work, $candidate)), 'outdated form cannot omit the service group marker');
    $rows = t2_editor_rows($body_before);
    $t2e_assert(array_column($rows, 'type') === array('heading', 'text', 'image'), 'legacy headings, paragraphs and image are editable');
    $t2e_assert($rows[2]['url'] === $first_url && $rows[2]['normalize_image'], 'legacy small image selects uploaded original');
    $result = t2_editor_validate($work, $input_for($work));
    $t2e_assert(!is_wp_error($result) && strpos($result['content'], 'size-full') !== false && strpos($result['content'], $first_url) !== false && strpos($result['content'], '203x1024') === false, 'saving unchanged legacy rows upgrades full image markup');
    $html = t2_portfolio_content_html($gallery);
    $t2e_assert(strpos($html, $first_url) !== false && strpos($html, '203x1024') === false, 'public renderer upgrades nested legacy gallery image before editor save');
    $t2e_assert(get_post($work)->post_content === $body_before, 'opening, parsing and validating never writes original content');

    $gallery_work = $make_post($gallery, '-gallery');
    $rows = t2_editor_rows(get_post($gallery_work)->post_content);
    $t2e_assert(count($rows) === 1 && $rows[0]['type'] === 'image' && $rows[0]['flatten_gallery'], 'real nested image gallery converts to editable image row');
    $result = t2_editor_validate($gallery_work, $input_for($gallery_work));
    $t2e_assert(!is_wp_error($result) && strpos($result['content'], 'wp:gallery') === false && strpos($result['content'], $first_url) !== false, 'saving simple gallery produces a full image row');
    $complex_gallery = str_replace('</figure><!-- /wp:gallery -->', '<figcaption>Gallery caption</figcaption></figure><!-- /wp:gallery -->', $gallery);
    $t2e_assert(t2_editor_rows($complex_gallery)[0]['type'] === 'preserved', 'gallery with aggregate caption remains preserved');

    $missing_id = str_replace('"id":' . $first, '"id":2147483000', $image_block);
    $missing_work = $make_post($missing_id, '-missing-image-id');
    $rows = t2_editor_rows(get_post($missing_work)->post_content);
    $t2e_assert($rows[0]['id'] === 0 && $rows[0]['url'] === $legacy_url, 'deleted attachment retains existing external source');
    $t2e_assert(!is_wp_error(t2_editor_validate($missing_work, $input_for($missing_work))), 'retained external image does not block saving');

    $special_work = $make_post($special . "\n\n" . $paragraph, '-preserved');
    $special_body = get_post($special_work)->post_content;
    $rows = t2_editor_rows($special_body);
    $t2e_assert($rows[0]['type'] === 'preserved' && $rows[1]['type'] === 'text', 'unsupported group stays read-only');
    $result = t2_editor_validate($special_work, $input_for($special_work));
    $t2e_assert(!is_wp_error($result) && $result['content'] === $special_body, 'unchanged compatible content remains byte-identical');
    $rows[1]['text'] = "Line one: 'quoted' & \"double\"\nLine two：段落";
    $rows = array($rows[1], $rows[0]);
    $result = t2_editor_validate($special_work, $input_for($special_work, $rows));
    $t2e_assert(!is_wp_error($result) && strpos($result['content'], $special) !== false, 'moving preserved block and editing neighboring text retains raw special markup');
    $t2e_assert(t2_editor_rows($result['content'])[0]['text'] === $rows[0]['text'], 'newlines and quotes round-trip through safe text blocks');
    foreach (array(array('type' => 'text', 'text' => "Line 1\n\nLine 3"), array('type' => 'heading', 'level' => 2, 'text' => "Line 1\nLine 2"), array('type' => 'image', 'id' => $first, 'url' => $first_url, 'alt' => '', 'caption' => "Caption 1\nCaption 2")) as $roundtrip) {
        $expected_text = $roundtrip[$roundtrip['type'] === 'image' ? 'caption' : 'text'];
        for ($iteration = 0; $iteration < 3; $iteration++) { $roundtrip = t2_editor_rows(t2_editor_row_html($roundtrip))[0]; }
        $t2e_assert($roundtrip[$roundtrip['type'] === 'image' ? 'caption' : 'text'] === $expected_text, $roundtrip['type'] . ' repeated saves never multiply line breaks');
    }
    $result = t2_editor_validate($special_work, $input_for($special_work, array($rows[0])));
    $t2e_assert(is_wp_error($result) && $result->get_error_code() === 'preserved', 'unknown block cannot be silently removed');
    $rows[1]['type'] = 'text'; $rows[1]['text'] = 'Forged';
    $result = t2_editor_validate($special_work, $input_for($special_work, $rows));
    $t2e_assert(is_wp_error($result) && $result->get_error_code() === 'preserved', 'unknown raw block cannot be overwritten by forged row type');

    foreach (array('nonce' => array('t2_editor_nonce' => 'forged'), 'editor_not_ready' => array('t2_editor_ready' => '0'), 'rows' => array('t2_editor_rows' => '{"unexpected":"object"}'), 'title' => array('post_title' => str_repeat('a', 501)), 'cover' => array('t2_cover_id' => 'not-an-id')) as $code => $changes) {
        $result = t2_editor_validate($work, array_merge($input_for($work), $changes));
        $t2e_assert(is_wp_error($result) && $result->get_error_code() === $code, 'invalid ' . $code . ' form rejected');
    }
    $cross = $input_for($work); $cross['post_ID'] = (string) $other;
    $result = t2_editor_validate($other, $cross);
    $t2e_assert(is_wp_error($result) && $result->get_error_code() === 'nonce', 'nonce from another valid work cannot edit different work');
    $input = $input_for($work); wp_set_current_user(0); $result = t2_editor_validate($work, $input);
    $t2e_assert(is_wp_error($result) && $result->get_error_code() === 'forbidden', 'unauthorized user cannot save');
    wp_set_current_user((int) $admins[0]);
    $rows = t2_editor_rows(get_post($work)->post_content); $rows[] = array('type' => 'image', 'id' => 0, 'url' => 'https://example.com/arbitrary.jpg', 'alt' => '', 'caption' => '');
    $t2e_assert(is_wp_error(t2_editor_validate($work, $input_for($work, $rows))), 'new images must come from WordPress media');
    $rows = t2_editor_rows(get_post($work)->post_content); $rows[] = $rows[0];
    $t2e_assert(is_wp_error(t2_editor_validate($work, $input_for($work, $rows))), 'duplicate origins rejected');
    $rows = t2_editor_rows(get_post($work)->post_content); $rows[2]['id'] = array('invalid');
    $t2e_assert(is_wp_error(t2_editor_validate($work, $input_for($work, $rows))), 'malformed image ID does not detach an existing attachment reference');

    // Native admin pre-write guard must stop title, settings, categories and content together.
    $stale = $input_for($work);
    update_post_meta($work, '_t2_related_label', 'Newer metadata');
    $stale['post_title'] = 'Should never be saved'; $stale['t2_related_label'] = 'Should never be saved';
    $_POST = wp_slash($stale); $GLOBALS['t2_editor_validated'] = array(); $blocked = false;
    try {
        do_action('admin_action_editpost');
        wp_update_post(array('ID' => $work, 'post_title' => 'Should never be saved', 'meta_input' => array('_t2_related_label' => 'Should never be saved')));
    } catch (RuntimeException $error) { $blocked = strpos($error->getMessage(), 'Expected guard:') === 0; }
    $_POST = array();
    $t2e_assert($blocked && get_post($work)->post_title === $t2e_prefix . '-primary' && get_post_meta($work, '_t2_related_label', true) === 'Newer metadata' && get_post($work)->post_content === $body_before, 'stale snapshot blocks entire native save before any title, meta or content writes');

    // Save through native edit_post(), as post.php does after our guard.
    $term = wp_insert_term($t2e_prefix . '-category', 't2_work_category'); $t2e_terms[] = (int) $term['term_id'];
    $rows = t2_editor_rows(get_post($work)->post_content);
    $rows[2]['id'] = $second; $rows[2]['url'] = 'https://example.com/ignored-user-url.jpg'; $rows[2]['caption'] = 'Replaced image';
    $rows[1]['text'] = "Fixed fields 'quote'\n第二行說明";
    $rows = array($rows[2], $rows[1], $rows[0], array('type' => 'image', 'id' => $first, 'url' => $first_url, 'alt' => 'New row', 'caption' => ''));
    $input = $input_for($work, $rows);
    $input += array('post_type' => 't2_work', 'post_status' => 'draft', 'original_post_status' => 'draft', 't2_work_nonce' => wp_create_nonce('t2_save_work'), 't2_related_url' => 'https://example.com/final', 't2_related_label' => '前往網站', 't2_featured' => '1', 't2_display_order' => '7', 't2_service_ids' => array('web'), 't2_content_status' => 'complete', 'tax_input' => array('t2_work_category' => array((int) $term['term_id'])));
    $input['post_title'] = 'Fixed editor title'; $input['excerpt'] = 'One line summary'; $input['t2_cover_id'] = (string) $second; $input['t2_service_groups'] = array('event');
    $_POST = wp_slash($input); $GLOBALS['t2_editor_validated'] = array();
    do_action('admin_action_editpost');
    $saved = edit_post($_POST);
    $_POST = array(); $GLOBALS['t2_editor_validated'] = array();
    $t2e_assert($saved === $work && get_post($work)->post_title === 'Fixed editor title' && get_post($work)->post_excerpt === 'One line summary', 'native draft save uses fixed title and summary');
    $saved_rows = t2_editor_rows(get_post($work)->post_content);
    $t2e_assert(array_column($saved_rows, 'type') === array('image', 'text', 'heading', 'image') && $saved_rows[0]['id'] === $second && $saved_rows[3]['id'] === $first, 'image replacement, addition and row ordering persist');
    $t2e_assert($saved_rows[0]['url'] === $second_url && $saved_rows[1]['text'] === $rows[1]['text'], 'native save preserves quotes/newlines and enforces original media URL');
    $t2e_assert(get_post_thumbnail_id($work) === $second && get_post_meta($work, '_t2_related_url', true) === 'https://example.com/final' && get_post_meta($work, '_t2_featured', true) === '1' && get_post_meta($work, '_t2_service_ids', true) === array('web'), 'cover and existing display settings save with native form');
    $t2e_assert(wp_get_object_terms($work, 't2_work_category', array('fields' => 'ids')) === array((int) $term['term_id']), 'native taxonomy assignment saves');
    $t2e_assert(t2_portfolio_work_groups($work) === array('event') && get_post_meta($work, '_t2_service_ids', true) === array('web'), 'new fixed group saves independently without deleting or remapping legacy services');
    $t2e_assert(count(wp_get_post_revisions($work)) > 0, 'native revisions retain body history');
    $t2e_assert(get_post($first) && get_post($second), 'replacing and rearranging references never deletes attachments');

    // Empty/partial JS form must never reach native save.
    $invalid = $input_for($work); $invalid['t2_editor_ready'] = '0'; $invalid['t2_editor_rows'] = '[]';
    $_POST = wp_slash($invalid); $before_nojs = get_post($work)->post_content; $blocked = false;
    try { do_action('admin_action_editpost'); edit_post($_POST); } catch (RuntimeException $error) { $blocked = strpos($error->getMessage(), 'Expected guard:') === 0; }
    $_POST = array(); $GLOBALS['t2_editor_validated'] = array();
    $t2e_assert($blocked && get_post($work)->post_content === $before_nojs, 'uninitialized JavaScript cannot blank the body');

    $before_bad_groups = array(get_post($work)->post_title, get_post($work)->post_content, get_post_meta($work, '_t2_related_url', true), t2_portfolio_work_groups($work));
    $invalid = $input_for($work); $invalid['t2_service_groups'] = array('icon'); $invalid['post_title'] = 'Must not save'; $invalid['t2_related_url'] = 'https://example.com/must-not-save';
    $_POST = wp_slash($invalid); $blocked = false;
    try { do_action('admin_action_editpost'); edit_post($_POST); } catch (RuntimeException $error) { $blocked = strpos($error->getMessage(), 'Expected guard:') === 0; }
    $_POST = array(); $GLOBALS['t2_editor_validated'] = array();
    $t2e_assert($blocked && array(get_post($work)->post_title, get_post($work)->post_content, get_post_meta($work, '_t2_related_url', true), t2_portfolio_work_groups($work)) === $before_bad_groups, 'invalid service group blocks title, body and metadata as one save');

    $clear = $input_for($other); unset($clear['t2_service_groups']); $clear += array('post_type' => 't2_work', 'post_status' => 'draft', 'original_post_status' => 'draft');
    $_POST = wp_slash($clear); do_action('admin_action_editpost'); edit_post($_POST); $_POST = array(); $GLOBALS['t2_editor_validated'] = array();
    $t2e_assert(metadata_exists('post', $other, '_t2_service_groups') && get_post_meta($other, '_t2_service_groups', true) === array() && get_post_meta($other, '_t2_service_ids', true) === array('brand', 'motion', 'social'), 'unchecking all groups persists explicit empty while preserving legacy IDs');

    // Existing shutdown publisher still sees the complete native save, and only once.
    $t2e_watch = $work;
    $connected = t2_publish_save_connection(array('_wpnonce' => wp_create_nonce('t2_connect_site'), 'connection_credential' => 'github_pat_T2_EDITOR_LOCAL_FAKE_123456789', 'auto_publish' => '1'));
    $t2e_assert($connected === true, 'local mock publisher connected');
    $baseline = count($t2e_requests); $GLOBALS['t2_publish_request_changed'] = false;
    $input = $input_for($work); $input['post_title'] = 'Published fixed editor title'; $input['post_status'] = 'publish'; $input['original_post_status'] = 'draft'; $input['post_type'] = 't2_work'; $input['publish'] = 'Publish';
    $_POST = wp_slash($input); do_action('admin_action_editpost'); edit_post($_POST); $_POST = array(); $GLOBALS['t2_editor_validated'] = array();
    $t2e_assert(count($t2e_requests) === $baseline, 'native publish waits until end of complete save');
    t2_publish_flush();
    $last = end($t2e_requests);
    $t2e_assert(count($t2e_requests) === $baseline + 1 && $last['title'] === 'Published fixed editor title' && $last['cover'] === $second && $last['link'] === 'https://example.com/final' && strpos($last['content'], $second_url) !== false && $last['groups'] === array('event'), 'single publish request observes final fixed fields, service groups, cover and link');
    t2_publish_flush(); $t2e_assert(count($t2e_requests) === $baseline + 1, 'duplicate flush is suppressed');
    wp_update_post(array('ID' => $other, 'post_title' => 'Still a draft')); t2_publish_flush();
    $t2e_assert(count($t2e_requests) === $baseline + 1, 'ordinary drafts still do not trigger website updates');
    ob_start(); t2_editor_form(get_post($work)); $form_html = ob_get_clean();
    $t2e_assert(strpos($form_html, '上傳／選擇封面圖片') !== false && strpos($form_html, '上傳／選擇內頁圖片') !== false && strpos($form_html, 'name="t2_editor_ready" value="0"') !== false, 'fixed upload fields render with fail-safe initialization');
    $t2e_assert(substr_count($form_html, 'name="t2_service_groups[]"') === 4 && strpos($form_html, '新增標籤') !== false && strpos($form_html, '新增分類') === false && strpos($form_html, 't2_service_ids[]') === false, 'editor offers fixed group checkboxes and separate free tags');
    $request = new WP_REST_Request('GET'); $request['per_page'] = 100; $request['page'] = 1;
    $catalog = t2_portfolio_rest_works($request)->get_data(); $api_work = null;
    foreach ($catalog as $item) { if ($item['slug'] === get_post($work)->post_name) { $api_work = $item; break; } }
    $t2e_assert($api_work && $api_work['serviceGroups'] === array('event') && $api_work['serviceIds'] === array('web') && count($api_work['categories']) === 1, 'API keeps fixed groups and free tags as independent fields');
    echo wp_json_encode(array('passed' => $t2e_count, 'mock_requests' => count($t2e_requests), 'external_requests' => 0)) . "\n";
} finally {
    $_POST = array(); $GLOBALS['t2_editor_validated'] = array();
    update_option('t2_publish_enabled', '0', false); $GLOBALS['t2_publish_request_changed'] = false;
    foreach ($t2e_posts as $id) { wp_delete_post($id, true); }
    foreach ($t2e_attachments as $id) { wp_delete_attachment($id, true); }
    foreach ($t2e_terms as $id) { wp_delete_term($id, 't2_work_category'); }
    foreach ($t2e_options as $key => $value) { if ($value === null) { delete_option($key); } else { update_option($key, $value, false); } }
    $GLOBALS['t2_publish_request_changed'] = false;
    wp_set_current_user($t2e_user); $_POST = $t2e_post;
    remove_filter('pre_http_request', $t2e_http, PHP_INT_MAX);
    remove_filter('wp_die_handler', $t2e_die);
}

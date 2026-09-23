<?php
/** Server-side GitHub publishing. No credentials are exposed through REST or HTML. */
if (!defined('ABSPATH')) { exit; }

function t2_publish_repository() {
    $repository = defined('T2_GITHUB_REPOSITORY') ? T2_GITHUB_REPOSITORY : 'singlehorns/xutai-design';
    return is_string($repository) && preg_match('#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#D', $repository) ? $repository : '';
}

function t2_publish_enabled() { return get_option('t2_publish_enabled', '0') === '1'; }

function t2_publish_key() {
    return hash('sha256', wp_salt('auth') . wp_salt('secure_auth') . 't2-portfolio-publish-v1', true);
}

function t2_publish_encrypt($credential) {
    if (!function_exists('openssl_encrypt')) { return new WP_Error('encryption_unavailable', '主機尚未提供安全連線儲存功能，請聯絡管理者。'); }
    try {
        $iv = random_bytes(12);
        $tag = '';
        $encrypted = openssl_encrypt($credential, 'aes-256-gcm', t2_publish_key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($encrypted === false) { throw new RuntimeException('Encryption failed.'); }
        return wp_json_encode(array('v' => 1, 'iv' => base64_encode($iv), 'tag' => base64_encode($tag), 'data' => base64_encode($encrypted)));
    } catch (Throwable $error) {
        return new WP_Error('encryption_failed', '無法安全儲存連線授權，請稍後再試。');
    }
}

function t2_publish_credential() {
    if (defined('T2_GITHUB_TOKEN')) { return is_string(T2_GITHUB_TOKEN) ? T2_GITHUB_TOKEN : ''; }
    $stored = json_decode(get_option('t2_publish_connection', ''), true);
    if (!function_exists('openssl_decrypt') || !is_array($stored) || ($stored['v'] ?? 0) !== 1) { return ''; }
    foreach (array('iv', 'tag', 'data') as $field) { if (!isset($stored[$field]) || !is_string($stored[$field])) { return ''; } }
    $iv = base64_decode($stored['iv'] ?? '', true);
    $tag = base64_decode($stored['tag'] ?? '', true);
    $data = base64_decode($stored['data'] ?? '', true);
    if ($iv === false || strlen($iv) !== 12 || $tag === false || strlen($tag) !== 16 || $data === false) { return ''; }
    try { $credential = openssl_decrypt($data, 'aes-256-gcm', t2_publish_key(), OPENSSL_RAW_DATA, $iv, $tag); }
    catch (Throwable $error) { return ''; }
    return is_string($credential) ? $credential : '';
}

function t2_portfolio_dispatch_ready() {
    return t2_publish_repository() !== '' && t2_publish_credential() !== '';
}

/** No raw HTTP response/error/credential is ever persisted in status. */
function t2_publish_record($status, $error = '', $http_status = 0, $source = 'auto') {
    $state = array('status' => $status, 'error' => $error, 'http_status' => (int) $http_status, 'source' => $source, 'requested_at' => time());
    update_option('t2_publish_state', $state, false);
    return $state;
}

function t2_publish_dispatch($source = 'auto') {
    if ($source === 'auto' && !t2_publish_enabled()) { return false; }
    if (!t2_portfolio_dispatch_ready()) { t2_publish_record('failed', 'connection', 0, $source); return false; }
    $response = wp_remote_post('https://api.github.com/repos/' . t2_publish_repository() . '/actions/workflows/deploy.yml/dispatches', array(
        'timeout' => 10, 'redirection' => 0, 'blocking' => true,
        'headers' => array('Authorization' => 'Bearer ' . t2_publish_credential(), 'Accept' => 'application/vnd.github+json', 'Content-Type' => 'application/json', 'X-GitHub-Api-Version' => '2022-11-28'),
        'body' => wp_json_encode(array('ref' => 'main')),
    ));
    if (is_wp_error($response)) { t2_publish_record('failed', 'network', 0, $source); return false; }
    $code = (int) wp_remote_retrieve_response_code($response);
    if (in_array($code, array(200, 204), true)) { t2_publish_record('accepted', '', $code, $source); return true; }
    $error = in_array($code, array(401, 403), true) ? 'authorization' : ($code === 404 ? 'workflow' : ($code === 429 ? 'rate_limit' : 'remote'));
    t2_publish_record('failed', $error, $code, $source);
    return false;
}

function t2_publish_is_public($post) {
    return $post instanceof WP_Post && $post->post_type === 't2_work' && $post->post_status === 'publish' && $post->post_password === '';
}

function t2_publish_skip_post($id) {
    return (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($id) || wp_is_post_autosave($id);
}

function t2_publish_mark_changed() {
    if (t2_publish_enabled()) { $GLOBALS['t2_publish_request_changed'] = true; }
}

/** Dispatch after all writes in THIS request, including Gutenberg metadata and term assignments. */
function t2_publish_flush() {
    if (empty($GLOBALS['t2_publish_request_changed'])) { return; }
    $GLOBALS['t2_publish_request_changed'] = false;
    t2_publish_dispatch('auto');
}
add_action('shutdown', 't2_publish_flush', PHP_INT_MAX);

add_action('wp_after_insert_post', function ($id, $post, $update, $before) {
    if (t2_publish_skip_post($id)) { return; }
    if (t2_publish_is_public($post) || t2_publish_is_public($before)) { t2_publish_mark_changed(); }
}, 100, 4);

// Scheduled publishing and direct status changes also enter the same end-of-request queue.
add_action('transition_post_status', function ($new, $old, $post) {
    if ($post->post_type !== 't2_work' || t2_publish_skip_post($post->ID)) { return; }
    if ($new !== $old && $new === 'publish' && $post->post_password === '') { t2_publish_mark_changed(); }
}, 100, 3);

add_action('before_delete_post', function ($id, $post) {
    if (!t2_publish_skip_post($id) && t2_publish_is_public($post)) { t2_publish_mark_changed(); }
}, 10, 2);

function t2_publish_post_meta_changed($meta_id, $object_id, $key) {
    if (t2_publish_skip_post($object_id)) { return; }
    if (($key === '_thumbnail_id' || strpos($key, '_t2_') === 0) && t2_publish_is_public(get_post($object_id))) { t2_publish_mark_changed(); }
    if (in_array($key, array('_wp_attachment_image_alt', '_wp_attached_file', '_wp_attachment_metadata'), true) && t2_publish_attachment_used($object_id)) { t2_publish_mark_changed(); }
}
foreach (array('added_post_meta', 'updated_post_meta', 'deleted_post_meta') as $hook) { add_action($hook, 't2_publish_post_meta_changed', 100, 3); }

add_action('set_object_terms', function ($id, $terms, $tt_ids, $taxonomy) {
    if ($taxonomy === 't2_work_category' && !t2_publish_skip_post($id) && t2_publish_is_public(get_post($id))) { t2_publish_mark_changed(); }
}, 100, 4);

foreach (array('created_t2_work_category', 'edited_t2_work_category', 'delete_t2_work_category') as $hook) { add_action($hook, 't2_publish_mark_changed', 100, 0); }

/** Cover captions/alt and media replacements need a rebuild even if the work itself wasn't saved. */
function t2_publish_attachment_used($id) {
    if (get_post_type($id) !== 'attachment') { return false; }
    $works = get_posts(array('post_type' => 't2_work', 'post_status' => 'publish', 'has_password' => false, 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_thumbnail_id', 'meta_value' => (int) $id));
    return !empty($works);
}
add_action('edit_attachment', function ($id) { if (t2_publish_attachment_used($id)) { t2_publish_mark_changed(); } }, 100);
add_action('delete_attachment', function ($id) { if (t2_publish_attachment_used($id)) { t2_publish_mark_changed(); } }, 10);

/** Testable authorization gate shared by both admin POST handlers. */
function t2_publish_authorize($nonce, $action) {
    if (!current_user_can('manage_options')) { return new WP_Error('forbidden', '沒有管理權限。'); }
    if (!is_string($nonce) || !wp_verify_nonce($nonce, $action)) { return new WP_Error('invalid_nonce', '操作已過期，請重新整理後再試。'); }
    return true;
}

function t2_publish_save_connection($input) {
    $authorization = t2_publish_authorize($input['_wpnonce'] ?? '', 't2_connect_site');
    if (is_wp_error($authorization)) { return $authorization; }
    if (($input['connection_action'] ?? '') === 'disconnect') {
        delete_option('t2_publish_connection');
        update_option('t2_publish_enabled', '0', false);
        t2_publish_record('paused');
        return true;
    }
    if (isset($input['connection_credential']) && !is_string($input['connection_credential'])) { return new WP_Error('invalid_credential', '連線授權格式不正確，請重新貼上完整授權。'); }
    $credential = trim($input['connection_credential'] ?? '');
    if ($credential !== '' && !defined('T2_GITHUB_TOKEN')) {
        if (!preg_match('/^[A-Za-z0-9_]{20,255}$/D', $credential)) { return new WP_Error('invalid_credential', '連線授權格式不正確，請重新貼上完整授權。'); }
        $encrypted = t2_publish_encrypt($credential);
        if (is_wp_error($encrypted)) { return $encrypted; }
        update_option('t2_publish_connection', $encrypted, false);
    }
    if (!empty($input['auto_publish']) && !t2_portfolio_dispatch_ready()) { return new WP_Error('not_connected', '請先提供有效的連線授權。'); }
    update_option('t2_publish_enabled', !empty($input['auto_publish']) ? '1' : '0', false);
    if (t2_publish_enabled()) { t2_publish_dispatch('connect'); }
    else { t2_publish_record('paused'); }
    return true;
}

function t2_publish_retry($nonce) {
    $authorization = t2_publish_authorize($nonce, 't2_rebuild_site');
    if (is_wp_error($authorization)) { return $authorization; }
    return t2_publish_dispatch('manual');
}

function t2_publish_status_message($state) {
    if (!t2_portfolio_dispatch_ready()) { return '尚未連線，請管理者完成下方設定。'; }
    if (($state['status'] ?? '') === 'failed') {
        $messages = array(
            'authorization' => '更新請求未成功：連線授權已失效或權限不足，請管理者重新連線。',
            'workflow' => '更新請求未成功：找不到網站發布流程，請管理者確認連線。',
            'rate_limit' => '更新請求暫時受限，請稍後按「重試更新網站」。',
            'network' => '暫時無法連上發布服務；作品已儲存，請按「重試更新網站」。',
            'connection' => '連線需要重新設定；作品已儲存，請管理者重新連線。',
        );
        return $messages[$state['error'] ?? ''] ?? '更新請求未成功；作品已儲存，請稍後重試。';
    }
    if (!t2_publish_enabled()) { return '自動更新已暫停。'; }
    if (($state['status'] ?? '') === 'accepted') { return '更新請求已送出，等待網站建置與部署完成。'; }
    return '自動更新已就緒。';
}

add_action('admin_menu', function () {
    add_submenu_page('edit.php?post_type=t2_work', '網站更新', '網站更新', 'manage_options', 't2-site-update', 't2_portfolio_update_page');
});

function t2_portfolio_update_page() {
    if (!current_user_can('manage_options')) { return; }
    $state = get_option('t2_publish_state', array());
    $ready = t2_portfolio_dispatch_ready();
    echo '<div class="wrap"><h1>網站自動更新</h1><p>啟用後，發布、修改或下架作品，以及調整服務大項或作品標籤時，都會自動更新網站。一般儲存草稿不會發布到前台。</p>';
    $notice = get_transient('t2_connection_notice_' . get_current_user_id());
    if ($notice) { echo '<div class="notice notice-error"><p>' . esc_html($notice) . '</p></div>'; delete_transient('t2_connection_notice_' . get_current_user_id()); }
    echo '<div class="notice ' . (($state['status'] ?? '') === 'failed' ? 'notice-error' : 'notice-info') . '"><p>' . esc_html(t2_publish_status_message($state)) . '</p></div>';
    if (!empty($state['requested_at'])) { echo '<p>最近一次更新狀態：' . esc_html(wp_date('Y/m/d H:i:s', (int) $state['requested_at'])) . '</p>'; }
    echo '<p>作品儲存完成後，建置與部署通常需要幾分鐘。完成前保留上次成功的網站；收到更新請求不代表部署已完成。</p>';
    if ($ready) {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('t2_rebuild_site');
        echo '<input type="hidden" name="action" value="t2_rebuild_site" />';
        submit_button(($state['status'] ?? '') === 'failed' ? '重試更新網站' : '立即更新網站', 'secondary');
        echo '</form>';
    }
    echo '<p><a target="_blank" rel="noopener noreferrer" href="' . esc_url('https://github.com/' . t2_publish_repository() . '/actions/workflows/deploy.yml') . '">查看網站更新進度</a></p>';
    echo '<details' . (!$ready ? ' open' : '') . '><summary>管理員連線設定</summary><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('t2_connect_site');
    echo '<input type="hidden" name="action" value="t2_connect_site" />';
    if (!defined('T2_GITHUB_TOKEN')) {
        echo '<p><label for="t2-connection-credential">網站發布授權</label><br /><input type="password" id="t2-connection-credential" name="connection_credential" autocomplete="new-password" class="regular-text" value="" /></p>';
        echo '<p>' . ($ready ? '已保存連線授權。留空會保留現有授權；需要更換時再貼上新的授權。' : '請貼上僅允許此網站發布的專用 GitHub 授權。儲存後不會再顯示授權內容。') . '</p>';
    } else { echo '<p>連線授權由主機管理者提供。</p>'; }
    echo '<p><label><input type="checkbox" name="auto_publish" value="1" ' . checked(t2_publish_enabled() || !$ready, true, false) . ' /> 啟用自動更新網站</label></p>';
    echo '<button class="button button-primary" type="submit" name="connection_action" value="save">儲存連線並確認</button>';
    if ($ready) { echo ' <button class="button" type="submit" name="connection_action" value="disconnect">關閉自動更新' . (!defined('T2_GITHUB_TOKEN') ? '並移除授權' : '') . '</button>'; }
    echo '</form></details></div>';
}

add_action('admin_post_t2_connect_site', function () {
    $result = t2_publish_save_connection(wp_unslash($_POST));
    if (is_wp_error($result)) {
        if (in_array($result->get_error_code(), array('forbidden', 'invalid_nonce'), true)) { wp_die(esc_html($result->get_error_message()), '', array('response' => 403)); }
        set_transient('t2_connection_notice_' . get_current_user_id(), $result->get_error_message(), 120);
    }
    wp_safe_redirect(add_query_arg(array('post_type' => 't2_work', 'page' => 't2-site-update'), admin_url('edit.php')));
    exit;
});

add_action('admin_post_t2_rebuild_site', function () {
    $result = t2_publish_retry(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')));
    if (is_wp_error($result)) { wp_die(esc_html($result->get_error_message()), '', array('response' => 403)); }
    wp_safe_redirect(add_query_arg(array('post_type' => 't2_work', 'page' => 't2-site-update'), admin_url('edit.php')));
    exit;
});

add_action('admin_notices', function () {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 't2_work' || !t2_publish_enabled()) { return; }
    $state = get_option('t2_publish_state', array());
    if (($state['status'] ?? '') === 'failed') { echo '<div class="notice notice-warning"><p>作品已儲存，但網站更新請求未成功。請管理者前往「網站更新」確認連線並重試。</p></div>'; }
});

<?php
/**
 * Plugin Name: T2 作品管理
 * Description: 管理作品、分類、作品內容與單一相關連結，提供 Astro 靜態網站的公開內容 API。
 * Version: 1.1.0
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: T2
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) { exit; }

function t2_portfolio_register() {
    register_post_type('t2_work', array(
        'labels' => array('name' => '作品案例', 'singular_name' => '作品', 'add_new' => '新增作品', 'add_new_item' => '新增作品', 'edit_item' => '編輯作品', 'all_items' => '所有作品', 'featured_image' => '作品封面', 'set_featured_image' => '設定作品封面', 'remove_featured_image' => '移除作品封面', 'not_found' => '尚無作品'),
        'public' => false, 'show_ui' => true, 'show_in_rest' => true, 'rest_base' => 't2-works',
        'menu_icon' => 'dashicons-portfolio', 'menu_position' => 20,
        'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions'),
        'taxonomies' => array('t2_work_category'), 'capability_type' => 'post', 'map_meta_cap' => true,
    ));
    register_taxonomy('t2_work_category', array('t2_work'), array(
        'labels' => array('name' => '作品分類', 'singular_name' => '作品分類', 'add_new_item' => '新增作品分類', 'edit_item' => '編輯作品分類', 'search_items' => '搜尋作品分類'),
        'public' => false, 'show_ui' => true, 'show_admin_column' => true, 'show_in_rest' => true,
        'rest_base' => 't2-work-categories', 'hierarchical' => true,
    ));
    add_post_type_support('t2_work', 'thumbnail');
}
add_action('init', 't2_portfolio_register');
add_action('after_setup_theme', function () { add_theme_support('post-thumbnails'); });

add_filter('allowed_block_types_all', function ($allowed, $context) {
    if (empty($context->post) || $context->post->post_type !== 't2_work') { return $allowed; }
    return array('core/paragraph', 'core/heading', 'core/list', 'core/list-item', 'core/image', 'core/gallery', 'core/quote', 'core/table', 'core/separator', 'core/code', 'core/preformatted');
}, 10, 2);

function t2_portfolio_services() { return array('brand' => '品牌與視覺', 'print' => '印刷與輸出', 'web' => '網站', 'social' => '社群與廣告', 'motion' => '影音'); }

add_action('add_meta_boxes_t2_work', function () {
    add_meta_box('t2-work-settings', '作品顯示設定', 't2_portfolio_meta_box', 't2_work', 'normal', 'high');
});

function t2_portfolio_meta_box($post) {
    wp_nonce_field('t2_save_work', 't2_work_nonce');
    $meta = function ($key) use ($post) { return get_post_meta($post->ID, '_t2_' . $key, true); };
    $services = (array) $meta('service_ids');
    ?>
    <p>標題用於作品名稱；「摘要」填寫一行簡短描述。上方編輯器可以依順序新增文字、圖片與圖庫；右側「作品封面」用於作品列表。分類會同步顯示於篩選列和作品內頁。</p>
    <p><label><input type="checkbox" name="t2_featured" value="1" <?php checked($meta('featured'), '1'); ?> /> 顯示於首頁精選作品</label></p>
    <p><label>顯示順序（數字越小越前面） <input type="number" name="t2_display_order" value="<?php echo esc_attr($meta('display_order') ?: 0); ?>" /></label></p>
    <p><label for="t2-related-url">相關連結（選填，只顯示一個）</label><br /><input class="widefat" id="t2-related-url" type="url" name="t2_related_url" placeholder="https://example.com/" value="<?php echo esc_attr($meta('related_url')); ?>" /></p>
    <p><label for="t2-related-label">連結文字</label><br /><input class="widefat" id="t2-related-label" name="t2_related_label" maxlength="120" placeholder="前往網站" value="<?php echo esc_attr($meta('related_label')); ?>" /></p>
    <p>相關連結留空時，作品頁不顯示「相關連結」區塊。</p>
    <p><label>客戶／品牌（選填） <input name="t2_client_name" value="<?php echo esc_attr($meta('client_name')); ?>" /></label></p>
    <p><label>年份（選填） <input name="t2_year" value="<?php echo esc_attr($meta('year')); ?>" /></label></p>
    <fieldset><legend>對應服務（保留服務頁與作品的對應，不影響自訂分類）</legend>
    <?php foreach (t2_portfolio_services() as $key => $label) : ?>
        <label style="display:inline-block;margin:8px 16px 8px 0"><input type="checkbox" name="t2_service_ids[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $services, true)); ?> /> <?php echo esc_html($label); ?></label>
    <?php endforeach; ?>
    </fieldset>
    <p><label>資料整理狀態 <select name="t2_content_status">
    <?php foreach (array('complete' => '資料完整', 'partial' => '部分資料待補', 'legacy' => '舊作紀錄') as $key => $label) : ?>
        <option value="<?php echo esc_attr($key); ?>" <?php selected($meta('content_status') ?: 'complete', $key); ?>><?php echo esc_html($label); ?></option>
    <?php endforeach; ?></select></label></p>
    <p><?php echo t2_publish_enabled() && t2_portfolio_dispatch_ready() ? '發布／更新後，系統會自動更新網站；建置與部署完成前，前台維持上一次成功版本。' : '作品儲存後，請由管理者在「網站更新」完成自動更新連線。'; ?>草稿、私人作品及有密碼的作品不會出現在公開內容 API。</p>
    <?php
}

add_action('save_post_t2_work', function ($id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || wp_is_post_revision($id) || !current_user_can('edit_post', $id)) { return; }
    if (!isset($_POST['t2_work_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['t2_work_nonce'])), 't2_save_work')) { return; }
    foreach (array('related_label', 'client_name', 'year') as $key) {
        update_post_meta($id, '_t2_' . $key, sanitize_text_field(wp_unslash($_POST['t2_' . $key] ?? '')));
    }
    $url = esc_url_raw(wp_unslash($_POST['t2_related_url'] ?? ''), array('http', 'https'));
    if ($url && !preg_match('#^https?://#i', $url)) { $url = ''; }
    update_post_meta($id, '_t2_related_url', $url);
    update_post_meta($id, '_t2_featured', isset($_POST['t2_featured']) ? '1' : '0');
    update_post_meta($id, '_t2_display_order', intval($_POST['t2_display_order'] ?? 0));
    $services = isset($_POST['t2_service_ids']) && is_array($_POST['t2_service_ids']) ? array_map('sanitize_key', wp_unslash($_POST['t2_service_ids'])) : array();
    update_post_meta($id, '_t2_service_ids', array_values(array_intersect(array_keys(t2_portfolio_services()), $services)));
    $status = sanitize_key($_POST['t2_content_status'] ?? 'complete');
    update_post_meta($id, '_t2_content_status', in_array($status, array('complete', 'partial', 'legacy'), true) ? $status : 'complete');
});

function t2_portfolio_plain($value) { return html_entity_decode(wp_strip_all_tags($value), ENT_QUOTES, 'UTF-8'); }
function t2_portfolio_term($term) { return array('key' => rawurldecode($term->slug), 'label' => t2_portfolio_plain($term->name)); }

function t2_portfolio_rest_categories() {
    $terms = get_terms(array('taxonomy' => 't2_work_category', 'hide_empty' => false, 'orderby' => 'term_id', 'order' => 'ASC'));
    if (is_wp_error($terms)) { return $terms; }
    $response = rest_ensure_response(array_values(array_map('t2_portfolio_term', $terms)));
    $response->header('Cache-Control', 'no-store');
    return $response;
}

function t2_portfolio_full_image($html, $block) {
    if (($block['blockName'] ?? '') !== 'core/image' || empty($block['attrs']['id']) || ($block['attrs']['sizeSlug'] ?? '') !== 'full') { return $html; }
    $original = wp_get_original_image_url((int) $block['attrs']['id']);
    if (!$original) { return $html; }
    // Keep intentionally smaller image sizes; "full" means the uploaded original.
    $processor = new WP_HTML_Tag_Processor($html);
    if ($processor->next_tag('img')) {
        $processor->set_attribute('src', $original);
        foreach (array('srcset', 'sizes', 'width', 'height') as $attribute) { $processor->remove_attribute($attribute); }
    }
    return $processor->get_updated_html();
}

function t2_portfolio_content_html($content) {
    add_filter('render_block', 't2_portfolio_full_image', 10, 2);
    try { return wp_kses_post(do_blocks($content)); }
    finally { remove_filter('render_block', 't2_portfolio_full_image', 10); }
}

function t2_portfolio_rest_works($request) {
    $query = new WP_Query(array(
        'post_type' => 't2_work', 'post_status' => 'publish', 'has_password' => false,
        'posts_per_page' => $request['per_page'], 'paged' => $request['page'],
        'orderby' => 'ID', 'order' => 'ASC', 'ignore_sticky_posts' => true,
    ));
    $items = array();
    foreach ($query->posts as $post) {
        $meta = function ($key) use ($post) { return get_post_meta($post->ID, '_t2_' . $key, true); };
        $terms = wp_get_object_terms($post->ID, 't2_work_category', array('orderby' => 'term_id'));
        $cover = null;
        $cover_id = get_post_thumbnail_id($post);
        if ($cover_id && wp_get_attachment_url($cover_id)) {
            $cover = array('src' => wp_get_original_image_url($cover_id) ?: wp_get_attachment_url($cover_id), 'alt' => t2_portfolio_plain(get_post_meta($cover_id, '_wp_attachment_image_alt', true)), 'caption' => t2_portfolio_plain(wp_get_attachment_caption($cover_id)));
        }
        $url = esc_url_raw($meta('related_url'), array('http', 'https'));
        $status = $meta('content_status');
        $items[] = array(
            'slug' => rawurldecode($post->post_name), 'title' => t2_portfolio_plain($post->post_title),
            'summary' => t2_portfolio_plain($post->post_excerpt),
            'categories' => is_wp_error($terms) ? array() : array_values(array_map('t2_portfolio_term', $terms)),
            // Only supported core blocks; no shortcodes or arbitrary content filters are executed.
            'contentHtml' => t2_portfolio_content_html($post->post_content), 'coverImage' => $cover,
            'relatedLink' => $url ? array('href' => $url, 'label' => $meta('related_label') ?: '前往網站') : null,
            'featured' => $meta('featured') === '1', 'displayOrder' => intval($meta('display_order')),
            'serviceIds' => array_values(array_intersect(array_keys(t2_portfolio_services()), (array) $meta('service_ids'))),
            'contentStatus' => in_array($status, array('complete', 'partial', 'legacy'), true) ? $status : 'complete',
            'clientName' => t2_portfolio_plain($meta('client_name')), 'year' => t2_portfolio_plain($meta('year')),
        );
    }
    $response = rest_ensure_response($items);
    $response->header('X-WP-Total', (string) $query->found_posts);
    $response->header('X-WP-TotalPages', (string) $query->max_num_pages);
    $response->header('Cache-Control', 'no-store');
    return $response;
}

add_action('rest_api_init', function () {
    register_rest_route('t2-portfolio/v1', '/categories', array('methods' => 'GET', 'callback' => 't2_portfolio_rest_categories', 'permission_callback' => '__return_true'));
    register_rest_route('t2-portfolio/v1', '/works', array(
        'methods' => 'GET', 'callback' => 't2_portfolio_rest_works', 'permission_callback' => '__return_true',
        'args' => array('page' => array('default' => 1, 'type' => 'integer', 'minimum' => 1, 'maximum' => 10000), 'per_page' => array('default' => 100, 'type' => 'integer', 'minimum' => 1, 'maximum' => 100)),
    ));
});

require_once __DIR__ . '/publish.php';

/** Import only explicitly supplied local media; never download a URL from a manifest. */
function t2_portfolio_import_media($filename, $root, $alt) {
    if (!is_string($filename) || basename($filename) !== $filename) { throw new RuntimeException('Invalid media filename.'); }
    $source = realpath($root . DIRECTORY_SEPARATOR . $filename);
    if (!$source || strpos($source, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($source) || filesize($source) > 25 * 1024 * 1024) { throw new RuntimeException('Media outside approved directory or too large: ' . $filename); }
    $type = wp_check_filetype($source, array('jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'));
    if (!$type['type'] || !wp_get_image_mime($source)) { throw new RuntimeException('Unsupported media: ' . $filename); }
    $hash = hash_file('sha256', $source);
    $existing = get_posts(array('post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_t2_import_hash', 'meta_value' => $hash, 'numberposts' => 1, 'fields' => 'ids'));
    if ($existing) { return $existing[0]; }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $temporary = wp_tempnam($filename);
    if (!$temporary || !copy($source, $temporary)) { throw new RuntimeException('Cannot copy media.'); }
    add_filter('big_image_size_threshold', '__return_false');
    try { $id = media_handle_sideload(array('name' => $filename, 'tmp_name' => $temporary), 0); }
    finally { remove_filter('big_image_size_threshold', '__return_false'); }
    if (is_wp_error($id)) { if (file_exists($temporary)) { unlink($temporary); } throw new RuntimeException($id->get_error_message()); }
    update_post_meta($id, '_t2_import_hash', $hash);
    update_post_meta($id, '_wp_attachment_image_alt', sanitize_text_field($alt));
    return $id;
}

function t2_portfolio_import_blocks($blocks, $media_ids) {
    foreach ($blocks as &$block) {
        if (($block['blockName'] ?? '') === 'core/image') {
            foreach ($media_ids as $filename => $id) {
                $marker = 't2-media://' . $filename;
                if (strpos($block['innerHTML'], $marker) !== false) {
                    $block['attrs']['id'] = (int) $id;
                    $replace = function ($html) use ($marker, $id) {
                        $html = str_replace($marker, esc_url(wp_get_attachment_url($id)), $html);
                        return str_replace('<img ', '<img class="wp-image-' . intval($id) . '" ', $html);
                    };
                    $block['innerHTML'] = $replace($block['innerHTML']);
                    $block['innerContent'] = array_map(function ($html) use ($replace) { return is_string($html) ? $replace($html) : $html; }, $block['innerContent']);
                    break;
                }
            }
        }
        if (!empty($block['innerBlocks'])) { $block['innerBlocks'] = t2_portfolio_import_blocks($block['innerBlocks'], $media_ids); }
    }
    return $blocks;
}

/** Import the public portfolio export. Existing work slugs are preserved and skipped. */
function t2_portfolio_import($manifest_path, $media_root) {
        $path = realpath($manifest_path);
        $root = realpath($media_root);
        if (!$path || !is_file($path) || !$root || !is_dir($root) || filesize($path) > 10 * 1024 * 1024) { throw new RuntimeException('Invalid manifest or media directory.'); }
        $manifest = json_decode(file_get_contents($path), true);
        if (!is_array($manifest) || ($manifest['version'] ?? 0) !== 1 || !isset($manifest['works'], $manifest['categories']) || !is_array($manifest['works']) || !is_array($manifest['categories']) || count($manifest['works']) > 100) { throw new RuntimeException('Invalid T2 public export.'); }
        foreach ($manifest['categories'] as $term) {
            if (empty($term['key']) || empty($term['label']) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $term['key'])) { throw new RuntimeException('Invalid category.'); }
        }
        foreach ($manifest['works'] as $item) {
            if (empty($item['slug']) || empty($item['title']) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $item['slug']) || !isset($item['images']) || !is_array($item['images'])) { throw new RuntimeException('Invalid work.'); }
            foreach ($item['images'] as $media) {
                if (empty($media['filename']) || basename($media['filename']) !== $media['filename'] || !is_file($root . DIRECTORY_SEPARATOR . $media['filename'])) { throw new RuntimeException('Missing or invalid local media.'); }
            }
        }
        $category_ids = array();
        foreach ($manifest['categories'] as $term) {
            $existing = get_term_by('slug', $term['key'], 't2_work_category');
            $result = $existing ? array('term_id' => $existing->term_id) : wp_insert_term(sanitize_text_field($term['label']), 't2_work_category', array('slug' => $term['key']));
            if (is_wp_error($result)) { throw new RuntimeException($result->get_error_message()); }
            $category_ids[$term['key']] = (int) $result['term_id'];
        }
        $created = 0;
        $skipped = 0;
        foreach ($manifest['works'] as $item) {
            if (get_page_by_path($item['slug'], OBJECT, 't2_work')) { $skipped++; continue; }
            $media_ids = array();
            try {
                foreach ($item['images'] as $media) { $media_ids[$media['filename']] = t2_portfolio_import_media($media['filename'], $root, $media['alt'] ?? $item['title']); }
            } catch (RuntimeException $error) { throw $error; }
            $content = serialize_blocks(t2_portfolio_import_blocks(parse_blocks($item['contentHtml'] ?? ''), $media_ids));
            $id = wp_insert_post(wp_slash(array('post_type' => 't2_work', 'post_status' => 'publish', 'post_name' => $item['slug'], 'post_title' => sanitize_text_field($item['title']), 'post_excerpt' => sanitize_textarea_field($item['summary'] ?? ''), 'post_content' => wp_kses_post($content))), true);
            if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
            $assigned = array();
            foreach (($item['categories'] ?? array()) as $key) { if (isset($category_ids[$key])) { $assigned[] = $category_ids[$key]; } }
            wp_set_object_terms($id, $assigned, 't2_work_category');
            if (!empty($item['cover']) && isset($media_ids[$item['cover']])) { set_post_thumbnail($id, $media_ids[$item['cover']]); }
            update_post_meta($id, '_t2_featured', !empty($item['featured']) ? '1' : '0');
            update_post_meta($id, '_t2_display_order', intval($item['displayOrder'] ?? 0));
            update_post_meta($id, '_t2_service_ids', array_values(array_intersect(array_keys(t2_portfolio_services()), $item['serviceIds'] ?? array())));
            foreach (array('clientName' => 'client_name', 'year' => 'year', 'contentStatus' => 'content_status') as $source => $key) { update_post_meta($id, '_t2_' . $key, sanitize_text_field($item[$source] ?? '')); }
            $link = $item['relatedLink'] ?? null;
            update_post_meta($id, '_t2_related_url', $link ? esc_url_raw($link['href'], array('http', 'https')) : '');
            update_post_meta($id, '_t2_related_label', $link ? sanitize_text_field($link['label']) : '');
            $created++;
        }
        return array('created' => $created, 'skipped' => $skipped);
}

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('t2 import', function ($args, $assoc) {
        if (!isset($args[0], $assoc['media-dir'])) { WP_CLI::error('Usage: wp t2 import manifest.json --media-dir=/absolute/path/to/images'); }
        try { $result = t2_portfolio_import($args[0], $assoc['media-dir']); }
        catch (Throwable $error) { WP_CLI::error($error->getMessage()); }
        WP_CLI::success('Imported ' . $result['created'] . ' works; skipped ' . $result['skipped'] . ' existing works.');
    });
}

add_action('admin_menu', function () {
    add_submenu_page('edit.php?post_type=t2_work', '匯入現有作品', '匯入現有作品', 'manage_options', 't2-import', function () {
        if (!current_user_can('manage_options')) { return; }
        echo '<div class="wrap"><h1>匯入現有作品</h1><p>上傳由本網站匯出的作品 ZIP（包含 manifest.json 與 images 資料夾）。相同代稱的作品會略過，不會覆蓋已編輯的內容。</p>';
        $notice = get_transient('t2_import_notice_' . get_current_user_id());
        if ($notice) { echo '<div class="notice notice-info"><p>' . esc_html($notice) . '</p></div>'; delete_transient('t2_import_notice_' . get_current_user_id()); }
        if (!class_exists('ZipArchive')) { echo '<p>此主機尚未啟用 PHP ZipArchive，請主機管理者啟用，或使用 WP-CLI 匯入。</p></div>'; return; }
        echo '<form method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('t2_import_works');
        echo '<input type="hidden" name="action" value="t2_import_works" /><input required type="file" accept=".zip,application/zip" name="t2_export" />';
        submit_button('匯入作品');
        echo '</form></div>';
    });
});

/** Bounded ZIP extraction, with no archive paths used as filesystem paths. */
function t2_portfolio_import_zip($file) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    if (!class_exists('ZipArchive') || !is_file($file) || filesize($file) > 100 * 1024 * 1024) { throw new RuntimeException('ZIP 不存在、超過 100MB，或主機不支援 ZIP。'); }
    $zip = new ZipArchive();
    if ($zip->open($file) !== true) { throw new RuntimeException('無法讀取 ZIP。'); }
    $temporary = wp_tempnam('t2-import');
    if (!$temporary) { $zip->close(); throw new RuntimeException('無法建立暫存資料夾。'); }
    unlink($temporary);
    $written = array();
    $root_created = false;
    try {
        if ($zip->numFiles > 150) { throw new RuntimeException('ZIP 檔案數量過多。'); }
        $entries = array();
        $expanded_size = 0;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->statIndex($index);
            $name = $entry['name'];
            if ($name === 'images/') { continue; }
            if ($name !== 'manifest.json' && !preg_match('#^images/([a-zA-Z0-9._-]+\.(?:jpg|jpeg|png|webp|gif))$#D', $name)) { throw new RuntimeException('ZIP 包含不允許的路徑或檔案。'); }
            if (isset($entries[$name]) || $entry['size'] > 25 * 1024 * 1024) { throw new RuntimeException('ZIP 包含重複檔案或過大的圖片。'); }
            $expanded_size += $entry['size'];
            if ($expanded_size > 100 * 1024 * 1024) { throw new RuntimeException('ZIP 解壓縮後超過 100MB。'); }
            $entries[$name] = $index;
        }
        if (!isset($entries['manifest.json'])) { throw new RuntimeException('ZIP 缺少 manifest.json。'); }
        if (!wp_mkdir_p($temporary . '/images')) { throw new RuntimeException('無法建立暫存資料夾。'); }
        $root_created = true;
        foreach ($entries as $name => $index) {
            $contents = $zip->getFromIndex($index);
            $destination = $name === 'manifest.json' ? $temporary . '/manifest.json' : $temporary . '/images/' . basename($name);
            if ($contents === false || file_put_contents($destination, $contents) === false) { throw new RuntimeException('無法解壓縮作品資料。'); }
            $written[] = $destination;
        }
        return t2_portfolio_import($temporary . '/manifest.json', $temporary . '/images');
    } finally {
        $zip->close();
        foreach ($written as $path) { if (is_file($path)) { unlink($path); } }
        if ($root_created) { rmdir($temporary . '/images'); rmdir($temporary); }
    }
}

add_action('admin_post_t2_import_works', function () {
    if (!current_user_can('manage_options')) { wp_die('沒有權限。', '', array('response' => 403)); }
    check_admin_referer('t2_import_works');
    try {
        if (!isset($_FILES['t2_export']) || $_FILES['t2_export']['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['t2_export']['tmp_name'])) { throw new RuntimeException('上傳未成功，請檢查主機上傳容量限制。'); }
        $result = t2_portfolio_import_zip($_FILES['t2_export']['tmp_name']);
        $notice = '已匯入 ' . $result['created'] . ' 個作品，略過 ' . $result['skipped'] . ' 個既有作品。請確認作品內容後再更新前台。';
    } catch (Throwable $error) { $notice = '匯入未完成：' . $error->getMessage(); }
    set_transient('t2_import_notice_' . get_current_user_id(), $notice, 120);
    wp_safe_redirect(add_query_arg(array('post_type' => 't2_work', 'page' => 't2-import'), admin_url('edit.php')));
    exit;
});

<?php
/** Fixed portfolio fields on WordPress's native edit/save/revision screen. */
if (!defined('ABSPATH')) { exit; }

add_filter('use_block_editor_for_post_type', function ($enabled, $type) { return $type === 't2_work' ? false : $enabled; }, 100, 2);
add_filter('enter_title_here', function ($label, $post) { return $post->post_type === 't2_work' ? '作品名稱' : $label; }, 10, 2);

function t2_editor_original_url($id, $fallback = '') {
    return $id && wp_attachment_is_image($id) ? (wp_get_original_image_url($id) ?: wp_get_attachment_url($id)) : $fallback;
}

/** Snapshot includes all editable data so stale tabs cannot overwrite newer changes. */
function t2_editor_snapshot($id) {
    $post = get_post($id);
    $terms = wp_get_object_terms($id, 't2_work_category', array('fields' => 'ids'));
    if (is_wp_error($terms)) { $terms = array(); }
    sort($terms, SORT_NUMERIC);
    $meta = array();
    foreach (array('_thumbnail_id', '_t2_related_url', '_t2_related_label', '_t2_featured', '_t2_display_order', '_t2_client_name', '_t2_year', '_t2_service_ids', '_t2_content_status') as $key) { $meta[$key] = get_post_meta($id, $key, true); }
    $meta['_t2_service_groups'] = array(metadata_exists('post', $id, '_t2_service_groups'), get_post_meta($id, '_t2_service_groups', true));
    return hash('sha256', wp_json_encode(array($post->post_title, $post->post_excerpt, $post->post_content, $post->post_status, $post->post_password, $post->post_name, $terms, $meta)));
}

function t2_editor_plain_inner($html, $tag) {
    if (!preg_match('#^\s*<' . $tag . '(?:\s[^>]*)?>([\s\S]*)</' . $tag . '>\s*$#i', $html, $match)) { return null; }
    $inner = preg_replace('#<br\s*/?>(?:\r?\n)?#i', "\n", $match[1]);
    if (preg_match('/<[^>]+>/', $inner)) { return null; }
    return html_entity_decode($inner, ENT_QUOTES, 'UTF-8');
}

/** Parse only reversible basic rows; all other blocks remain server-owned raw records. */
function t2_editor_rows($content) {
    $rows = array();
    foreach (parse_blocks($content) as $block) {
        if ($block['blockName'] === 'core/gallery' && !empty($block['innerBlocks']) && !array_diff(array_keys($block['attrs']), array('linkTo', 'columns', 'imageCrop', 'sizeSlug')) && ($block['attrs']['linkTo'] ?? 'none') === 'none' && stripos($block['innerHTML'], '<figcaption') === false) {
            $children_are_images = count(array_filter($block['innerBlocks'], function ($child) { return $child['blockName'] === 'core/image'; })) === count($block['innerBlocks']);
            $children = $children_are_images ? t2_editor_rows(serialize_blocks($block['innerBlocks'])) : array();
            if ($children && count(array_filter($children, function ($child) { return $child['type'] === 'image'; })) === count($children)) {
                foreach ($children as $child) { $child['origin'] = (string) count($rows); $child['flatten_gallery'] = true; $rows[] = $child; }
                continue;
            }
        }
        $raw = serialize_block($block);
        if (trim($raw) === '') { continue; }
        $row = array('type' => 'preserved', 'raw' => $raw, 'origin' => (string) count($rows));
        $name = $block['blockName'];
        if ($name === 'core/paragraph' && empty($block['attrs'])) {
            $plain = t2_editor_plain_inner($block['innerHTML'], 'p');
            if ($plain !== null) { $row['type'] = 'text'; $row['text'] = $plain; }
        } elseif ($name === 'core/heading' && !array_diff(array_keys($block['attrs']), array('level'))) {
            $level = (int) ($block['attrs']['level'] ?? 2);
            $plain = in_array($level, array(2, 3, 4), true) ? t2_editor_plain_inner($block['innerHTML'], 'h' . $level) : null;
            if ($plain !== null) { $row['type'] = 'heading'; $row['text'] = $plain; $row['level'] = $level; }
        } elseif ($name === 'core/image' && !array_diff(array_keys($block['attrs']), array('id', 'sizeSlug', 'linkDestination')) && ($block['attrs']['linkDestination'] ?? 'none') === 'none' && !preg_match('#<(?:a|video|svg|script)\b|\sstyle=#i', $block['innerHTML'])) {
            $processor = new WP_HTML_Tag_Processor($block['innerHTML']);
            if ($processor->next_tag('img')) {
                $src = $processor->get_attribute('src');
                $alt = $processor->get_attribute('alt');
                $id = (int) ($block['attrs']['id'] ?? 0);
                if ($id && !wp_attachment_is_image($id)) { $id = 0; }
                $caption = '';
                if (preg_match('#<figcaption(?:\s[^>]*)?>([\s\S]*?)</figcaption>#i', $block['innerHTML'], $match)) { $caption = t2_editor_plain_inner('<p>' . $match[1] . '</p>', 'p'); }
                if (is_string($src) && preg_match('#^https?://#i', $src) && $caption !== null && !$processor->next_tag('img')) {
                    $url = t2_editor_original_url($id, $src);
                    $row = array_merge($row, array('type' => 'image', 'id' => $id, 'url' => $url, 'alt' => is_string($alt) ? $alt : '', 'caption' => $caption, 'normalize_image' => $id && ($src !== $url || ($block['attrs']['sizeSlug'] ?? '') !== 'full')));
                }
            }
        }
        $rows[] = $row;
    }
    return $rows;
}

function t2_editor_row_fields($row) {
    $keys = $row['type'] === 'image' ? array('type', 'id', 'url', 'alt', 'caption') : ($row['type'] === 'heading' ? array('type', 'text', 'level') : array('type', 'text'));
    return array_intersect_key($row, array_flip($keys));
}

function t2_editor_row_html($row) {
    if ($row['type'] === 'image') {
        $attrs = array('sizeSlug' => 'full', 'linkDestination' => 'none');
        if ($row['id']) { $attrs['id'] = $row['id']; }
        return '<!-- wp:image ' . wp_json_encode($attrs) . ' --><figure class="wp-block-image size-full"><img src="' . esc_url($row['url']) . '" alt="' . esc_attr($row['alt']) . '"' . ($row['id'] ? ' class="wp-image-' . $row['id'] . '"' : '') . '/>' . ($row['caption'] !== '' ? '<figcaption class="wp-element-caption">' . str_replace("\n", '<br>', esc_html($row['caption'])) . '</figcaption>' : '') . '</figure><!-- /wp:image -->';
    }
    $text = str_replace("\n", '<br>', esc_html($row['text']));
    if ($row['type'] === 'heading') { return '<!-- wp:heading {"level":' . $row['level'] . '} --><h' . $row['level'] . ' class="wp-block-heading">' . $text . '</h' . $row['level'] . '><!-- /wp:heading -->'; }
    return '<!-- wp:paragraph --><p>' . $text . '</p><!-- /wp:paragraph -->';
}

function t2_editor_validate($id, $input) {
    $post = get_post($id);
    if (!$post || $post->post_type !== 't2_work' || !current_user_can('edit_post', $id)) { return new WP_Error('forbidden', '沒有編輯此作品的權限。'); }
    if (wp_is_post_revision($id) || wp_is_post_autosave($id) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) { return new WP_Error('autosave', '請使用作品頁上的儲存草稿或更新按鈕。'); }
    if (!isset($input['t2_editor_nonce']) || !is_string($input['t2_editor_nonce']) || !wp_verify_nonce($input['t2_editor_nonce'], 't2_edit_work_' . $id)) { return new WP_Error('nonce', '編輯頁已過期，請重新整理後再試。'); }
    if (($input['t2_editor_ready'] ?? '') !== '1') { return new WP_Error('editor_not_ready', '作品表單尚未載入完成，本次不會儲存。請重新整理後再試。'); }
    if (!isset($input['t2_editor_snapshot']) || !is_string($input['t2_editor_snapshot']) || !hash_equals(t2_editor_snapshot($id), $input['t2_editor_snapshot'])) { return new WP_Error('conflict', '作品已在其他分頁更新。本次尚未儲存，請先保留輸入，再重新開啟作品確認。'); }
    if (!isset($input['post_title']) || !is_string($input['post_title']) || mb_strlen($input['post_title']) > 500) { return new WP_Error('title', '作品名稱最多可填寫 500 字。'); }
    if (($input['t2_service_groups_present'] ?? '') !== '1') { return new WP_Error('groups', '服務大項欄位尚未載入完成，請重新整理後再試。'); }
    $groups = $input['t2_service_groups'] ?? array();
    if (!is_array($groups) || array_keys($groups) !== ($groups ? range(0, count($groups) - 1) : array()) || count($groups) > 4) { return new WP_Error('groups', '服務大項格式不正確。'); }
    foreach ($groups as $group) { if (!is_string($group) || !array_key_exists($group, t2_portfolio_service_groups())) { return new WP_Error('groups', '請從現有四個服務大項勾選。'); } }
    if (count(array_unique($groups)) !== count($groups)) { return new WP_Error('groups', '服務大項不可重複。'); }
    if (!isset($input['t2_editor_rows']) || !is_string($input['t2_editor_rows']) || strlen($input['t2_editor_rows']) > 2000000) { return new WP_Error('rows', '作品內容格式不正確。'); }
    $submitted = json_decode($input['t2_editor_rows'], true);
    if (!is_array($submitted) || array_keys($submitted) !== ($submitted ? range(0, count($submitted) - 1) : array()) || count($submitted) > 100) { return new WP_Error('rows', '作品內容最多可放置 100 個段落或圖片。'); }
    $original = t2_editor_rows($post->post_content);
    $used = array(); $rendered = array(); $unchanged = count($original) === count($submitted);
    foreach ($submitted as $index => $row) {
        if (!is_array($row) || !isset($row['type']) || !in_array($row['type'], array('text', 'heading', 'image', 'preserved'), true)) { return new WP_Error('row', '作品段落格式不正確。'); }
        $origin = isset($row['origin']) && is_string($row['origin']) ? $row['origin'] : '';
        $old = $origin !== '' && ctype_digit($origin) && isset($original[(int) $origin]) ? $original[(int) $origin] : null;
        if ($origin !== '' && (!$old || isset($used[$origin]))) { return new WP_Error('row_origin', '原作品段落參照不正確，請重新整理。'); }
        if ($old) { $used[$origin] = true; }
        if ($row['type'] === 'preserved' || ($old && $old['type'] === 'preserved')) {
            if (!$old || $old['type'] !== 'preserved' || $row['type'] !== 'preserved') { return new WP_Error('preserved', '既有特殊內容必須保持原樣。'); }
            $rendered[] = $old['raw'];
            $unchanged = $unchanged && (string) $index === $origin;
            continue;
        }
        $clean = array('type' => $row['type']);
        if ($row['type'] === 'image') {
            if (!isset($row['id']) || (!is_int($row['id']) && !(is_string($row['id']) && ctype_digit($row['id']))) || (int) $row['id'] < 0) { return new WP_Error('image', '圖片參照格式不正確。'); }
            $image_id = (int) $row['id'];
            if ($image_id && !wp_attachment_is_image($image_id)) { return new WP_Error('image', '選取的媒體不是有效圖片。'); }
            $url = t2_editor_original_url($image_id, isset($row['url']) && is_string($row['url']) ? $row['url'] : '');
            if (!$image_id && (!$old || $old['type'] !== 'image' || $url !== $old['url'])) { return new WP_Error('image', '新增圖片請從媒體庫上傳或選取。'); }
            if (!preg_match('#^https?://#i', $url)) { return new WP_Error('image', '請先選取內頁圖片。'); }
            foreach (array('alt', 'caption') as $field) { if (!isset($row[$field]) || !is_string($row[$field]) || mb_strlen($row[$field]) > 2000) { return new WP_Error('image_text', '圖片說明格式不正確或過長。'); } }
            $clean += array('id' => $image_id, 'url' => $url, 'alt' => sanitize_text_field($row['alt']), 'caption' => sanitize_textarea_field($row['caption']));
        } else {
            if (!isset($row['text']) || !is_string($row['text']) || mb_strlen($row['text']) > 50000) { return new WP_Error('text', '段落文字格式不正確或過長。'); }
            $clean['text'] = sanitize_textarea_field($row['text']);
            if ($row['type'] === 'heading') { $clean['level'] = isset($row['level']) && in_array((int) $row['level'], array(2, 3, 4), true) ? (int) $row['level'] : 2; }
        }
        $same = $old && t2_editor_row_fields($old) === t2_editor_row_fields($clean);
        $unchanged = $unchanged && $same && (string) $index === $origin && empty($old['flatten_gallery']) && empty($old['normalize_image']);
        $rendered[] = $same && empty($old['normalize_image']) ? $old['raw'] : t2_editor_row_html($clean);
    }
    foreach ($original as $index => $row) { if ($row['type'] === 'preserved' && !isset($used[(string) $index])) { return new WP_Error('preserved', '既有特殊內容已保留，不可透過表單遺漏或覆寫。'); } }
    $cover = $input['t2_cover_id'] ?? '';
    if ((!is_string($cover) && !is_int($cover)) || !preg_match('/^\d+$/D', (string) $cover)) { return new WP_Error('cover', '作品封面格式不正確。'); }
    $cover = (int) $cover;
    if ($cover && !wp_attachment_is_image($cover)) { return new WP_Error('cover', '請選取有效的封面圖片。'); }
    if (!isset($input['excerpt']) || !is_string($input['excerpt']) || mb_strlen($input['excerpt']) > 4000) { return new WP_Error('excerpt', '簡短描述格式不正確或過長。'); }
    return array('content' => $unchanged ? $post->post_content : implode("\n\n", $rendered), 'excerpt' => sanitize_textarea_field($input['excerpt']), 'cover' => $cover, 'serviceGroups' => array_values(array_intersect(array_keys(t2_portfolio_service_groups()), $groups)));
}

/** Runs before core edit_post touches title, content, metadata, or taxonomy relationships. */
function t2_editor_admin_guard() {
    if (empty($_POST['t2_editor_present'])) { return; }
    $id = absint($_POST['post_ID'] ?? 0);
    $validated = t2_editor_validate($id, wp_unslash($_POST));
    if (is_wp_error($validated)) { wp_die(esc_html($validated->get_error_message()), '作品尚未儲存', array('response' => 409, 'back_link' => true)); }
    $GLOBALS['t2_editor_validated'][$id] = $validated;
}
add_action('admin_action_editpost', 't2_editor_admin_guard', 0);

add_filter('wp_insert_post_data', function ($data, $postarr) {
    if ($data['post_type'] !== 't2_work' || empty($_POST['t2_editor_present']) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) { return $data; }
    $id = absint($postarr['ID'] ?? 0);
    $validated = $GLOBALS['t2_editor_validated'][$id] ?? t2_editor_validate($id, wp_unslash($_POST));
    if (is_wp_error($validated)) { wp_die(esc_html($validated->get_error_message()), '作品尚未儲存', array('response' => 409, 'back_link' => true)); }
    $GLOBALS['t2_editor_validated'][$id] = $validated;
    $data['post_content'] = wp_slash($validated['content']);
    $data['post_excerpt'] = wp_slash($validated['excerpt']);
    return $data;
}, 20, 2);

add_action('save_post_t2_work', function ($id) {
    if (empty($_POST['t2_editor_present']) || !isset($GLOBALS['t2_editor_validated'][$id]) || wp_is_post_revision($id) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) { return; }
    $cover = $GLOBALS['t2_editor_validated'][$id]['cover'];
    if ($cover) { set_post_thumbnail($id, $cover); } else { delete_post_thumbnail($id); }
    update_post_meta($id, '_t2_service_groups', $GLOBALS['t2_editor_validated'][$id]['serviceGroups']);
}, 20);

add_action('add_meta_boxes_t2_work', function () {
    remove_meta_box('postexcerpt', 't2_work', 'normal');
    remove_meta_box('postimagediv', 't2_work', 'side');
    remove_meta_box('t2_work_categorydiv', 't2_work', 'side');
    add_meta_box('t2-work-fields', '作品資料與圖片', 't2_editor_form', 't2_work', 'normal', 'high');
}, 30);

add_action('admin_enqueue_scripts', function () {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 't2_work' || $screen->base !== 'post') { return; }
    // Custom rows save together through the native draft/publish buttons, never via partial autosave.
    wp_deregister_script('autosave');
    wp_enqueue_media();
    wp_enqueue_style('t2-work-editor', plugins_url('editor.css', __FILE__), array(), '1.3.0');
    wp_enqueue_script('t2-work-editor', plugins_url('editor.js', __FILE__), array('jquery', 'media-views'), '1.3.0', true);
});

function t2_editor_form($post) {
    $rows = t2_editor_rows($post->post_content);
    wp_nonce_field('t2_edit_work_' . $post->ID, 't2_editor_nonce');
    echo '<input type="hidden" name="t2_editor_present" value="1" /><input type="hidden" name="t2_editor_ready" value="0" /><input type="hidden" name="t2_editor_snapshot" value="' . esc_attr(t2_editor_snapshot($post->ID)) . '" />';
    echo '<textarea hidden name="t2_editor_rows" id="t2-editor-rows">' . esc_textarea(wp_json_encode(array_map(function ($row) { unset($row['raw']); return $row; }, $rows))) . '</textarea>';
    echo '<div id="t2-work-form" data-post-id="' . intval($post->ID) . '"><p class="description">依序填寫下方欄位。右側可儲存草稿或發布；圖片會以原始尺寸用於前台，移除圖片只會移除作品中的引用。</p>';
    echo '<label class="t2-field-label" for="t2-work-excerpt">簡短描述</label><textarea id="t2-work-excerpt" name="excerpt" class="widefat" rows="2" maxlength="4000">' . esc_textarea($post->post_excerpt) . '</textarea>';
    echo '<fieldset><legend class="t2-field-label">服務大項</legend><input type="hidden" name="t2_service_groups_present" value="1" /><p class="description">勾選適用的服務，可複選。作品案例頁依這四個大項篩選。</p><div class="t2-service-groups">';
    $groups = t2_portfolio_work_groups($post->ID);
    foreach (t2_portfolio_service_groups() as $key => $label) { echo '<label><input type="checkbox" name="t2_service_groups[]" value="' . esc_attr($key) . '" ' . checked(in_array($key, $groups, true), true, false) . ' /> ' . esc_html($label) . '</label>'; }
    echo '</div></fieldset>';
    echo '<div class="t2-field-label">作品標籤</div><p class="description">可自由新增，例如 ICON、Logo 或設計風格。標籤顯示於作品內頁，不會增加篩選大項。</p><div class="t2-category-box"><input type="hidden" name="tax_input[t2_work_category][]" value="0" /><ul id="t2-category-list" class="categorychecklist">';
    wp_terms_checklist($post->ID, array('taxonomy' => 't2_work_category', 'checked_ontop' => false));
    echo '</ul>';
    $taxonomy = get_taxonomy('t2_work_category');
    if (current_user_can($taxonomy->cap->manage_terms)) { echo '<div class="t2-new-category"><label for="t2-new-category-name">新增標籤</label><input id="t2-new-category-name" type="text" maxlength="120" /><button class="button" type="button" data-add-category>加入標籤</button><span role="status" data-category-status></span></div>'; }
    echo '</div><div class="t2-field-label">作品封面</div>';
    $cover = get_post_thumbnail_id($post);
    $url = t2_editor_original_url($cover);
    echo '<input type="hidden" name="t2_cover_id" id="t2-cover-id" value="' . intval($cover) . '" /><div class="t2-cover-preview" data-cover-preview>' . ($url ? '<img src="' . esc_url($url) . '" alt="作品封面預覽" />' : '<span>尚未選取封面</span>') . '</div>';
    echo '<p><button class="button" type="button" data-select-cover>上傳／選擇封面圖片</button> <button class="button" type="button" data-remove-cover>移除封面</button></p>';
    echo '<div class="t2-field-label">作品內頁內容</div><p class="description">新增圖片或文字段落，用上移／下移調整順序。圖片預覽可點擊查看原圖。</p><div id="t2-content-rows">';
    foreach ($rows as $row) { t2_editor_render_row($row); }
    echo '</div><p class="t2-add-row"><button class="button" type="button" data-add-row="image">＋ 新增內頁圖片</button> <button class="button" type="button" data-add-row="text">＋ 新增段落說明</button> <button class="button" type="button" data-add-row="heading">＋ 新增小標題</button></p><p role="status" aria-live="polite" data-editor-status></p></div>';
}

function t2_editor_render_row($row) {
    $type = $row['type'];
    $labels = array('image' => '內頁圖片', 'text' => '段落說明', 'heading' => '小標題', 'preserved' => '既有內容（保留）');
    echo '<section class="t2-content-row" data-type="' . esc_attr($type) . '" data-origin="' . esc_attr($row['origin'] ?? '') . '"><div class="t2-row-toolbar"><strong>' . esc_html($labels[$type]) . '</strong><div><button type="button" class="button" data-row-up>上移</button> <button type="button" class="button" data-row-down>下移</button>' . ($type !== 'preserved' ? ' <button type="button" class="button" data-row-remove>移除</button>' : '') . '</div></div>';
    if ($type === 'preserved') { echo '<p class="description">這段包含原有特殊排版，已完整保留；本表單不會覆寫。可移動位置或在前後新增內容。</p><div class="t2-preserved-preview">' . wp_kses_post(t2_portfolio_content_html($row['raw'])) . '</div>'; }
    elseif ($type === 'image') {
        echo '<input type="hidden" data-image-id value="' . intval($row['id']) . '" /><input type="hidden" data-image-url value="' . esc_attr($row['url']) . '" /><div class="t2-image-preview">' . ($row['url'] ? '<a href="' . esc_url($row['url']) . '" target="_blank" rel="noopener noreferrer"><img src="' . esc_url($row['url']) . '" alt="內頁圖片預覽" /></a>' : '<span>尚未選取圖片</span>') . '</div><p><button type="button" class="button" data-select-image>上傳／選擇內頁圖片</button></p><label>圖片替代文字<input class="widefat" data-image-alt maxlength="2000" value="' . esc_attr($row['alt']) . '" /></label><label>圖片說明<textarea class="widefat" data-image-caption rows="2" maxlength="2000">' . esc_textarea($row['caption']) . '</textarea></label>';
    } else {
        if ($type === 'heading') { echo '<select data-heading-level aria-label="標題層級">'; foreach (array(2 => '主要小標題', 3 => '次要小標題', 4 => '補充小標題') as $level => $label) { echo '<option value="' . $level . '" ' . selected($row['level'], $level, false) . '>' . esc_html($label) . '</option>'; } echo '</select>'; }
        echo '<textarea class="widefat" data-row-text rows="' . ($type === 'heading' ? '2' : '5') . '" maxlength="50000" aria-label="' . esc_attr($labels[$type]) . '">' . esc_textarea($row['text']) . '</textarea>';
    }
    echo '</section>';
}

add_action('wp_ajax_t2_add_work_category', function () {
    $id = absint($_POST['post_id'] ?? 0);
    $taxonomy = get_taxonomy('t2_work_category');
    if (get_post_type($id) !== 't2_work' || !current_user_can('edit_post', $id) || !current_user_can($taxonomy->cap->manage_terms)) { wp_send_json_error(array('message' => '沒有新增標籤的權限。'), 403); }
    check_ajax_referer('t2_edit_work_' . $id, 'nonce');
    $name = isset($_POST['name']) && is_string($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    if ($name === '' || mb_strlen($name) > 120) { wp_send_json_error(array('message' => '請輸入標籤名稱（最多 120 字）。'), 400); }
    $term = wp_insert_term($name, 't2_work_category');
    if (is_wp_error($term)) { wp_send_json_error(array('message' => $term->get_error_message()), 400); }
    wp_send_json_success(array('id' => (int) $term['term_id'], 'name' => $name));
});

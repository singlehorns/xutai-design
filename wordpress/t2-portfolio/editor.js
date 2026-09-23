(function ($) {
  'use strict';
  const root = document.getElementById('t2-work-form');
  if (!root || !window.wp || !wp.media) return;
  const rows = document.getElementById('t2-content-rows');
  const serialized = document.getElementById('t2-editor-rows');
  const form = document.getElementById('post');
  const status = root.querySelector('[data-editor-status]');
  document.getElementById('title').maxLength = 500;
  function sync() {
    serialized.value = JSON.stringify(Array.from(rows.children).map((row) => {
      const data = { type: row.dataset.type, origin: row.dataset.origin || '' };
      if (data.type === 'image') Object.assign(data, { id: Number(row.querySelector('[data-image-id]').value), url: row.querySelector('[data-image-url]').value, alt: row.querySelector('[data-image-alt]').value, caption: row.querySelector('[data-image-caption]').value });
      if (data.type === 'text' || data.type === 'heading') data.text = row.querySelector('[data-row-text]').value;
      if (data.type === 'heading') data.level = Number(row.querySelector('[data-heading-level]').value);
      return data;
    }));
    Array.from(rows.children).forEach((row, index, all) => {
      row.querySelector('[data-row-up]').disabled = index === 0;
      row.querySelector('[data-row-down]').disabled = index === all.length - 1;
    });
  }
  function preview(target, url, cover) {
    target.replaceChildren();
    if (!url) { const label = document.createElement('span'); label.textContent = cover ? '尚未選取封面' : '尚未選取圖片'; target.append(label); return; }
    const image = document.createElement('img'); image.src = url; image.alt = cover ? '作品封面預覽' : '內頁圖片預覽';
    if (cover) target.append(image);
    else { const link = document.createElement('a'); link.href = url; link.target = '_blank'; link.rel = 'noopener noreferrer'; link.append(image); target.append(link); }
  }
  function imageData(row, attachment) {
    row.querySelector('[data-image-id]').value = attachment.id;
    row.querySelector('[data-image-url]').value = attachment.originalImageURL || attachment.url;
    row.querySelector('[data-image-alt]').value = attachment.alt || '';
    const caption = new DOMParser().parseFromString(attachment.caption || '', 'text/html');
    row.querySelector('[data-image-caption]').value = caption.body.textContent;
    preview(row.querySelector('.t2-image-preview'), attachment.originalImageURL || attachment.url, false);
  }
  function newRow(type, after) {
    const row = document.createElement('section'); row.className = 't2-content-row'; row.dataset.type = type; row.dataset.origin = '';
    const label = { image: '內頁圖片', text: '段落說明', heading: '小標題' }[type];
    row.innerHTML = '<div class="t2-row-toolbar"><strong></strong><div><button type="button" class="button" data-row-up>上移</button> <button type="button" class="button" data-row-down>下移</button> <button type="button" class="button" data-row-remove>移除</button></div></div>';
    row.querySelector('strong').textContent = label;
    if (type === 'image') row.insertAdjacentHTML('beforeend', '<input type="hidden" data-image-id value="0"><input type="hidden" data-image-url value=""><div class="t2-image-preview"><span>尚未選取圖片</span></div><p><button type="button" class="button" data-select-image>上傳／選擇內頁圖片</button></p><label>圖片替代文字<input class="widefat" data-image-alt maxlength="2000"></label><label>圖片說明<textarea class="widefat" data-image-caption rows="2" maxlength="2000"></textarea></label>');
    else {
      if (type === 'heading') row.insertAdjacentHTML('beforeend', '<select data-heading-level aria-label="標題層級"><option value="2">主要小標題</option><option value="3">次要小標題</option><option value="4">補充小標題</option></select>');
      const field = document.createElement('textarea'); field.className = 'widefat'; field.dataset.rowText = ''; field.rows = type === 'heading' ? 2 : 5; field.maxLength = 50000; field.setAttribute('aria-label', label); row.append(field);
    }
    if (after) after.after(row); else rows.append(row);
    sync(); return row;
  }
  function selectImage(row, cover) {
    const frame = wp.media({ title: cover ? '上傳或選擇封面圖片' : '上傳或選擇內頁圖片', button: { text: cover ? '使用此封面' : '加入內頁圖片' }, library: { type: 'image' }, multiple: !cover });
    frame.on('select', function () {
      const selected = frame.state().get('selection').toJSON();
      if (cover) {
        if (!selected[0]) return;
        document.getElementById('t2-cover-id').value = selected[0].id;
        preview(root.querySelector('[data-cover-preview]'), selected[0].originalImageURL || selected[0].url, true);
      } else {
        selected.forEach((attachment, index) => { if (index) row = newRow('image', row); imageData(row, attachment); });
      }
      sync();
    });
    frame.open();
  }
  root.addEventListener('input', sync); root.addEventListener('change', sync);
  root.addEventListener('click', function (event) {
    const button = event.target.closest('button'); if (!button) return;
    const row = button.closest('.t2-content-row');
    if (button.hasAttribute('data-add-row')) { const added = newRow(button.dataset.addRow); if (button.dataset.addRow === 'image') selectImage(added, false); else added.querySelector('textarea').focus(); }
    if (button.hasAttribute('data-select-cover')) selectImage(null, true);
    if (button.hasAttribute('data-remove-cover')) { document.getElementById('t2-cover-id').value = '0'; preview(root.querySelector('[data-cover-preview]'), '', true); }
    if (button.hasAttribute('data-select-image')) selectImage(row, false);
    if (button.hasAttribute('data-row-remove') && window.confirm('移除此段內容？媒體庫的原始圖片會保留。')) row.remove();
    if (button.hasAttribute('data-row-up') && row.previousElementSibling) row.previousElementSibling.before(row);
    if (button.hasAttribute('data-row-down') && row.nextElementSibling) row.nextElementSibling.after(row);
    if (button.hasAttribute('data-add-category')) {
      const field = document.getElementById('t2-new-category-name'); const message = root.querySelector('[data-category-status]');
      if (!field.value.trim()) { message.textContent = '請輸入分類名稱。'; return; }
      button.disabled = true;
      $.post(window.ajaxurl, { action: 't2_add_work_category', post_id: root.dataset.postId, nonce: form.querySelector('[name="t2_editor_nonce"]').value, name: field.value.trim() }).done(function (result) {
        if (!result.success) { message.textContent = result.data.message || '新增未成功。'; return; }
        const li = document.createElement('li'); const label = document.createElement('label'); const check = document.createElement('input'); check.type = 'checkbox'; check.name = 'tax_input[t2_work_category][]'; check.value = result.data.id; check.checked = true; label.append(check, document.createTextNode(' ' + result.data.name)); li.append(label); document.getElementById('t2-category-list').append(li); field.value = ''; message.textContent = '分類已新增，儲存作品後套用。';
      }).fail(function (response) { message.textContent = response.responseJSON?.data?.message || '新增未成功，請稍後再試。'; }).always(function () { button.disabled = false; });
    }
    sync();
  });
  $(form).on('submit.t2', function (event) {
    sync();
    const empty = Array.from(rows.querySelectorAll('[data-type="image"]')).find((row) => !row.querySelector('[data-image-url]').value);
    if (empty) { event.preventDefault(); status.textContent = '請先選取內頁圖片，或移除空白圖片列。'; empty.querySelector('button[data-select-image]').focus(); }
  });
  sync(); form.querySelector('[name="t2_editor_ready"]').value = '1';
})(jQuery);

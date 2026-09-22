# WordPress 作品後台

目前的 Astro 版型保留，由 WordPress 管理作品資料。作品分類會同步用於「作品案例」篩選列、作品卡片與內頁黃色分類標籤。作品內頁的文字與圖片順序使用 WordPress 區塊編輯器管理。

## 日常操作

1. 登入 WordPress，開啟「作品案例 → 新增作品」。
2. 填寫作品標題；「摘要」填寫一行短描述。
3. 在編輯器新增段落、標題、圖片或圖庫，可重新排序。圖片可在媒體庫更換，並設定替代文字與圖說。
4. 指定「作品封面」，用於作品列表。內頁圖片請另外加入編輯器。
5. 選擇「作品分類」，或到「作品案例 → 作品分類」新增／重新命名。新分類即使尚無作品，也會出現在前台篩選列，選取時顯示尚無作品。
6. 在「作品顯示設定」填寫一個相關連結與連結文字；網址留空時不顯示該區塊。也可設定首頁精選與顯示順序。
7. 按「發布／更新」，再前往「作品案例 → 網站更新」。目前使用手動發布：按「前往 GitHub 發布網站」，登入 GitHub 後按 **Run workflow → Run workflow**。等待該次 build 與 deploy 都成功，再查看正式網站。另行設定專用授權後，也可直接從 WordPress 按「更新網站」。

前台是靜態網站，WordPress 儲存成功後仍需等待建置與部署完成。草稿、私人及密碼保護作品不會出現在公開作品 API。WordPress 預覽不是 Astro 版型預覽，驗收請查看前台。

作品代稱建議使用小寫英文及連字號（例如 `new-brand-project`），中文也可使用。避免 `all`、`index`、`page`、`api`、`feed` 等保留名稱。既有作品代稱請保留，以維持作品網址與服務頁近期案件連結。

## 首次安裝與匯入

使用 Cloudways 既有伺服器中的獨立 Basic WordPress 應用程式，PHP 8.2 以上。外掛本身需求為 WordPress 6.5 以上、PHP 7.4 以上；ZIP 匯入需要 PHP ZipArchive。

1. 將 `wordpress/t2-portfolio/` 打包成 ZIP，ZIP 內應包含 `t2-portfolio/t2-portfolio.php`。
2. 在 WordPress「外掛 → 安裝外掛 → 上傳外掛」上傳並啟用。
3. 在本機執行 `npm run wordpress:export`。產物存入被 Git 忽略的 `backups/wordpress-export-.../`。
4. 將產物內的 `manifest.json` 與 `images/` 壓縮在 ZIP 根目錄，勿再包一層資料夾。
5. 到「作品案例 → 匯入現有作品」上傳 ZIP。匯入只新增尚不存在的作品代稱，不覆蓋已編輯的作品；相同圖片依內容雜湊重複利用。
6. 確認 9 個既有作品、3 個既有分類與 8 張圖片，再設定前台資料來源。

匯出只讀取 `src/content/works/` 與 `public/images/works/` 內已公開資料，不包含私人履歷、原始素材、密碼或備份。圖片匯入保留原始解析度；完整尺寸的長版截圖使用原圖顯示。

也可透過主機 WP-CLI 匯入：

```sh
wp t2 import /absolute/path/manifest.json --media-dir=/absolute/path/images
```

## 前台連線

複製根目錄 `.env.example` 為 `.env`，設定公開的 WordPress 網站網址：

```dotenv
WORDPRESS_URL=https://your-wordpress-host.example
```

若採用 WordPress 樸素永久連結，改用 `https://your-wordpress-host.example/?rest_route=/`。使用 WordPress 網站根目錄，不是 `/wp-admin/`。內容 API 為：

- `/wp-json/t2-portfolio/v1/categories`
- `/wp-json/t2-portfolio/v1/works?page=1&per_page=100`

本機修改 `.env` 後重啟 Astro。正式 GitHub Pages 在 Repository → Settings → Secrets and variables → Actions → Variables 新增 `WORDPRESS_URL`，內容相同。此網址是公開資料，不需 WordPress 帳密。

未設定網址時，前台繼續使用現有 Markdown；設定後若 API 失敗、資料格式不完整或路徑衝突，建置會失敗，GitHub Pages 保留上一次成功版本，不會悄悄切回舊資料。切換前先確認 SSL 憑證有效、媒體網址可公開讀取，再執行 `npm run test:wordpress` 與 `npm run build`。

## WordPress 按鈕觸發 GitHub 更新

`.github/workflows/deploy.yml` 支援手動 `workflow_dispatch` 及 `repository_dispatch` 的 `t2-content-updated` 事件。先將程式更新至 GitHub，並設定上面的 Repository Variable。

若要直接從 WordPress 按「更新網站」，由管理者在該 WordPress 的 `wp-config.php`（停止編輯註解之前）設定：

```php
define('T2_GITHUB_REPOSITORY', 'singlehorns/xutai-design');
define('T2_GITHUB_TOKEN', '由管理者安全設定的專用憑證');
```

使用僅授權該 repository 的 fine-grained GitHub token，Contents: Read and write（GitHub repository dispatch 的必要權限），並設定到期日。不要把 token 寫入本專案、`.env`、前台 JavaScript、工作紀錄或公開文件。也不要把 WordPress 管理者密碼提供給 Astro。

成功送出請求代表 GitHub 已接受排程，不代表部署完成；請以 GitHub Actions 的 build/deploy 成功為準。若不設定 token，仍可手動執行 GitHub Actions。

## 驗證與還原

```sh
npm run test:wordpress
npm run build
```

部署流程也會先執行資料串接測試。導入前的程式備份位於本機 `backups/wordpress-integration-20260923-002010/`，Git 基準為 `2a7f4b2`。移除 `WORDPRESS_URL` 並重新建置可回到原有 Markdown 內容模式，WordPress 作品與媒體仍保留。

參考：[WordPress 自訂內容 REST API](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-rest-api-support-for-custom-content-types/)、[Cloudways 新增應用程式](https://support.cloudways.com/en/articles/5124126-how-to-launch-server-and-add-an-application-on-the-cloudways-platform)、[GitHub Repository dispatch API](https://docs.github.com/en/rest/repos/repos#create-a-repository-dispatch-event)。

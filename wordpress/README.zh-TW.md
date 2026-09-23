# WordPress 作品後台

目前的 Astro 版型保留，由 WordPress 管理作品資料。作品使用專用固定欄位表單，並將「服務大項」與「作品標籤」分開：服務大項控制作品案例頁的篩選；自由新增的標籤顯示為內頁黃色純文字標籤，不會連到其他頁面或增加篩選按鈕。

## 日常操作

1. 登入 WordPress，開啟「作品案例 → 新增作品」。
2. 填寫最上方的「作品名稱」，在「簡短描述」填寫一行短描述。
3. 在「服務大項」勾選適用項目，可複選：網站建置・維護管理、活動・教育推廣、社群・廣告推廣、商家印刷・製作。前台固定顯示「全部」及這四項；未勾選的作品仍出現在「全部」。接著選擇或新增「作品標籤」，例如 ICON、Logo、設計風格；新增標籤不會改變服務大項。
4. 按「上傳／選擇封面圖片」，上傳檔案或從媒體庫選圖。封面用於作品列表，與內頁圖片分別設定。
5. 在「作品內頁內容」按「新增內頁圖片」「新增段落說明」或「新增小標題」。內頁圖片可一次選取多張，用「上移／下移」排序。圖片替代文字與圖片說明各有固定欄位，預覽可點開原圖。移除圖片列只移除這份作品的引用，媒體庫原圖仍會保留。
6. 在「相關連結與顯示設定」填寫一個相關連結與連結文字；網址留空時不顯示該區塊。也可設定首頁精選、顯示順序與資料整理狀態。標籤可到「作品案例 → 作品標籤」重新命名；沒有標籤的作品不顯示黃色佔位標籤。
7. 按「發布／更新」。管理員完成一次連線後，作品、服務大項與標籤的變更會自動通知 GitHub 更新網站，不必另外開啟 GitHub 操作。等待建置與部署完成後，再查看正式網站。

前台是靜態網站，WordPress 儲存成功後仍需等待建置與部署完成；完成前保留上一次成功的網站。草稿、私人及密碼保護作品不會出現在公開作品 API。WordPress 預覽不是 Astro 版型預覽，驗收請查看前台。若「網站更新」顯示請求失敗，作品仍已儲存，管理員可在同一頁按「重試更新網站」。

作品表單使用 WordPress 原生的儲存草稿、發布、更新與修訂版本。請在編輯完成後按儲存；本表單停用局部自動儲存，避免圖片與文字分開寫入。表單程式尚未載入完成，或作品已被其他分頁修改時，會阻止整次儲存，避免部分資料被覆寫。遇到提示請先保留尚未儲存的文字，再重新開啟作品。

升級 1.2.0 不會批次改寫現有作品。原本的段落、小標題與單張圖片會出現在固定欄位中；簡單圖庫會在按下儲存時轉成依序排列的圖片。圖片有有效媒體 ID 時，前台與表單優先使用上傳的原圖，不使用 203px 等縮圖。找不到原媒體的舊圖仍保留既有網址。特殊區塊或複雜排版顯示「既有內容（保留）」，原始內容由伺服器保留，可以調整位置、在前後新增列，不會透過固定欄位默默刪除或覆寫。

升級 1.3.0 保留原有標籤 ID、名稱、代稱與作品關聯；原本名為「作品分類」的欄位改稱「作品標籤」。服務大項使用獨立設定，不會刪除舊的服務對應。尚未設定新欄位的舊作，只沿用可確認的網站／社群／印刷對應；品牌與影音不會自動猜成活動類。若尚無適合的大項，作品仍保留於「全部」，可日後手動勾選。取消所有勾選並儲存後，會保持空白，不會再自動補回舊對應。

作品代稱建議使用小寫英文及連字號（例如 `new-brand-project`），中文也可使用。避免 `all`、`index`、`page`、`api`、`feed` 等保留名稱。既有作品代稱請保留，以維持作品網址與服務頁近期案件連結。

## 首次安裝與匯入

使用 Cloudways 既有伺服器中的獨立 Basic WordPress 應用程式，PHP 8.2 以上。外掛本身需求為 WordPress 6.5 以上、PHP 7.4 以上；ZIP 匯入需要 PHP ZipArchive。

1. 將整個 `wordpress/t2-portfolio/` 打包成 ZIP，包含 `t2-portfolio.php`、`publish.php`、`editor.php`、`editor.js` 與 `editor.css`，五個檔案都位於 ZIP 內的 `t2-portfolio/` 資料夾。
2. 在 WordPress「外掛 → 安裝外掛 → 上傳外掛」上傳並啟用。
3. 在本機執行 `npm run wordpress:export`。產物存入被 Git 忽略的 `backups/wordpress-export-.../`。
4. 將產物內的 `manifest.json` 與 `images/` 壓縮在 ZIP 根目錄，勿再包一層資料夾。
5. 到「作品案例 → 匯入現有作品」上傳 ZIP。匯入只新增尚不存在的作品代稱，不覆蓋已編輯的作品；相同圖片依內容雜湊重複利用。
6. 確認 9 個既有作品、3 個既有標籤與 8 張圖片，再設定前台資料來源。

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

為保留舊版相容性，`categories` 端點與作品中的 `categories` 欄位名稱維持不變，內容代表自由作品標籤。1.3.0 的作品 API 另有 `serviceGroups`（`web`、`event`、`social`、`print`）；舊的 `serviceIds` 原資料保留。前台遇到舊外掛尚未提供 `serviceGroups` 時使用已確認的舊服務對應，收到明確的空陣列時則保留空白。原本 version 1 匯入包仍可使用，不需重新匯入或批次改寫現有作品。

本機修改 `.env` 後重啟 Astro。正式 GitHub Pages 在 Repository → Settings → Secrets and variables → Actions → Variables 新增 `WORDPRESS_URL`，內容相同。此網址是公開資料，不需 WordPress 帳密。

未設定網址時，前台繼續使用現有 Markdown；設定後若 API 失敗、資料格式不完整或路徑衝突，建置會失敗，GitHub Pages 保留上一次成功版本，不會悄悄切回舊資料。切換前先確認 SSL 憑證有效、媒體網址可公開讀取，再執行 `npm run test:wordpress` 與 `npm run build`。

## 管理員設定自動發布

外掛 1.1.0 使用 GitHub `workflow_dispatch` 呼叫 `main` 分支上的 `.github/workflows/deploy.yml`。先確認該 workflow 已存在於 GitHub，並已設定前面的 `WORDPRESS_URL` Repository Variable。

1. 在 GitHub → Settings → Developer settings → Personal access tokens → Fine-grained tokens 建立一個專用授權。
2. Resource owner 選 `singlehorns`，Repository access 只選 `xutai-design`。Repository permissions 只需 **Actions: Read and write**，並保留必要的 **Metadata: Read**；不需要 Contents write。
3. 設定到期日。將授權直接貼到 WordPress「作品案例 → 網站更新 → 管理員連線設定」的「網站發布授權」密碼欄位，不要貼到聊天、程式碼或文件。
4. 勾選「啟用自動更新網站」，按「儲存連線並確認」。這會送出一次更新請求，驗證發布連線。
5. 狀態顯示「更新請求已送出」代表 GitHub 已接受，不代表部署完成。可按「查看網站更新進度」確認 build/deploy；一般作者日後只需在 WordPress 儲存作品。

連線授權加密後存入 WordPress 資料庫，使用此站 WordPress salts 派生金鑰，且該選項不會 autoload。授權不會回填到 HTML、公開 API 或狀態訊息。OpenSSL AES-256-GCM 無法使用時，系統會拒絕保存授權，不降級為明文。更換 WordPress salts 或移站後，可能需要重新連線。需要更換授權時再次貼上新值；留空會保留現有授權。「關閉自動更新並移除授權」會刪除資料庫中的加密授權。

也可由主機管理者在 `wp-config.php` 的停止編輯註解之前設定，覆蓋後台保存的授權：

```php
define('T2_GITHUB_REPOSITORY', 'singlehorns/xutai-design');
define('T2_GITHUB_TOKEN', '由管理者安全設定的專用憑證');
```

使用主機設定時，仍需到 WordPress 勾選「啟用自動更新網站」。後台關閉時會停止自動請求，但不會修改主機設定。不要把授權寫入本專案、`.env`、前台 JavaScript、工作紀錄或公開文件；Astro 也不需要 WordPress 管理者密碼。

自動更新包含：公開作品的發布／修改／下架／刪除、公開與密碼保護之間切換、排程作品到期發布、作品標籤新增／改名／刪除、標籤關聯、服務大項、相關連結／精選／排序與封面設定修改。普通草稿、私人作品、修訂與自動儲存不會觸發發布。未被作品使用的自由標籤不會增加前台篩選選項；固定四個服務大項則始終顯示。

每個儲存請求會先收集所有變更，等圖片、文字、服務大項、標籤與設定都寫入完成後才送出一次更新，不會每個欄位各送一次。初次通知直接在請求結尾執行，最多等待 10 秒，不依賴訪客觸發 WordPress Cron；連線失敗時保留安全的錯誤狀態，管理員可立即重試。GitHub 本身的建置／部署失敗仍以 Actions 結果為準，前台保留上次成功版本。

## 驗證與還原

```sh
npm run test:wordpress
npm run build
```

PHP 自動發布整合測試必須在本機可拋棄的 WordPress 執行；先啟用本外掛，不要配置真實授權常數。測試會攔截全部外部 HTTP，只使用假的本機授權，建立自己的暫存作品／分類並於結束後清理及恢復設定：

```sh
wp eval "define('T2_PUBLISH_TEST_ALLOW', true); require '/absolute/path/scripts/wordpress-publish.integration.php';"
```

Playground 亦可在 PHP 執行環境載入 `wp-load.php` 後，定義相同允許常數並 `require` 測試檔。不要在正式站執行此測試。

固定欄位表單另有真實 WordPress 整合測試，涵蓋固定服務大項與標籤分離、舊服務保留、空選項、不合法資料整次阻擋、舊圖庫轉換、原圖尺寸、段落與圖片排序、換行、特殊內容保留、nonce／權限／衝突阻擋、原生草稿與發布、單次自動更新。測試同樣限本機環境，會攔截外部 HTTP，並清理自己建立的測試作品與媒體紀錄：

```sh
wp eval "define('T2_EDITOR_TEST_ALLOW', true); require '/absolute/path/scripts/wordpress-editor.integration.php';"
```

部署流程也會先執行資料串接測試。導入前的程式備份位於本機 `backups/wordpress-integration-20260923-002010/`，Git 基準為 `2a7f4b2`。移除 `WORDPRESS_URL` 並重新建置可回到原有 Markdown 內容模式，WordPress 作品與媒體仍保留。

參考：[WordPress 自訂內容 REST API](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-rest-api-support-for-custom-content-types/)、[Cloudways 新增應用程式](https://support.cloudways.com/en/articles/5124126-how-to-launch-server-and-add-an-application-on-the-cloudways-platform)、[GitHub Workflow dispatch API](https://docs.github.com/en/rest/actions/workflows#create-a-workflow-dispatch-event)。

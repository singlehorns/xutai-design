# 旭泰整合設計

旭泰整合設計的個人工作室品牌網站。公開服務提供者使用 Tseting Chen，網站聚焦品牌視覺、印刷、網站與數位內容服務，使用 Astro、TypeScript strict、Astro Content Collections、原生 CSS 與少量原生 JavaScript 建立。

## 頁面

- `/`：首頁，包含 Hero、精選作品、專業能力、About、Experience、Contact。
- `/works/`：作品列表與分類篩選。
- `/works/[slug]/`：單一作品頁，含專案資料、圖片、上一個與下一個作品。
- `/about/`：關於我、技能與獎項摘要。
- `/experience/`：工作經歷時間軸。
- `/contact/`：聯絡資訊佔位與所在地。

## 技術

- Astro
- TypeScript strict mode
- Astro Content Collections
- 原生 Astro components
- 原生 CSS
- 少量原生 JavaScript：手機選單與作品篩選

## 本機預覽

```bash
npm.cmd install
npm.cmd run dev
```

開啟 Astro 顯示的 localhost URL。PowerShell 若擋到 `npm.ps1`，請使用 `npm.cmd`。

## 驗證

```bash
npm.cmd run check
npm.cmd run build
```

目前驗證結果：

- `npm.cmd run check`：Astro diagnostics 0 errors、0 warnings、0 hints。
- `npm.cmd run build`：成功產生 14 個靜態頁面到 `dist/`。

備註：在 Codex sandbox 內，Astro/Vite 背景依賴預處理曾印出非致命的 canceled / access 訊息，但指令 exit code 為 0，且 build 已完成。

## 隱私處理

公開網站不顯示：

- 完整手機號碼
- 完整通訊地址
- 年齡與出生資料
- 役別資料
- 待業狀態
- 希望薪資
- 可上班日期
- 駕照與交通工具
- 完整 Email

聯絡頁目前顯示 `CONTACT EMAIL / 待使用者確認`，所在地僅顯示 `Tainan, Taiwan`。

## 待補資料

PDF 內嵌字型造成部分文字抽取缺字，因此只採用可確認資訊。以下內容仍待補充：

- 部分作品年份
- 部分客戶正式名稱
- 作品使用工具
- 個別網站作品的完整負責範圍
- 影片作品的正式名稱與縮圖
- 社群設計作品圖片
- 動態影像作品圖片或連結
- 攝影作品的主題與使用情境

## Agent 分工

詳見 `AGENTS.md` 與 `.codex/agents/`。

- Portfolio Curator：整理履歷、作品與工作經歷。
- Art Director：規劃視覺、版面與 RWD。
- Content Editor：整理自我介紹與文字。
- Asset Manager：管理圖片與待補項目。
- Astro Builder：實作 Astro 網站。
- QA Reviewer：檢查版面、內容、隱私、連結與 build。

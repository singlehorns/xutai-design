export type Locale = "zh-Hant" | "en" | "ja";
import { translations } from "./page-translations";
export const label = (value: string, locale: Locale) => locale === "zh-Hant" ? value : translations[value]?.[locale === "en" ? 0 : 1] ?? value;
const caseTitles: Record<string, readonly [string, string]> = {
  "久發網工作室-保健品牌發想保健包裝印刷": ["久發網 Studio | Health brand and packaging", "久發網スタジオ｜ヘルスケアブランドとパッケージ"],
  "久發網工作室-選舉視覺與宣傳周邊製作": ["久發網 Studio | Election campaign visuals", "久發網スタジオ｜選挙広報ビジュアル"],
  "久發網資訊工會": ["久發網 Studio | Information website", "久發網スタジオ｜情報サイト"],
  "天空之城-黃金之殿主題ui": ["天空之城 | Golden Palace UI", "天空之城｜黄金の宮殿テーマUI"],
  "台灣創新博覽會產品三折頁": ["Taiwan Innovation Expo | Product brochure", "台湾イノベーション博覧会｜商品三つ折りパンフレット"],
  "印象行-數位影印網頁": ["印象行 | Digital printing website", "印象行｜デジタル印刷サイト"],
  "好在麵包店-品牌周邊印刷製作": ["好+在 Bakery | Branded print", "好+在ベーカリー｜ブランド印刷物"],
  "亞墨金珠寶": ["亞墨金 Jewellery | WordPress website", "亞墨金ジュエリー｜WordPressサイト"],
  "典陸教育dm": ["典陸 Education | Direct mail", "典陸教育｜DM"],
  "和牛活動周邊視覺": ["Wagyu event | Visual materials", "和牛イベント｜関連ビジュアル"],
  "保健食品品牌設計": ["久發網 Studio | Health brand visuals and print", "久發網スタジオ｜ヘルスケアブランドのビジュアル・印刷"],
  "南科育成中心周邊": ["Nanke Incubation Center | Spatial graphics", "南科育成センター｜空間ビジュアル"],
  "南臺育成中心-活動視覺整合與延伸設計": ["Southern Taiwan Incubation Center | Event visuals", "南臺育成センター｜イベントビジュアル"],
  "建教生-勞動人權勞動權益-app": ["Student workers' rights | App", "建教生の労働権利｜アプリ"],
  "珠心學苑活動報名頁後台管理": ["珠心學苑 | Registration and admin pages", "珠心學苑｜申込ページと管理画面"],
  "高科大-ai-智慧製造-活動視覺與印刷製作": ["NKUST AI manufacturing | Event visuals and print", "高科大AIスマート製造｜イベントビジュアル・印刷"],
  "棒球癮tv-小聯盟-yt頻道經營": ["棒球癮 TV | Minor League YouTube channel", "棒球癮TV｜マイナーリーグYouTubeチャンネル"],
  "集美生技-電商視覺-跨媒介應用": ["GM Biotech | E-commerce visuals", "集美生技｜ECビジュアル"],
  "嘉義良昌冊店展覽行銷-梅山瑞峰茶香": ["Meishan Ruifeng Tea | Exhibition visuals", "梅山瑞峰茶香｜展示会ビジュアル"],
  "監視器紙盒": ["鉅龍 Surveillance | Print production", "鉅龍監視システム｜印刷制作"],
  "鴻泰資訊-數位介面設計與實體視覺應用": ["鴻泰 Information | Digital and physical visuals", "鴻泰資訊｜デジタル・実物のビジュアル"],
  "職前訓練課程-招生廣告meta": ["Vocational training | Meta recruitment ads", "職業訓練講座｜Meta募集広告"],
  "鑽之韻-品牌電商視覺與多媒體內容製作": ["鑽之韻 | E-commerce and multimedia visuals", "鑽之韻｜EC・マルチメディアビジュアル"],
  "aaaaaa": ["VELORIA | Jewellery website", "VELORIA｜ジュエリーサイト"],
  "auroraseiki-官網": ["auroraseiki | Corporate website", "auroraseiki｜企業サイト"],
  "dream-shop衣飾店品牌": ["Dream shop | Fashion brand", "Dream shop｜衣料品ブランド"],
  "gm-集美精油官網": ["GM Essential Oils | Website", "GM集美精油｜ウェブサイト"],
  "hebe-晨耀企業": ["HEBE | Corporate visuals", "HEBE晨耀企業｜企業ビジュアル"],
  "wsbc-u15亞洲青少棒錦標賽": ["WSBC U15 Asian Baseball Championship", "WSBC U15アジア青少年野球選手権"]
};
export const caseTitle = (slug: string, original: string, locale: Locale) => locale === "zh-Hant" ? original : caseTitles[slug]?.[locale === "en" ? 0 : 1] ?? original;
export const localeFromPath = (path: string): Locale => /\/(en|ja)(\/|$)/.exec(path)?.[1] as Locale || "zh-Hant";
export const localizedPath = (path: string, locale: Locale) => locale === "zh-Hant" ? path : `/${locale}${path}`;

export const ui = {
  "zh-Hant": { works: "作品案例", all: "全部", back: "所有作品案例", previous: "上一個案例", next: "下一個案例", summary: "作品簡短描述", background: "專案背景", challenge: "面對的課題", approach: "執行方式", deliverables: "實際交付內容", outcomes: "專案成果", newCopy: "新撰介紹（依現有標題與圖片整理，非原文翻譯）" },
  en: { works: "Selected work", all: "All", back: "All projects", previous: "Previous project", next: "Next project", summary: "Project overview", background: "Background", challenge: "Challenge", approach: "Approach", deliverables: "Deliverables", outcomes: "Outcomes", newCopy: "New editorial description based on the existing title and images; not a translation." },
  ja: { works: "制作事例", all: "すべて", back: "事例一覧", previous: "前の事例", next: "次の事例", summary: "作品概要", background: "背景", challenge: "課題", approach: "制作内容", deliverables: "納品内容", outcomes: "成果", newCopy: "既存のタイトルと画像をもとにした新規紹介文です。原文の翻訳ではありません。" }
} as const;

const descriptions: Record<string, { en: string; ja: string }> = {
  bestbrass: { en: "A website visual shown in the supplied project screenshot. The full brief and scope remain unconfirmed.", ja: "提供されたスクリーンショットに掲載されているウェブサイトのビジュアルです。制作目的と担当範囲の詳細は未確認です。" },
  "glitter8-catalog": { en: "A catalog and graphic design example shown in the supplied mockup. Page count and print specifications remain unconfirmed.", ja: "提供されたモックアップに掲載されているカタログとグラフィックの事例です。ページ数と印刷仕様は未確認です。" },
  itayamachibaru: { en: "A long-form website visual from the supplied project images. Detailed project context remains unconfirmed.", ja: "提供された作品画像に掲載されている縦長のウェブサイトビジュアルです。詳しい制作背景は未確認です。" },
  "iwata-shotenkai-flyer": { en: "A single-page graphic presenting event or shopping-district information. The brief and text details remain unconfirmed.", ja: "イベントまたは商店街の情報を伝える一枚もののグラフィックです。依頼内容と掲載文言の詳細は未確認です。" },
  "photography-sample": { en: "A photography or product-image sample from the supplied portfolio materials. The subject and usage remain unconfirmed.", ja: "提供された作品資料に含まれる写真または商品画像の事例です。被写体と使用目的は未確認です。" },
  "shiroki-sr": { en: "A long-form website visual and image asset example. Detailed project context remains unconfirmed.", ja: "縦長のウェブサイトビジュアルと画像素材の事例です。詳しい制作背景は未確認です。" },
  suzukitosou: { en: "A website visual shown in the supplied long screenshot. Further project details remain unconfirmed.", ja: "提供された縦長のスクリーンショットに掲載されているウェブサイトビジュアルです。詳細は未確認です。" },
  tokaiunit: { en: "A website visual and content layout shown in the supplied long screenshot. The project background remains unconfirmed.", ja: "提供された縦長のスクリーンショットに掲載されているウェブサイトのビジュアルとコンテンツ配置です。制作背景は未確認です。" },
  "web-portfolio-links": { en: "A collection of website links listed in the résumé. Individual project scope remains unconfirmed.", ja: "履歴書に掲載されたウェブサイト作品リンク集です。各案件の担当範囲は未確認です。" }
};
export function caseDescription(slug: string, locale: Locale, title = ""): string | undefined {
  if (locale === "zh-Hant") return undefined;
  return descriptions[slug]?.[locale] ?? (locale === "en"
    ? `A portfolio example titled “${title}”. The images and project name are retained from the original case; a verified English account of the brief, responsibilities and outcomes is not yet available.`
    : `「${title}」の制作事例です。画像と作品名は元の事例を使用しています。制作目的、担当範囲、成果について確認済みの日本語資料はまだありません。`);
}

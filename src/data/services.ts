/** Shared service content for the homepage and Services page. No CMS dependency. */
export type ServiceId = "brand" | "print" | "web" | "social" | "motion";

export interface Service {
  id: ServiceId;
  slug: string;
  title: string;
  shortTitle: string;
  summary: string;
  description: string;
  suitableFor: readonly string[];
  deliverables: readonly string[];
  relatedServiceIds: readonly ServiceId[];
  featured: boolean;
  displayOrder: number;
}

const services: readonly Service[] = [
  {
    id: "brand",
    slug: "brand-visual-design",
    title: "品牌與視覺設計",
    shortTitle: "品牌視覺",
    summary: "協助品牌建立或延伸一致的視覺，讓品牌、活動與紙本文宣有清楚的表達。",
    description: "從品牌想傳達的訊息與實際使用情境出發，我會整理視覺方向、資訊層級與版面，再延伸到品牌識別、主視覺、海報、DM、名片與型錄等品牌文宣。適合品牌建立、活動、教育招生、展覽與紙本宣傳需求。",
    suitableFor: [
      "新品牌需要建立 Logo、識別與基本視覺應用。",
      "既有品牌需要統一 Logo、識別、主視覺與紙本文宣。",
      "活動、研習會或教育招生需要清楚呈現主題與資訊。",
      "展覽或日常宣傳需要型錄、DM、名片與海報。"
    ],
    deliverables: ["Logo 設計", "品牌識別", "品牌視覺延伸", "主視覺設計", "活動視覺", "研習會海報", "教育招生海報", "DM", "名片", "型錄"],
    relatedServiceIds: ["print", "web", "social"],
    featured: true,
    displayOrder: 1
  },
  {
    id: "print",
    slug: "print-production",
    title: "印刷與實體輸出",
    shortTitle: "印刷輸出",
    summary: "從視覺設計延伸到印刷品與活動現場，協助處理完稿、尺寸規格與廠商對接。",
    description: "依照商品、宣傳與現場使用需求，整理適合的印刷尺寸與規格，將設計完成為可交付的輸出檔案。我也可協助印刷廠商對接，讓品牌視覺延伸到標籤、紙本與活動輸出物。",
    suitableFor: [
      "商品需要標籤、瓶身標籤或貼紙等實體應用。",
      "品牌需要製作名片、DM 或海報等紙本宣傳品。",
      "活動或展覽需要掛布、展架、大圖與現場文宣。",
      "既有設計需要調整印刷尺寸、規格與完稿檔案。"
    ],
    deliverables: ["商品標籤", "貼紙", "瓶身標籤", "名片", "DM", "海報", "掛布", "展架", "大圖輸出", "展場文宣", "活動輸出物", "印刷完稿", "印刷尺寸與規格處理", "印刷廠商對接"],
    relatedServiceIds: ["brand", "social"],
    featured: true,
    displayOrder: 2
  },
  {
    id: "web",
    slug: "web-wordpress",
    title: "網站與 WordPress",
    shortTitle: "網站製作",
    summary: "協助品牌、企業與活動建立網站，從頁面設計、WordPress 建置到後續內容更新與基本維護。",
    description: "先釐清網站要提供的資訊與訪客需要完成的事，再整理內容、版面與不同裝置的呈現方式。以品牌官網、企業形象網站及活動頁面為主，支援 WordPress 製作、改版、基本維護與基礎搜尋及流量工具設定。",
    suitableFor: [
      "品牌或企業需要建立介紹服務、產品與聯絡方式的官網。",
      "活動或宣傳需要集中呈現資訊的 Landing Page。",
      "既有網站需要改版、改善手機呈現或更新內容。",
      "WordPress 網站需要基本維護與基礎搜尋、流量工具設定。"
    ],
    deliverables: ["WordPress 網站", "企業形象網站", "品牌官網", "Landing Page", "活動頁面", "RWD 響應式網站", "網站改版", "網站內容更新", "網站基本維護", "Google Search Console", "GA4", "基礎 SEO 設定"],
    relatedServiceIds: ["brand", "social", "motion"],
    featured: true,
    displayOrder: 3
  },
  {
    id: "social",
    slug: "social-digital-support",
    title: "社群與數位行銷支援",
    shortTitle: "社群支援",
    summary: "協助製作社群內容與廣告素材，支援基礎數位投放執行及成效整理。",
    description: "依品牌訊息、活動時程與使用版位，製作社群貼文、廣告和網站導流素材，並延伸不同尺寸。可協助 META 廣告發布與基礎廣告成效整理，讓日常宣傳有可持續安排的製作與執行支援。",
    suitableFor: [
      "品牌需要定期更新 Facebook 或 Instagram 視覺素材。",
      "活動宣傳需要社群貼文、廣告與不同版位的素材。",
      "網站與社群需要使用一致訊息的導流素材。",
      "需要協助 META 廣告發布與基礎成效整理。"
    ],
    deliverables: ["Facebook / Instagram 社群素材", "社群貼文視覺", "活動宣傳素材", "META 廣告素材", "META 廣告發布", "不同版位素材延伸", "網站與社群導流素材", "基礎廣告成效整理"],
    relatedServiceIds: ["brand", "web", "motion"],
    featured: true,
    displayOrder: 4
  },
  {
    id: "motion",
    slug: "video-motion-content",
    title: "影片與動態內容",
    shortTitle: "影音內容",
    summary: "將品牌、商品與社群訊息整理為短影音，透過剪輯、字幕與動態文字呈現重點。",
    description: "依品牌與商品需要傳達的內容，整理適合社群觀看的影片結構與節奏，製作短片、Reels 或直式影片。以 CapCut 剪輯、字幕、動態文字與基礎轉場為製作方式，也可依內容需求生成及應用 AI 影像素材。",
    suitableFor: [
      "商品需要以短影音介紹特點或使用情境。",
      "品牌需要短片、Reels 或社群直式影片。",
      "已有影像需要整理剪輯、字幕與動態文字。",
      "短影音製作需要搭配 AI 影像素材。"
    ],
    deliverables: ["商品短影音", "品牌短片", "Reels", "社群直式影片", "字幕製作", "動態文字", "CapCut 剪輯", "基礎轉場與節奏剪輯", "AI 影像素材生成與應用"],
    relatedServiceIds: ["brand", "web", "social"],
    featured: true,
    displayOrder: 5
  }
];

export function getServices(): Service[] {
  return [...services].sort((a, b) => a.displayOrder - b.displayOrder);
}

export function getFeaturedServices(): Service[] {
  return getServices().filter((service) => service.featured);
}

export function getRelatedServices(id: ServiceId): Service[] {
  const relatedIds = services.find((service) => service.id === id)?.relatedServiceIds ?? [];
  return getServices().filter((service) => relatedIds.includes(service.id));
}

import { getServices, type Service, type ServiceId } from "./services";

export type SolutionId = "brand-building" | "event-promotion" | "product-launch" | "ongoing-brand-support";

export interface Solution {
  id: SolutionId;
  slug: string;
  title: string;
  shortTitle: string;
  summary: string;
  description: string;
  suitableFor: readonly string[];
  scenario: readonly string[];
  deliverables: readonly string[];
  serviceIds: readonly ServiceId[];
  process: readonly string[];
  featured: boolean;
  displayOrder: number;
}

const solutions: readonly Solution[] = [
  {
    id: "brand-building",
    slug: "brand-building",
    title: "品牌建立",
    shortTitle: "品牌建立",
    summary: "依品牌目前階段，整理識別、宣傳素材與網站，建立一致的對外形象。",
    description: "新品牌起步、既有品牌更新，或希望重新整理整體視覺與線上形象時，可以先釐清最需要被看見的訊息，再安排識別、文宣與網站等製作項目。我會依目前已有的資料與使用需求，協助選擇適合的服務組合，逐步完成對外溝通需要的內容。",
    suitableFor: ["新品牌需要建立識別與基本對外素材。", "既有品牌需要更新視覺或整理品牌應用。", "企業希望統一紙本文宣、社群與網站的形象。"],
    scenario: ["已有品牌名稱，希望從 Logo 與基本識別開始。", "現有名片、文宣與社群素材各自製作，想整理成一致的方向。", "品牌視覺已有基礎，需要延伸網站與線上介紹。"],
    deliverables: ["Logo 設計", "品牌識別", "品牌主視覺", "名片", "DM / 品牌文宣", "社群基礎視覺", "品牌網站", "WordPress 官網", "基礎搜尋設定"],
    serviceIds: ["brand", "print", "web", "social"],
    process: ["品牌需求與現況整理", "視覺方向確認", "識別與主視覺", "文宣與社群延伸", "品牌網站", "交付與後續更新"],
    featured: true,
    displayOrder: 1
  },
  {
    id: "event-promotion",
    slug: "event-promotion",
    title: "活動宣傳",
    shortTitle: "活動宣傳",
    summary: "串連活動宣傳、線上資訊與現場輸出，依時程整理需要的視覺和製作項目。",
    description: "從研習會、教育招生活動到展覽、品牌與節慶活動，宣傳素材經常需要跨越紙本、社群、網頁與現場。我會先整理活動資訊、使用位置與製作期限，再將主視覺延伸到海報、DM、掛布、展架及線上素材，讓各階段可以依序確認與交付。",
    suitableFor: ["研習會", "教育活動", "展覽", "招生活動", "品牌活動", "節慶活動", "實體活動"],
    scenario: ["研習會或招生活動，需要讓課程、時間與參與方式清楚可讀。", "展覽已有主題，需要準備 DM、掛布、展架及現場文宣。", "活動需要同步安排社群宣傳、活動頁面與短影音。", "活動結束後，希望將已有內容整理成後續宣傳素材。"],
    deliverables: ["活動主視覺", "海報", "DM", "社群宣傳素材", "META 廣告素材", "掛布", "展架", "現場輸出物", "Landing Page", "活動網站", "短影音", "活動後續素材"],
    serviceIds: ["brand", "print", "web", "social", "motion"],
    process: ["活動資訊與時程整理", "活動主視覺", "宣傳文宣與社群素材", "活動頁面與短影音", "現場印刷輸出", "活動後續素材"],
    featured: true,
    displayOrder: 2
  },
  {
    id: "product-launch",
    slug: "product-launch",
    title: "商品上市",
    shortTitle: "商品上市",
    summary: "從商品視覺與標籤印刷，延伸社群、影片和網站，配合上市需求安排製作。",
    description: "新品上市、商品更新或新系列推出時，可以把商品介紹、實體應用與線上宣傳一起整理。我會依已確認的商品資訊與上市時程，安排主視覺、標籤、DM、短影音及網頁更新等項目，讓同一組商品訊息在不同媒介中延伸使用。",
    suitableFor: ["新品上市", "商品更新", "新系列推出", "電商 / 品牌宣傳"],
    scenario: ["新商品需要標籤、貼紙或瓶貼，也需要對外介紹的素材。", "商品系列更新，希望統一 DM、社群與廣告視覺。", "已有商品資訊，需要整理成短影音與商品介紹頁。", "上市宣傳需要配合 WordPress 網頁更新及 META 廣告發布。"],
    deliverables: ["商品主視覺", "商品標籤", "貼紙 / 瓶貼", "商品 DM", "社群素材", "廣告素材", "商品短影音", "Reels", "Landing Page", "商品介紹頁", "WordPress 網頁更新", "META 廣告發布"],
    serviceIds: ["brand", "print", "web", "social", "motion"],
    process: ["商品需求與資訊整理", "商品視覺", "標籤 / 印刷", "社群素材", "短影音", "Landing Page / 商品頁", "META 廣告"],
    featured: true,
    displayOrder: 3
  },
  {
    id: "ongoing-brand-support",
    slug: "ongoing-brand-support",
    title: "長期品牌支援",
    shortTitle: "長期支援",
    summary: "把持續性的設計與網站工作納入外部合作，透過固定窗口與需求排程維持製作節奏。",
    description: "當企業持續需要美編、平面設計、網站維護或社群素材，部分工作也可以採月度或半遠端的外部合作模式。我會以遠端執行、固定窗口、需求排程、定期會議、部分現場協作與工作進度整理的方式，協助安排已確認的設計及網站工作；合作範圍依實際需求討論。",
    suitableFor: ["每個月都有設計需求", "不定期需要網站更新", "常有活動／新品", "需要社群素材", "偶爾需要印刷", "偶爾需要影片", "暫時不需要增加完整全職人力"],
    scenario: ["日常文宣與社群素材持續出現，希望有固定窗口協助整理製作。", "活動與新品需求分散在不同時間，需要依優先順序安排。", "網站需要不定期更新，同時偶爾會有印刷或影片需求。", "目前需要部分持續性支援，希望先評估月度或半遠端合作範圍。"],
    deliverables: ["品牌日常視覺", "社群素材", "活動視覺", "印刷文宣", "網站內容更新", "WordPress 維護", "Landing Page", "商品素材", "短影音", "廣告素材", "META 廣告發布", "其他雙方確認的設計／網站工作"],
    serviceIds: ["brand", "print", "web", "social", "motion"],
    process: ["固定窗口整理需求", "確認範圍與優先順序", "需求排程", "遠端設計與網站執行", "定期會議確認", "工作進度與交付整理", "後續需求安排"],
    featured: true,
    displayOrder: 4
  }
];

export function getSolutions(): Solution[] {
  return [...solutions].sort((a, b) => a.displayOrder - b.displayOrder);
}

export function getFeaturedSolutions(): Solution[] {
  return getSolutions().filter((solution) => solution.featured);
}

export function getSolutionServices(id: SolutionId): Service[] {
  const ids = solutions.find((solution) => solution.id === id)?.serviceIds ?? [];
  return getServices().filter((service) => ids.includes(service.id));
}

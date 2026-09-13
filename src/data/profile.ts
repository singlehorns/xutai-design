import { site } from "./site";

export type ResumeStatus = "coming-soon" | "available";

export interface BackgroundArea {
  id: string;
  title: string;
  summary: string;
  points: readonly string[];
}

export interface ExperienceSummary {
  id: string;
  title: string;
  description: string;
}

export interface ToolGroup {
  id: string;
  title: string;
  tools: readonly string[];
}

export const publicProfile = {
  brandName: site.siteName,
  ownerDisplayName: site.ownerDisplayName,
  tagline: "從視覺設計出發，將品牌、印刷、網站與數位內容整合成可以實際執行的服務。",
  shortBio:
    "T2（旭泰整合設計）由 Tseting Chen 提供品牌視覺、印刷、網站與數位內容相關服務，協助企業把想傳達的資訊整理成可閱讀、可製作、可延伸的設計輸出。",
  aboutIntro:
    "我以個人工作室品牌的方式承接專案，從需求整理、視覺設計、印刷輸出、網站內容到數位素材，依企業當下需要安排單次或持續性的遠端合作。",
  backgroundSummary:
    "工業設計背景讓我習慣從使用情境、資訊層級與製作條件思考設計。後續在品牌平面、教育與活動宣傳、網站視覺、WordPress 內容與數位素材工作中，逐步形成現在橫跨視覺、印刷、網站與內容支援的服務範圍。",
  backgroundAreas: [
    {
      id: "visual",
      title: "視覺與平面設計",
      summary: "整理品牌、活動與宣傳內容，轉換成清楚的視覺層級與版面。",
      points: ["品牌識別與視覺延伸", "活動與教育招生宣傳", "DM、海報、型錄與社群視覺"]
    },
    {
      id: "print",
      title: "印刷與實體製作",
      summary: "依照實際輸出條件處理尺寸、規格、完稿與廠商溝通。",
      points: ["名片、DM、海報與標籤", "展場與活動輸出物", "印刷完稿與規格整理"]
    },
    {
      id: "web",
      title: "網站與 WordPress",
      summary: "協助品牌或活動建立可維護的網站資訊架構與頁面呈現。",
      points: ["WordPress 網站與內容更新", "品牌官網與 Landing Page", "RWD 頁面與基礎 SEO 設定"]
    },
    {
      id: "digital",
      title: "數位內容與遠端協作",
      summary: "讓社群素材、廣告素材、短影音與網站更新能被持續整理與執行。",
      points: ["社群與廣告素材延伸", "短影音與字幕製作", "GitHub 與遠端工作流程"]
    }
  ] satisfies readonly BackgroundArea[],
  workingStyle: [
    "由 Tseting Chen 直接溝通與執行，需求、排程與交付內容保持清楚。",
    "先整理目標、使用情境與現有素材，再確認製作範圍與優先順序。",
    "以遠端協作為主，透過線上回饋、定期確認與工作排程推進。",
    "網站與數位專案可依需求使用 GitHub 等工具管理版本或協作內容。",
    "必要時依專案性質討論現場會議。"
  ],
  experienceSummaries: [
    {
      id: "brand-print",
      title: "品牌與平面視覺",
      description: "曾在企業、品牌與產品相關情境中處理平面宣傳、型錄、標籤與日常視覺需求。"
    },
    {
      id: "education-event",
      title: "教育與活動宣傳",
      description: "具備教育單位、活動資訊與招生宣傳相關視覺整理經驗，重視資訊清楚與閱讀順序。"
    },
    {
      id: "web-production",
      title: "網站製作與內容維護",
      description: "可支援品牌官網、活動頁面、WordPress 內容更新與網站基礎維護。"
    },
    {
      id: "remote-support",
      title: "個人專案與遠端支援",
      description: "以個人工作室方式提供固定窗口，協助企業把不定期或持續性的設計需求整理成可執行工作。"
    }
  ] satisfies readonly ExperienceSummary[],
  toolGroups: [
    { id: "design", title: "Design", tools: ["Illustrator", "Photoshop", "Figma"] },
    { id: "web", title: "Web", tools: ["WordPress", "Astro", "HTML", "CSS"] },
    { id: "collaboration", title: "Collaboration", tools: ["GitHub", "Remote Workflow"] },
    { id: "video", title: "Video", tools: ["CapCut"] }
  ] satisfies readonly ToolGroup[],
  resume: {
    label: "Public Resume / Tseting Chen",
    status: "coming-soon" as ResumeStatus,
    note: "公開版履歷尚未製作完成；正式履歷或合約所需資料，將依需求私下提供。"
  },
  cta: {
    title: "如果你正在規劃品牌、活動、網站，或需要持續性的設計支援，可以先聊聊需求。",
    href: "/contact/",
    label: "前往聯絡合作"
  }
} as const;

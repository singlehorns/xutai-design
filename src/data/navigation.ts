/** V2 public sitemap. Detail routes continue to come from the existing works collection. */
export const navigation = [
  { href: "/services/", label: "服務項目" },
  { href: "/works/", label: "作品案例" },
  { href: "/cooperation/", label: "合作方式" },
  { href: "/about/", label: "關於我" },
  { href: "/shopping-guide/", label: "購物須知" },
  { href: "/contact/", label: "聯絡合作" }
] as const;

export const sitemap = [
  { href: "/", label: "首頁" },
  ...navigation,
  { href: "/works/[slug]/", label: "個別案例" }
] as const;

// Preserve this route until About migration and deployment redirects are approved.
export const legacyRoutes = [
  { href: "/experience/", futureDestination: "/about/#experience", status: "retained" }
] as const;

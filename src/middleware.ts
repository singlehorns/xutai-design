import { defineMiddleware } from "astro:middleware";
import { translations } from "./data/page-translations";

const decode = (value: string) => value.replace(/&amp;/g, "&").replace(/&quot;/g, '"').replace(/&#39;|&#x27;/g, "'");
const escape = (value: string) => value.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
const orderedTranslations = Object.entries(translations).sort(([a], [b]) => b.length - a.length);

function translate(value: string, language: "en" | "ja") {
  const normalized = decode(value.trim());
  const entry = translations[normalized];
  if (entry) return value.replace(value.trim(), escape(entry[language === "en" ? 0 : 1]));
  return value;
}

function translateMetadata(value: string, language: "en" | "ja") {
  let result = translate(value, language);
  for (const [source, target] of orderedTranslations) {
    if (result.includes(source)) result = result.replaceAll(source, escape(target[language === "en" ? 0 : 1]));
  }
  return result;
}

export const onRequest = defineMiddleware(async ({ url }, next) => {
  const language = /^\/xutai-design\/(en|ja)(?:\/|$)/.exec(url.pathname)?.[1] as "en" | "ja" | undefined;
  const response = await next();
  if (!language || !/^\/xutai-design\/(?:en|ja)\/(?:|services\/|cooperation\/|about\/|experience\/|contact\/|shopping-guide\/)$/i.test(url.pathname) || !response.headers.get("content-type")?.includes("text/html")) return response;
  let html = await response.text();
  html = html.replace(/>([^<>]+)</g, (_match, value: string) => `>${translate(value, language)}<`);
  html = html.replace(/<title>([^<]*)<\/title>/g, (_match, value: string) => `<title>${translateMetadata(value, language)}</title>`);
  html = html.replace(/(placeholder|aria-label|alt|title|content|value)="([^"]*)"/g, (_match, attribute: string, value: string) => `${attribute}="${attribute === "content" || attribute === "aria-label" ? translateMetadata(value, language) : translate(value, language)}"`);
  const headers = new Headers(response.headers);
  headers.delete("content-length");
  return new Response(html, { status: response.status, statusText: response.statusText, headers });
});

const base = import.meta.env.BASE_URL;

export function withBase(path: string): string {
  if (!path.startsWith("/")) return path;
  const cleanBase = base.endsWith("/") ? base.slice(0, -1) : base;
  if (!cleanBase) return path;
  return `${cleanBase}${path}`;
}

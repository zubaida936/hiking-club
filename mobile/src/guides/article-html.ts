import type { Article } from '@/api/types';
import type { Colors } from '@/constants/theme';
import { formatDate } from '@/format';
import { strings } from '@/strings';

type Theme = (typeof Colors)['light'] | (typeof Colors)['dark'];

/** Escapes text for HTML. The body itself is already safe HTML from the API (raw HTML in the CMS text is escaped). */
function escape(text: string): string {
  return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/**
 * The whole article as one HTML page for the web view: title, author line, cover, the CMS body and links to more
 * guides, in the app's colours. Links are plain `<a href>`: the screen opens them in the app or the browser.
 */
export function articleHtml(article: Article, theme: Theme): string {
  const meta = strings.guides.meta(article.author, article.published_at ? formatDate(article.published_at) : null, article.reading_minutes);
  const cover = article.cover_url ? `<img class="cover" src="${escape(article.cover_url)}" alt="">` : '';
  const more =
    article.more.length > 0
      ? `<h2>${escape(strings.guides.more)}</h2><ul class="more">${article.more
          .map((other) => `<li><a href="${escape(other.web_url)}">${escape(other.title)}</a></li>`)
          .join('')}</ul>`
      : '';

  return `<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body { margin: 0; padding: 16px 16px 48px; background: ${theme.background}; color: ${theme.text};
    font-family: -apple-system, Roboto, sans-serif; font-size: 17px; line-height: 1.6; overflow-wrap: break-word; }
  h1 { font-size: 26px; line-height: 1.25; margin: 0 0 8px; }
  h2 { font-size: 21px; margin: 28px 0 8px; }
  h3 { font-size: 18px; margin: 22px 0 6px; }
  .meta { color: ${theme.textSecondary}; font-size: 14px; margin: 0 0 16px; }
  img { max-width: 100%; height: auto; border-radius: 12px; }
  .cover { width: 100%; margin-bottom: 16px; }
  a { color: ${theme.accent}; }
  blockquote { margin: 16px 0; padding: 4px 16px; border-left: 4px solid ${theme.border}; color: ${theme.textSecondary}; }
  pre, code { background: ${theme.backgroundElement}; border-radius: 6px; padding: 2px 4px; }
  table { border-collapse: collapse; width: 100%; }
  td, th { border: 1px solid ${theme.border}; padding: 6px; }
  .more { padding-left: 20px; }
</style>
</head>
<body>
<h1>${escape(article.title)}</h1>
<p class="meta">${escape(meta)}</p>
${cover}
<article>${article.body_html}</article>
${more}
</body>
</html>`;
}

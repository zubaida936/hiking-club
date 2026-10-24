# Content (CMS)

Content the club edits itself in the CMS, without a developer: **Guide articles**, the **Gallery**, the
**Homepage slides**, the **Team** and the newsletter **Subscribers** list. ClickUp: "8. Admin Panel → Manage content".

| CMS section | Permission | Roles |
|---|---|---|
| Guide articles, Homepage slides, Team | `content.manage` | Super Admin, Admin, Content Editor |
| Gallery | `media.manage` | Super Admin, Admin, Content Editor |
| Subscribers | `subscribers.manage` (personal data) | Super Admin, Admin |

Code: `app/Domain/Content` (save actions, `HomepageHero`), models `Article`, `Album` / `AlbumPhoto`, `HeroSlide`,
`TeamMember`, controllers in `app/Http/Controllers/Admin`, views in `resources/views/admin/{articles,albums,hero-slides,team,subscribers}`.

## How it works

- **Photos** are resized on upload and stored as JPEG on the public disk (`storage/app/public/{articles,albums,hero,team}`):
  article covers max 1600 px, gallery and slide photos 1920 px, team portraits 800 px. Replacing or removing a photo, or
  deleting the article / album / slide / person, deletes the file. JPG, PNG or WebP up to 10 MB each.
- **Guide articles**: title, short summary (optional; otherwise the start of the text), text in **Markdown**
  (`## Heading`, `**bold**`, lists, links), author, cover photo, URL slug. "Published" + publish date: empty date =
  today, a future date schedules the article (it appears on that day, Lebanon time), unticked = draft. Raw HTML in the
  text is shown as text and `javascript:` links are dropped, so an article can't inject scripts.
- **Gallery**: an album has a title, an optional hike (the event it's from), date, description and "Show in the
  gallery". "New album" with `?event={id}` prefills the hike. Photos: up to 20 per upload and 25 MB in total (the
  server's upload limits); upload more in batches. New photos go at the end; "Cover" moves a photo to the front, the
  first photo is the cover. Deleting a hike keeps its albums (unlinked).
- **Homepage slides**: the carousel at the top of the homepage. Small title, heading, text, button text + link (a page
  of this site like `/events`, or a full `https://` link; both or neither), background photo (darkened for
  readability; without one the slide uses a coloured background), order and "Show on the homepage". **While no slide
  is shown, the homepage uses the 3 placeholder slides in `config/site.php` → `hero`**, so it never looks empty.
- **Team**: name, role, short bio, portrait, order, "Show on the About page".
- **Subscribers** ("Next Destination Alerts"): counts (getting emails, phone numbers, unconfirmed), filters
  (subscribed, email confirmed, awaiting confirmation, with a phone number, unsubscribed), search by email or phone,
  **Download CSV** of the current filter (e.g. the phone numbers for a WhatsApp broadcast), **Unsubscribe** (keeps the
  record that they opted out) and **Delete** (for "delete my data" requests).

## Where the content is shown

- Homepage slides: live on the homepage now.
- Articles, albums and team members are stored and edited here; the public pages that show them are separate ClickUp
  tasks ("7. Content Pages": Guides / blog, Photo gallery, About page). Use `Article::published()`,
  `Album::published()` and `TeamMember::shown()` there; `Article::bodyHtml()` gives the safe HTML of the text.

## Testing it locally

1. `docker compose exec app php artisan migrate` and `docker compose exec app php artisan db:seed --class=RolesAndPermissionsSeeder`
   (adds the new `subscribers.manage` permission). Sample content: `docker compose exec app php artisan db:seed --class=DemoContentSeeder`
   (the 3 placeholder slides as editable slides, 3 team members, a published and a draft article; also part of `migrate:fresh --seed`).
2. CMS → Homepage slides: edit a slide, add a photo, change the order, then open the homepage.
3. CMS → Guide articles / Gallery / Team: create, upload photos, edit, delete.
4. CMS → Subscribers: sign up on the homepage form first (confirmation email in Mailpit), then filter, export, unsubscribe.
5. Log in as a Content Editor (create one in Staff & roles): the content sections are there, Subscribers is not.

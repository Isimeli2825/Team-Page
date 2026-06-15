# Team Page Module

**Version:** 1.0.0  
**Drupal Core:** ^10 || ^11  
**PHP:** >= 8.1  
**Package:** `spc/team_page`

A production-ready Drupal module that provides a professional Team Page with responsive grid layout, department filtering, team member cards, and accessible modal dialogs.

Built with MVC architecture, Drupal coding standards, and best practices for enterprise-grade Drupal development.

---

## 📋 Table of Contents

- [Features](#-features)
- [Quick Start](#-quick-start)
- [Architecture](#-architecture)
- [File Structure](#-file-structure)
- [Data Flow](#-data-flow)
- [Configuration](#-configuration)
- [Migration to Dynamic Content](#-migration-to-dynamic-content)
- [Views Integration](#-views-integration)
- [Layout Builder Compatibility](#-layout-builder-compatibility)
- [Theming](#-theming)
- [Development](#-development)
- [Accessibility](#-accessibility)
- [Caching](#-caching)
- [Extending](#-extending)
- [Troubleshooting](#-troubleshooting)

---

## ✨ Features

| Feature | Description |
|---------|-------------|
| **`/team` page** | Professional team listing with responsive card grid |
| **Department filtering** | JavaScript-powered filter with URL param persistence |
| **Member detail modals** | Accessible modal dialogs with focus trapping |
| **Dark/light theme** | Automatic theme support via `prefers-color-scheme` |
| **Responsive design** | Mobile-first, 1→2→3 column grid with auto-fill |
| **Hover animations** | Card lift, image zoom, social overlay transitions |
| **Accessibility** | ARIA roles, keyboard navigation, screen reader support |
| **SEO** | JSON-LD structured data (schema.org/Person) |
| **No inline JS** | Progressive enhancement via Drupal behaviors |
| **PSR-4 compliant** | Namespaced classes with dependency injection |
| **Render array API** | Cache-safe, Layout Builder compatible |
| **Print styles** | Clean print output without interactive elements |

---

## 🚀 Quick Start

```bash
# 1. Copy module to your custom modules directory
cp -r team_page /path/to/drupal/web/modules/custom/

# 2. Enable the module
drush en team_page -y

# 3. Clear cache
drush cr

# 4. Visit the team page
# → http://your-site.com/team
```

---

## 🏗 Architecture

### MVC Pattern

```
┌──────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Routing    │ ──> │   Controller     │ ──> │  Service Layer  │
│  .routing.yml│     │ TeamPageController│     │ TeamMemberService│
└──────────────┘     └──────────────────┘     └─────────────────┘
                            │
                            ▼
                     ┌──────────────┐     ┌─────────────────┐
                     │  hook_theme  │ ──> │    Twig View    │
                     │ .module file │     │ team-page.html  │
                     └──────────────┘     └─────────────────┘
                            │
                            ▼
                     ┌──────────────────────────────────────┐
                     │  Assets: CSS + JS (via .libraries.yml)│
                     └──────────────────────────────────────┘
```

### Key Design Decisions

1. **Service Layer with Interface** — `TeamMemberServiceInterface` allows swapping data sources without changing controllers or templates.

2. **Preprocess Hook** — `team_page_preprocess_team_page()` handles data transformation (initials generation, HTML IDs, JSON-LD) keeping Twig clean.

3. **Dependency Injection** — Controller receives the team member service via constructor injection, making it testable and configurable.

4. **Progressive Enhancement** — Filtering and modals require JavaScript, but the page is fully functional without it.

5. **Cache Safety** — Render array includes proper cache tags, contexts, and max-age.

---

## 📁 File Structure

```
team_page/
│
├── team_page.info.yml          # Module metadata + dependencies
├── team_page.routing.yml       # Route: /team
├── team_page.libraries.yml     # CSS/JS asset library definition
├── team_page.module            # hook_theme(), preprocess, theme suggestions
├── team_page.services.yml      # Service container definition
├── composer.json               # Composer package metadata
├── README.md                   # This file
│
├── src/
│   ├── Controller/
│   │   └── TeamPageController.php    # Page controller (DI-enabled)
│   └── Service/
│       ├── TeamMemberService.php          # Data service implementation
│       └── TeamMemberServiceInterface.php # Service contract
│
├── templates/
│   └── team-page.html.twig       # Main team page template
│
├── css/
│   └── team-page.css             # All styles (responsive, themes, animations)
│
├── js/
│   └── team-page.js              # Drupal behaviors (filtering, modals)
│
└── images/
    └── (placeholder for default avatar)
```

---

## 🔄 Data Flow

```
1. User visits /team
         │
2. Drupal routing matches to TeamPageController::build()
         │
3. Controller calls TeamMemberService::getTeamMembers()
         │                          │
         │    (Currently hardcoded  │  Future: entity query /
         │     array data)          │  database query)
         │                          │
4. Controller returns render array:
   ┌─────────────────────────────────────────────┐
   │ '#theme' => 'team_page'                     │
   │ '#members' => [...]                          │
   │ '#attached' => ['library' => [...]]          │
   │ '#cache' => ['tags' => [...], ...]           │
   └─────────────────────────────────────────────┘
         │
5. Preprocess hook transforms data:
   - Generates initials from names
   - Creates clean HTML IDs
   - Builds JSON-LD structured data
   - Passes drupalSettings to JS
         │
6. Twig template renders HTML:
   - HTML5 semantic elements
   - BEM CSS classes
   - ARIA attributes
   - Progressive enhancement data attributes
         │
7. CSS applies styling + animations
   JS adds filtering + modals
```

---

## ⚙️ Configuration

### Admin Settings (Future Enhancement)

The routing file includes a commented route for admin settings. To implement:

1. Create `src/Form/TeamPageSettingsForm.php`
2. Uncomment the `team_page.settings` route in `team_page.routing.yml`
3. Create `config/schema/team_page.schema.yml` for configuration storage

### Adjusting Team Data

To change team members, edit the hardcoded array in:

```
src/Service/TeamMemberService.php → getTeamMembers()
```

Each member supports:
- `name` (string, required)
- `position` (string, required)
- `bio` (string, optional)
- `image` (string URL, optional — empty for initials fallback)
- `email` (string, optional)
- `socials` (assoc array, optional — e.g., `['linkedin' => 'url']`)
- `department` (string, optional — groups members)
- `cta` (array|null, optional — `['text' => '...', 'url' => '...']`)
- `weight` (int, optional — for ordering)

---

## 🔄 Migration to Dynamic Content

The module is architected for easy migration from hardcoded data to Drupal-managed content:

### Step 1: Create Content Type

Create a `team_member` content type with fields matching the data structure:

| Data Key | Field Type | Machine Name |
|----------|-----------|--------------|
| name | Node title | `title` |
| position | Text (plain) | `field_position` |
| bio | Text (formatted, long) | `field_bio` |
| image | Image | `field_profile_image` |
| email | Email | `field_email` |
| socials | Link (multiple) | `field_social_links` |
| department | Term reference | `field_department` |
| cta | Link | `field_cta` |
| weight | Integer | `field_weight` |

### Step 2: Update Service

Replace `TeamMemberService` implementation with entity queries:

```php
public function getTeamMembers(): array {
  $nids = \Drupal::entityQuery('node')
    ->condition('type', 'team_member')
    ->condition('status', 1)
    ->sort('field_weight', 'ASC')
    ->accessCheck(TRUE)
    ->execute();

  $nodes = \Drupal::entityTypeManager()
    ->getStorage('node')
    ->loadMultiple($nids);

  $members = [];
  foreach ($nodes as $node) {
    $members[] = [
      'name' => $node->label(),
      'position' => $node->get('field_position')->value ?? '',
      'bio' => $node->get('field_bio')->value ?? '',
      'image' => $node->get('field_profile_image')->entity?->createFileUrl() ?? '',
      'email' => $node->get('field_email')->value ?? '',
      'socials' => /* parse social links field */,
      'department' => $node->get('field_department')->entity?->label() ?? '',
      'cta' => /* parse CTA link field */,
    ];
  }

  return $members;
}
```

### Step 3: Update Cache Tags

In the controller, add `'node_list:team_member'` to cache tags so the page invalidates when team members change.

### Step 4: Add Views Integration

Create a view via `config/install/views.view.team_members.yml` for the admin UI.

---

## 🧩 Views Integration

The template structure supports easy Views integration:

1. Create a View showing `team_member` content
2. Use `team-page.html.twig` as a custom row template
3. Map View fields to template variables via preprocess

Alternatively, the `teams` and `members` data structure mirrors a View's grouped output, making it straightforward to replace the service with a View execution result.

---

## 🧱 Layout Builder Compatibility

The controller returns a standard Drupal render array, which is fully compatible with Layout Builder:

- Can be placed as a block (create `TeamMembersBlock` extending `BlockBase`)
- Can be embedded in a page via `#theme` reference
- All assets attach via `#attached` — no global overrides
- Cache metadata respects Layout Builder's caching layers

---

## 🎨 Theming

### Override the Template

```bash
# Copy to your theme
cp modules/custom/team_page/templates/team-page.html.twig themes/custom/your_theme/templates/
```

### Theme Suggestions

Add department-specific templates:

- `team-page--executive-leadership.html.twig`
- `team-page--ict-edutech.html.twig`

### Override the CSS

Add a `team-page.css` to your theme's CSS directory, or use the library to override:

```yaml
# In your theme's libraries.yml
team-page-override:
  css:
    theme:
      css/team-page.css: {}
  dependencies:
    - team_page/team-page
```

### CSS Custom Properties

All colors and spacing use CSS custom properties, making theme overrides simple:

```css
/* In your theme's CSS */
:root {
  --team-page-color-primary: #your-color;
  --team-page-color-bg-card: #your-bg;
  --team-page-grid-columns: 4;
}
```

---

## 🛠 Development

### Enable with Drush

```bash
drush en team_page -y
drush cr
```

### Coding Standards

```bash
# Check Drupal coding standards
phpcs --standard=Drupal web/modules/custom/team_page

# Fix automatically
phpcbf --standard=Drupal web/modules/custom/team_page
```

### Run PHPStan

```bash
phpstan analyse web/modules/custom/team_page --level=5
```

---

## ♿ Accessibility

| Feature | Implementation |
|---------|---------------|
| **ARIA roles** | `role="main"`, `role="tablist"`, `role="dialog"`, `role="list"` |
| **ARIA states** | `aria-selected`, `aria-hidden`, `aria-modal`, `aria-controls`, `aria-labelledby` |
| **Keyboard** | Tab trapping in modal, Escape to close |
| **Focus management** | Focus restored to trigger on modal close |
| **Screen readers** | `visually-hidden` class for icon-only buttons, `aria-label` on social links |
| **Reduced motion** | `prefers-reduced-motion: reduce` disables all animations |
| **Color contrast** | Dark/light themes tested for WCAG AA compliance |
| **Semantic HTML** | `<header>`, `<nav>`, `<article>`, `<h1>`-`<h3>` hierarchy |

---

## ⚡ Caching

The render array includes proper cache metadata:

```php
'#cache' => [
  'tags' => ['team_page:members'],
  'contexts' => ['url.path', 'url.query_args:department'],
  'max-age' => 3600,
],
```

When migrating to dynamic entities, add `'node_list:team_member'` to cache tags for automatic invalidation.

---

## 🔧 Extending

### Add a New Social Platform

1. Add the SVG icon in `team-page.html.twig` (in the social links section)
2. Add the platform key to member data in `TeamMemberService.php`

### Add a Custom Block

Create `src/Plugin/Block/TeamMembersBlock.php` extending `BlockBase`, using the same service and template.

### Add AJAX Filtering

The JavaScript filter currently works with DOM hiding. For AJAX:

1. Add a route for JSON data: `/api/team-page/people`
2. Modify the JS to fetch filtered data via `drupalAjax` or `fetch()` API
3. The `#attached` `drupalSettings` already passes department data

---

## 🔍 Troubleshooting

**Q: The team page shows a blank page.**  
A: Ensure the module is enabled (`drush pm:list | grep team_page`) and cache is cleared (`drush cr`).

**Q: No CSS/JS is loading.**  
A: Verify the library is attached. Check `admin/config/development/performance` — aggregation may need clearing.

**Q: Department filter isn't working.**  
A: Ensure JavaScript is enabled and no console errors. The filter uses progressive enhancement — the full list shows without JS.

**Q: Modal doesn't open.**  
A: Check that `show_modals` is TRUE in the controller. The modal requires JavaScript.

---

## 📄 License

This module is licensed under GPL-2.0-or-later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html) for details.

---

Built for the **Pacific Community (SPC) | Educational Quality and Assessment Programme (EQAP)**

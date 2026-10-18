---
title: Style guide
summary: Every element a post or page can contain, in one place, for designing the site.
unlisted: true
---

This page shows every element that posts and pages can contain, so they can be designed in one
place, in light and dark mode. It's **unlisted**: reachable by its address, but not in search
engines, the sitemap or the menus. Each section's title says what it shows.

It comes with the TinyTank skeleton: keep it while designing your site, adapt it, or delete
`content/pages/style-guide/` if you don't need it.

## Text

A paragraph of ordinary text, long enough to wrap over a few lines, so line height, measure and
spacing between paragraphs can be judged. It contains **bold text**, *italic text*, ***both***,
~~strikethrough~~ and `inline code`.

A second paragraph, with a line break here,  
and the line after it. Then an [internal link](/about), an [external link](https://datastar.dev)
that opens in a new tab, and a bare address that becomes a link: https://tinytank.dev

---

The line above is a horizontal rule.

## Heading level 2

Headings get a permalink, shown on hover.

### Heading level 3

Text under a third-level heading.

#### Heading level 4

Text under a fourth-level heading.

##### Heading level 5

###### Heading level 6

## Lists

A bullet list:

- First item
- Second item, long enough to wrap onto a second line so the indentation of wrapped lines can be
  checked
- Third item, with nested items:
  - Nested item
  - Another nested item
    - A third level

A numbered list:

1. First step
2. Second step
   1. A sub-step
   2. Another sub-step
3. Third step

A task list:

- [x] A completed task
- [ ] A task still to do
- [ ] Another one

## Quote

> A quote block. Good design is as little design as possible: less, but better, because it
> concentrates on the essential aspects.
>
> A second paragraph in the same quote.

## Code blocks

PHP:

```php
<?php

declare(strict_types=1);

final class Plan
{
    public function __construct(
        public readonly string $name,
        private array $tags = [],
    ) {
    }

    public function isPopular(): bool
    {
        return in_array('popular', $this->tags, true); // a comment
    }
}
```

Twig:

```twig
{% for post in posts().tag('news').limit(3) %}
    <a href="{{ path('blog_post', {slug: post.slug}) }}">{{ post.title|upper }}</a>
{% endfor %}
```

HTML:

```html
<article class="card">
    <h2>Example</h2>
    <a href="https://example.com" target="_blank">Visit</a>
</article>
```

CSS:

```css
.card {
    padding: 1.5rem;
    border-radius: 0.5rem;
    color: #657c2d;
}
```

JavaScript:

```js
import { persist } from 'tinytank';

const total = [1, 2, 3].reduce((sum, n) => sum + n, 0);
await persist(['volume'], { key: 'player' });
```

JSON:

```json
{
    "name": "you/my-site",
    "private": true,
    "count": 42
}
```

YAML:

```yaml
title: Style guide
unlisted: true
tags: [design, guide]
```

Shell:

```bash
composer create-project tinytank/tinytank my-site
cd my-site && npm install
```

A diff:

```diff
- 'sizes' => '100vw',
+ 'sizes' => '(min-width: 48rem) 48rem, 100vw',
```

A block without a language:

```
Plain text, shown as it is.
    Indentation is kept.
```

A very long line, to see how wide code scrolls:

```php
$message = 'This line is deliberately much longer than the content column, so the horizontal scrolling of code blocks can be checked on every screen size.';
```

## Table

| Plan | Includes | Since | Price |
|---|---|:---:|---:|
| Starter | One site | 2026 | €0 |
| Studio | Five sites | 2026 | €49 |
| A plan with a deliberately long name | Everything | 2025 | €199 |

## Images

A photo from this page's folder, in responsive sizes (AVIF and WebP):

![A green and orange sample landscape](sample-photo.jpg)

An image from elsewhere on the site, shown as it is:

![The site icon](/favicon.svg)

## Components

A video, loaded from YouTube only when the visitor presses play:

::youtube{id="aqz-KE-bpKQ" title="Big Buck Bunny"}

Related posts:

::related-posts{limit=2}

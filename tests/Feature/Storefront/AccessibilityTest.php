<?php

namespace Tests\Feature\Storefront;

use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * WCAG 2.1 AA checks that can be read from the page's HTML (REQ-QLT-02). The full browser scan
 * (axe-core on 32 page views, contrast, sideways scrolling) is in docs/m4-review-notes.md.
 */
class AccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public static function pages(): array
    {
        return array_combine($pages = ['/', '/shop', '/bundles', '/product/wrap-set', '/product/rosemary-hijab', '/cart', '/checkout', '/contact', 'ar:/', 'ar:/shop', 'ar:/product/rosemary-hijab'], array_map(fn ($page) => [$page], $pages));
    }

    #[Test]
    #[DataProvider('pages')]
    public function req_qlt_02_a_control_named_by_aria_label_includes_the_words_it_shows(string $page): void
    {
        $document = HTMLDocument::createFromString($this->get($page)->assertOk()->getContent(), LIBXML_NOERROR);

        $checked = 0;
        foreach ($document->querySelectorAll('a[aria-label], button[aria-label], summary[aria-label]') as $control) {
            $shown = trim(preg_replace('/\s+/u', ' ', $this->shownText($control)));
            if ($shown === '') {
                continue; // an icon button: the label is its only name
            }
            // WCAG 2.5.3: someone using voice control says the words on the button.
            $this->assertStringContainsString(mb_strtolower($shown), mb_strtolower($control->getAttribute('aria-label')), "{$page}: {$control->outerHTML}");
            $checked++;
        }
        $this->assertGreaterThan(0, $checked);
    }

    #[Test]
    #[DataProvider('pages')]
    public function req_qlt_02_aria_label_is_only_used_where_it_names_something(string $page): void
    {
        $document = HTMLDocument::createFromString($this->get($page)->assertOk()->getContent(), LIBXML_NOERROR);

        foreach ($document->querySelectorAll('div[aria-label], span[aria-label], p[aria-label]') as $element) {
            $this->assertTrue($element->hasAttribute('role'), "{$page}: a plain {$element->localName} cannot carry a name: {$element->outerHTML}");
        }
    }

    #[Test]
    public function req_ux_38_bag_views_have_grey_stand_ins_while_they_load(): void
    {
        $this->get('/cart')->assertSee('<template id="bag-line-template">', false);

        $this->assertStringContainsString('.ds-placeholder {', file_get_contents(resource_path('css/components/feedback.css')));
        $this->assertStringContainsString('export const PLACEHOLDER_AFTER_MS = 300;', file_get_contents(resource_path('js/store/bag.js')));
    }

    /** The text a sighted visitor sees inside an element: no hidden parts, icons or templates. */
    private function shownText(Element $element): string
    {
        $text = '';
        foreach ($element->childNodes as $child) {
            if ($child instanceof Element) {
                if ($child->hasAttribute('hidden') || in_array($child->localName, ['svg', 'template', 'img'], true) || str_contains($child->getAttribute('class') ?? '', 'ds-visually-hidden')) {
                    continue;
                }
                $text .= ' '.$this->shownText($child);
            } else {
                $text .= $child->textContent;
            }
        }

        return $text;
    }
}

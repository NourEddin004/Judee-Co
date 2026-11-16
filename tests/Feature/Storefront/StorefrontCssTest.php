<?php

namespace Tests\Feature\Storefront;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The "CSS review" checks of the SRS, run over the storefront's stylesheets. What a browser has to
 * measure (contrast, sideways scrolling, sizes on screen) is in docs/m4-review-notes.md.
 */
class StorefrontCssTest extends TestCase
{
    /** Display titles that keep the demo's tighter line height (Appendix D.3, D.5.12, D.6). */
    private const DISPLAY_TITLES = ['.ds-hero__headline', '.ds-page-heading__title', '.ds-collection-intro__title', '.ds-confirmation__title', '.ds-contact__title'];

    /** Single-line marks that sit on line height 1: the logo's words and the flag emojis. */
    private const ONE_LINE_MARKS = ['.ds-brand__name', '.ds-brand__subtitle', '.ds-phone__flag', 'span.ds-testimonial__flag'];

    /** Only the hero rack moves for longer than 300 ms or with its own easing (REQ-UX-31, 32). */
    private const RACK = ['.ds-rack', '.ds-garment'];

    #[Test]
    public function req_ux_08_headings_use_line_height_1_2(): void
    {
        $this->assertSame('1.2', $this->token('leading-heading'));
        $this->assertMatchesRegularExpression('/h1, h2, h3, h4, h5, h6 \{\s*line-height: var\(--leading-heading\)/', $this->css('base.css'));

        foreach ($this->rules() as [$file, $selector, $body]) {
            if (preg_match('/line-height:\s*var\(--leading-(tight|none)\)/', $body)) {
                $this->assertContains($selector, [...self::DISPLAY_TITLES, ...self::ONE_LINE_MARKS], "{$file}: {$selector}");
            }
        }
    }

    #[Test]
    public function req_ux_30_31_transitions_last_150_200_or_300_ms_with_the_standard_easing(): void
    {
        $this->assertSame(['.15s', '.2s', '.3s', '.3s'], array_map($this->token(...), ['duration-fast', 'duration-base', 'duration-slow', 'duration-image']));
        $this->assertSame('cubic-bezier(.4, 0, .2, 1)', $this->token('ease-standard'));
        $this->assertSame('var(--ease-standard)', $this->token('ease-out'));

        $checked = 0;
        foreach ($this->rules() as [$file, $selector, $body]) {
            if (! preg_match('/(?<![-\w])transition:\s*([^;]+);/', $body, $match) || $match[1] === 'none' || $this->isRack($selector)) {
                continue;
            }
            foreach (preg_split('/,(?![^(]*\))/', $match[1]) as $part) {
                $this->assertMatchesRegularExpression('/^\s*[a-z-]+ var\(--duration-(fast|base|slow|image)\) var\(--ease-(standard|out)\)\s*$/', $part, "{$file}: {$selector}");
                $checked++;
            }
        }
        $this->assertGreaterThan(20, $checked);
    }

    #[Test]
    public function req_ux_32_34_only_the_rack_animates_longer_and_animations_move_or_fade_only(): void
    {
        foreach ($this->rules() as [$file, $selector, $body]) {
            if (preg_match('/(?<![-\w])animation:\s*([^;]+);/', $body, $match) && $match[1] !== 'none' && ! str_starts_with($match[1], 'none')) {
                $this->assertTrue($this->isRack($selector) || str_contains($match[1], 'var(--duration-slow)'), "{$file}: {$selector} runs {$match[1]}");
            }
        }

        $all = implode("\n", array_map($this->css(...), ['components/button.css', 'components/home.css', 'tokens.css']));
        preg_match_all('/@keyframes\s+([\w-]+)\s*\{((?:[^{}]*\{[^{}]*\})*)[^{}]*\}/', $all, $keyframes, PREG_SET_ORDER);
        $this->assertNotEmpty($keyframes);
        foreach ($keyframes as [, $name, $steps]) {
            preg_match_all('/([a-z-]+)\s*:/', $steps, $properties);
            $this->assertEmpty(array_diff($properties[1], ['transform', 'translate', 'rotate', 'scale', 'opacity']), "@keyframes {$name}");
        }
    }

    #[Test]
    public function req_dmo_08_the_shop_has_no_sort_menu_and_hides_its_toolbar_only_when_it_is_empty(): void
    {
        $this->assertStringNotContainsString('ds-sort', file_get_contents(resource_path('views/storefront/shop.blade.php')));
        $this->assertStringNotContainsString('--size-sort', $this->css('tokens.css'));

        foreach ($this->rules() as [$file, $selector, $body]) {
            $this->assertDoesNotMatchRegularExpression('/\.ds-sort\b/', $selector, "{$file}: {$selector}");
            // From 1024 px the Filters button is hidden, so the row hides unless a search chip shows.
            if (preg_match('/\.ds-results__meta\b(?![_-])/', $selector) && str_contains($body, 'display: none')) {
                $this->assertSame('.ds-results__meta:not(:has(.ds-results__chip))', $selector, $file);
            }
        }
    }

    #[Test]
    public function req_dmo_01_the_parts_the_m4_check_found_use_the_appendix_d_values(): void
    {
        $expected = [
            ['home.css', '.ds-hero', 'padding-top: calc(var(--spacing-header)  + var(--space-32))'], // D.6.1, from 769 px
            ['card.css', '.ds-card__badge', 'top: var(--space-8)'], // D.5.2, phones
            ['card.css', '.ds-card__body', 'padding: var(--space-12) var(--space-12) var(--space-16)'], // D.5.6, phones
            ['card.css', '.ds-card__body', 'padding: var(--space-16) var(--space-16) var(--space-24)'], // D.5.6, from 769 px
            ['cart.css', '.ds-cart-line--page', 'column-gap: var(--space-24)'], // D.5.9
            ['product.css', '.ds-back + .ds-pdp', 'padding-top: var(--space-48)'], // D.6.3
            ['pages.css', '.ds-prose', 'padding: var(--space-48) 0 var(--space-96)'], // D.5.12, phones
            ['shop.css', '.ds-filters__section', 'padding: var(--space-16) 0 var(--space-24)'], // D.5.7: Size section 24 px under the colors
        ];
        foreach ($expected as [$file, $selector, $declaration]) {
            $this->assertNotEmpty(array_filter($this->rules(), fn (array $rule) => $rule[0] === $file && $rule[1] === $selector && str_contains($rule[2], $declaration)), "{$file}: {$selector} { {$declaration} }");
        }

        foreach ($this->rules() as [$file, $selector, $body]) {
            if ($selector === '.ds-faq__question' && str_contains($body, 'min-height')) {
                $this->assertStringContainsString('min-height: calc(var(--spacing) * 16)', $body, 'D.5.12: 64 px on every screen');
            }
        }
        $this->assertStringNotContainsString('ds-icon--lg', file_get_contents(resource_path('views/storefront/contact.blade.php')), 'D.5.12: contact card icons are 19 px');
    }

    /** @return list<array{string, string, string}> file, selector, declarations of every innermost rule */
    private function rules(): array
    {
        $rules = [];
        foreach ([...glob(resource_path('css/components/*.css')), resource_path('css/base.css')] as $path) {
            preg_match_all('/([^{};]+)\{([^{}]*)\}/', $this->css(substr($path, strlen(resource_path('css/')))), $matches, PREG_SET_ORDER);
            foreach ($matches as [, $selector, $body]) {
                $rules[] = [basename($path), trim($selector), $body];
            }
        }

        return $rules;
    }

    private function css(string $file): string
    {
        return preg_replace('#/\*.*?\*/#s', '', file_get_contents(resource_path("css/{$file}")));
    }

    private function token(string $name): string
    {
        preg_match('/--'.preg_quote($name, '/').':\s*([^;]+);/', $this->css('tokens.css'), $match);

        return trim($match[1] ?? '');
    }

    private function isRack(string $selector): bool
    {
        return array_any(self::RACK, fn (string $prefix): bool => str_contains($selector, $prefix));
    }
}

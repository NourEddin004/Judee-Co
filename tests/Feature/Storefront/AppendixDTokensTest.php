<?php

namespace Tests\Feature\Storefront;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The design tokens against the tables of SRS Appendix D.2 to D.4 (REQ-DMO-01, 02). How each part
 * uses them (419 values, measured in a browser) is checked by tools/checks/appendix-d.cjs; the results
 * are in docs/m4-review-notes.md.
 */
class AppendixDTokensTest extends TestCase
{
    #[Test]
    public function req_dmo_01_the_colours_are_the_d2_tokens(): void
    {
        $this->assertTokens([
            'color-white' => '#fff', 'color-surface' => '#fff', 'color-ink' => '#2e1c22', 'color-ink-soft' => '#50393f',
            'color-ink-muted' => '#735e64', 'color-taupe' => '#735e64', 'color-ink-subtle' => '#7c656b', 'color-rose' => '#a24f68',
            'color-rose-strong' => '#874058', 'color-rose-soft' => '#f6d5de', 'color-selection' => '#f6d5de', 'color-plum' => '#4a2633',
            'color-wine' => '#8c4459', 'color-wine-deep' => '#6e3547', 'color-gold' => '#eab2bf', 'color-blush-300' => '#eab2bf',
            'color-nav-underline' => '#eab2bf', 'color-accent' => '#b84d70', 'color-on-brand' => '#fff7f9', 'color-blush-50' => '#fdf6f8',
            'color-blush-100' => '#faebef', 'color-blush-200' => '#f6dde4', 'color-surface-muted' => '#faeff2',
            'color-surface-sunken' => '#f4e0e6', 'color-surface-image' => '#fbf2f4', 'color-glass-badge' => '#ffffffe6',
            'color-glass-edge' => '#fff9', 'color-scrim' => '#4a263373', 'color-line' => '#8c445924', 'color-line-faint' => '#8c445917',
            'color-line-strong' => '#8c445933', 'color-line-bold' => '#8c445942', 'color-line-dashed' => '#e5cbd2',
            'color-line-gold' => '#f2d3db', 'color-hero-text' => '#5e2f3f', 'color-hero-lead' => '#50393fe0',
            'color-rack-hanger' => '#9b6b5c', 'color-scrollbar' => '#8c445947', 'color-swatch-fallback' => '#c8b8bd',
            // The demo's pink focus glow is replaced by the rose-strong outline (REQ-UX-20).
            'color-focus' => '#874058',
        ]);
    }

    #[Test]
    public function req_dmo_01_the_ten_filter_swatches_are_seeded_with_the_d2_shades(): void
    {
        $this->assertTokens([
            'color-shade-black' => '#2b2427', 'color-shade-white' => '#f7f4f2', 'color-shade-gray' => '#8a8d93',
            'color-shade-beige' => '#cdb59a', 'color-shade-brown' => '#6b4430', 'color-shade-green' => '#6f7454',
            'color-shade-blue' => '#2f3e5f', 'color-shade-pink' => '#e3aeb8', 'color-shade-red' => '#7d2335',
            'color-shade-purple' => '#8e7590',
        ]);
    }

    #[Test]
    public function req_dmo_01_the_gradients_are_the_d2_gradients(): void
    {
        $this->assertTokens([
            'gradient-hero' => 'radial-gradient(38% 52% at 70% 55%, #ffffff59 0%, #fff0 100%), linear-gradient(90deg, var(--color-blush-50) 0%, var(--color-blush-100) 45%, var(--color-blush-200) 100%)',
            'gradient-hero-ground' => 'radial-gradient(50% 50% at 50% 50%, #82425838 0%, #82425800 100%)',
            'gradient-rack-rail' => 'linear-gradient(180deg, #f6e3db 0%, #d2a597 40%, #a5715f 100%)',
            'gradient-rack-post' => 'linear-gradient(90deg, #a5715f 0%, #ecd0c5 45%, #b98676 100%)',
            // D.2 has #ffffffa8 to #ffffff75; the owner chose a whiter glass on 24 October 2026 so the
            // header links keep 4.5:1 over photos (REQ-UX-16).
            'gradient-glass' => 'linear-gradient(180deg, #ffffffdb 0%, #ffffffc2 100%)',
        ]);
    }

    #[Test]
    public function req_dmo_01_the_type_sizes_follow_the_d3_size_map(): void
    {
        $px = ['micro' => 11, '3xs' => 11, '2xs' => 11, 'xs' => 11, 'sm' => 12, 'md' => 14, 'base' => 14, 'lg' => 16, 'xl' => 16,
            '2xl' => 16, '3xl' => 20, '4xl' => 20, '5xl' => 24, '6xl' => 30, '7xl' => 30, '8xl' => 36, 'review' => 20, 'review-name' => 20,
            'page' => 30, 'paragraph' => 16, 'input' => 16];
        foreach ($px as $name => $size) {
            $this->assertSame($size, $this->px($this->token("text-{$name}")), "--text-{$name}");
        }
        $this->assertMatchesRegularExpression('/@media \(min-width: 48\.0625rem\) \{\s*:root \{\s*--text-page: 2\.25rem;/', $this->css());

        $this->assertTokens([
            'text-hero-headline' => 'clamp(3rem, 6.4vw, 6rem)', 'text-headline' => 'clamp(2.625rem, 6vw, 4.625rem)',
            'text-poster' => 'clamp(3.5rem, 8vw, 7rem)',
            'font-body' => '"Montserrat", ui-sans-serif, system-ui, sans-serif', 'font-display' => '"Merriweather", Georgia, serif',
            'font-brand' => '"Bodoni Moda", Georgia, serif',
            'tracking-snug' => '-.01em', 'tracking-normal' => '0em', 'tracking-wide' => '.03em', 'tracking-wider' => '.06em',
            'tracking-brand' => '.09em', 'tracking-label' => '.12em', 'tracking-eyebrow' => '.34em',
            'leading-none' => '1', 'leading-tight' => '1.1', 'leading-heading' => '1.2', 'leading-snug' => '1.3',
            'leading-normal' => '1.5', 'leading-relaxed' => '1.5', 'leading-review' => '1.55', 'underline-offset' => '3px',
        ]);
    }

    #[Test]
    public function req_dmo_01_spacing_has_only_the_ten_d41_steps(): void
    {
        preg_match_all('/--space-(\d+):\s*([^;]+);/', $this->css(), $steps, PREG_SET_ORDER);
        $this->assertSame([4, 8, 12, 16, 24, 32, 48, 64, 96, 128], array_map(fn ($step) => (int) $step[1], $steps));
        foreach ($steps as [, $name, $value]) {
            $this->assertSame((int) $name, $this->px($value), "--space-{$name}");
        }
    }

    #[Test]
    public function req_dmo_01_radii_shadows_and_motion_follow_d42_to_d44(): void
    {
        $this->assertTokens([
            'radius-sm' => '.5rem', 'radius-md' => '.5rem', 'radius-lg' => '.5rem', 'radius-xl' => '.75rem', 'radius-2xl' => '.75rem',
            'radius-full' => '9999px', 'radius-flag' => '.125rem',
            'shadow-card' => 'var(--shadow-1)', 'shadow-panel' => 'var(--shadow-1)', 'shadow-image' => 'var(--shadow-1)',
            'shadow-float' => 'var(--shadow-2)', 'shadow-toast' => 'var(--shadow-2)', 'shadow-raised' => 'none',
            'shadow-dialog' => 'var(--shadow-3)', 'shadow-drawer' => 'var(--shadow-3-side)', 'shadow-swatch' => 'none', 'shadow-focus' => 'none',
            'shadow-glass' => 'inset 0 1px 0 #ffffffbf, inset 0 -1px 0 #fff3, 0 8px 28px #2b0a122e', 'shadow-rack' => '0 6px 14px #5e2f3f33',
            'shadow-garment' => 'drop-shadow(0 18px 16px #5e2f3f38)',
            'duration-fast' => '.15s', 'duration-base' => '.2s', 'duration-slow' => '.3s', 'duration-image' => '.3s',
            'ease-standard' => 'cubic-bezier(.4, 0, .2, 1)', 'scale-hover' => '1.035', 'scale-press' => '.96',
            'duration-rack-enter' => '1.1s', 'ease-rack' => 'cubic-bezier(.22, 1, .36, 1)', 'delay-rack-step' => '.13s',
            'duration-rack-sway' => '5.5s', 'rotate-garment-sway' => '1.6deg', 'rotate-garment-enter' => '7deg',
        ]);
    }

    #[Test]
    public function req_dmo_01_layers_blur_and_sizes_follow_d45(): void
    {
        $this->assertTokens([
            'z-sticky' => '40', 'z-float' => '90', 'z-header' => '100', 'z-dropdown' => '110', 'z-modal' => '300', 'z-toast' => '500',
            'z-skip' => '1000', 'border-thin' => '1px', 'border-thick' => '2px', 'blur-xs' => '2px', 'blur-sm' => '4px',
            'blur-glass' => '18px', 'stroke-icon' => '1.7', 'stroke-icon-light' => '1.4', 'stroke-hanger' => '3.6',
            'aspect-portrait' => '3 / 4', 'aspect-review' => '4 / 5', 'aspect-hero-stage' => '4 / 3',
        ]);
        $px = ['size-tap' => 44, 'size-icon-sm' => 15, 'size-icon' => 19, 'size-icon-lg' => 24, 'size-brand-logo' => 32,
            'size-brand-logo-footer' => 44, 'size-thumb' => 76, 'size-search-thumb' => 54, 'size-cart-thumb-w' => 88,
            'size-cart-thumb-h' => 100, 'size-cart-thumb-page-w' => 104, 'size-cart-thumb-page-h' => 128, 'size-swatch' => 16,
            'size-color-swatch' => 32, 'size-trust-flag' => 28, 'size-review-flag' => 18, 'size-confirmation-icon' => 56,
            'size-flow-arrow' => 36, 'spacing-header' => 72, 'space-header-float' => 10, 'size-count' => 20, 'size-dot' => 12];
        foreach ($px as $name => $size) {
            $this->assertSame($size, $this->px($this->token($name)), "--{$name}");
        }
        $this->assertStringContainsString('.ds-icon--xl', file_get_contents(resource_path('css/components/icon.css')));
    }

    private function assertTokens(array $expected): void
    {
        foreach ($expected as $name => $value) {
            $this->assertSame($value, $this->token($name), "--{$name}");
        }
    }

    private function css(): string
    {
        return preg_replace('#/\*.*?\*/#s', '', file_get_contents(resource_path('css/tokens.css')));
    }

    private function token(string $name): string
    {
        $this->assertSame(1, preg_match('/^\s*--'.preg_quote($name, '/').':\s*([^;]+);/m', $this->css(), $match), "--{$name} is missing");

        return trim($match[1]);
    }

    private function px(string $value): int
    {
        return (int) round(str_ends_with($value, 'rem') ? (float) $value * 16 : (float) $value);
    }
}

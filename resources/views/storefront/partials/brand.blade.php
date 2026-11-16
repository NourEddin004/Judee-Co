@inject('settings', 'App\Domain\Settings\StoreSettings')
@inject('content', 'App\Domain\Content\SiteContent')
<span class="ds-brand">
    @if ($logo = $settings->logoUrl())
        <img class="ds-brand__logo" src="{{ $logo }}" srcset="{{ srcset($settings->get('logo')) }}" sizes="10rem" alt="{{ $content->text('brand.full_name') }}">
    @else
        <span class="ds-brand__wordmark"><span class="ds-brand__name">{{ $content->text('brand.name') }}</span></span>
    @endif
</span>

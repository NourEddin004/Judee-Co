@extends('layouts.store', ['page' => 'contact', 'title' => __('store.contact.tab_title')])
@inject('settings', 'App\Domain\Settings\StoreSettings')
@inject('content', 'App\Domain\Content\SiteContent')
@php($international = $settings->get('phone_international'))

@section('main')
    <div class="ds-contact">
        <header class="ds-contact__header">
            <p class="ds-eyebrow">{{ $content->text('contact.kicker') }}</p>
            <h1 class="ds-contact__title">{{ $content->text('contact.title') }}</h1>
            <p class="ds-contact__intro">{{ $content->text('contact.intro') }}</p>
        </header>
        <div class="ds-contact__layout">
            <aside class="ds-contact__card">
                {!! icon('instagram') !!}
                <h2 class="ds-contact__card-title">{{ $content->text('contact.instagram_title') }}</h2>
                <p class="ds-contact__card-text" dir="ltr">{{ $settings->get('instagram_handle') }}</p>
                <a href="{{ $settings->get('instagram_url') }}" target="_blank" rel="noreferrer" class="ds-btn ds-btn--secondary">{{ $content->text('contact.instagram_cta') }}</a>
                {!! icon('phone', 'ds-contact__card-icon') !!}
                <h3 class="ds-contact__card-subtitle">{{ $content->text('contact.phone_title') }}</h3>
                <p class="ds-contact__card-text" dir="ltr">{{ $settings->get('phone_display') }}</p>
                <div class="ds-contact__actions">
                    {{-- Only WhatsApp is a primary button (REQ-UX-23). --}}
                    <a href="https://wa.me/{{ $settings->get('whatsapp_international') }}" target="_blank" rel="noreferrer" class="ds-btn ds-btn--primary"><img class="ds-contact__logo" src="{{ asset('images/whatsapp.svg') }}" alt=""> {{ __('store.whatsapp') }}</a>
                    <a href="tel:+{{ $international }}" class="ds-btn ds-btn--secondary">{!! icon('phone') !!} {{ __('store.call') }}</a>
                </div>
            </aside>
            <div class="ds-faq">
                @foreach ($faqs as $faq)
                    <details class="ds-faq__item">
                        <summary class="ds-faq__question">{{ $faq->t('question') }} {!! icon('plus') !!}</summary>
                        <p class="ds-faq__answer">{{ $faq->t('answer') }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </div>
@endsection

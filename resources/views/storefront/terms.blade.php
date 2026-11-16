@extends('layouts.store', ['page' => 'terms', 'title' => __('store.terms.tab_title')])
@inject('content', 'App\Domain\Content\SiteContent')

@section('main')
    <article class="ds-prose">
        <h1 class="ds-prose__title">{{ $content->text('terms.title') }}</h1>
        <p class="ds-prose__intro">{{ $content->text('terms.intro') }}</p>
        @foreach ($sections as $section)
            <section class="ds-prose__section" id="{{ \Illuminate\Support\Str::slug($section->title_en) }}">
                <h2 class="ds-prose__heading">{{ $section->t('title') }}</h2>
                <p class="ds-prose__body">{{ $section->t('body') }}</p>
            </section>
        @endforeach
    </article>
@endsection

{{-- Switches the language on the same page; the address stays the same (REQ-DMO-84). --}}
@php($toArabic = app()->getLocale() !== 'ar')
<a href="{{ switch_language_url() }}" class="{{ $class }}" lang="{{ $toArabic ? 'ar' : 'en' }}" rel="nofollow" data-language-link>{{ $toArabic ? __('store.arabic') : __('store.english') }}</a>

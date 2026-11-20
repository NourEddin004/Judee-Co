{{-- English / العربية in the admin top bar and on the sign-in page. --}}
@php($toArabic = app()->getLocale() !== 'ar')
<a href="{{ route('filament.admin.language', $toArabic ? 'ar' : 'en') }}"
   class="jude-admin-lang"
   data-language-link
   lang="{{ $toArabic ? 'ar' : 'en' }}"
   hreflang="{{ $toArabic ? 'ar' : 'en' }}">{{ $toArabic ? 'العربية' : 'English' }}</a>

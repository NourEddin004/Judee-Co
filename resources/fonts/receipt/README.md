# Receipt fonts

The PDF receipt (Appendix D.6.12) embeds these fonts, so it looks like the site even where they are
not installed. The PDF library (mPDF) reads only TrueType files, so the site's own `.woff2` files in
`public/fonts` can't be used. All three families are under the SIL Open Font License 1.1, whose text
is in the `LICENSE-*.txt` files next to them.

| File | Made from | Changes |
| --- | --- | --- |
| `Montserrat-Regular.ttf`, `-SemiBold.ttf`, `-Bold.ttf` | `ofl/montserrat/Montserrat[wght].ttf` in [google/fonts](https://github.com/google/fonts), version 9.000 | Fixed at weight 400, 600 and 700; hinting removed; mark filtering sets removed |
| `Merriweather-Light.ttf` | `ofl/merriweather/Merriweather[opsz,wdth,wght].ttf` in google/fonts, version 2.100 | Fixed at weight 300, width 100 and optical size 18; Latin letters only; hinting removed |
| `IBMPlexSansArabic-Regular.ttf`, `-SemiBold.ttf`, `-Bold.ttf` | `ofl/ibmplexsansarabic/` in google/fonts, version 1.101 | None |

The changes were made with fontTools (`instancer` and `pyftsubset`). Mark filtering sets are removed
because mPDF stops with "contains MarkGlyphSets which is not supported" when a font uses them. They
only refine how separate combining accents stack, as in Vietnamese; letters such as é are typed as one
ready-made character and are not affected.

When the store's fonts change, replace these files and the names in `App\Domain\Orders\OrderReceipt::FONTS`.

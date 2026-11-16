<?php

namespace App\Domain\Orders;

use App\Domain\Content\SiteContent;
use App\Domain\Media\ImageStore;
use App\Domain\Settings\StoreSettings;
use App\Models\Order;
use App\Support\DesignTokens;
use GdImage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Traits\Localizable;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * The order's PDF receipt (REQ-CHK-24, Appendix D.6.12): A4, in the language the customer ordered
 * in, Arabic right to left (REQ-CHK-25), set in the store's own fonts and colours.
 */
class OrderReceipt
{
    use Localizable;

    /** The fonts in resources/fonts/receipt, by the names the template uses. */
    private const FONTS = [
        'montserrat' => ['R' => 'Montserrat-Regular.ttf', 'B' => 'Montserrat-Bold.ttf'],
        'montserratsemibold' => ['R' => 'Montserrat-SemiBold.ttf'],
        'merriweatherlight' => ['R' => 'Merriweather-Light.ttf'],
        'plexarabic' => ['R' => 'IBMPlexSansArabic-Regular.ttf', 'B' => 'IBMPlexSansArabic-Bold.ttf'],
        'plexarabicsemibold' => ['R' => 'IBMPlexSansArabic-SemiBold.ttf'],
    ];

    /** The logo copy that is sharp at 12 mm high without making the file heavy. */
    private const LOGO_WIDTH = 800;

    public function __construct(
        private readonly StoreSettings $settings,
        private readonly SiteContent $content,
        private readonly Filesystem $disk,
    ) {}

    /** JUDE-order-K7P2QX.pdf (REQ-DMO-102). */
    public function filename(Order $order): string
    {
        return "JUDE-order-{$order->number}.pdf";
    }

    /** The page the PDF is made from, in the order's language. */
    public function html(Order $order): string
    {
        return $this->withLocale($order->language, fn () => view('receipt.order', [
            'order' => $order,
            'rtl' => $order->language === 'ar',
            'logo' => $this->logoPath() !== null,
            'brand' => $this->content->text('brand.name'),
            'phone' => $this->settings->get('phone_display'),
            'instagram' => $this->settings->get('instagram_handle'),
            'color' => fn (string $token) => $this->solid($token),
        ])->render());
    }

    public function pdf(Order $order): string
    {
        $rtl = $order->language === 'ar';
        $mpdf = new Mpdf($this->config($rtl));

        $this->withLocale($order->language, function () use ($mpdf, $order) {
            $mpdf->SetTitle(__('store.receipt.title').' #'.$order->number);
            $mpdf->SetAuthor($this->content->text('brand.full_name'));
            $mpdf->SetCreator($this->content->text('brand.full_name'));
        });
        if (($logo = $this->logoPng()) !== null) {
            $mpdf->imageVars['logo'] = $logo;
        }
        $mpdf->WriteHTML($this->html($order));

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    /** @return array<string, mixed> */
    private function config(bool $rtl): array
    {
        return [
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            // 16 mm all round; the footer sits 16 mm above the bottom edge, under the content.
            'margin_left' => 16,
            'margin_right' => 16,
            'margin_top' => 16,
            'margin_bottom' => 34,
            'margin_footer' => 16,
            'tempDir' => storage_path('framework/cache/mpdf'),
            'fontDir' => [...(new ConfigVariables)->getDefaults()['fontDir'], resource_path('fonts/receipt')],
            'fontdata' => (new FontVariables)->getDefaults()['fontdata']
                + array_map(fn (array $files) => $files + ['useOTL' => 0xFF], self::FONTS),
            'default_font' => $rtl ? 'plexarabic' : 'montserrat',
            'directionality' => $rtl ? 'rtl' : 'ltr',
            // A name or address typed in another script is set in a font that has its letters.
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'languageToFont' => new ReceiptFonts,
            // The logo is handed over in memory; the receipt reads no file or address of its own.
            'whitelistStreamWrappers' => [],
        ];
    }

    private function logoPath(): ?string
    {
        $logo = $this->settings->get('logo');

        if (! ImageStore::isSafePath($logo)) {
            return null;
        }

        $copy = ImageStore::variantPath($logo, self::LOGO_WIDTH);

        foreach ([$copy, $logo] as $path) {
            if ($this->disk->exists($path)) {
                return $this->disk->path($path);
            }
        }

        return null;
    }

    /** The logo as a PNG, which keeps its see-through background in the PDF; uploads are stored as WebP. */
    private function logoPng(): ?string
    {
        $path = $this->logoPath();
        $image = $path === null ? false : @imagecreatefromstring((string) file_get_contents($path));

        if (! $image instanceof GdImage) {
            return null;
        }

        imagesavealpha($image, true);
        ob_start();
        imagepng($image, null, 9);

        return (string) ob_get_clean();
    }

    /** A colour token as a solid hex colour; see-through lines are mixed with the white page. */
    private function solid(string $token): string
    {
        $hex = ltrim(DesignTokens::color($token), '#');

        if (strlen($hex) === 3) {
            $hex = preg_replace('/(.)/', '$1$1', $hex);
        }

        if (strlen($hex) !== 8) {
            return '#'.substr($hex, 0, 6);
        }

        $alpha = hexdec(substr($hex, 6, 2)) / 255;
        $channels = array_map(fn (string $pair) => (int) round(255 - (255 - hexdec($pair)) * $alpha), str_split(substr($hex, 0, 6), 2));

        return vsprintf('#%02x%02x%02x', $channels);
    }
}

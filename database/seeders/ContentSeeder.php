<?php

namespace Database\Seeders;

use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\PolicySection;
use Illuminate\Database\Seeder;

/**
 * Editable texts (Appendix D.8.1, "editable" rows). English is the demo text word for word
 * (REQ-DMO-85); Arabic is a draft for the owner to approve before launch (REQ-DMO-87).
 */
class ContentSeeder extends Seeder
{
    private const BLOCKS = [
        'brand.name' => ['JUDE', 'JUDE'],
        'brand.full_name' => ['JUDE Boutique', 'JUDE Boutique'],
        'brand.tagline' => ['Elegant. Comfy. Modest.', 'أنيقة. مريحة. محتشمة.'],
        'brand.description' => ['JUDE: comfy, modest and elegant hijabs and clothing sets.', 'JUDE: حجابات وأطقم ملابس مريحة ومحتشمة وأنيقة.'],

        'home.hero.title' => ['Elegant. Comfy. Modest.', 'أنيقة. مريحة. محتشمة.'],
        'home.hero.body' => ['Hijabs and clothing sets made to feel as good as they look.', 'حجابات وأطقم ملابس مريحة بقدر ما هي جميلة.'],
        'home.hero.cta' => ['Shop now', 'تسوّقي الآن'],
        'home.hero.rack_label' => ['Pieces on the rail', 'قطع على العلّاقة'],

        'home.about.kicker' => ['JUDE', 'JUDE'],
        'home.about.title' => ['About the brand', 'عن العلامة'],
        'home.about.body' => [
            "JUDE brings together modest pieces you'll reach for again and again: soft modal hijabs in prints and classic colors, and matching two-piece sets that look polished without trying. Every piece is chosen to stay comfortable from morning to night, style easily, and feel quietly elegant.",
            'تجمع JUDE قطعًا محتشمة ستعودين إليها مرة بعد مرة: حجابات مودال ناعمة بنقشات وألوان كلاسيكية، وأطقم متناسقة من قطعتين تبدو أنيقة دون عناء. اخترنا كل قطعة لتبقى مريحة من الصباح حتى المساء، وسهلة التنسيق، وأنيقة بهدوء.',
        ],
        'home.about.cta' => ['Our products', 'منتجاتنا'],

        'home.order.title' => ['How to order', 'كيف تطلبين'],
        'home.order.note' => ['Order on our website in four easy steps', 'اطلبي من موقعنا في أربع خطوات سهلة'],
        'home.order.step1_title' => ['Pick your piece', 'اختاري قطعتك'],
        'home.order.step1_text' => ['Browse hijabs, sets and bundles, then choose your color and size.', 'تصفحي الحجابات والأطقم والباقات، ثم اختاري اللون والمقاس.'],
        'home.order.step2_title' => ['Add to bag', 'أضيفي إلى الحقيبة'],
        'home.order.step2_text' => ['Choose how many pieces you want and add them to your bag.', 'اختاري عدد القطع التي تريدينها وأضيفيها إلى حقيبتك.'],
        'home.order.step3_title' => ['Enter your details', 'أدخلي بياناتك'],
        'home.order.step3_text' => ['At checkout, add your name, phone number and delivery address.', 'عند إتمام الطلب، أضيفي اسمك ورقم هاتفك وعنوان التوصيل.'],
        'home.order.step4_title' => ['Pay on delivery', 'ادفعي عند الاستلام'],
        'home.order.step4_text' => ['We confirm your order by phone, and you pay in cash when it arrives.', 'نؤكد طلبك بالهاتف، وتدفعين نقدًا عند وصوله.'],
        'home.order.cta' => ['Start shopping', 'ابدئي التسوق'],

        'home.reviews.title' => ["Our beautiful clients' feedbacks", 'آراء عميلاتنا الجميلات'],

        'product.perk1' => ['Cash on delivery', 'الدفع عند الاستلام'],
        'product.perk2' => ['Delivery in 1–3 days', 'التوصيل خلال 1–3 أيام'],

        'contact.kicker' => ['We are here to help', 'نحن هنا لمساعدتك'],
        'contact.title' => ['Contact + FAQs', 'تواصلي معنا + الأسئلة الشائعة'],
        'contact.intro' => [
            'Reach JUDE on Instagram, WhatsApp or by phone. We are happy to help you pick your piece and size.',
            'تواصلي مع JUDE عبر إنستغرام أو واتساب أو الهاتف. يسعدنا مساعدتك في اختيار قطعتك ومقاسك.',
        ],
        'contact.instagram_title' => ['Instagram DM', 'رسالة على إنستغرام'],
        'contact.instagram_cta' => ['Open Instagram', 'افتحي إنستغرام'],
        'contact.phone_title' => ['Call or WhatsApp', 'اتصلي أو راسلينا على واتساب'],

        'terms.title' => ['Terms and Conditions', 'الشروط والأحكام'],
        'terms.intro' => ['How ordering, delivery and payment work at JUDE.', 'كيف يتم الطلب والتوصيل والدفع في JUDE.'],

        // Search engine texts per page (REQ-CMS-34); empty means the brand description is used.
        'seo.home' => ['', null],
        'seo.shop' => ['', null],
        'seo.contact' => ['', null],
        'seo.terms' => ['', null],
        'seo.cart' => ['', null],
        'seo.checkout' => ['', null],
    ];

    private const FAQS = [
        [
            'Where does JUDE ship?', 'إلى أين تشحن JUDE؟',
            'Place your order right here on the website. We deliver across Jordan and to the Gulf countries: Saudi Arabia, the UAE, Kuwait, Qatar, Bahrain and Oman.',
            'اطلبي من هنا عبر الموقع. نوصّل إلى جميع أنحاء الأردن وإلى دول الخليج: السعودية والإمارات والكويت وقطر والبحرين وعُمان.',
        ],
        [
            'How do I choose a color?', 'كيف أختار اللون؟',
            "Compare the photos of each piece, or message us on WhatsApp and we'll help. Colors can look slightly different on different screens.",
            'قارني صور كل قطعة، أو راسلينا على واتساب وسنساعدك. قد تبدو الألوان مختلفة قليلًا من شاشة لأخرى.',
        ],
    ];

    /** Four sections still hold the demo's instructions; the owner supplies their text (D.6.9). */
    private const TERMS = [
        ['Product presentation', 'عرض المنتجات', 'Explain how product photos and displayed colors may vary from the actual pieces.', null],
        [
            'Orders and payment', 'الطلبات والدفع',
            'Place your order here on the website. We call you to confirm it, and you pay in cash when it arrives. We never ask for card details.',
            'اطلبي من هنا عبر الموقع. نتصل بك لتأكيد الطلب، وتدفعين نقدًا عند وصوله. لا نطلب أبدًا بيانات البطاقة.',
        ],
        [
            'Delivery', 'التوصيل',
            'We deliver across Jordan for 3 JD (1–3 working days), and to Saudi Arabia, the UAE, Kuwait, Qatar, Bahrain and Oman for 25 JD per order.',
            'نوصّل إلى جميع أنحاء الأردن مقابل 3 دنانير (1–3 أيام عمل)، وإلى السعودية والإمارات والكويت وقطر والبحرين وعُمان مقابل 25 دينارًا للطلب.',
        ],
        ['Returns and exchanges', 'الإرجاع والاستبدال', 'Add the owner-approved policy for exchanges, sizing issues, damaged items and order errors.', null],
        ['Fabric and care', 'القماش والعناية', 'Add the fabric and care instructions for each piece.', null],
        ['Privacy', 'الخصوصية', 'Add the final data, cookies, account and marketing privacy policy before collecting customer information.', null],
    ];

    public function run(): void
    {
        foreach (self::BLOCKS as $key => [$en, $ar]) {
            ContentBlock::updateOrCreate(['key' => $key], ['value_en' => $en, 'value_ar' => $ar]);
        }

        // The three looks beside the about text: product colours, by id (D.8.1).
        $looks = collect(SeedData::catalog()['looks'])->map(fn (array $look) => SeedData::productColorId($look['product'], $look['color']))->filter()->implode(',');
        ContentBlock::updateOrCreate(['key' => 'home.about.looks'], ['value_en' => $looks, 'value_ar' => null]);

        Faq::query()->delete();
        foreach (self::FAQS as $position => [$question, $questionAr, $answer, $answerAr]) {
            Faq::create([
                'question_en' => $question, 'question_ar' => $questionAr,
                'answer_en' => $answer, 'answer_ar' => $answerAr,
                'position' => $position, 'is_published' => true,
            ]);
        }

        PolicySection::query()->where('page', 'terms')->delete();
        foreach (self::TERMS as $position => [$title, $titleAr, $body, $bodyAr]) {
            PolicySection::create([
                'page' => 'terms', 'title_en' => $title, 'title_ar' => $titleAr,
                'body_en' => $body, 'body_ar' => $bodyAr, 'position' => $position,
            ]);
        }
    }
}

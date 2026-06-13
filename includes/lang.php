<?php
/**
 * includes/lang.php
 * Tiny i18n helper. Default language is Thai.
 *
 * Usage in any view:
 *   echo t('nav.home');                     // "หน้าแรก" / "Home"
 *   echo t('home.cat.count', ['n' => 12]);  // "12 รายการ" / "12 items"
 *
 * Switching language:
 *   add ?lang=th  or  ?lang=en  to any URL  → saved to $_SESSION['lang']
 */

function gz_currentLang(): string
{
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'th'], true)) {
        $_SESSION['lang'] = $_GET['lang'];
    }
    return $_SESSION['lang'] ?? 'th';
}

function t(string $key, array $vars = []): string
{
    static $dict = null;
    if ($dict === null) { $dict = gz_translations(); }
    $lang = gz_currentLang();
    $str = $dict[$lang][$key] ?? $dict['en'][$key] ?? $key;
    foreach ($vars as $k => $v) {
        $str = str_replace('{' . $k . '}', (string)$v, $str);
    }
    return $str;
}

/** Build a URL that switches language while preserving the current query string. */
function gz_langSwitchUrl(string $to): string
{
    $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    $qs   = $_SERVER['QUERY_STRING'] ?? '';
    $qs   = preg_replace('/(^|&)lang=[^&]*/', '', $qs);
    $qs   = ltrim((string)$qs, '&');
    return $path . '?' . ($qs ? $qs . '&' : '') . 'lang=' . $to;
}

function gz_translations(): array
{
    return [
        'en' => [
            // Navigation
            'nav.home'         => 'Home',
            'nav.shop'         => 'Shop',
            'nav.categories'   => 'Categories',
            'nav.deals'        => 'Deals',
            'nav.account'      => 'My Account',
            'nav.login'        => 'Log In',
            'nav.cart'         => 'Cart',
            'nav.admin'        => 'Admin',
            'nav.search'       => 'Search gadgets…',

            // Hero
            'home.hero.eyebrow' => '⚡ New season drops are live',
            'home.hero.title.1' => 'Your World.',
            'home.hero.title.accent' => 'Next-Level',
            'home.hero.title.2' => 'Technology.',
            'home.hero.sub'     => 'Discover handpicked flagship gadgets — smartphones, laptops, audio and more — at prices that move as fast as the tech.',
            'home.hero.shop'    => 'Shop Now',
            'home.hero.deals'   => 'Explore Deals',
            'home.hero.stat.products'  => 'Products',
            'home.hero.stat.customers' => 'Happy Customers',
            'home.hero.stat.rating'    => 'Average Rating',
            'home.hero.badge.title'    => '🔥 Hot Deal Today',
            'home.hero.badge.sub'      => 'Up to 40% Off',

            // Feature strip
            'feat.delivery'        => 'Free Delivery',
            'feat.delivery.sub'    => 'On orders over ৳5,000',
            'feat.returns'         => '7-Day Returns',
            'feat.returns.sub'     => 'Hassle-free refunds',
            'feat.warranty'        => '2-Year Warranty',
            'feat.warranty.sub'    => 'On all gadgets',
            'feat.support'         => '24/7 Support',
            'feat.support.sub'     => 'Always here to help',
            'feat.payment'         => 'Secure Payment',
            'feat.payment.sub'     => 'Encrypted checkout',

            // Sections
            'section.viewall'   => 'View all →',
            'section.category.1' => 'Shop by',
            'section.category.2' => 'Category',
            'section.featured.1' => 'Featured',
            'section.featured.2' => 'Products',
            'section.new.1'     => 'New',
            'section.new.2'     => 'Arrivals',
            'section.test.1'    => 'What Customers',
            'section.test.2'    => 'Say',
            'home.cat.count'    => '{n} items',

            // Deal of the day
            'deal.eyebrow'  => '⚡ Deal of the Day',
            'deal.add'      => 'Add to Cart',
            'deal.shop'     => 'View Shop',
            'deal.hours'    => 'Hours',
            'deal.mins'     => 'Mins',
            'deal.secs'     => 'Secs',
            'deal.perks.1'  => '🚚 Free express delivery',
            'deal.perks.2'  => '🛡️ 2-year warranty',
            'deal.perks.3'  => '↩️ 7-day easy returns',
            'deal.perks.4'  => '📦 In stock — ships today',

            // Newsletter
            'news.title'       => 'Get Exclusive Deals First 🎁',
            'news.sub'         => 'Subscribe and be the first to know about drops, discounts and flash sales.',
            'news.placeholder' => 'Enter your email address',
            'news.btn'         => 'Subscribe',

            // Product card
            'product.add'      => 'Add to Cart',

            // Login
            'auth.welcome'        => 'Welcome Back 👋',
            'auth.welcome.sub'    => 'Log in to your GadgetZone account',
            'auth.email'          => 'Email Address',
            'auth.password'       => 'Password',
            'auth.login'          => 'Log In',
            'auth.signup.text'    => "Don't have an account?",
            'auth.signup.link'    => 'Create one →',
            'auth.demo.label'     => '🎭 Demo Account',
            'auth.demo.placeholder' => '— Select an account to auto-fill —',
            'auth.demo.member'    => '🛍️ Member · demo@gadgetzone.com',
            'auth.demo.admin'     => '🛠️ Super Admin · admin@gadgetzone.com',
            'auth.err.both'       => 'Please enter both email and password.',
            'auth.err.invalid'    => 'Invalid email or password.',

            // Register
            'reg.title'      => 'Create Account 🚀',
            'reg.sub'        => 'Join GadgetZone and start shopping',
            'reg.firstname'  => 'First Name',
            'reg.lastname'   => 'Last Name',
            'reg.confirm'    => 'Confirm Password',
            'reg.btn'        => 'Create Account',
            'reg.login.text' => 'Already have an account?',
            'reg.login.link' => 'Log in →',
            'reg.err.name'   => 'First and last name are required.',
            'reg.err.email'  => 'A valid email address is required.',
            'reg.err.passlen'=> 'Password must be at least 6 characters.',
            'reg.err.match'  => 'Passwords do not match.',
            'reg.err.taken'  => 'An account with this email already exists.',

            // Footer
            'footer.tagline'      => 'Your world. Next-level technology. Premium gadgets delivered to your door.',
            'footer.shop.title'   => 'Shop',
            'footer.shop.all'     => 'All Products',
            'footer.shop.new'     => 'New Arrivals',
            'footer.shop.sale'    => 'On Sale',
            'footer.shop.hot'     => 'Hot Picks',
            'footer.acc.title'    => 'Account',
            'footer.acc.my'       => 'My Account',
            'footer.acc.cart'     => 'Cart',
            'footer.acc.login'    => 'Login',
            'footer.acc.register' => 'Register',
            'footer.support.title'    => 'Support',
            'footer.support.shipping' => 'Shipping & Returns',
            'footer.support.warranty' => 'Warranty',
            'footer.support.privacy'  => 'Privacy Policy',
            'footer.rights'       => 'All rights reserved.',

            // Language switcher
            'lang.switch.to' => 'EN',
        ],
        'th' => [
            // Navigation
            'nav.home'         => 'หน้าแรก',
            'nav.shop'         => 'ร้านค้า',
            'nav.categories'   => 'หมวดหมู่',
            'nav.deals'        => 'ดีลพิเศษ',
            'nav.account'      => 'บัญชีของฉัน',
            'nav.login'        => 'เข้าสู่ระบบ',
            'nav.cart'         => 'ตะกร้า',
            'nav.admin'        => 'ผู้ดูแล',
            'nav.search'       => 'ค้นหาสินค้า…',

            // Hero
            'home.hero.eyebrow' => '⚡ สินค้าซีซั่นใหม่มาแล้ว',
            'home.hero.title.1' => 'โลกของคุณ',
            'home.hero.title.accent' => 'เทคโนโลยี',
            'home.hero.title.2' => 'ระดับสูงสุด',
            'home.hero.sub'     => 'ค้นพบสินค้าไอทีตัวท็อปคัดสรรพิเศษ — สมาร์ทโฟน, โน้ตบุ๊ก, เครื่องเสียง และอีกมากมาย ในราคาที่อัปเดตเร็วทันใจ',
            'home.hero.shop'    => 'ช้อปเลย',
            'home.hero.deals'   => 'ดูดีลพิเศษ',
            'home.hero.stat.products'  => 'สินค้า',
            'home.hero.stat.customers' => 'ลูกค้าพึงพอใจ',
            'home.hero.stat.rating'    => 'คะแนนเฉลี่ย',
            'home.hero.badge.title'    => '🔥 ดีลร้อนวันนี้',
            'home.hero.badge.sub'      => 'ลดสูงสุด 40%',

            // Feature strip
            'feat.delivery'        => 'จัดส่งฟรี',
            'feat.delivery.sub'    => 'สำหรับยอดเกิน ৳5,000',
            'feat.returns'         => 'คืนได้ใน 7 วัน',
            'feat.returns.sub'     => 'คืนเงินง่ายไม่มีเงื่อนไข',
            'feat.warranty'        => 'รับประกัน 2 ปี',
            'feat.warranty.sub'    => 'ทุกชิ้นในร้าน',
            'feat.support'         => 'ซัพพอร์ต 24/7',
            'feat.support.sub'     => 'ช่วยเหลือตลอดเวลา',
            'feat.payment'         => 'ชำระเงินปลอดภัย',
            'feat.payment.sub'     => 'เข้ารหัสทุกขั้นตอน',

            // Sections
            'section.viewall'   => 'ดูทั้งหมด →',
            'section.category.1' => 'ช้อปตาม',
            'section.category.2' => 'หมวดหมู่',
            'section.featured.1' => 'สินค้า',
            'section.featured.2' => 'แนะนำ',
            'section.new.1'     => 'สินค้า',
            'section.new.2'     => 'มาใหม่',
            'section.test.1'    => 'รีวิว',
            'section.test.2'    => 'จากลูกค้า',
            'home.cat.count'    => '{n} รายการ',

            // Deal of the day
            'deal.eyebrow'  => '⚡ ดีลแห่งวัน',
            'deal.add'      => 'เพิ่มลงตะกร้า',
            'deal.shop'     => 'ไปที่ร้าน',
            'deal.hours'    => 'ชั่วโมง',
            'deal.mins'     => 'นาที',
            'deal.secs'     => 'วินาที',
            'deal.perks.1'  => '🚚 จัดส่งด่วนฟรี',
            'deal.perks.2'  => '🛡️ รับประกัน 2 ปี',
            'deal.perks.3'  => '↩️ คืนได้ใน 7 วัน',
            'deal.perks.4'  => '📦 มีของพร้อมส่งวันนี้',

            // Newsletter
            'news.title'       => 'รับโปรโมชั่นพิเศษก่อนใคร 🎁',
            'news.sub'         => 'สมัครรับข่าวสารและเป็นคนแรกที่รู้เกี่ยวกับสินค้าใหม่ ส่วนลด และแฟลชเซล',
            'news.placeholder' => 'กรอกอีเมลของคุณ',
            'news.btn'         => 'สมัคร',

            // Product card
            'product.add'      => 'เพิ่มลงตะกร้า',

            // Login
            'auth.welcome'        => 'ยินดีต้อนรับกลับ 👋',
            'auth.welcome.sub'    => 'เข้าสู่ระบบบัญชี GadgetZone ของคุณ',
            'auth.email'          => 'อีเมล',
            'auth.password'       => 'รหัสผ่าน',
            'auth.login'          => 'เข้าสู่ระบบ',
            'auth.signup.text'    => 'ยังไม่มีบัญชี?',
            'auth.signup.link'    => 'สมัครสมาชิก →',
            'auth.demo.label'     => '🎭 บัญชีตัวอย่าง',
            'auth.demo.placeholder' => '— เลือกบัญชีเพื่อกรอกอัตโนมัติ —',
            'auth.demo.member'    => '🛍️ ผู้ซื้อ · demo@gadgetzone.com',
            'auth.demo.admin'     => '🛠️ ผู้ดูแลระบบ · admin@gadgetzone.com',
            'auth.err.both'       => 'กรุณากรอกอีเมลและรหัสผ่าน',
            'auth.err.invalid'    => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง',

            // Register
            'reg.title'      => 'สมัครสมาชิก 🚀',
            'reg.sub'        => 'สมัครกับ GadgetZone และเริ่มช้อปได้เลย',
            'reg.firstname'  => 'ชื่อ',
            'reg.lastname'   => 'นามสกุล',
            'reg.confirm'    => 'ยืนยันรหัสผ่าน',
            'reg.btn'        => 'สมัครสมาชิก',
            'reg.login.text' => 'มีบัญชีอยู่แล้ว?',
            'reg.login.link' => 'เข้าสู่ระบบ →',
            'reg.err.name'   => 'กรุณากรอกชื่อและนามสกุล',
            'reg.err.email'  => 'กรุณากรอกอีเมลที่ถูกต้อง',
            'reg.err.passlen'=> 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร',
            'reg.err.match'  => 'รหัสผ่านทั้งสองช่องไม่ตรงกัน',
            'reg.err.taken'  => 'อีเมลนี้ถูกใช้สมัครไปแล้ว',

            // Footer
            'footer.tagline'      => 'โลกของคุณ เทคโนโลยีระดับสูงสุด สินค้าไอทีพรีเมียมส่งตรงถึงบ้าน',
            'footer.shop.title'   => 'ร้านค้า',
            'footer.shop.all'     => 'สินค้าทั้งหมด',
            'footer.shop.new'     => 'สินค้ามาใหม่',
            'footer.shop.sale'    => 'สินค้าลดราคา',
            'footer.shop.hot'     => 'สินค้ายอดนิยม',
            'footer.acc.title'    => 'บัญชี',
            'footer.acc.my'       => 'บัญชีของฉัน',
            'footer.acc.cart'     => 'ตะกร้า',
            'footer.acc.login'    => 'เข้าสู่ระบบ',
            'footer.acc.register' => 'สมัครสมาชิก',
            'footer.support.title'    => 'ช่วยเหลือ',
            'footer.support.shipping' => 'จัดส่งและคืนสินค้า',
            'footer.support.warranty' => 'การรับประกัน',
            'footer.support.privacy'  => 'นโยบายความเป็นส่วนตัว',
            'footer.rights'       => 'สงวนลิขสิทธิ์',

            // Language switcher
            'lang.switch.to' => 'TH',
        ],
    ];
}

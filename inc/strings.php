<?php
/**
 * Persian fallbacks for the WooCommerce text visitors see most.
 *
 * The store is Persian only, but WordPress may run in English (e.g. for the admin). When the
 * site language is not Persian, these replace the most visible front-end WooCommerce strings.
 * Installing WooCommerce's Persian language pack (Settings › General › Site language: فارسی)
 * covers everything else.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

if ( 0 !== strpos( get_locale(), 'fa' ) ) {
	add_filter( 'gettext_woocommerce', 'fv_wc_strings', 10, 2 );
	add_filter( 'ngettext_woocommerce', function ( $translation, $single, $plural, $number ) {
		return fv_wc_strings( $translation, 1 === (int) $number ? $single : $plural );
	}, 10, 4 );
	add_filter( 'gettext_with_context_woocommerce', function ( $translation, $text ) {
		return fv_wc_strings( $translation, $text );
	}, 10, 2 );
}

// The privacy notes under checkout and registration are saved as settings in English on install,
// so translation never reaches them. Swap them only while they are still WooCommerce's defaults.
add_filter( 'woocommerce_get_privacy_policy_text', function ( $text, $type ) {
	$defaults = array(
		'checkout'     => array( 'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our [privacy_policy].', 'اطلاعات شما فقط برای انجام سفارش و پشتیبانی استفاده می‌شود؛ جزئیات در [privacy_policy] آمده است.' ),
		'registration' => array( 'Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our [privacy_policy].', 'اطلاعات شما فقط برای مدیریت حساب کاربری و پشتیبانی استفاده می‌شود؛ جزئیات در [privacy_policy] آمده است.' ),
	);
	if ( isset( $defaults[ $type ] ) && trim( $text ) === $defaults[ $type ][0] ) {
		return $defaults[ $type ][1];
	}
	return $text;
}, 10, 2 );

/**
 * @param string $translation Current translation.
 * @param string $text        Original English text.
 */
function fv_wc_strings( $translation, $text ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $translation;
	}
	static $map = null;
	if ( null === $map ) {
		$map = array(
			'Add to cart'                                   => 'افزودن به سبد',
			'Select options'                                => 'انتخاب گزینه‌ها',
			'Read more'                                     => 'مشاهده',
			'View cart'                                     => 'مشاهدهٔ سبد',
			'Sale!'                                         => 'حراج',
			'In stock'                                      => 'موجود',
			'%s in stock'                                   => '%s عدد موجود',
			'Out of stock'                                  => 'ناموجود',
			'Available on backorder'                        => 'قابل سفارش',
			'Shop'                                          => 'فروشگاه',
			'Cart'                                          => 'سبد خرید',
			'Checkout'                                      => 'تسویه حساب',
			'My account'                                    => 'حساب کاربری',
			'Subtotal'                                      => 'جمع جزء',
			'Subtotal:'                                     => 'جمع جزء:',
			'Total'                                         => 'جمع کل',
			'Shipping'                                      => 'ارسال',
			'Product'                                       => 'محصول',
			'Price'                                         => 'قیمت',
			'Quantity'                                      => 'تعداد',
			'Proceed to checkout'                           => 'تسویه حساب',
			'Place order'                                   => 'ثبت سفارش',
			'Update cart'                                   => 'به‌روزرسانی سبد',
			'Apply coupon'                                  => 'اعمال کد',
			'Coupon code'                                   => 'کد تخفیف',
			'Cart totals'                                   => 'جمع سبد',
			'Return to shop'                                => 'بازگشت به فروشگاه',
			'Your cart is currently empty.'                 => 'سبد خرید شما خالی است.',
			'No products in the cart.'                      => 'سبدتان خالی است.',
			'Clear'                                         => 'پاک کردن',
			'Choose an option'                              => 'یک گزینه انتخاب کنید',
			'Billing details'                               => 'اطلاعات صورتحساب',
			'Additional information'                        => 'اطلاعات بیشتر',
			'Your order'                                    => 'سفارش شما',
			'Order notes'                                   => 'یادداشت سفارش',
			'Have a coupon?'                                => 'کد تخفیف دارید؟',
			'Click here to enter your code'                 => 'اینجا وارد کنید',
			'Login'                                         => 'ورود',
			'Log in'                                        => 'ورود',
			'Register'                                      => 'ثبت‌نام',
			'Username or email address'                     => 'نام کاربری یا ایمیل',
			'Password'                                      => 'رمز عبور',
			'Remember me'                                   => 'مرا به خاطر بسپار',
			'Lost your password?'                           => 'رمز را فراموش کرده‌اید؟',
			'Email address'                                 => 'ایمیل',
			'Dashboard'                                     => 'پیشخوان',
			'Orders'                                        => 'سفارش‌ها',
			'Downloads'                                     => 'دانلودها',
			'Addresses'                                     => 'نشانی‌ها',
			'Account details'                               => 'جزئیات حساب',
			'Log out'                                       => 'خروج',
			'Reviews'                                       => 'نظرات',
			'There are no reviews yet.'                     => 'هنوز نظری ثبت نشده است.',
			'Your rating'                                   => 'امتیاز شما',
			'Your review'                                   => 'نظر شما',
			'Submit'                                        => 'ارسال',
			'Related products'                              => 'محصولات مرتبط',
			'Description'                                   => 'توضیحات',
			'SKU:'                                          => 'کد کالا:',
			'Category:'                                     => 'دسته:',
			'Categories:'                                   => 'دسته‌ها:',
			'Search results: &ldquo;%s&rdquo;'              => 'نتیجهٔ جستجو برای «%s»',
			'No products were found matching your selection.' => 'محصولی با این انتخاب پیدا نشد.',
			'Showing all %d results'                        => 'نمایش همهٔ %d نتیجه',
			'Home'                                          => 'خانه',
			'Thank you. Your order has been received.'      => 'متشکریم؛ سفارش شما ثبت شد.',
			'Order number:'                                 => 'شمارهٔ سفارش:',
			'Date:'                                         => 'تاریخ:',
			'Total:'                                        => 'جمع کل:',
			'Payment method:'                               => 'روش پرداخت:',
			'Remove this item'                              => 'حذف این کالا',
			'&ldquo;%s&rdquo; has been added to your cart.' => '«%s» به سبد اضافه شد.',
			'%s has been added to your cart.'               => '%s به سبد اضافه شد.',
			'Free shipping'                                 => 'ارسال رایگان',
			'Flat rate'                                     => 'هزینهٔ ثابت',
			'Local pickup'                                  => 'تحویل حضوری',
			'(optional)'                                    => '(اختیاری)',
			'required'                                      => 'الزامی',
			'optional'                                      => 'اختیاری',
			'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our %s.' => 'اطلاعات شما فقط برای انجام سفارش و پشتیبانی استفاده می‌شود؛ جزئیات در %s آمده است.',
			'Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our %s.' => 'اطلاعات شما فقط برای مدیریت حساب کاربری و پشتیبانی استفاده می‌شود؛ جزئیات در %s آمده است.',
			'privacy policy'                                => 'سیاست حفظ حریم خصوصی',
			'Notes about your order, e.g. special notes for delivery.' => 'مثلاً ساعت تحویل یا توضیح برای باربری',
			'A link to set a new password will be sent to your email address.' => 'پیوند ساختن رمز عبور به ایمیل شما فرستاده می‌شود.',
			'Your cart is currently empty!'                 => 'سبد خرید شما خالی است.',
			'New in store'                                  => 'تازه‌ها',
			'Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.' => 'نام کاربری یا ایمیل خود را وارد کنید تا پیوند ساختن رمز تازه برایتان فرستاده شود.',
			'Reset password'                                => 'بازیابی رمز عبور',
			'Username or email'                             => 'نام کاربری یا ایمیل',
			'View'                                          => 'مشاهده',
			'Pay'                                           => 'پرداخت',
			'Cancel'                                        => 'لغو',
			'Order'                                         => 'سفارش',
			'Date'                                          => 'تاریخ',
			'Status'                                        => 'وضعیت',
			'Actions'                                       => 'عملیات',
			'Pending payment'                               => 'در انتظار پرداخت',
			'Processing'                                    => 'در حال انجام',
			'On hold'                                       => 'در انتظار بررسی',
			'Completed'                                     => 'تکمیل‌شده',
			'Cancelled'                                     => 'لغوشده',
			'Refunded'                                      => 'مسترد شده',
			'Failed'                                        => 'ناموفق',
			'%1$s for %2$s item'                            => '%1$s برای %2$s قلم',
			'%1$s for %2$s items'                           => '%1$s برای %2$s قلم',
			'%s is a required field.'                       => '%s را وارد کنید.',
			'Billing %s'                                    => '%s',
			'Shipping %s'                                   => '%s (ارسال)',
			'Order details'                                 => 'جزئیات سفارش',
			'Billing address'                               => 'نشانی صورتحساب',
			'Shipping address'                              => 'نشانی ارسال',
			'Order again'                                   => 'سفارش مجدد',
			'No order has been made yet.'                   => 'هنوز سفارشی ثبت نکرده‌اید.',
			'Browse products'                               => 'دیدن محصولات',
			'Save changes'                                  => 'ذخیرهٔ تغییرات',
			'Save address'                                  => 'ذخیرهٔ نشانی',
			'First name'                                    => 'نام',
			'Last name'                                     => 'نام خانوادگی',
			'Display name'                                  => 'نام نمایشی',
			'Password change'                               => 'تغییر رمز عبور',
			'(customer review)'                             => '(نظر مشتری)',
			'Search'                                        => 'جستجو',
			'Returning customer?'                           => 'قبلاً خرید کرده‌اید؟',
			'Click here to login'                           => 'وارد شوید',
			'Shipping to %s.'                               => 'ارسال به %s.',
			'Change address'                                => 'تغییر نشانی',
			'Calculate shipping'                            => 'محاسبهٔ هزینهٔ ارسال',
			'Iran'                                          => 'ایران',
			'Ship to a different address?'                  => 'ارسال به نشانی دیگر؟',
			'Create an account?'                            => 'حساب کاربری ساخته شود؟',
		);
	}
	return $map[ $text ] ?? $translation;
}

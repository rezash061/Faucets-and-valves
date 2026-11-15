<?php
/**
 * Card-to-card payment (WooCommerce "Direct bank transfer" / BACS).
 *
 * In WooCommerce › Settings › Payments › Direct bank transfer, enter for each account:
 *   Account name → card holder, Account number → 16-digit card number, Bank name,
 *   IBAN → شبا (IR...).
 *
 * After the order the customer sees the card, the exact amount (with copy buttons) and a
 * receipt form: tracking number, last 4 card digits, optional photo/PDF. The receipt is
 * saved on the order (note + box on the order screen) and the shop is emailed.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

/**
 * Replace WooCommerce's own bank details block on the thank-you page with ours.
 */
add_action( 'woocommerce_thankyou_bacs', function () {
	$gateways = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();
	if ( isset( $gateways['bacs'] ) ) {
		remove_action( 'woocommerce_thankyou_bacs', array( $gateways['bacs'], 'thankyou_page' ) );
	}
}, 1 );
add_action( 'woocommerce_thankyou_bacs', 'fv_card_to_card_box', 5 );

/**
 * Also on My account › view order while the order waits for payment.
 */
add_action( 'woocommerce_view_order', function ( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( $order && 'bacs' === $order->get_payment_method() && $order->has_status( array( 'on-hold', 'pending' ) ) ) {
		fv_card_to_card_box( $order_id );
	}
}, 5 );

/**
 * Bank accounts from the BACS settings.
 *
 * @return array[]
 */
function fv_bacs_accounts() {
	$accounts = get_option( 'woocommerce_bacs_accounts', array() );
	return array_values( array_filter( (array) $accounts, function ( $a ) {
		return ! empty( $a['account_number'] ) || ! empty( $a['iban'] );
	} ) );
}

/**
 * Group digits for display: 6037 9975 1234 5678.
 */
function fv_group_digits( $value, $size = 4 ) {
	$digits = preg_replace( '/\s+/', '', (string) $value );
	return trim( chunk_split( $digits, $size, ' ' ) );
}

/**
 * The card-to-card instructions + receipt form.
 *
 * @param int $order_id Order ID.
 */
function fv_card_to_card_box( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	$accounts = fv_bacs_accounts();
	$receipt  = $order->get_meta( '_fv_receipt' );
	$amount   = wc_format_decimal( $order->get_total(), 0 );
	$cur      = fv_currency_label();
	?>
	<section class="c2c" aria-labelledby="c2c-title">
		<header class="c2c__head">
			<h2 id="c2c-title"><?php esc_html_e( 'پرداخت کارت به کارت', 'faucets-valves' ); ?></h2>
			<p><?php esc_html_e( 'مبلغ دقیق سفارش را به کارت زیر واریز کنید و رسید را همین‌جا ثبت کنید. سفارش پس از بررسی رسید تأیید و ارسال می‌شود.', 'faucets-valves' ); ?></p>
		</header>

		<div class="c2c__amount">
			<span class="c2c__label"><?php esc_html_e( 'مبلغ قابل واریز', 'faucets-valves' ); ?></span>
			<span class="c2c__value"><?php echo esc_html( number_format( round( (float) $amount, wc_get_price_decimals() ), wc_get_price_decimals(), '.', ',' ) ); ?> <small><?php echo esc_html( $cur ); ?></small></span>
			<button type="button" class="c2c__copy" data-copy="<?php echo esc_attr( $amount ); ?>"><?php esc_html_e( 'کپی مبلغ', 'faucets-valves' ); ?></button>
			<span class="c2c__ref">
				<?php
				/* translators: %s: order number */
				echo esc_html( sprintf( __( 'شمارهٔ سفارش: %s', 'faucets-valves' ), fv_fa_digits( $order->get_order_number() ) ) );
				?>
			</span>
		</div>

		<div class="c2c__body">
		<?php if ( $accounts ) : ?>
			<div class="c2c__cards">
				<?php foreach ( $accounts as $a ) : ?>
					<div class="bank-card">
						<span class="bank-card__bank"><?php echo esc_html( $a['bank_name'] ?? '' ); ?></span>
						<?php if ( ! empty( $a['account_number'] ) ) : ?>
							<span class="bank-card__label"><?php esc_html_e( 'شمارهٔ کارت', 'faucets-valves' ); ?></span>
							<span class="bank-card__number" dir="ltr"><?php echo esc_html( fv_group_digits( $a['account_number'] ) ); ?></span>
							<button type="button" class="c2c__copy" data-copy="<?php echo esc_attr( preg_replace( '/\D/', '', $a['account_number'] ) ); ?>"><?php esc_html_e( 'کپی شمارهٔ کارت', 'faucets-valves' ); ?></button>
						<?php endif; ?>
						<?php if ( ! empty( $a['iban'] ) ) : ?>
							<span class="bank-card__label"><?php esc_html_e( 'شمارهٔ شبا', 'faucets-valves' ); ?></span>
							<span class="bank-card__iban" dir="ltr"><?php echo esc_html( fv_group_digits( strtoupper( $a['iban'] ) ) ); ?></span>
							<button type="button" class="c2c__copy" data-copy="<?php echo esc_attr( strtoupper( preg_replace( '/\s+/', '', $a['iban'] ) ) ); ?>"><?php esc_html_e( 'کپی شبا', 'faucets-valves' ); ?></button>
						<?php endif; ?>
						<?php if ( ! empty( $a['account_name'] ) ) : ?>
							<span class="bank-card__holder"><?php echo esc_html( $a['account_name'] ); ?></span>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php elseif ( current_user_can( 'manage_woocommerce' ) ) : ?>
			<p class="c2c__warn"><?php esc_html_e( 'مدیر: شمارهٔ کارت را در ووکامرس › پیکربندی › پرداخت‌ها › انتقال مستقیم بانکی وارد کنید.', 'faucets-valves' ); ?></p>
		<?php endif; ?>

		<?php if ( $receipt ) : ?>
			<div class="c2c__done" role="status">
				<?php echo fv_icon( 'shield' ); // phpcs:ignore ?>
				<div>
					<b><?php esc_html_e( 'رسید شما ثبت شد.', 'faucets-valves' ); ?></b>
					<p>
						<?php
						/* translators: %s: tracking number */
						echo esc_html( sprintf( __( 'شمارهٔ پیگیری: %s — پس از بررسی، سفارش تأیید می‌شود.', 'faucets-valves' ), fv_fa_digits( $receipt['ref'] ) ) );
						?>
					</p>
				</div>
			</div>
		<?php elseif ( $order->has_status( array( 'on-hold', 'pending' ) ) ) : ?>
			<form class="c2c__form" method="post" enctype="multipart/form-data" action="">
				<h3><?php esc_html_e( 'ثبت رسید واریز', 'faucets-valves' ); ?></h3>
				<p class="form-row form-row-first">
					<label for="fv_ref"><?php esc_html_e( 'شمارهٔ پیگیری / مرجع', 'faucets-valves' ); ?> <abbr class="required" title="<?php esc_attr_e( 'الزامی', 'faucets-valves' ); ?>">*</abbr></label>
					<input type="text" id="fv_ref" name="fv_ref" required inputmode="numeric" dir="ltr" autocomplete="off" maxlength="40">
				</p>
				<p class="form-row form-row-last">
					<label for="fv_last4"><?php esc_html_e( '۴ رقم آخر کارت پرداخت‌کننده', 'faucets-valves' ); ?></label>
					<input type="text" id="fv_last4" name="fv_last4" inputmode="numeric" dir="ltr" maxlength="4" autocomplete="off">
				</p>
				<p class="form-row form-row-wide">
					<label for="fv_receipt_file"><?php esc_html_e( 'تصویر رسید (اختیاری؛ JPG، PNG یا PDF تا ۵ مگابایت)', 'faucets-valves' ); ?></label>
					<span class="file-pick">
						<input type="file" id="fv_receipt_file" name="fv_receipt_file" accept=".jpg,.jpeg,.png,.webp,.pdf" class="file-pick__input">
						<span class="file-pick__btn" aria-hidden="true"><?php echo fv_icon( 'document' ); // phpcs:ignore ?><?php esc_html_e( 'انتخاب فایل', 'faucets-valves' ); ?></span>
						<span class="file-pick__name" aria-hidden="true" data-empty="<?php esc_attr_e( 'فایلی انتخاب نشده', 'faucets-valves' ); ?>"><?php esc_html_e( 'فایلی انتخاب نشده', 'faucets-valves' ); ?></span>
					</span>
				</p>
				<input type="hidden" name="fv_order" value="<?php echo esc_attr( $order->get_id() ); ?>">
				<input type="hidden" name="fv_key" value="<?php echo esc_attr( $order->get_order_key() ); ?>">
				<?php wp_nonce_field( 'fv_receipt_' . $order->get_id(), 'fv_receipt_nonce' ); ?>
				<button type="submit" class="pill" name="fv_receipt_submit" value="1"><?php esc_html_e( 'ثبت رسید', 'faucets-valves' ); ?></button>
			</form>
		<?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * Handle the receipt form.
 */
add_action( 'template_redirect', function () {
	if ( empty( $_POST['fv_receipt_submit'] ) || empty( $_POST['fv_order'] ) ) {
		return;
	}
	$order_id = absint( $_POST['fv_order'] );
	if ( ! isset( $_POST['fv_receipt_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['fv_receipt_nonce'] ), 'fv_receipt_' . $order_id ) ) {
		wc_add_notice( __( 'نشست منقضی شده است؛ صفحه را تازه کنید و دوباره تلاش کنید.', 'faucets-valves' ), 'error' );
		return;
	}
	$order = wc_get_order( $order_id );
	$key   = sanitize_text_field( wp_unslash( $_POST['fv_key'] ?? '' ) );
	if ( ! $order || ! hash_equals( $order->get_order_key(), $key ) || 'bacs' !== $order->get_payment_method() ) {
		wc_add_notice( __( 'سفارش پیدا نشد.', 'faucets-valves' ), 'error' );
		return;
	}
	if ( $order->get_meta( '_fv_receipt' ) ) {
		return; // Already submitted.
	}

	$ref   = preg_replace( '/[^0-9A-Za-z\-]/', '', fv_latin_digits( sanitize_text_field( wp_unslash( $_POST['fv_ref'] ?? '' ) ) ) );
	$last4 = preg_replace( '/\D/', '', fv_latin_digits( sanitize_text_field( wp_unslash( $_POST['fv_last4'] ?? '' ) ) ) );
	if ( strlen( $ref ) < 4 ) {
		wc_add_notice( __( 'شمارهٔ پیگیری را کامل وارد کنید.', 'faucets-valves' ), 'error' );
		return;
	}

	$file_url = '';
	if ( ! empty( $_FILES['fv_receipt_file']['name'] ) ) {
		$file = $_FILES['fv_receipt_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( $file['size'] > 5 * MB_IN_BYTES ) {
			wc_add_notice( __( 'حجم فایل رسید بیشتر از ۵ مگابایت است.', 'faucets-valves' ), 'error' );
			return;
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$to_receipts = function ( $dirs ) {
			$dirs['subdir'] = '/fv-receipts';
			$dirs['path']   = $dirs['basedir'] . $dirs['subdir'];
			$dirs['url']    = $dirs['baseurl'] . $dirs['subdir'];
			return $dirs;
		};
		add_filter( 'upload_dir', $to_receipts );
		$upload = wp_handle_upload( $file, array(
			'test_form'                => false,
			'mimes'                    => array(
				'jpg|jpeg' => 'image/jpeg',
				'png'      => 'image/png',
				'webp'     => 'image/webp',
				'pdf'      => 'application/pdf',
			),
			// Unguessable file name: receipts can carry personal and bank details.
			'unique_filename_callback' => function ( $dir, $name, $ext ) use ( $order_id ) {
				return 'receipt-' . $order_id . '-' . wp_generate_password( 24, false ) . $ext;
			},
		) );
		remove_filter( 'upload_dir', $to_receipts );
		if ( isset( $upload['error'] ) ) {
			wc_add_notice( __( 'فایل رسید پذیرفته نشد. فقط JPG، PNG، WEBP یا PDF.', 'faucets-valves' ), 'error' );
			return;
		}
		$file_url = $upload['url'];
		fv_protect_receipts_dir( dirname( $upload['file'] ) );
	}

	$receipt = array(
		'ref'   => $ref,
		'last4' => $last4,
		'file'  => $file_url,
		'time'  => time(),
	);
	$order->update_meta_data( '_fv_receipt', $receipt );
	/* translators: 1: tracking number 2: last 4 digits */
	$order->add_order_note( sprintf( __( 'رسید کارت به کارت ثبت شد. شمارهٔ پیگیری: %1$s — ۴ رقم آخر کارت: %2$s', 'faucets-valves' ), $ref, $last4 ? $last4 : '—' ) . ( $file_url ? "\n" . $file_url : '' ) );
	$order->save();

	wp_mail(
		get_option( 'admin_email' ),
		/* translators: %s: order number */
		sprintf( __( 'رسید کارت به کارت برای سفارش %s', 'faucets-valves' ), $order->get_order_number() ),
		/* translators: 1: order number 2: tracking 3: amount 4: admin link */
		sprintf( __( "سفارش %1\$s\nشمارهٔ پیگیری: %2\$s\nمبلغ: %3\$s\n\nبررسی: %4\$s", 'faucets-valves' ), $order->get_order_number(), $ref, wp_strip_all_tags( $order->get_formatted_order_total() ), $order->get_edit_order_url() )
	);
	do_action( 'fv_receipt_submitted', $order, $receipt );

	wc_add_notice( __( 'رسید شما ثبت شد. پس از بررسی، سفارش تأیید می‌شود.', 'faucets-valves' ) );
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : $order->get_checkout_order_received_url() );
	exit;
} );

/**
 * Keep the receipts folder out of directory listings.
 */
function fv_protect_receipts_dir( $dir ) {
	if ( ! file_exists( $dir . '/index.php' ) ) {
		file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
}

/**
 * Receipt box on the admin order screen (classic and HPOS).
 */
add_action( 'add_meta_boxes', function () {
	$screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
	add_meta_box( 'fv-receipt', __( 'رسید کارت به کارت', 'faucets-valves' ), function ( $object ) {
		$order   = $object instanceof WP_Post ? wc_get_order( $object->ID ) : $object;
		$receipt = $order ? $order->get_meta( '_fv_receipt' ) : null;
		if ( ! $receipt ) {
			echo '<p>' . esc_html__( 'رسیدی ثبت نشده است.', 'faucets-valves' ) . '</p>';
			return;
		}
		printf( '<p><strong>%s</strong> <code>%s</code></p>', esc_html__( 'شمارهٔ پیگیری:', 'faucets-valves' ), esc_html( $receipt['ref'] ) );
		printf( '<p><strong>%s</strong> %s</p>', esc_html__( '۴ رقم آخر کارت:', 'faucets-valves' ), esc_html( $receipt['last4'] ? $receipt['last4'] : '—' ) );
		printf( '<p><strong>%s</strong> %s</p>', esc_html__( 'زمان ثبت:', 'faucets-valves' ), esc_html( fv_jalali_date( $receipt['time'] ) . ' ' . wp_date( 'H:i', $receipt['time'] ) ) );
		if ( $receipt['file'] ) {
			$is_img = preg_match( '/\.(jpe?g|png|webp)$/i', $receipt['file'] );
			printf( '<p><a href="%1$s" target="_blank" rel="noopener">%2$s</a></p>', esc_url( $receipt['file'] ), $is_img ? '<img src="' . esc_url( $receipt['file'] ) . '" style="max-width:100%;height:auto;border:1px solid #ddd">' : esc_html__( 'مشاهدهٔ فایل رسید', 'faucets-valves' ) );
		}
	}, $screen, 'side', 'high' );
} );

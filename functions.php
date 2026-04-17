<?php
/**
 * DPSG Stamm Theme Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Enqueue child theme styles.
 * For block (FSE) child themes, WordPress loads the parent style automatically.
 * We only need to enqueue the child stylesheet with a cache-busting version.
 */
function dpsg_stamm_enqueue_styles() {
	wp_enqueue_style(
		'dpsg-stamm-style',
		get_stylesheet_directory_uri() . '/style.css',
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'dpsg_stamm_enqueue_styles' );

/**
 * Privacy & Performance Optimization
 * Disable Emojis (stops external request to s.w.org for GDPR compliance)
 */
function dpsg_stamm_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'dpsg_stamm_disable_emojis' );

/**
 * Auto-create Impressum & Datenschutz pages upon theme activation.
 */
function dpsg_stamm_setup_default_pages() {
	$pages_to_create = array(
		'impressum' => array(
			'title'   => 'Impressum',
			'content' => '<!-- wp:paragraph {"style":{"color":{"background":"#ffcccc"}},"fontSize":"large"} --><p class="has-background has-large-font-size" style="background-color:#ffcccc"><strong>ACHTUNG: Dies ist ein Platzhalter-Impressum. Bitte tragen Sie hier unverzüglich die korrekten Daten Ihres DPSG Stammes, Diözese oder e.V. ein.</strong></p><!-- /wp:paragraph --><!-- wp:paragraph --><p>DPSG Stamm Musterstadt<br>Musterstraße 1<br>12345 Musterstadt</p><!-- /wp:paragraph --><!-- wp:paragraph --><p><strong>Vertreten durch:</strong><br>Vorstand: Max Mustermann, Maria Musterfrau</p><!-- /wp:paragraph --><!-- wp:paragraph --><p><strong>Kontakt:</strong><br>Telefon: +49 (0) 123 44 55 66<br>E-Mail: info@dpsg-musterstadt.de</p><!-- /wp:paragraph --><!-- wp:paragraph --><p><strong>Verantwortlich für den Inhalt nach § 55 Abs. 2 RStV:</strong><br>Max Mustermann<br>Musterstraße 1<br>12345 Musterstadt</p><!-- /wp:paragraph -->',
			'status'  => 'draft',
		),
		'datenschutz' => array(
			'title'   => 'Datenschutzerklärung',
			'content' => '<!-- wp:paragraph {"style":{"color":{"background":"#ffcccc"}},"fontSize":"large"} --><p class="has-background has-large-font-size" style="background-color:#ffcccc"><strong>ACHTUNG: Diese Datenschutzerklärung ist ein Muster und muss individuell auf die Funktionen Ihrer Website (z. B. Kontaktformular, Anmeldungen) angepasst werden. Klären Sie dies bestenfalls mit Ihrem e.V. Vorstand oder Datenschutzbeauftragten!</strong></p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Die Nutzung unserer Webseite ist in der Regel ohne Angabe personenbezogener Daten möglich. Soweit auf unseren Seiten personenbezogene Daten (beispielsweise Name, Anschrift oder eMail-Adressen) erhoben werden, erfolgt dies, soweit möglich, stets auf freiwilliger Basis.</p><!-- /wp:paragraph -->',
			'status'  => 'draft',
		),
		'startseite' => array(
			'title'   => 'Startseite',
			'content' => '<!-- wp:pattern {"slug":"dpsg-stamm/front-page"} /-->',
			'status'  => 'publish',
		),
		'unser-stamm' => array(
			'title'   => 'Unser Stamm',
			'content' => '<!-- wp:paragraph --><p>Wir sind der DPSG Stamm Musterstadt. Auf dieser Seite erhaltet ihr alle wichtigen Informationen zu unserer ehrenamtlichen Arbeit, unseren Aktionen und wie ihr euch anmelden könnt!</p><!-- /wp:paragraph -->'
			             . '<!-- wp:spacer {"height":"40px"} --><div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->'
			             . '<!-- wp:pattern {"slug":"dpsg-stamm/leitungsteam"} /-->'
			             . '<!-- wp:spacer {"height":"40px"} --><div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->'
			             . '<!-- wp:pattern {"slug":"dpsg-stamm/termine"} /-->'
			             . '<!-- wp:spacer {"height":"40px"} --><div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->'
			             . '<!-- wp:pattern {"slug":"dpsg-stamm/faq"} /-->'
			             . '<!-- wp:spacer {"height":"40px"} --><div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->'
			             . '<!-- wp:pattern {"slug":"dpsg-stamm/downloads"} /-->'
			             . '<!-- wp:spacer {"height":"40px"} --><div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->'
			             . '<!-- wp:pattern {"slug":"dpsg-stamm/praevention"} /-->',
			'status'  => 'publish',
		),
		'stufen' => array(
			'title'   => 'Die Stufen',
			'content' => '<!-- wp:paragraph --><p>Die DPSG teilt sich in verschiedene Altersstufen auf. Hier könnt ihr erklären, wie das Stufensystem funktioniert.</p><!-- /wp:paragraph -->',
			'status'  => 'publish',
		),
		'gruppenstunden' => array(
			'title'   => 'Gruppenstunden',
			'content' => '<!-- wp:paragraph --><p>Übersicht aller wöchentlichen Gruppenstunden:</p><ul><li>Biber: Montags 16-17 Uhr</li><li>Wölflinge: Dienstags 17-18:30 Uhr</li><li>...</li></ul><!-- /wp:paragraph -->',
			'status'  => 'publish',
		),
		'kontakt' => array(
			'title'   => 'Kontakt & Mitmachen',
			'content' => '<!-- wp:paragraph --><p>Du hast Lust Pfadfinder zu werden? Melde dich bei uns!</p><!-- /wp:paragraph -->',
			'status'  => 'publish',
		),
	);

	$startseite_id = 0;

	foreach ( $pages_to_create as $slug => $page_data ) {
		// Use get_page_by_path() for reliable slug-based duplicate detection.
		$existing = get_page_by_path( $slug, OBJECT, 'page' );

		$page_id = 0;
		if ( ! $existing ) {
			$new_page = array(
				'post_type'    => 'page',
				'post_title'   => $page_data['title'],
				'post_content' => $page_data['content'],
				'post_status'  => $page_data['status'],
				'post_author'  => get_current_user_id(), // Use actual current admin, not hardcoded ID 1.
				'post_name'    => $slug,
				'menu_order'   => isset( $page_data['menu_order'] ) ? $page_data['menu_order'] : 0,
			);
			$page_id = wp_insert_post( $new_page );
		} else {
			$page_id = $existing->ID;
		}

		if ( $slug === 'startseite' && $page_id ) {
			$startseite_id = $page_id;
		}
	}

	// Set Startseite as the official front page.
	if ( $startseite_id ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $startseite_id );
	}

	// Create Example Posts.
	$posts_to_create = array(
		'sommerlager-anmeldung' => array(
			'title'   => 'Anmeldung fürs Sommerlager gestartet!',
			'content' => '<!-- wp:paragraph --><p>Liebe Pfadfinderinnen und Pfadfinder, es ist soweit! Die Anmeldung für unser diesjähriges Sommerlager in Schweden ist online. Sichert euch schnell einen Platz. Wir freuen uns auf ein tolles Lager mit euch!</p><!-- /wp:paragraph -->',
		),
		'stammesversammlung-2026' => array(
			'title'   => 'Ergebnisse der Stammesversammlung',
			'content' => '<!-- wp:paragraph --><p>Am vergangenen Wochenende fand unsere jährliche Stammesversammlung statt. Wir danken allen für ihr zahlreiches Erscheinen. Wir begrüßen außerdem ganz herzlich unseren neuen Vorstand und verabschieden uns von den scheidenden Mitgliedern mit einem kräftigen Gut Pfad!</p><!-- /wp:paragraph -->',
		),
	);

	foreach ( $posts_to_create as $slug => $post_data ) {
		// Use slug-based check for posts too.
		$existing_post = get_page_by_path( $slug, OBJECT, 'post' );

		if ( ! $existing_post ) {
			$new_post = array(
				'post_type'      => 'post',
				'post_title'     => $post_data['title'],
				'post_content'   => $post_data['content'],
				'post_status'    => 'publish',
				'post_author'    => get_current_user_id(),
				'post_name'      => $slug,
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			);
			wp_insert_post( $new_post );
		}
	}
}

/**
 * Handle Theme Activation Setup Options
 */
function dpsg_stamm_on_activation() {
	update_option( 'dpsg_stamm_needs_setup', true );
}
add_action( 'after_switch_theme', 'dpsg_stamm_on_activation' );

function dpsg_stamm_capture_setup_action() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['dpsg_setup'] ) ) {
		return;
	}

	// CSRF protection: verify nonce before processing any setup action.
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'dpsg_setup_action' ) ) {
		wp_die( esc_html__( 'Sicherheitsprüfung fehlgeschlagen. Bitte lade die Seite neu und versuche es erneut.', 'dpsg-stamm' ) );
	}

	if ( $_GET['dpsg_setup'] === 'install' ) {
		dpsg_stamm_setup_default_pages();
		delete_option( 'dpsg_stamm_needs_setup' );
		wp_redirect( admin_url( 'index.php?dpsg_setup_complete=1' ) );
		exit;
	} elseif ( $_GET['dpsg_setup'] === 'skip' ) {
		delete_option( 'dpsg_stamm_needs_setup' );
		wp_redirect( admin_url( 'index.php' ) );
		exit;
	}
}
add_action( 'admin_init', 'dpsg_stamm_capture_setup_action' );

function dpsg_stamm_admin_notice() {
	if ( get_option( 'dpsg_stamm_needs_setup' ) ) {
		// Generate nonce-protected URLs for both actions.
		$install_url = esc_url( wp_nonce_url( admin_url( '?dpsg_setup=install' ), 'dpsg_setup_action' ) );
		$skip_url    = esc_url( wp_nonce_url( admin_url( '?dpsg_setup=skip' ), 'dpsg_setup_action' ) );
		?>
		<div class="notice notice-info" style="border-left-color: #003056;">
			<p><strong>DPSG Stamm Theme aktiviert! ⚜️</strong></p>
			<p>Möchtest du, dass das Theme automatisch Beispiel-Seiten ("Unser Stamm", "Stufen", Vorlagen) und Demo-Beiträge generiert, um dir den Einstieg zu erleichtern?</p>
			<p>
				<a href="<?php echo $install_url; ?>" class="button button-primary" style="background: #e02030; border-color: #bd1423; color: white;">Ja, Beispielinhalte generieren</a>
				<a href="<?php echo $skip_url; ?>" class="button button-secondary">Nein danke, ich starte mit leerem Theme</a>
			</p>
		</div>
		<?php
	}
	if ( isset( $_GET['dpsg_setup_complete'] ) ) {
		?>
		<div class="notice notice-success is-dismissible">
			<p><strong>Beispielinhalte wurden erfolgreich generiert!</strong> Dein Stamm ist nun einsatzbereit.</p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'dpsg_stamm_admin_notice' );

/**
 * Fallback Thumbnail for missing Featured Images.
 * Width/height attributes are set to avoid Cumulative Layout Shift (CLS).
 */
function dpsg_stamm_fallback_thumbnail( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
	if ( empty( $html ) ) {
		$fallback_url = get_stylesheet_directory_uri() . '/assets/images/hero-forest.jpg';
		$html = '<img src="' . esc_url( $fallback_url ) . '" alt="" width="1200" height="675" style="aspect-ratio:16/9;object-fit:cover;" />';
	}
	return $html;
}
add_filter( 'post_thumbnail_html', 'dpsg_stamm_fallback_thumbnail', 10, 5 );

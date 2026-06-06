<?php
/**
 * DPSG Stamm Theme Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// 1. STYLES
// ---------------------------------------------------------------------------

function dpsg_stamm_enqueue_styles() {
	wp_enqueue_style(
		'dpsg-stamm-style',
		get_stylesheet_directory_uri() . '/style.css',
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'dpsg_stamm_enqueue_styles' );

function dpsg_stamm_inject_asset_vars() {
	$logo_url  = esc_url( get_stylesheet_directory_uri() . '/assets/images/logo.svg' );
	$footer_bg = esc_url( get_stylesheet_directory_uri() . '/assets/images/footer_background.jpg' );
	$css = "
		:root {
			--dpsg-logo-url: url('{$logo_url}');
			--dpsg-footer-bg-url: url('{$footer_bg}');
		}
		.dpsg-header-logo img,
		.dpsg-header-logo {
			content: var(--dpsg-logo-url);
		}
		.dpsg-footer-cover {
			background-image: var(--dpsg-footer-bg-url) !important;
			background-size: cover;
			background-position: center;
		}
	";
	wp_add_inline_style( 'dpsg-stamm-style', $css );
}
add_action( 'wp_enqueue_scripts', 'dpsg_stamm_inject_asset_vars', 20 );

// ---------------------------------------------------------------------------
// 2. PRIVACY / PERFORMANCE
// ---------------------------------------------------------------------------

function dpsg_stamm_disable_emojis() {
	remove_action( 'wp_head',             'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles',     'print_emoji_styles' );
	remove_action( 'admin_print_styles',  'print_emoji_styles' );
	remove_filter( 'the_content_feed',    'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss',    'wp_staticize_emoji' );
	remove_filter( 'wp_mail',             'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'dpsg_stamm_disable_emojis' );

// ---------------------------------------------------------------------------
// 3. FALLBACK THUMBNAIL
// ---------------------------------------------------------------------------

function dpsg_stamm_fallback_thumbnail( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
	if ( empty( $html ) ) {
		$fallback_url = get_stylesheet_directory_uri() . '/assets/images/hero-forest.jpg';
		$html = '<img src="' . esc_url( $fallback_url ) . '" alt="" width="1200" height="675" style="aspect-ratio:16/9;object-fit:cover;" />';
	}
	return $html;
}
add_filter( 'post_thumbnail_html', 'dpsg_stamm_fallback_thumbnail', 10, 5 );

// ---------------------------------------------------------------------------
// 4. WRITE NAV REFS INTO TEMPLATE-PART FILES
// ---------------------------------------------------------------------------

/**
 * After setup, write the wp_navigation ref IDs directly into the template-part
 * .html files. This is the only approach that works reliably across all
 * WordPress block-rendering paths (including mobile, caching plugins, REST).
 *
 * Runs once on admin_init after setup, then never again (guarded by a check
 * for whether the file already contains the correct ref).
 */
function dpsg_stamm_write_nav_refs_to_files() {
	$header_id = (int) get_option( 'dpsg_stamm_header_nav_id', 0 );
	$footer_id = (int) get_option( 'dpsg_stamm_footer_nav_id', 0 );

	if ( ! $header_id && ! $footer_id ) {
		return;
	}

	$parts = array(
		'header' => array( 'id' => $header_id, 'file' => get_stylesheet_directory() . '/parts/header.html' ),
		'footer' => array( 'id' => $footer_id, 'file' => get_stylesheet_directory() . '/parts/footer.html' ),
	);

	foreach ( $parts as $part ) {
		$file = $part['file'];
		$id   = $part['id'];

		if ( ! $id || ! file_exists( $file ) || ! is_writable( $file ) ) {
			continue;
		}

		$content = file_get_contents( $file );

		// Already has this exact ref → skip.
		if ( strpos( $content, '"ref":' . $id ) !== false ) {
			continue;
		}

		// Replace the first wp:navigation block (with or without existing attrs)
		// with one that has the correct ref. We use a simple string replacement
		// to avoid regex delimiter issues.
		$new_content = dpsg_stamm_replace_first_nav_block( $content, $id );

		if ( $new_content !== $content ) {
			file_put_contents( $file, $new_content );

			// Clear the block template cache so WordPress picks up the change.
			if ( function_exists( 'wp_cache_delete' ) ) {
				wp_cache_delete( 'template_part_' . basename( $file, '.html' ), 'theme_blocks' );
			}
			// Also clear the full WP object cache for this template part.
			WP_Block_Templates_Registry::get_instance(); // ensure registry is loaded
		}
	}
}
add_action( 'admin_init', 'dpsg_stamm_write_nav_refs_to_files' );

/**
 * Replace the first occurrence of a wp:navigation block in $content with
 * a version that carries the given $ref ID.
 * Uses simple string scanning to avoid regex delimiter/escaping issues.
 *
 * @param  string $content  Raw HTML of the template part.
 * @param  int    $ref      wp_navigation post ID.
 * @return string           Modified content.
 */
function dpsg_stamm_replace_first_nav_block( $content, $ref ) {
	$open  = '<!-- wp:navigation';
	$close = '/-->';

	$start = strpos( $content, $open );
	if ( $start === false ) {
		return $content;
	}

	$end = strpos( $content, $close, $start );
	if ( $end === false ) {
		return $content;
	}

	$end += strlen( $close );

	// Extract the existing block so we can preserve layout/style attrs.
	$old_block = substr( $content, $start, $end - $start );

	// Parse existing attrs JSON (the part between "wp:navigation" and "}")
	$attrs_raw = trim( substr( $old_block, strlen( $open ), strrpos( $old_block, $close ) - strlen( $open ) ) );
	$attrs_raw = trim( $attrs_raw, " \t\n\r/" ); // strip leading/trailing whitespace and self-close slash

	$attrs = array();
	if ( $attrs_raw && $attrs_raw[0] === '{' ) {
		$decoded = json_decode( $attrs_raw, true );
		if ( is_array( $decoded ) ) {
			$attrs = $decoded;
		}
	}

	// Inject/overwrite the ref.
	$attrs['ref'] = $ref;

	$new_block = '<!-- wp:navigation ' . wp_json_encode( $attrs ) . ' /-->';

	return substr( $content, 0, $start ) . $new_block . substr( $content, $end );
}

// ---------------------------------------------------------------------------
// 5. SETUP: PAGES, POSTS, NAVIGATIONS
// ---------------------------------------------------------------------------

function dpsg_stamm_setup_default_pages() {

	$author = get_current_user_id();

	$pages = array(
		'impressum' => array(
			'title'  => 'Impressum',
			'status' => 'draft',
			'content' =>
				'<!-- wp:paragraph {"style":{"color":{"background":"#ffcccc"}},"fontSize":"large"} -->'
				. '<p class="has-background has-large-font-size" style="background-color:#ffcccc">'
				. '<strong>ACHTUNG: Dies ist ein Platzhalter-Impressum. Bitte tragen Sie hier unverzüglich die korrekten Daten Ihres DPSG Stammes, Diözese oder e.V. ein.</strong>'
				. '</p><!-- /wp:paragraph -->'
				. '<!-- wp:paragraph --><p>DPSG Stamm Musterstadt<br>Musterstraße 1<br>12345 Musterstadt</p><!-- /wp:paragraph -->'
				. '<!-- wp:paragraph --><p><strong>Vertreten durch:</strong><br>Vorstand: Max Mustermann, Maria Musterfrau</p><!-- /wp:paragraph -->'
				. '<!-- wp:paragraph --><p><strong>Kontakt:</strong><br>Telefon: +49 (0) 123 44 55 66<br>E-Mail: info@dpsg-musterstadt.de</p><!-- /wp:paragraph -->'
				. '<!-- wp:paragraph --><p><strong>Verantwortlich für den Inhalt nach § 55 Abs. 2 RStV:</strong><br>Max Mustermann<br>Musterstraße 1<br>12345 Musterstadt</p><!-- /wp:paragraph -->',
		),
		'datenschutz' => array(
			'title'  => 'Datenschutzerklärung',
			'status' => 'draft',
			'content' =>
				'<!-- wp:paragraph {"style":{"color":{"background":"#ffcccc"}},"fontSize":"large"} -->'
				. '<p class="has-background has-large-font-size" style="background-color:#ffcccc">'
				. '<strong>ACHTUNG: Diese Datenschutzerklärung ist ein Muster und muss individuell angepasst werden.</strong>'
				. '</p><!-- /wp:paragraph -->'
				. '<!-- wp:paragraph --><p>Die Nutzung unserer Webseite ist in der Regel ohne Angabe personenbezogener Daten möglich.</p><!-- /wp:paragraph -->',
		),
		'startseite' => array(
			'title'  => 'Startseite',
			'status' => 'publish',
			'content' => '<!-- wp:pattern {"slug":"dpsg-stamm/front-page"} /-->',
		),
		'unser-stamm' => array(
			'title'  => 'Unser Stamm',
			'status' => 'publish',
			'content' =>
				'<!-- wp:paragraph --><p>Wir sind der DPSG Stamm Musterstadt.</p><!-- /wp:paragraph -->'
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
		),
		'stufen' => array(
			'title'  => 'Die Stufen',
			'status' => 'publish',
			'content' => '<!-- wp:paragraph --><p>Die DPSG teilt sich in verschiedene Altersstufen auf.</p><!-- /wp:paragraph -->',
		),
		'gruppenstunden' => array(
			'title'  => 'Gruppenstunden',
			'status' => 'publish',
			'content' => '<!-- wp:paragraph --><p>Übersicht aller wöchentlichen Gruppenstunden.</p><!-- /wp:paragraph -->',
		),
		'kontakt' => array(
			'title'  => 'Kontakt & Mitmachen',
			'status' => 'publish',
			'content' => '<!-- wp:paragraph --><p>Du hast Lust Pfadfinder zu werden? Melde dich bei uns!</p><!-- /wp:paragraph -->',
		),
	);

	$page_ids = array();

	foreach ( $pages as $slug => $data ) {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $existing ) {
			$page_ids[ $slug ] = $existing->ID;
		} else {
			$id = wp_insert_post( array(
				'post_type'    => 'page',
				'post_name'    => $slug,
				'post_title'   => $data['title'],
				'post_content' => $data['content'],
				'post_status'  => $data['status'],
				'post_author'  => $author,
			) );
			if ( $id && ! is_wp_error( $id ) ) {
				$page_ids[ $slug ] = $id;
			}
		}
	}

	$startseite_id = $page_ids['startseite'] ?? 0;
	if ( $startseite_id ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $startseite_id );
	}

	// Demo Posts.
	$posts = array(
		'sommerlager-anmeldung'   => 'Anmeldung fürs Sommerlager gestartet!',
		'stammesversammlung-2026' => 'Ergebnisse der Stammesversammlung',
	);
	foreach ( $posts as $slug => $title ) {
		if ( ! get_page_by_path( $slug, OBJECT, 'post' ) ) {
			wp_insert_post( array(
				'post_type'      => 'post',
				'post_name'      => $slug,
				'post_title'     => $title,
				'post_content'   => '<!-- wp:paragraph --><p>Beispielinhalt – bitte ersetzen.</p><!-- /wp:paragraph -->',
				'post_status'    => 'publish',
				'post_author'    => $author,
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			) );
		}
	}

	// Create navigations and store IDs → admin_init will write them to files.
	dpsg_stamm_create_navigations( $page_ids );
}

function dpsg_stamm_page_url( $slug, $page_ids ) {
	return ! empty( $page_ids[ $slug ] )
		? get_permalink( $page_ids[ $slug ] )
		: home_url( '/' . $slug . '/' );
}

function dpsg_stamm_create_navigations( array $page_ids ) {
	$header_content =
		'<!-- wp:navigation-link {"label":"Startseite","url":"'       . esc_url( home_url( '/' ) )                                   . '","kind":"url"} /-->'
		. '<!-- wp:navigation-link {"label":"Unser Stamm","url":"'    . esc_url( dpsg_stamm_page_url( 'unser-stamm',    $page_ids ) ) . '","kind":"url"} /-->'
		. '<!-- wp:navigation-link {"label":"Die Stufen","url":"'     . esc_url( dpsg_stamm_page_url( 'stufen',         $page_ids ) ) . '","kind":"url"} /-->'
		. '<!-- wp:navigation-link {"label":"Gruppenstunden","url":"' . esc_url( dpsg_stamm_page_url( 'gruppenstunden', $page_ids ) ) . '","kind":"url"} /-->'
		. '<!-- wp:navigation-link {"label":"Kontakt","url":"'        . esc_url( dpsg_stamm_page_url( 'kontakt',        $page_ids ) ) . '","kind":"url"} /-->';

	$footer_content =
		'<!-- wp:navigation-link {"label":"Impressum","url":"'     . esc_url( dpsg_stamm_page_url( 'impressum',   $page_ids ) ) . '","kind":"url"} /-->'
		. '<!-- wp:navigation-link {"label":"Datenschutz","url":"' . esc_url( dpsg_stamm_page_url( 'datenschutz', $page_ids ) ) . '","kind":"url"} /-->';

	$header_id = dpsg_stamm_upsert_navigation( 'DPSG Hauptnavigation',   $header_content );
	$footer_id = dpsg_stamm_upsert_navigation( 'DPSG Footer Navigation', $footer_content );

	if ( $header_id ) {
		update_option( 'dpsg_stamm_header_nav_id', $header_id );
	}
	if ( $footer_id ) {
		update_option( 'dpsg_stamm_footer_nav_id', $footer_id );
	}

	// Write refs to files immediately (we're already on admin_init via the
	// setup redirect, so this is safe to call directly too).
	dpsg_stamm_write_nav_refs_to_files();
}

function dpsg_stamm_upsert_navigation( $title, $content ) {
	$existing = get_posts( array(
		'post_type'      => 'wp_navigation',
		'post_status'    => 'publish',
		'title'          => $title,
		'posts_per_page' => 1,
		'fields'         => 'ids',
	) );

	if ( ! empty( $existing ) ) {
		wp_update_post( array( 'ID' => $existing[0], 'post_content' => $content ) );
		return $existing[0];
	}

	$id = wp_insert_post( array(
		'post_type'    => 'wp_navigation',
		'post_title'   => $title,
		'post_content' => $content,
		'post_status'  => 'publish',
	) );

	return ( $id && ! is_wp_error( $id ) ) ? $id : false;
}

// ---------------------------------------------------------------------------
// 6. SETUP FLOW
// ---------------------------------------------------------------------------

function dpsg_stamm_on_activation() {
	update_option( 'dpsg_stamm_needs_setup', true );
}
add_action( 'after_switch_theme', 'dpsg_stamm_on_activation' );

function dpsg_stamm_capture_setup_action() {
	if ( ! current_user_can( 'manage_options' ) || ! isset( $_GET['dpsg_setup'] ) ) {
		return;
	}
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
		$install_url = esc_url( wp_nonce_url( admin_url( '?dpsg_setup=install' ), 'dpsg_setup_action' ) );
		$skip_url    = esc_url( wp_nonce_url( admin_url( '?dpsg_setup=skip' ),    'dpsg_setup_action' ) );
		?>
		<div class="notice notice-info" style="border-left-color:#003056;">
			<p><strong>DPSG Stamm Theme aktiviert! ⚜️</strong></p>
			<p>Möchtest du, dass das Theme automatisch Seiten, Navigationen und Demo-Beiträge anlegt?</p>
			<p>
				<a href="<?php echo $install_url; ?>" class="button button-primary" style="background:#e02030;border-color:#bd1423;color:white;">Ja, Beispielinhalte &amp; Navigationen generieren</a>
				<a href="<?php echo $skip_url; ?>" class="button button-secondary">Nein danke, ich starte leer</a>
			</p>
		</div>
		<?php
	}
	if ( isset( $_GET['dpsg_setup_complete'] ) ) {
		?>
		<div class="notice notice-success is-dismissible">
			<p><strong>Beispielinhalte &amp; Navigationen wurden erfolgreich generiert!</strong> Bitte befülle Impressum und Datenschutzerklärung mit echten Daten, bevor du live gehst.</p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'dpsg_stamm_admin_notice' );

/**
 * Logo: absoluten src-Pfad und korrekten home_url-Link setzen.
 *
 * Das src="assets/images/logo.svg" im HTML-Template wird ohne Korrektur
 * als relativer Pfad zur aktuellen Domain aufgelöst → 404.
 * Dieser Filter ersetzt src und href zur Laufzeit mit den richtigen URLs.
 */
function dpsg_stamm_logo_home_link( $content, $parsed_block ) {
	if (
		$parsed_block['blockName'] !== 'core/image' ||
		empty( $parsed_block['attrs']['className'] ) ||
		strpos( $parsed_block['attrs']['className'], 'dpsg-header-logo' ) === false
	) {
		return $content;
	}

	$logo_url = esc_url( get_stylesheet_directory_uri() . '/assets/images/logo.svg' );
	$home_url = esc_url( home_url( '/' ) );
	$site_name = esc_attr( get_bloginfo( 'name' ) );

	// Fix src: replace any relative or wrong path with absolute theme URL.
	$content = preg_replace( '/ src="[^"]*logo[^"]*"/', ' src="' . $logo_url . '"', $content );

	// Fix href: use home_url() instead of hardcoded "/".
	$content = preg_replace( '/<a href="[^"]*"/', '<a href="' . $home_url . '"', $content );

	// Fix alt text for accessibility.
	$content = preg_replace( '/ alt="[^"]*"/', ' alt="' . $site_name . '"', $content );

	return $content;
}
add_filter( 'render_block', 'dpsg_stamm_logo_home_link', 10, 2 );

/**
 * Favicon: DPSG-Lilie (assets/images/favicon.png) als Site-Icon verwenden,
 * solange kein individuelles Favicon unter Einstellungen → Allgemein gesetzt ist.
 */
function dpsg_stamm_favicon_fallback() {
	if ( ! has_site_icon() ) {
		$favicon_url = esc_url( get_stylesheet_directory_uri() . '/assets/images/favicon.png' );
		echo '<link rel="icon" type="image/png" href="' . $favicon_url . '">' . "\n";
	}
}
add_action( 'wp_head', 'dpsg_stamm_favicon_fallback' );

// ---------------------------------------------------------------------------
// UPDATE-CHECKER (v1.1.0+)
// ---------------------------------------------------------------------------
require_once get_stylesheet_directory() . '/inc/class-dpsg-stamm-update-checker.php';

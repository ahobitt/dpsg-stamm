<?php
/**
 * GitHub-Release Update-Checker für das DPSG Stamm Theme.
 *
 * Funktionsweise:
 *  1. Prüft einmal alle 6 Stunden via GitHub API ob eine neuere Version
 *     unter ahobitt/dpsg-stamm als Release verfügbar ist.
 *  2. Zeigt den WP-Standard-Update-Hinweis unter Design → Themes an.
 *  3. Ermöglicht automatisches 1-Klick-Update direkt aus dem WP-Admin.
 *
 * Voraussetzungen auf GitHub-Seite:
 *  - Release-Tag im Format "v1.1.0" (mit v-Prefix)
 *  - Am Release eine ZIP-Datei angehängt deren Name mit "dpsg-stamm" beginnt
 *    und auf ".zip" endet, z.B. "dpsg-stamm-theme.zip"
 *    (ohne dieses Asset fällt der Checker auf den GitHub-Zipball zurück,
 *    der eine falsche Verzeichnisstruktur hat und nicht als WP-Update erkannt wird)
 *
 * Keine personenbezogenen Daten werden an GitHub übermittelt.
 * GitHub erhält nur eine anonyme GET-Anfrage an die öffentliche API.
 *
 * @package DPSG_Stamm
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DPSG_Stamm_Update_Checker {

	/** GitHub repository: "owner/repo" */
	const REPO = 'ahobitt/dpsg-stamm';

	/** WordPress-Theme-Slug (= Ordnername des Themes) */
	const SLUG = 'dpsg-stamm';

	/** Transient-Key für den gecachten Release-Stand */
	const TRANSIENT = 'dpsg_stamm_github_release';

	/** Cache-Dauer bei erfolgreichem API-Abruf: 6 Stunden */
	const CACHE_TTL = 6 * HOUR_IN_SECONDS;

	/** Cache-Dauer bei fehlgeschlagenem API-Abruf: 30 Minuten */
	const CACHE_TTL_NEGATIVE = 30 * MINUTE_IN_SECONDS;

	/**
	 * Filter und Hooks registrieren.
	 */
	public static function register() {
		add_filter( 'site_transient_update_themes', array( __CLASS__, 'inject_update' ) );
		add_filter( 'themes_api',                   array( __CLASS__, 'themes_api'    ), 10, 3 );
	}

	/**
	 * Update-Information in den WP-Themes-Transient injizieren.
	 * WordPress prüft diesen Transient beim Laden des Theme-Screens
	 * und zeigt bei Bedarf den roten Update-Hinweis an.
	 *
	 * @param  mixed $transient  Aktueller Wert des site_transient_update_themes.
	 * @return mixed             Ggf. modifizierter Transient.
	 */
	public static function inject_update( $transient ) {
		if ( empty( $transient ) || ! is_object( $transient ) ) {
			return $transient;
		}

		$release   = self::get_latest_release();
		$installed = wp_get_theme( self::SLUG )->get( 'Version' );

		if ( ! $release || ! $installed ) {
			return $transient;
		}

		if ( version_compare( $installed, $release['version'], '<' ) ) {
			// Neuere Version verfügbar → Update-Eintrag setzen.
			$transient->response[ self::SLUG ] = array(
				'theme'        => self::SLUG,
				'new_version'  => $release['version'],
				'url'          => $release['html_url'],
				'package'      => $release['zip_url'],
				'requires'     => '6.2',
				'requires_php' => '7.4',
			);
		} else {
			// Aktuell → no_update-Liste pflegen (für korrekte WP-Admin-Anzeige).
			$transient->no_update[ self::SLUG ] = array(
				'theme'       => self::SLUG,
				'new_version' => $installed,
				'url'         => 'https://github.com/' . self::REPO,
				'package'     => '',
			);
		}

		return $transient;
	}

	/**
	 * Details für das "Version-Details"-Modal im WP-Admin liefern.
	 * Öffnet sich wenn der Admin auf "Details anzeigen" klickt.
	 *
	 * @param  mixed  $result  Bisheriges Ergebnis.
	 * @param  string $action  API-Aktion.
	 * @param  object $args    Übergabeparameter inkl. $args->slug.
	 * @return mixed
	 */
	public static function themes_api( $result, $action, $args ) {
		if ( 'theme_information' !== $action ) {
			return $result;
		}
		if ( empty( $args->slug ) || $args->slug !== self::SLUG ) {
			return $result;
		}

		$release = self::get_latest_release();
		if ( ! $release ) {
			return $result;
		}

		return (object) array(
			'name'          => 'DPSG Stamm',
			'slug'          => self::SLUG,
			'version'       => $release['version'],
			'author'        => 'ahobitt',
			'homepage'      => 'https://github.com/' . self::REPO,
			'download_link' => $release['zip_url'],
			'sections'      => array(
				'description' => __( 'Datenschutzkonformes WordPress Block-Theme für DPSG-Stämme.', 'dpsg-stamm' ),
				'changelog'   => ! empty( $release['notes'] )
					? wpautop( esc_html( $release['notes'] ) )
					: '<p>' . esc_html__( 'Keine Release-Notes verfügbar.', 'dpsg-stamm' ) . '</p>',
			),
		);
	}

	/**
	 * Neuesten GitHub-Release abrufen, gecacht via Transient.
	 *
	 * Gibt null zurück wenn:
	 *  - kein Internet / GitHub nicht erreichbar
	 *  - API-Rate-Limit überschritten (60 req/h für anonyme Zugriffe)
	 *  - negativer Cache aktiv (verhindert erneuten Versuch für 30 Min.)
	 *
	 * @return array|null  Assoziatives Array mit 'version', 'html_url',
	 *                     'zip_url', 'notes' – oder null bei Fehler.
	 */
	public static function get_latest_release() {
		$cached = get_transient( self::TRANSIENT );

		// Negativer Cache aktiv → nicht erneut versuchen.
		if ( is_array( $cached ) && ! empty( $cached['_negative'] ) ) {
			return null;
		}

		// Positiver Cache vorhanden → direkt zurückgeben.
		if ( is_array( $cached ) && ! empty( $cached['version'] ) ) {
			return $cached;
		}

		// GitHub API abrufen.
		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::REPO . '/releases/latest',
			array(
				'timeout' => 8,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'dpsg-stamm-theme/' . wp_get_theme( self::SLUG )->get( 'Version' ),
				),
			)
		);

		// Fehler oder kein 200-Status → negativen Cache setzen.
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			set_transient( self::TRANSIENT, array( '_negative' => true ), self::CACHE_TTL_NEGATIVE );
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $data['tag_name'] ) ) {
			set_transient( self::TRANSIENT, array( '_negative' => true ), self::CACHE_TTL_NEGATIVE );
			return null;
		}

		// ZIP-Asset bestimmen: bevorzuge explizites "dpsg-stamm*.zip"-Asset,
		// falle auf GitHub-Zipball zurück wenn keins vorhanden.
		$zip_url = isset( $data['zipball_url'] ) ? $data['zipball_url'] : '';
		if ( ! empty( $data['assets'] ) && is_array( $data['assets'] ) ) {
			foreach ( $data['assets'] as $asset ) {
				if (
					! empty( $asset['name'] ) &&
					stripos( $asset['name'], 'dpsg-stamm' ) === 0 &&
					substr( strtolower( $asset['name'] ), -4 ) === '.zip' &&
					! empty( $asset['browser_download_url'] )
				) {
					$zip_url = $asset['browser_download_url'];
					break;
				}
			}
		}

		$release = array(
			'version'  => ltrim( $data['tag_name'], 'vV' ),
			'html_url' => isset( $data['html_url'] ) ? $data['html_url'] : 'https://github.com/' . self::REPO,
			'zip_url'  => $zip_url,
			'notes'    => isset( $data['body'] ) ? $data['body'] : '',
		);

		set_transient( self::TRANSIENT, $release, self::CACHE_TTL );

		return $release;
	}

	/**
	 * Transient manuell leeren – nützlich nach dem Veröffentlichen eines neuen
	 * Releases, damit WP den Update sofort anzeigt ohne 6h Wartezeit.
	 *
	 * Aufruf per WP-CLI:
	 *   wp eval "DPSG_Stamm_Update_Checker::flush_cache();"
	 *
	 * Oder als URL-Parameter für Admins:
	 *   Aufruf via dpsg_stamm_flush_update_cache() unten.
	 */
	public static function flush_cache() {
		delete_transient( self::TRANSIENT );
	}
}

// Checker registrieren.
DPSG_Stamm_Update_Checker::register();

/**
 * Admin-seitige Cache-Leerung via ?dpsg_flush_update_cache=1 (nur für Admins).
 * Nützlich direkt nach einem neuen Release: einmal aufrufen, dann zeigt
 * WP den Update-Hinweis sofort anstatt bis zu 6h zu warten.
 */
add_action( 'admin_init', function () {
	if (
		isset( $_GET['dpsg_flush_update_cache'] ) &&
		current_user_can( 'manage_options' ) &&
		check_admin_referer( 'dpsg_flush_cache' )
	) {
		DPSG_Stamm_Update_Checker::flush_cache();
		wp_redirect( remove_query_arg( array( 'dpsg_flush_update_cache', '_wpnonce' ) ) );
		exit;
	}
} );

<?php
/**
 * Language Rights Reports → Releases: each region's release status, set by an
 * admin on launch day instead of over SSH.
 *
 * It runs the same code as `wp wt rights status` (Importer::change_region_status),
 * so a repeat is a no-op and every change is logged with who made it. PDFs
 * still reach the server from the command line: this screen publishes the
 * PDFs already staged privately, and warns when a region has none.
 *
 * @package WT\Rights
 */

const WT_RIGHTS_RELEASES_PAGE = 'wt-rights-releases';

/**
 * Who stages report PDFs on the server, named on the Releases screen so that
 * others know whom to ask. Change it here, or with the
 * `wt_rights_release_contact` filter.
 */
const WT_RIGHTS_RELEASE_CONTACT = 'Frederico';

/**
 * The person to ask about release files and PDFs.
 */
function wt_rights_release_contact(): string {
	return (string) apply_filters( 'wt_rights_release_contact', WT_RIGHTS_RELEASE_CONTACT );
}

/**
 * Adds the Releases screen under Language Rights Reports, for admins only.
 */
function wt_rights_releases_menu(): void {
	add_submenu_page(
		'edit.php?post_type=rights_reports',
		'Releases',
		'Releases',
		'manage_options',
		WT_RIGHTS_RELEASES_PAGE,
		'wt_rights_releases_page'
	);
}
add_action( 'admin_menu', 'wt_rights_releases_menu' );

/**
 * What a region holds, for the Releases screen.
 *
 * @param int $term_id A region term.
 * @return array{reports: int, drafts: int, staged: int, published: int}
 */
function wt_rights_region_counts( int $term_id ): array {
	$ids     = get_posts(
		array(
			'post_type'        => 'rights_reports',
			'post_status'      => array( 'publish', 'private', 'draft' ),
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
			'tax_query'        => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- one region, admin only.
				array(
					'taxonomy' => 'rights_region',
					'terms'    => array( $term_id ),
				),
			),
		)
	);
	$private = wt_rights_private_dir();
	$counts  = array(
		'reports'   => count( $ids ),
		'drafts'    => 0,
		'staged'    => 0,
		'published' => 0,
	);
	foreach ( $ids as $id ) {
		$id = (int) $id;
		if ( '1' === get_post_meta( $id, '_rights_draft', true ) ) {
			++$counts['drafts'];
		}
		$file = (string) get_post_meta( $id, '_rights_pdf_file', true );
		if ( '' !== $file && is_string( $private ) && is_readable( $private . '/' . $file ) ) {
			++$counts['staged'];
		}
		if ( (int) get_post_meta( $id, '_rights_pdf_attachment', true ) ) {
			++$counts['published'];
		}
	}
	return $counts;
}

/**
 * What a change does, in the confirmation readers see before it runs.
 *
 * @param string $name    The region's name.
 * @param string $status  The status it would move to.
 * @param int    $reports How many reports it has.
 */
function wt_rights_status_confirmation( string $name, string $status, int $reports ): string {
	$these = sprintf( '%d %s', $reports, 1 === $reports ? 'report' : 'reports' );
	$verb  = 1 === $reports ? 'is' : 'are';
	switch ( $status ) {
		case \WT\Rights\Release_Status::RELEASED:
			return sprintf( 'Release %s now? Its %s %s made public and indexed by search engines, and its staged PDFs are published for download through the gateway.', $name, $these, $verb );
		case \WT\Rights\Release_Status::EMBARGOED:
			return sprintf( 'Embargo %s? Press accounts and admins can read its %s. The public still sees only the release date and the list of nations.', $name, $these );
		default:
			return sprintf( 'Move %s back to upcoming? Its %s %s hidden from the public and press, and its PDFs are deleted from the server.', $name, $these, $verb );
	}
}

/**
 * The button label for a move to a status.
 *
 * @param string $status A release status.
 */
function wt_rights_status_action( string $status ): string {
	$labels = array(
		\WT\Rights\Release_Status::UPCOMING  => 'Back to upcoming',
		\WT\Rights\Release_Status::EMBARGOED => 'Embargo',
		\WT\Rights\Release_Status::RELEASED  => 'Release',
	);
	return $labels[ $status ] ?? $status;
}

/**
 * Renders the Releases screen.
 */
function wt_rights_releases_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have permission to change releases.' );
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- display only: the result of the redirect below.
	$done    = isset( $_GET['wt_rights_done'] ) ? sanitize_key( wp_unslash( $_GET['wt_rights_done'] ) ) : '';
	$region  = isset( $_GET['wt_rights_region'] ) ? sanitize_key( wp_unslash( $_GET['wt_rights_region'] ) ) : '';
	$changed = isset( $_GET['wt_rights_changed'] ) ? (int) $_GET['wt_rights_changed'] : 0;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	$release = get_option( 'wt_rights_release' );
	?>
	<div class="wrap">
		<h1>Releases</h1>
		<p>Each region’s release status sets who can read its reports: upcoming for admins only, embargoed for press and admins, released for everyone. The same change as <code>wp wt rights status</code>; see <em>docs/language-rights.md</em>.</p>
		<?php if ( is_array( $release ) ) : ?>
			<p>Release <strong><?php echo esc_html( (string) $release['release'] ); ?></strong><?php echo ! empty( $release['preview'] ) ? ' (preview)' : ''; ?>, imported <?php echo esc_html( wp_date( 'F j, Y, H:i', (int) strtotime( (string) $release['imported_at'] ) ) ); ?>.</p>
		<?php endif; ?>

		<?php
		$term = '' !== $region ? get_term_by( 'slug', $region, 'rights_region' ) : false;
		if ( $term instanceof WP_Term && in_array( $done, \WT\Rights\Release_Status::all(), true ) ) :
			$counts = wt_rights_region_counts( $term->term_id );
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo esc_html( sprintf( '%s is now %s; %d %s changed visibility.', $term->name, $done, $changed, 1 === $changed ? 'report' : 'reports' ) ); ?></p>
			</div>
			<?php if ( \WT\Rights\Release_Status::UPCOMING !== $done && $counts['staged'] < $counts['reports'] ) : ?>
				<div class="notice notice-warning">
					<p><?php echo esc_html( sprintf( '%d of %d reports have no PDF on the server, so they show no download button. PDFs can’t be added from this screen: ask %s to stage them.', $counts['reports'] - $counts['staged'], $counts['reports'], wt_rights_release_contact() ) ); ?></p>
				</div>
			<?php endif; ?>
		<?php elseif ( 'error' === $done ) : ?>
			<div class="notice notice-error"><p>That change could not be made. Nothing was changed.</p></div>
		<?php endif; ?>

		<div class="notice notice-info inline">
			<p><strong>Report PDFs aren’t added here.</strong> They are printed and copied to the server from the machine that cuts releases, and published by this screen once a region is released. If a region is missing PDFs, ask <?php echo esc_html( wt_rights_release_contact() ); ?> before you release it.</p>
			<details>
				<summary>For whoever stages them</summary>
				<p>Copy the PDFs to the server’s home folder (never <code>public_html</code>), then run <code>wp wt rights import &lt;release file&gt; --execute --pdfs=&lt;dir&gt;</code>. Only embargoed and released regions get PDFs; see <em>docs/language-rights.md</em>.</p>
			</details>
		</div>

		<table class="widefat striped">
			<thead>
				<tr>
					<th scope="col">Region</th>
					<th scope="col">Planned release</th>
					<th scope="col">Status</th>
					<th scope="col">Reports</th>
					<th scope="col">PDFs</th>
					<th scope="col">Last change</th>
					<th scope="col">Change to</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( wt_rights_regions() as $wt_region ) : ?>
					<?php
					$status = wt_rights_region_status( $wt_region->term_id );
					$counts = wt_rights_region_counts( $wt_region->term_id );
					$date   = (string) get_term_meta( $wt_region->term_id, 'release_date', true );
					$log    = json_decode( (string) get_term_meta( $wt_region->term_id, 'status_log', true ), true );
					$last   = is_array( $log ) && $log ? $log[0] : null;
					?>
					<tr>
						<th scope="row"><a href="<?php echo esc_url( (string) get_term_link( $wt_region ) ); ?>"><?php echo esc_html( $wt_region->name ); ?></a></th>
						<td><?php echo esc_html( '' !== $date ? wp_date( 'F j, Y', (int) strtotime( $date . ' 12:00:00' ) ) : '—' ); ?></td>
						<td><strong><?php echo esc_html( ucfirst( $status ) ); ?></strong></td>
						<td>
							<?php echo esc_html( (string) $counts['reports'] ); ?>
							<?php if ( $counts['drafts'] ) : ?>
								<br><small><?php echo esc_html( sprintf( '%d preview %s: stay hidden at any status', $counts['drafts'], 1 === $counts['drafts'] ? 'draft' : 'drafts' ) ); ?></small>
							<?php endif; ?>
						</td>
						<td>
							<?php echo esc_html( sprintf( '%d staged · %d published', $counts['staged'], $counts['published'] ) ); ?>
							<?php if ( \WT\Rights\Release_Status::UPCOMING !== $status && $counts['staged'] < $counts['reports'] - $counts['drafts'] ) : ?>
								<br><small><?php echo esc_html( sprintf( '%d missing: ask %s', $counts['reports'] - $counts['drafts'] - $counts['staged'], wt_rights_release_contact() ) ); ?></small>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( is_array( $last ) ) : ?>
								<?php echo esc_html( sprintf( '%s → %s', ucfirst( (string) $last['from'] ), ucfirst( (string) $last['to'] ) ) ); ?><br>
								<small><?php echo esc_html( sprintf( '%s, %s', (string) $last['by'], wp_date( 'F j, Y, H:i', (int) strtotime( (string) $last['at'] ) ) ) ); ?></small>
							<?php else : ?>
								—
							<?php endif; ?>
						</td>
						<td>
							<?php if ( $counts['reports'] ) : ?>
								<?php foreach ( \WT\Rights\Release_Status::all() as $target ) : ?>
									<?php
									if ( $target === $status ) {
										continue;
									}
									?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline" onsubmit="return window.confirm(this.dataset.confirm);" data-confirm="<?php echo esc_attr( wt_rights_status_confirmation( $wt_region->name, $target, $counts['reports'] ) ); ?>">
										<input type="hidden" name="action" value="wt_rights_set_status">
										<input type="hidden" name="region" value="<?php echo esc_attr( $wt_region->slug ); ?>">
										<input type="hidden" name="status" value="<?php echo esc_attr( $target ); ?>">
										<?php wp_nonce_field( 'wt_rights_set_status_' . $wt_region->slug ); ?>
										<button type="submit" class="button<?php echo \WT\Rights\Release_Status::RELEASED === $target ? ' button-primary' : ''; ?>"><?php echo esc_html( wt_rights_status_action( $target ) ); ?></button>
									</form>
								<?php endforeach; ?>
							<?php else : ?>
								<small>No reports imported yet</small>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description">Nothing is released automatically on the planned date: a person releases each region.</p>
	</div>
	<?php
}

/**
 * Handles a status change from the Releases screen, then returns to it.
 */
function wt_rights_handle_set_status(): void {
	$region = isset( $_POST['region'] ) ? sanitize_key( wp_unslash( $_POST['region'] ) ) : '';
	$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have permission to change releases.', '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'wt_rights_set_status_' . $region );

	$back = admin_url( 'edit.php?post_type=rights_reports&page=' . WT_RIGHTS_RELEASES_PAGE );
	$term = get_term_by( 'slug', $region, 'rights_region' );
	if ( ! $term instanceof WP_Term || ! \WT\Rights\Release_Status::is_valid( $status ) ) {
		wp_safe_redirect( add_query_arg( 'wt_rights_done', 'error', $back ) );
		exit;
	}
	$user   = wp_get_current_user();
	$result = \WT\Rights\Importer::change_region_status( $term->term_id, $status, $user->user_login );
	wp_safe_redirect(
		add_query_arg(
			array(
				'wt_rights_done'    => $status,
				'wt_rights_region'  => $term->slug,
				'wt_rights_changed' => $result['changed'],
			),
			$back
		)
	);
	exit;
}
add_action( 'admin_post_wt_rights_set_status', 'wt_rights_handle_set_status' );

<?php

/**
 * Implement backup command
 *
 * @todo fix
 * @package wp-cli
 * @subpackage commands/third-party
 */
class BackUpCommand extends WP_CLI_Command {

	/**
	 * Generate some posts.
	 *
	 * ## OPTIONS
	 *
	 * [--files_only]
	 * : Backup files only, default to off
	 *
	 * [--database_only]
	 * : Backup database only, defaults to off
	 *
	 * [--path]
	 * : dir that the backup should be save in, defaults to wp-content/backups/
	 *
	 * [--root]
	 * : dir that should be backed up, defaults to site root.
	 *
	 * [--zip_command_path]
	 * : path to your zip binary, standard locations are automatically used
	 *
	 * [--mysqldump_command_path]
	 * : path to your mysqldump binary, standard locations are automatically used
	 *
	 * ## Usage
	 *
	 *     wp backupwordpress backup [--files_only] [--database_only] [--path<dir>] [--root<dir>] [--zip_command_path=<path>] [--mysqldump_command_path=<path>]
	 */
	public function backup( $args, $assoc_args ) {

		// Make sure it's possible to do a backup
		if ( HM_Backup::is_safe_mode_active() )
			WP_CLI::error( sprintf( __( 'BackUpWordPress may not work when php is running with %s on', 'backupwordpress' ), 'safe_mode' ) );

		add_action( 'hmbkp_mysqldump_started', function() {
			WP_CLI::line( __( 'Backup: Dumping database...', 'backupwordpress' ) );
		} );

		add_action( 'hmbkp_archive_started', function() {
			WP_CLI::line( __( 'Backup: Zipping everything up...', 'backupwordpress' ) );
		} );

		// Clean up any mess left by a previous backup
		hmbkp_cleanup();

		$hm_backup = new HM_Backup();

		if ( ! empty( $assoc_args['path'] ) )
			$hm_backup->set_path( $assoc_args['path'] );

		if ( ! empty( $assoc_args['root'] ) )
			$hm_backup->set_root( $assoc_args['root'] );

		if ( ( ! is_dir( $hm_backup->get_path() ) && ( ! is_writable( dirname( $hm_backup->get_path() ) ) || ! wp_mkdir_p( $hm_backup->get_path() ) ) ) || ! is_writable( $hm_backup->get_path() ) ) {
			WP_CLI::error( __( 'Invalid backup path', 'backupwordpress' ) );
			return false;
		}

		if ( ! is_dir( $hm_backup->get_root() ) || ! is_readable( $hm_backup->get_root() ) ) {
			WP_CLI::error( __( 'Invalid root path', 'backupwordpress' ) );
			return false;
		}

		if ( ! empty( $assoc_args['files_only'] ) )
			$hm_backup->set_type( 'file' );

		if ( ! empty( $assoc_args['database_only'] ) )
			$hm_backup->set_type( 'database' );

		if ( isset( $assoc_args['mysqldump_command_path'] ) )
			$hm_backup->set_mysqldump_command_path( $assoc_args['mysqldump_command_path'] );

		if ( isset( $assoc_args['zip_command_path'] ) )
			$hm_backup->set_zip_command_path( $assoc_args['zip_command_path'] );

		if ( ! empty( $assoc_args['excludes'] ) )
			$hm_backup->set_excludes( $assoc_args['excludes'] );

		$hm_backup->backup();

		// Delete any old backup files
	    //hmbkp_delete_old_backups();

    	if ( file_exists( $hm_backup->get_archive_filepath() ) )
			WP_CLI::success( __( 'Backup Complete: ', 'backupwordpress' ) . $hm_backup->get_archive_filepath() );

		else
			WP_CLI::error( __( 'Backup Failed', 'backupwordpress' ) );

	}

}
WP_CLI::add_command( 'backupwordpress', 'BackUpCommand' );

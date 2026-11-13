<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Minimal per-participant listening audit for NHS Clinical Trial 2026 (cached memb_Groups tag 3716).
 * A single insert is made for each qualified play. No heartbeat, WP-Cron or Keap writes.
 */
final class btn_podcasts_clinical {
    const TAG_ID = 3716;
    const GROUPS_META = 'memb_Groups';
    // SQL must filter the comma-separated local Memberium tags before returning users.
    const GROUPS_TAG_REGEX = '(^|,)[[:space:]]*3716[[:space:]]*(,|$)';
    const DB_VERSION = '1';
    const DB_VERSION_KEY = 'btn_podcasts_clinical_db_version';
    const LAST_LOGIN_META = 'btn_podcasts_clinical_last_login_utc';

    public function add_wp_hooks() {
        add_action( 'wp_login', array( $this, 'record_login' ), 999, 2 );
        add_action( 'wp_ajax_btn_podcasts_qualified_listen', array( $this, 'record_listen' ) );
        add_action( 'wp_ajax_btn_podcasts_clinical_batch', array( $this, 'ajax_report_batch' ) );
        add_action( 'admin_menu', array( $this, 'register_report_page' ) );

        // Upgrade on first admin request, not on every page view or during audio playback.
        if ( is_admin() && ! wp_doing_ajax() ) {
            add_action( 'admin_init', array( $this, 'maybe_upgrade' ) );
        }
    }

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'btn_podcasts_clinical_listens';
    }

    public static function create_table() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = self::table_name();
        $collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            podcast_id bigint(20) unsigned NOT NULL,
            session_key varchar(64) NOT NULL,
            listened_at datetime NOT NULL,
            listened_on date NOT NULL,
            duration_seconds int(10) unsigned NOT NULL,
            played_seconds int(10) unsigned NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY one_session (user_id,session_key),
            KEY user_day (user_id,listened_on),
            KEY user_time (user_id,listened_at)
        ) {$collate};";
        dbDelta( $sql );
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
            update_option( self::DB_VERSION_KEY, self::DB_VERSION, false );
        }
    }

    public function maybe_upgrade() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( get_option( self::DB_VERSION_KEY ) !== self::DB_VERSION ) {
            self::create_table();
        }
    }

    /**
     * Match a complete comma-delimited tag ID, never a substring. Memberium's
     * memb_Groups value is a cached WordPress usermeta field, e.g. "183,3716".
     * No Memberium functions, Keap requests or external tag lookups are needed.
     */
    public static function groups_meta_has_tag( $value ) {
        if ( is_array( $value ) ) {
            foreach ( $value as $item ) {
                if ( self::groups_meta_has_tag( $item ) ) {
                    return true;
                }
            }
            return false;
        }
        if ( ! is_scalar( $value ) || is_bool( $value ) ) {
            return false;
        }
        return (bool) preg_match( '/(?:^|,)\s*' . self::TAG_ID . '\s*(?=,|$)/D', (string) $value );
    }

    /** Shared participant gate for clinical playback, logins and admin reports. */
    public static function is_participant( $user_id ) {
        $user_id = absint( $user_id );
        if ( ! $user_id ) {
            return false;
        }
        // Accept multiple memb_Groups rows as well as Memberium's usual CSV value.
        return self::groups_meta_has_tag( get_user_meta( $user_id, self::GROUPS_META, false ) );
    }

    public function record_login( $user_login, $user ) {
        if ( $user instanceof WP_User && self::is_participant( $user->ID ) ) {
            update_user_meta( $user->ID, self::LAST_LOGIN_META, current_time( 'mysql', true ) );
        }
    }

    /**
     * Accepted only after the browser measures at least 50% of the pod's
     * duration as elapsed media play time (seeks excluded). Note: like any
     * client-side audio analytics, this cannot prove a user listened attentively.
     */
    public function record_listen() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Login required.' ), 401 );
        }
        check_ajax_referer( 'btn_podcasts_qualified_listen', 'nonce' );

        $user_id = get_current_user_id();
        $pod_id = isset( $_POST['podcast_id'] ) ? absint( wp_unslash( $_POST['podcast_id'] ) ) : 0;
        $key = isset( $_POST['session_key'] ) ? sanitize_text_field( wp_unslash( $_POST['session_key'] ) ) : '';
        $duration = isset( $_POST['duration'] ) ? absint( wp_unslash( $_POST['duration'] ) ) : 0;
        $played = isset( $_POST['played'] ) ? absint( wp_unslash( $_POST['played'] ) ) : 0;

        if ( ! $pod_id || get_post_type( $pod_id ) !== btn_podcasts()->pods()->get_post_slug()
            || ! preg_match( '/^[a-zA-Z0-9_-]{16,64}$/D', $key )
            || $duration < 5 || $duration > 86400 || $played < ceil( $duration * 0.5 ) || $played > $duration + 2 ) {
            wp_send_json_error( array( 'message' => 'Invalid listening event.' ), 400 );
        }

        // Non-trial users keep their existing streak experience, with NO trial records.
        if ( ! self::is_participant( $user_id ) ) {
            $streak = self::update_streak( $user_id );
            wp_send_json_success( array( 'tracked' => false, 'streak' => $streak ) );
        }

        global $wpdb;
        $table = self::table_name();
        // The event should never cause an unbounded retry or a DB migration on playback.
        $result = $wpdb->query( $wpdb->prepare(
            "INSERT IGNORE INTO {$table} (user_id, podcast_id, session_key, listened_at, listened_on, duration_seconds, played_seconds) VALUES (%d, %d, %s, %s, %s, %d, %d)",
            $user_id, $pod_id, $key, current_time( 'mysql', true ), current_time( 'Y-m-d' ), $duration, $played
        ) );
        if ( false === $result ) {
            wp_send_json_error( array( 'message' => 'Could not save listening event. Check plugin database setup.' ), 500 );
        }
        $streak = null;
        if ( 1 === (int) $result ) {
            $streak = self::update_streak( $user_id );
        }
        wp_send_json_success( array( 'tracked' => true, 'new' => ( 1 === (int) $result ), 'streak' => $streak ) );
    }

    /** Existing player streak metakeys are retained for backwards compatibility. */
    public static function update_streak( $user_id ) {
        $today = current_time( 'Y-m-d' );
        $last_ts = (int) get_user_meta( $user_id, 'podcast_current_streak_date', true );
        // Legacy value is a Unix timestamp from local YYYY/MM/DD, so compare calendar dates.
        $last = $last_ts ? date( 'Y-m-d', $last_ts ) : '';
        $current_raw = (string) get_user_meta( $user_id, 'podcast_current_streak', true );
        $current = max( 0, (int) $current_raw );
        $longest = max( 0, (int) get_user_meta( $user_id, 'memb__ConsecutivedayslistenedtoPods', true ) );
        if ( $last !== $today ) {
            $yesterday = ( new DateTimeImmutable( 'yesterday', wp_timezone() ) )->format( 'Y-m-d' );
            $current = ( $last === $yesterday ) ? $current + 1 : 1;
            update_user_meta( $user_id, 'podcast_current_streak_date', strtotime( str_replace( '-', '/', $today ) ) );
            update_user_meta( $user_id, 'podcast_current_streak', $current . ( $current === 1 ? ' Day' : ' Days' ) );
            update_user_meta( $user_id, 'last_day_listen', str_replace( '-', '/', $today ) );
            if ( $current > $longest ) {
                $longest = $current;
                update_user_meta( $user_id, 'memb__ConsecutivedayslistenedtoPods', $longest );
            }
        }
        return array(
            'current_streak' => $current . ( $current === 1 ? ' Day' : ' Days' ),
            'long_streak' => $longest . ( $longest === 1 ? ' Day' : ' Days' ),
        );
    }

    public function register_report_page() {
        add_management_page(
            'NHS Clinical Trial Pod Report', 'Clinical Trial Pod Report', 'manage_options',
            'btn-podcasts-clinical-report', array( $this, 'render_report_page' )
        );
    }

    public function render_report_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Not allowed.' );
        }
        $today = new DateTimeImmutable( 'today', wp_timezone() );
        $week_start = $today->modify( '-6 days' )->format( 'Y-m-d' );
        $today_string = $today->format( 'Y-m-d' );

        echo '<div class="wrap"><h1>NHS Clinical Trial 2026 – Pod Listening Report</h1>';
        echo '<p>This report <strong>loads automatically</strong> and finds eligible users directly in MySQL using the <code>memb_Groups</code> user meta field and exact tag <strong>3716</strong>, including participants with zero qualifying listens. ';
        echo 'Listening is counted once 50% of a pod has actually played, starting from when tracking was installed.</p>';
        echo '<div style="display:flex;align-items:end;flex-wrap:wrap;gap:12px;margin:16px 0">';
        echo '<div><label for="btn-clinical-preset"><strong>Date range</strong></label><br><select id="btn-clinical-preset">';
        echo '<option value="7">Last 7 days</option><option value="30">Last 30 days</option><option value="all">All time</option><option value="custom">Custom dates</option>';
        echo '</select></div>';
        echo '<div><label for="btn-clinical-from"><strong>From</strong></label><br><input id="btn-clinical-from" type="date" value="' . esc_attr( $week_start ) . '"></div>';
        echo '<div><label for="btn-clinical-to"><strong>To</strong></label><br><input id="btn-clinical-to" type="date" value="' . esc_attr( $today_string ) . '"></div>';
        echo '<button type="button" id="btn-clinical-run" class="button button-primary">Refresh all participants</button>';
        echo '<button type="button" id="btn-clinical-download" class="button" disabled>Download CSV</button>';
        echo '<button type="button" id="btn-clinical-stop" class="button" disabled>Pause</button>';
        echo '<button type="button" id="btn-clinical-resume" class="button" style="display:none">Resume</button>';
        echo '</div>';
        echo '<details style="margin:12px 0"><summary>Optional: report only selected participant emails</summary>';
        echo '<p>Enter emails separated by spaces, commas or new lines. Leave blank to let MySQL automatically find users whose memb_Groups user meta contains the exact tag ID 3716.</p>';
        echo '<textarea id="btn-clinical-emails" rows="4" cols="70" placeholder="participant@example.com"></textarea>';
        echo '<p><button type="button" id="btn-clinical-apply" class="button">Apply email filter</button></p></details>';
        echo '<p class="description">Date selection filters <strong>listens within the range</strong> and calculates consecutive listening days as of the range end. ';
        echo 'The all-time total and first-listen date are not filtered. Last login is the latest login recorded, not a historical snapshot. Times use the WordPress site timezone.</p>';
        echo '<p id="btn-clinical-progress" role="status" aria-live="polite">Loading participants…</p>';
        echo '<div style="max-width:100%;overflow:auto"><table class="widefat striped"><thead><tr>';
        foreach ( array( 'Email', 'Last login (latest)', 'First qualified listen (all time)', 'Consecutive days as of end date', 'Listens in date range', 'Total listens (all time)' ) as $head ) {
            echo '<th>' . esc_html( $head ) . '</th>';
        }
        echo '</tr></thead><tbody id="btn-clinical-results"></tbody></table></div></div>';
        wp_enqueue_script( 'btn-podcasts-clinical-report', BTN_PODCASTS_ASSESTS_URL . 'js/clinical-report.js', array(), BTN_PODCASTS_VERSION, true );
        wp_localize_script( 'btn-podcasts-clinical-report', 'btnClinicalReport', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'btn_podcasts_clinical_report' ),
            'today' => $today_string,
                    ) );
    }

    /** Strictly validate a YYYY-MM-DD date; never pass arbitrary input into SQL. */
    private static function valid_report_date( $value ) {
        if ( ! is_string( $value ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/D', $value ) ) {
            return false;
        }
        $date = DateTimeImmutable::createFromFormat( '!Y-m-d', $value, wp_timezone() );
        return $date && $date->format( 'Y-m-d' ) === $value;
    }

    /**
     * Admin-only DB-side eligibility: MySQL matches only memb_Groups rows
     * containing the exact comma-separated tag 3716 before returning accounts.
     * Cursor pagination uses the indexed umeta_id primary key. No WP_User_Query,
     * PHP-side scan through untagged accounts, or remote Keap tag checks.
     * The meta_value REGEXP is not indexable in WordPress core schema, so
     * MySQL may still examine other memb_Groups rows internally.
     */
    public function ajax_report_batch() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Not permitted.' ), 403 );
        }
        check_ajax_referer( 'btn_podcasts_clinical_report', 'nonce' );
        $start = isset( $_POST['start'] ) ? sanitize_text_field( wp_unslash( $_POST['start'] ) ) : '';
        $end = isset( $_POST['end'] ) ? sanitize_text_field( wp_unslash( $_POST['end'] ) ) : '';
        if ( ( $start !== '' || $end !== '' ) && ( ! self::valid_report_date( $start ) || ! self::valid_report_date( $end ) || $start > $end ) ) {
            wp_send_json_error( array( 'message' => 'Choose a valid From and To date (From must not be after To).' ), 400 );
        }
        $cursor = isset( $_POST['cursor'] ) ? absint( $_POST['cursor'] ) : 0;
        $batch_size = isset( $_POST['limit'] ) ? max( 1, min( 50, absint( $_POST['limit'] ) ) ) : 25;
        $mode = isset( $_POST['mode'] ) && 'selected' === sanitize_key( wp_unslash( $_POST['mode'] ) ) ? 'selected' : 'all';
        $upper = isset( $_POST['upper'] ) ? absint( $_POST['upper'] ) : 0;
        global $wpdb;

        $checked = 0;
        $participants = array();
        if ( 'selected' === $mode ) {
            $emails = isset( $_POST['emails'] ) ? (array) wp_unslash( $_POST['emails'] ) : array();
            $emails = array_slice( array_values( array_unique( array_filter( array_map( 'sanitize_email', $emails ) ) ) ), 0, 500 );
            $chunk = array_slice( $emails, $cursor, $batch_size );
            if ( $chunk ) {
                // Filter exact cached tag membership in SQL, not after loading users.
                // The indexed user_email lookup keeps selected-email mode bounded.
                $email_placeholders = implode( ',', array_fill( 0, count( $chunk ), '%s' ) );
                $matched = $wpdb->get_results( $wpdb->prepare(
                    "SELECT u.ID, u.user_email FROM {$wpdb->users} AS u INNER JOIN {$wpdb->usermeta} AS m ON m.user_id = u.ID AND m.meta_key = %s WHERE u.user_email IN ({$email_placeholders}) AND m.meta_value REGEXP %s",
                    array_merge( array( self::GROUPS_META ), $chunk, array( self::GROUPS_TAG_REGEX ) )
                ) );
                if ( $wpdb->last_error ) {
                    wp_send_json_error( array( 'message' => 'Unable to filter selected participant emails. Check database logs.' ), 500 );
                }
                foreach ( (array) $matched as $user ) {
                    $id = absint( $user->ID );
                    $participants[ $id ] = (object) array( 'ID' => $id, 'user_email' => $user->user_email );
                }
            }
            $checked = count( $chunk );
            $next_cursor = $cursor + $checked;
            $done = $next_cursor >= count( $emails );
            $upper = count( $emails );
        } else {
            // MAX(primary key) is an index-only watermark, not a user scan.
            // New tag changes will be visible when the admin refreshes the report.
            if ( ! $upper ) {
                $upper = (int) $wpdb->get_var( "SELECT MAX(umeta_id) FROM {$wpdb->usermeta}" );
            }
            $matched_rows = array();
            if ( $cursor < $upper ) {
                // MySQL applies exact-tag filtering before accounts are returned.
                // Only matched participants are joined to wp_users; the browser
                // never downloads other users or their memb_Groups values.
                $matched_rows = (array) $wpdb->get_results( $wpdb->prepare(
                    "SELECT m.umeta_id, u.ID, u.user_email FROM {$wpdb->usermeta} AS m INNER JOIN {$wpdb->users} AS u ON u.ID = m.user_id WHERE m.meta_key = %s AND m.meta_value REGEXP %s AND m.umeta_id > %d AND m.umeta_id <= %d ORDER BY m.umeta_id ASC LIMIT %d",
                    self::GROUPS_META, self::GROUPS_TAG_REGEX, $cursor, $upper, $batch_size
                ) );
            }
            if ( $wpdb->last_error ) {
                wp_send_json_error( array( 'message' => 'Unable to query matching memb_Groups participants. Check database logs.' ), 500 );
            }
            $checked = count( $matched_rows );
            foreach ( $matched_rows as $user ) {
                $participants[ absint( $user->ID ) ] = (object) array(
                    'ID' => absint( $user->ID ),
                    'user_email' => $user->user_email,
                );
            }
            $next_cursor = $matched_rows ? (int) end( $matched_rows )->umeta_id : $upper;
            $done = $checked < $batch_size || $next_cursor >= $upper;
        }
        $rows = $this->make_report_rows( $participants, $start, $end );
        if ( $wpdb->last_error ) {
            wp_send_json_error( array( 'message' => 'Unable to read clinical listening records. Check table and database logs.' ), 500 );
        }
        wp_send_json_success( array(
            'rows' => $rows,
            'checked' => $checked,
            'nextCursor' => $next_cursor,
            'done' => $done,
            'upper' => $upper,
        ) );
    }

    /** Build individual totals using the indexed (user_id, listened_on) key. */
    private function make_report_rows( $participants, $start = '', $end = '' ) {
        if ( ! $participants ) {
            return array();
        }
        global $wpdb;
        $ids = array_map( 'absint', array_keys( $participants ) );
        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        $table = self::table_name();
        $totals = array();
        $summary = $wpdb->get_results( $wpdb->prepare(
            "SELECT user_id, COUNT(*) AS total, MIN(listened_at) AS first_listen FROM {$table} WHERE user_id IN ({$placeholders}) GROUP BY user_id",
            $ids
        ) );
        foreach ( (array) $summary as $record ) {
            $totals[ (int) $record->user_id ] = $record;
        }
        $ranged = array();
        if ( $start !== '' ) {
            $range_args = array_merge( $ids, array( $start, $end ) );
            $range_summary = $wpdb->get_results( $wpdb->prepare(
                "SELECT user_id, COUNT(*) AS in_range FROM {$table} WHERE user_id IN ({$placeholders}) AND listened_on BETWEEN %s AND %s GROUP BY user_id",
                $range_args
            ) );
            foreach ( (array) $range_summary as $record ) {
                $ranged[ (int) $record->user_id ] = (int) $record->in_range;
            }
        }
        $as_of = $end !== '' ? $end : current_time( 'Y-m-d' );
        $date_args = array_merge( $ids, array( $as_of ) );
        $dates = $wpdb->get_results( $wpdb->prepare(
            "SELECT user_id, listened_on FROM {$table} WHERE user_id IN ({$placeholders}) AND listened_on <= %s GROUP BY user_id, listened_on ORDER BY user_id ASC, listened_on DESC",
            $date_args
        ) );
        $daily = array();
        foreach ( (array) $dates as $record ) {
            $daily[ (int) $record->user_id ][] = $record->listened_on;
        }
        $report_day = DateTimeImmutable::createFromFormat( '!Y-m-d', $as_of, wp_timezone() );
        $previous_day = $report_day->modify( '-1 day' )->format( 'Y-m-d' );
        // Load last-login values for the batch in one query, rather than one
        // WordPress usermeta cache fill per participant.
        $login_rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s AND user_id IN ({$placeholders})",
            array_merge( array( self::LAST_LOGIN_META ), $ids )
        ) );
        $logins = array();
        foreach ( (array) $login_rows as $record ) {
            $logins[ (int) $record->user_id ] = $record->meta_value;
        }
        $rows = array();
        foreach ( $participants as $id => $user ) {
            $days = isset( $daily[ $id ] ) ? $daily[ $id ] : array();
            $streak = 0;
            // Historical as-of streak may span earlier than a selected date window.
            if ( $days && ( $days[0] === $as_of || $days[0] === $previous_day ) ) {
                $expected = DateTimeImmutable::createFromFormat( '!Y-m-d', $days[0], wp_timezone() );
                foreach ( $days as $day ) {
                    if ( ! $expected || $day !== $expected->format( 'Y-m-d' ) ) {
                        break;
                    }
                    $streak++;
                    $expected = $expected->modify( '-1 day' );
                }
            }
            $login = isset( $logins[ $id ] ) ? $logins[ $id ] : ''; 
            $first = isset( $totals[ $id ] ) ? $totals[ $id ]->first_listen : '';
            $all_total = isset( $totals[ $id ] ) ? (int) $totals[ $id ]->total : 0;
            $rows[] = array(
                'email' => $user->user_email,
                'lastLogin' => $login ? get_date_from_gmt( $login, 'Y-m-d H:i:s' ) : '',
                'firstListen' => $first ? get_date_from_gmt( $first, 'Y-m-d H:i:s' ) : '',
                'streak' => $streak,
                'rangeTotal' => $start !== '' ? ( isset( $ranged[ $id ] ) ? $ranged[ $id ] : 0 ) : $all_total,
                'total' => $all_total,
            );
        }
        return $rows;
    }
}

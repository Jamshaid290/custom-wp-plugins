<?php
/*
Plugin Name: File Monitor - Real Time Security Alert
Description: Instant persistent alert with exact file path if any file is added or modified in wp-content, wp-includes, wp-admin.
Version: 1.2
Author: Jamshaid Khan
*/

if (!defined('ABSPATH')) exit;

/*--------------------------------------------------
 REAL-TIME FILE SCAN
--------------------------------------------------*/
add_action('admin_init', 'fm_realtime_file_scan');

function fm_realtime_file_scan() {

    if (!current_user_can('administrator')) return;

    $dirs = [
        ABSPATH . 'wp-content',
        ABSPATH . 'wp-includes',
        ABSPATH . 'wp-admin'
    ];

    $current_state = [];
    $changed_files = [];

    foreach ($dirs as $dir) {

        if (!is_dir($dir)) continue;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {

            if ($file->isFile()) {

                $path = $file->getPathname();

                // ignore cache & logs
                if (strpos($path, 'cache') !== false) continue;
                if (strpos($path, 'logs') !== false) continue;

                $current_state[$path] = md5_file($path);
            }
        }
    }

    $old_state = get_option('fm_file_state');

    if ($old_state) {
        foreach ($current_state as $path => $hash) {

            if (!isset($old_state[$path])) {
                // new file
                $changed_files[] = 'NEW: ' . $path;
            }
            elseif ($old_state[$path] !== $hash) {
                // modified file
                $changed_files[] = 'MODIFIED: ' . $path;
            }
        }
    }

    if (!empty($changed_files)) {
        update_option('fm_alert_active', true);
        update_option('fm_changed_files', $changed_files);
    }

    update_option('fm_file_state', $current_state);
}

/*--------------------------------------------------
  PERSISTENT ADMIN ALERT WITH FILE PATHS
--------------------------------------------------*/
add_action('admin_notices', 'fm_persistent_admin_alert');

function fm_persistent_admin_alert() {

    if (!get_option('fm_alert_active')) return;

    $files = get_option('fm_changed_files', []);
    ?>
    <div class="notice notice-error">
        <p><strong>🚨 SECURITY ALERT:</strong></p>
        <p>Following files were added or modified:</p>

        <ul style="max-height:200px;overflow:auto;background:#fff;padding:10px;border:1px solid #ccc;">
            <?php foreach ($files as $file): ?>
                <li><code><?php echo esc_html($file); ?></code></li>
            <?php endforeach; ?>
        </ul>

        <p>
            <a href="<?php echo esc_url(admin_url('?fm_clear_alert=1')); ?>"
               style="background:#b32d2e;color:#fff;padding:8px 14px;
               text-decoration:none;border-radius:4px;">
                Clear Alert (I have checked files)
            </a>
        </p>
    </div>
    <?php
}

/*--------------------------------------------------
 MANUAL CLEAR ALERT
--------------------------------------------------*/
add_action('admin_init', 'fm_clear_alert_handler');

function fm_clear_alert_handler() {

    if (!current_user_can('administrator')) return;

    if (isset($_GET['fm_clear_alert']) && $_GET['fm_clear_alert'] == 1) {

        delete_option('fm_alert_active');
        delete_option('fm_changed_files');

        wp_redirect(admin_url());
        exit;
    }
}
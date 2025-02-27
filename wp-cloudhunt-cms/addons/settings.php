<?php

function cloudhunt_add_admin_menu() {
    add_options_page(
        'CloudHunt Sync Settings',
        'CloudHunt Sync',
        'manage_options',
        'cloudhunt_sync',
        'cloudhunt_plugin_settings_page'
    );
}
add_action('admin_menu', 'cloudhunt_add_admin_menu');

function cloudhunt_register_settings() {
    register_setting('cloudhunt_sync_options', 'cloudhunt_api_url');
    register_setting('cloudhunt_sync_options', 'cloudhunt_api_token');
}
add_action('admin_init', 'cloudhunt_register_settings');

function cloudhunt_plugin_settings_page() {
    ?>
    <div class="wrap">
        <h1>CloudHunt Sync Settings</h1>
        <form method="post" action="options.php">
            <?php 
                settings_fields('cloudhunt_sync_options'); 
                do_settings_sections('cloudhunt_sync_options'); 
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Laravel API URL</th>
                    <td>
                        <input type="text" name="cloudhunt_api_url" 
                               value="<?php echo esc_attr(get_option('cloudhunt_api_url')); ?>" 
                               style="width: 400px;">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Laravel API Token</th>
                    <td>
                        <input type="text" name="cloudhunt_api_token" 
                               value="<?php echo esc_attr(get_option('cloudhunt_api_token')); ?>" 
                               style="width: 400px;">
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <h2>Manual Sync</h2>
        <p>Click the button below to manually sync courses and modules with Laravel.</p>
        <button id="cloudhunt-sync-btn" class="button button-primary">Sync Now</button>
        <p id="cloudhunt-sync-status"></p>

        <script>
            document.getElementById("cloudhunt-sync-btn").addEventListener("click", function() {
                var button = this;
                button.innerText = "Syncing...";
                button.disabled = true;

                fetch("<?php echo admin_url('admin-ajax.php?action=cloudhunt_manual_sync'); ?>")
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById("cloudhunt-sync-status").innerText = data.message;
                        button.innerText = "Sync Now";
                        button.disabled = false;
                    })
                    .catch(error => {
                        document.getElementById("cloudhunt-sync-status").innerText = "Error syncing.";
                        button.innerText = "Sync Now";
                        button.disabled = false;
                    });
            });
        </script>
    </div>
    <?php
}

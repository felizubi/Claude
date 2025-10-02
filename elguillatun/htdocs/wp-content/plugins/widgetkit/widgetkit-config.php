<div class="wrap">

    <h2>Widgetkit</h2>

    <form name="form"
          action="<?php echo add_query_arg(array('page' => $app['name'] . '-config', 'action' => 'save'), admin_url('options-general.php')); ?>"
          method="post">

        <h3>API Keys</h3>
        <table class="form-table">
            <tr>
                <th><?php echo $app['translator']->trans('YOOtheme API Key'); ?></th>
                <td>
                    <label for="config-apikey">
                        <input id="config-apikey" class="regular-text" type="text" name="config[apikey]"
                               value="<?php echo $app['config']->get('apikey'); ?>">
                    </label>

                    <p class="description"><?php echo $app['translator']->trans('In order to update commercial extensions set up your API Key which can be found in your %link% account.', array('%link%' => '<a href="http://yootheme.com/account" target="_blank">YOOtheme</a>')); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php echo $app['translator']->trans('Google Maps API Key'); ?></th>
                <td>
                    <label for="google-maps-apikey">
                        <input id="google-maps-apikey" class="regular-text" type="text" name="config[googlemapseapikey]"
                               value="<?php echo $app['config']->get('googlemapseapikey'); ?>">
                    </label>

                    <p class="description"><?php echo $app['translator']->trans('Custom API Key generated in your %link% account.', array('%link%' => '<a href="https://console.developers.google.com/apis" target="_blank">Google API Console</a>')); ?></p>
                </td>
            </tr>
        </table>

        <h3>Editor</h3>
        <table class="form-table">
            <tr>
                <th><?php echo $app['translator']->trans('System Editor'); ?></th>
                <td>
                    <label for="config-editor">
                        <input id="config-editor" name="config[system_editor]" type="checkbox" value="1" <?php checked('1', $app['config']->get('system_editor')); ?>>
                        <?php echo $app['translator']->trans('Enable the Visual Editor for Widgetkit content fields.'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <h3>Cache</h3>

        <table class="form-table">
            <tr>
                <th><?php echo $app['translator']->trans('Clear Widgetkit Cache'); ?></th>
                <td width="10">
                    <button id="wk-clear-cache" type="button" class="button">Clear Cache</button>
                </td>
                <td>
                    <p class="description wk-cache-size"></p>
                </td>
            </tr>
        </table>

        <?php wp_nonce_field(); ?>
        <?php submit_button(); ?>
    </form>
</div>

<script>
jQuery(function($) {

    var getCache = function() {

        $.get('admin-ajax.php?action=widgetkit&p=/cache/get', function(data) {
            $('.wk-cache-size').text(JSON.parse(data));
        });
    }

    $('#wk-clear-cache').on('click', function(e) {
        e.preventDefault();
        $('.wk-cache-size').text('<?php echo $app['translator']->trans('Clearing cache...'); ?>');
        $.get('admin-ajax.php?action=widgetkit&p=/cache/clear', getCache);
    });

    getCache();

});
</script>

<div class="wrap">
    <h2><?php echo __('Causeway Settings', 'novastream-wp-causeway') ?></h2>
    <form method="post" action="">
        <h3>Main settings</h3>
        <p>Update the following settings to match your needs for your website.<br><br> Please note hitting the <strong>Save settings</strong> button will not import the information into your website. <br>After you save your settings, your listings should import every hour.</p>

        <p>
        <strong>Backend URL</strong> - This is the endpoint where the feed will be retrieved from, the default is <strong><?php echo $this->plugin->getDefaultEndpointUrl(); ?></strong> and should not change.<br>
        <strong>Server API Key</strong> - This is a key to grant access for your WordPress to access the feed. This key will be provided by NovaStream. If you do not have this key, please contact support@novastream.ca<br>
        <strong>Force import of all listings</strong> - By default, this plugin will only pull new entries since last import. Check this box if you want to re-import all listings.<br>
        <strong>Allow category label renaming</strong> - Check this if you want to rename the default category names inside the WordPress admin. This usually is not needed.<br>
        </p>

        <p>On first import (or if force import is checked), it may take a long time to do the import depending on how many listings are being retrieved.<br> Even if the connection times out, it should still be importing in the background and there is no need to hit the import button again.</p>

        <?php
        if (isset($_POST['causeway-save'])) {
            $this->plugin->save();
            delete_transient('causeway_data');
            echo '<p>Settings have been successfully saved.</p>';
        }

        if (isset($_POST['causeway-import'])) {
            delete_transient('causeway_data');
            $this->plugin->getImporter()->import();
        }
        ?>
        <table style="max-width: 90%; border-spacing: 10px;">
            <tr style="vertical-align: top;">
                <th scope="row" style="padding-bottom: 16px; text-align: left; width: 30%;">
                    <label for="causeway_url">Causeway Backend URL</label>
                </th>
                <td>
                    <input style="min-width: 20vw;" type="text" id="causeway_url" name="causeway_url" placeholder="" value="<?php echo esc_url($this->plugin->getEndpointUrl()); ?>" required="required" style="width: 100%;" />
                </td>
            </tr>
            <tr style="vertical-align: top;">
                <th scope="row" style="padding-bottom: 16px; text-align: left; width: 30%;">
                    <label for="causeway_key">Server API Key</label>
                </th>
                <td>
                    <input style="min-width: 20vw;" type="text" id="causeway_key" name="causeway_key" placeholder="" value="<?php echo $this->plugin->getOption('key'); ?>" required="required" style="width: 100%;" />
                </td>
            </tr>
        </table>



        <?php @submit_button(__('Save settings', 'novastream-wp-causeway'), 'primary', 'causeway-save', false); ?>
        <?php @submit_button(__('Import listings now', 'novastream-wp-causeway'), 'primary', 'causeway-import', false); ?>


        <!--<hr style="margin-top: 30px; margin-bottom: 30px;"/>
        <h3>Other actions</h3>
    -->
    </form>
</div>

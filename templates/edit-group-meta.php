<?php
/**
 * Edit Group Meta
 *
*/
?>
<div class="form-field">
    <table id="group-menu-order"  class="form-table">
        <tbody>
            <tr>
                <th>
                    <label for="cover-art-id"><?php _e('Group Menu Order', 'btn-podcasts'); ?></label>
                </th>
                <td class="group-image">
                    <div id="group-image" class="group-img-wrap">
                        <input type = "number" name="btn-podcasts-group-order" value="<?php echo $btn_podcasts_group_order?>">
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
<div class="form-field">
    <table id="group-cover-art" class="form-table">
        <tbody>

            <tr>
                <th>
                    <label for="cover-art-id"><?php _e('Group Cover Art', 'btn-podcasts'); ?></label>
                </th>
                <td class="group-image">
                    <div id="group-image" class="group-img-wrap">
                        <figure><?php echo $group_img; ?></figure>
                        <br />
                        <button id="upload-cover-img" class="upload-cover-img"><?php _e('Add or Change Image', 'btn-podcasts'); ?></button>
                        <button id="remove-cover-img" class="remove-cover-img" <?php echo $remove_display; ?>>x</button>
                        <input id="cover-art-id" class="cover-art" type="hidden" name="cover_art_id" value="<?php echo $group_img_id; ?>"/>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>

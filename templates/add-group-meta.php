<?php
/**
 * Add Group Meta
 *
*/
?>
<div class="form-field">
    <table id="group-menu-order" >
        <tbody>

            <tr>
                <td class="group-image">
                    <div id="group-image" class="group-img-wrap">
                        <label for="cover-art-id"><?php _e('Group Menu Order', 'btn-podcasts'); ?></label>

                        <input type = "number" name="btn-podcasts-group-order" >
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
<div class="form-field">
    <table id="group-cover-art" >
        <tbody>
            <tr>

                <td class="group-image">
                    <div id="group-image" class="group-img-wrap">
                        <label for="cover-art-id"><?php _e('Group Cover Art', 'btn-podcasts'); ?></label>
                        <figure></figure>
                        <br/>
                        <button id="upload-cover-img" class="upload-cover-img"><?php _e('Add or Change Image', 'btn-podcasts'); ?></button>
                        <button id="remove-cover-img" class="remove-cover-img" style="display:none">x</button>
                        <input id="cover-art-id" class="cover-art" type="hidden" name="cover_art_id" value=""/>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>

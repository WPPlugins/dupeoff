<?php
if(current_user_can('check_dupeoff'))
{

   $dupeoff_results = dupeoff_check_api($post->post_content, $_POST);
?>
   <td colspan="<?= $rowSpan ?>">
      <div class="inline-edit-col" style="margin-left: 40px;">
         <?php

         print_r($dupeoff_results);

         ?>
      </div>
      <p class="submit inline-edit-save">
         <a accesskey="c" href="#inline-edit" title="Ok" class="button-secondary dupeoffOk alignleft">Ok</a>
         <br class="clear"/>
      </p>
   </td>
<?php } ?>
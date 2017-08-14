<?php
add_action('admin_head', 'dupeoff_javascript');

function dupeoff_javascript() {
?>
<script type="text/javascript" >
jQuery(document).ready(function($) {
   $('.check_DupeOff').live('click', function(event) {
	event.preventDefault();
  
      var row = $(this).closest("tr");
  
      var data = {
         action: 'check_dupeoff',
         rowId: row.attr('id'),
         tdCount: row.find('td').size() + 1,
      };

	$('#'+row.attr('id')+' .row-title').append("<img id=\"dupeoff_throbber\" src=\"<?php print WP_PLUGIN_URL?>/dupeoff/throbber.gif\" />");
	

      jQuery.post(ajaxurl, data, function(response) {
         if(response)
         {	$('#'+row.attr('id')).remove("#dupeoff_throbber");
            row.data('oldHtml', row.html());
            row.html(response);
            row.addClass('inline-edit-row');
         }
         else
         {
         }

	
      });
      return false;
   });

   $('.dupeoffOk').live('click', function() {
      var row = $(this).closest("tr");
      row.removeClass('inline-edit-row');
      row.html(row.data('oldHtml'));
	  $('#dupeoff_throbber').remove();
   });
});
</script>
<?php
}

add_action('wp_ajax_check_dupeoff', 'dupeoff_ajax_check');

function dupeoff_ajax_check()
{
   $id   = $_POST['rowId'];
   $id   = explode('-', $id);
   $id   = $id[1];
   $rowSpan = $_POST['tdCount'];
   
   $post = get_post($id);

   include(dirname(__FILE__) . '/dupeoff_check.php');
   //echo "IN HERE";
   die();
}


// AJAX
add_action('wp_ajax_post_check_dupeoff', 'dupeoff_post_ajax_check');

function dupeoff_post_ajax_check()
{   
	$post = $_POST['text'];

	if(current_user_can('check_dupeoff'))
	{

		$dupeoff_results = dupeoff_check_api($post, $_POST);

		print_r($dupeoff_results);
	//echo "IN HERE";
	}


	die();
}
?>
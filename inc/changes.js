jQuery(document).ready(function($) {
	// COPY
	$("#changes_copy").click(function () {
		$("#changes_mbe_content").val( ((this.checked) ? $("#content").text() : "") );
	})

	// DIFF
    
	$('#changes-viewchanges').hide();
	
	$('#tbp-viewchanges').click(function() {
		var link = this;
	
		$(link).html('loading...');
	
		var data = {
			action: 'sppdupexam_diffcheck',
			post_id: changes_js_params.post_id
		}
	
		$.post(ajaxurl, data, function(response) {
			$('#changes-viewchanges').show();
			$('#changes-viewchanges').html(response);
			$(link).remove();
		});
		return false;
	});    
});
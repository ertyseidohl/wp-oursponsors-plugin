jQuery(document).ready(function($) {
	oursponsors_post('get_all_data', {}).then(
		oursponsors_handle_response,
		oursponsors_handle_err
	);

	var oursponsors_last_res = null;

	var default_image_url = ajax_object.plugin_url + '/_inc/purecode_logo_wide.png';

	var oursponsors_is_editing = false;

	$('.oursponsors_sponsor_add').click(oursponsors_add_sponsor);
	$('.oursponsors_sponsor_level_add').click(oursponsors_add_sponsor_level);

	function oursponsors_add_sponsor() {
		$new_row = jQuery('#oursponsors_sponsors_row_template').clone();
		$new_row.removeAttr('id');
		$new_row.removeAttr('style');
		jQuery('#oursponsors_sponsors').append($new_row);
		oursponsors_render_edit_sponsor_row(
			{
				id: 'x',
				name: '',
				text: '',
				url: '',
				image_id: 0,
				image_url: default_image_url,
				sponsor_level: 0,
				years: new Date().getFullYear()
			},
			oursponsors_last_res.levels,
			jQuery('#oursponsors_sponsors_edit_template'),
			$new_row
		);
	}

	function oursponsors_add_sponsor_level() {
		$new_row = jQuery('#oursponsors_sponsor_levels_row_template').clone();
		$new_row.removeAttr('id');
		$new_row.removeAttr('style');
		jQuery('#oursponsors_sponsor_levels').append($new_row);
		oursponsors_render_edit_level_row(
			{
				id: 'x',
				name: '',
				text: '',
				size: 2,
			},
			jQuery('#oursponsors_sponsor_levels_edit_template'),
			$new_row
		);
	}

	function oursponsors_create_media_selector(sponsor_id) {
		return new THB_MediaSelector( {
			select: function( selected_images ) {
				jQuery('.oursponsors_image_preview_' + sponsor_id)
					.attr('src', selected_images.url);
				jQuery('.oursponsors_sponsor_image_id_' + sponsor_id)
					.val(selected_images.id);
			},
			type: 'image'
		} );
	}

	function oursponsors_cols_to_string(cols) {
		return ({
			12: "Full Width (1 across)",
			6: "Half Width (2 across)",
			4: "Third Width (3 across)",
			3: "Quarter Width (4 across)",
			2: "Sixth Width (6 across)",
			1: "Twelfth Width (12 across)"
		})[cols] || 'Unknown Width';
	}

	function oursponsors_handle_response(res) {
		oursponsors_is_editing = false;
		res = JSON.parse(res);
		if (res === "none") {
			// no changes, try to restore from last request
			if (oursponsors_last_res) {
				oursponsors_render_sponsors(
					oursponsors_last_res,
					jQuery('#oursponsors_sponsors_row_template'),
					jQuery('#oursponsors_sponsors')
				);
				oursponsors_render_levels(
					oursponsors_last_res,
					jQuery('#oursponsors_sponsor_levels_row_template'),
					jQuery('#oursponsors_sponsor_levels')
				);
			} else {
				oursponsors_post('get_all_data', {}).then(
					oursponsors_handle_response,
					oursponsors_handle_err
				);
			}
		} else {
			oursponsors_render_sponsors(
				res,
				jQuery('#oursponsors_sponsors_row_template'),
				jQuery('#oursponsors_sponsors')
			);
			oursponsors_render_levels(
				res,
				jQuery('#oursponsors_sponsor_levels_row_template'),
				jQuery('#oursponsors_sponsor_levels')
			);
			oursponsors_last_res = res;
		}
	}

	function oursponsors_handle_err(err) {
		alert("err: ", err);
	}

	function oursponsors_post(action, data){
		return jQuery.post(ajax_object.ajax_url, {
			'action': 'manage_sponsors',
			'oursponsors_action': action,
			'payload': data
		});
	}

	function oursponsors_create_edit_sponsor_click_function(sponsor, levels, $edit_template, $row) {
		return function(evt) {
			if (!oursponsors_is_editing) {
				oursponsors_is_editing = true;
				oursponsors_render_edit_sponsor_row(sponsor, levels, $edit_template, $row);
			} else {
				alert("Currently unable to edit two at once, please save/cancel.");
			}
		};
	}

	function oursponsors_create_edit_sponsor_level_click_function(level, $edit_template, $row) {
		return function(evt) {
			if (!oursponsors_is_editing) {
				oursponsors_is_editing = true;
				oursponsors_render_edit_level_row(level, $edit_template, $row);
			} else {
				alert("Currently unable to edit two at once, please save/cancel.");
			}
		};
	}

	function oursponsors_render_sponsors(response, $row_template, $row_target) {
		$row_target.empty();
		var sponsors = response.sponsors;
		var levels = response.levels;
		for ( var i = 0; i < sponsors.length; i++ ) {
			var s = sponsors[i];
			$row_clone = $row_template.clone();
			$row_clone.removeAttr('id');
			$row_clone.removeAttr('style');
			$row_clone.data('sponsor-id', s.id);
			$row_clone.find('.oursponsors_sponsor_name').text(s.name);
			$row_clone.find('.oursponsors_sponsor_url').attr('href', s.url);
			$row_clone.find('.oursponsors_sponsor_level').text(
				levels.find(function(e){ return e.id == s.sponsor_level;}).name
			);
			$row_clone.find('.oursponsors_sponsor_years').text(s.years.split(",").join(", "));
			$row_clone.click(oursponsors_create_edit_sponsor_click_function(s, levels, jQuery('#oursponsors_sponsors_edit_template'), $row_clone));
			$row_target.append($row_clone);
		}
	}

	function oursponsors_render_edit_sponsor_row(sponsor_data, levels_data, $edit_template, $edit_target) {
		var s = sponsor_data;
		var levels = levels_data;
		$row_clone = $edit_template.clone();
		$row_clone.removeAttr('id');
		$row_clone.removeAttr('style');
		$row_clone.data('sponsor-id', s.id);

		$row_clone.find('.oursponsors_sponsor_name').val(s.name);
		$row_clone.find('.oursponsors_sponsor_text').val(s.text);
		$row_clone.find('.oursponsors_sponsor_url').val(s.url);

		var media_selector = oursponsors_create_media_selector(s.id);
		console.log(media_selector);
		$row_clone.find('.oursponsors_image_preview')
			.addClass('oursponsors_image_preview_' + s.id)
			.attr('src', parseInt(s.image_id, 10) ? s.image_url : default_image_url);
		$row_clone.find('.oursponsors_sponsor_image_id')
			.addClass('oursponsors_sponsor_image_id_' + s.id)
			.val(s.image_id);
		$row_clone.find('.oursponsors_open_media_selector').click(function() {
			media_selector.open([s.image_id]);
		});

		var $levels = $row_clone.find('.oursponsors_sponsor_level');
		for ( var j = 0; j < levels.length; j++) {
			var selected = levels[j].id === s.sponsor_level ? 'selected="selected"' : '';
			$levels.append('<option value="' + levels[j].id + '" ' + selected + '>' + levels[j].name + '</option>');
		}
		$row_clone.find('.oursponsors_sponsor_years').val(s.years);
		$edit_target.replaceWith($row_clone);

		$save_button = $row_clone.find('.oursponsors_sponsor_save');
		$save_button.click(oursponsors_handle_save_sponsor);

		$delete_button = $row_clone.find('.oursponsors_sponsor_delete');
		$delete_button.click(oursponsors_handle_delete_sponsor);

		$cancel_button = $row_clone.find('.oursponsors_sponsor_cancel');
		$cancel_button.click(function() {
			oursponsors_handle_response("\"none\"");
		});
	}

	function oursponsors_handle_save_sponsor(evt) {
		$row = jQuery(evt.target).parents('tr');
		oursponsors_post('update_sponsor', {
			id: $row.data('sponsor-id'),
			name: $row.find('.oursponsors_sponsor_name').val(),
			text: $row.find('.oursponsors_sponsor_text').val(),
			url: $row.find('.oursponsors_sponsor_url').val(),
			image_id: $row.find('.oursponsors_sponsor_image_id').val(),
			sponsor_level: $row.find('.oursponsors_sponsor_level').val(),
			years: $row.find('.oursponsors_sponsor_years').val()
		}).then(
			oursponsors_handle_response,
			oursponsors_handle_err
		);
	}

	function oursponsors_render_levels(response, $row_template, $row_target) {
		var levels = response.levels;
		$row_target.empty();
		for ( var i = 0; i < levels.length; i++ ) {
			var v = levels[i];
			$row_clone = $row_template.clone();
			$row_clone.removeAttr('id');
			$row_clone.removeAttr('style');
			$row_clone.data('level-id', v.id);
			$row_clone.find('.oursponsors_sponsor_level_name').text(v.name);
			$row_clone.find('.oursponsors_sponsor_level_size').text(oursponsors_cols_to_string(v.size));
			$row_clone.click(oursponsors_create_edit_sponsor_level_click_function(v, jQuery('#oursponsors_sponsor_levels_edit_template'), $row_clone));
			$row_target.append($row_clone);
		}
	}


	function oursponsors_render_edit_level_row(level_data, $edit_template, $edit_target) {
		var v = level_data;
		$row_clone = $edit_template.clone();
		$row_clone.removeAttr('id');
		$row_clone.removeAttr('style');
		$row_clone.data('level-id', v.id);
		$row_clone.find('.oursponsors_sponsor_level_name').val(v.name);
		$row_clone.find('.oursponsors_sponsor_level_text').val(v.text);
		$row_clone.find('.oursponsors_sponsor_level_size').val(v.size);
		$edit_target.replaceWith($row_clone);

		$save_button = $row_clone.find('.oursponsors_sponsor_level_save');
		$save_button.click(oursponsors_handle_save_level);

		$delete_button = $row_clone.find('.oursponsors_sponsor_level_delete');
		$delete_button.click(oursponsors_handle_delete_level);


		$cancel_button = $row_clone.find('.oursponsors_sponsor_level_cancel');
		$cancel_button.click(function() {
			oursponsors_handle_response("\"none\"");
		});
	}

	function oursponsors_handle_save_level(evt) {
		$row = jQuery(evt.target).parents('tr');
		oursponsors_post('update_sponsor_level', {
			id: $row.data('level-id'),
			name: $row.find('.oursponsors_sponsor_level_name').val(),
			text: $row.find('.oursponsors_sponsor_level_text').val(),
			size: $row.find('.oursponsors_sponsor_level_size').val()
		}).then(
			oursponsors_handle_response,
			oursponsors_handle_err
		);
	}

	function oursponsors_handle_delete_sponsor(evt) {
		id = jQuery(evt.target).parents('tr').data('sponsor-id');
		if (!id || id === 'x') {
			oursponsors_handle_response('\"none\"');
		} else {
			oursponsors_post('delete_sponsor', {
				id: id
			}).then(
				oursponsors_handle_response,
				oursponsors_handle_err
			);
		}
	}

	function oursponsors_handle_delete_level(evt) {
		id = jQuery(evt.target).parents('tr').data('level-id');
		if (!id || id === 'x') {
			oursponsors_handle_response('\"none\"');
		} else {
			oursponsors_post('delete_level', {
				id: id
			}).then(
				oursponsors_handle_response,
				oursponsors_handle_err
			);
		}
	}
});

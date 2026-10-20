// Order ids with a dispatch request still running: a second click would reserve and send a second set of pack numbers.
const shopup_venipak_shipping_dispatches_in_flight = {};

// Order ids whose pickup point is chosen in the order page's editor but not saved yet; the editor keeps it up to date.
// Dispatch sends the stored point, so a pending choice would be silently ignored.
const shopup_venipak_shipping_unsaved_point_changes = {};

// Red like a stored dispatch error and like the server's refusals, set as text so a message is never parsed as HTML.
function shopup_venipak_shipping_show_error(wrapper, message) {
	wrapper.html('<p style="color: red;"></p>').find('p').text(message);
}

function shopup_venipak_shipping_dispatch_order_by_id({ id, newDispatch, button }) {
	if (shopup_venipak_shipping_dispatches_in_flight[id]) return;
	const wrapper = jQuery('#shopup_venipak_shipping_wrapper_order_' + id);
	if (shopup_venipak_shipping_unsaved_point_changes[id]) {
		shopup_venipak_shipping_show_error(wrapper, venipakAdmin.savePointFirst);
		return;
	}

	// FormData so a customs document file can go along. The pack keys are the packs[i][width]
	// names jQuery.post produced before, so PHP still reads $_POST['packs'] as a list of packs.
	const data = new FormData();
	data.append('action', newDispatch ? 'woocommerce_shopup_venipak_shipping_dispatch_force' : 'woocommerce_shopup_venipak_shipping_dispatch');
	data.append('order_id', id);
	data.append('nonce', venipakAdmin.nonce);
	jQuery('#packages-table tr.venipak-pack').each(function (i) {
		const row = jQuery(this);
		data.append(`packs[${i}][width]`, row.find(".venipak-pack-width").val());
		data.append(`packs[${i}][length]`, row.find(".venipak-pack-length").val());
		data.append(`packs[${i}][height]`, row.find(".venipak-pack-height").val());
		data.append(`packs[${i}][weight]`, row.find(".venipak-pack-weight").val());
		data.append(`packs[${i}][description]`, row.find(".venipak-pack-description").val());
	});
	// The customs block is on the page only for destinations outside Venipak's network (decided on the server).
	if (jQuery('#shopup_venipak_shipping_customs').length) {
		const valueInput = jQuery('#shopup_venipak_shipping_value')[0];
		// Text the browser cannot read as a number arrives as "", which the server would take as "use the order total".
		if (valueInput.validity.badInput) {
			shopup_venipak_shipping_show_error(wrapper, venipakAdmin.invalidValue);
			return;
		}
		const file = jQuery('#shopup_venipak_shipping_doc_file')[0].files[0];
		if (file && file.size > venipakAdmin.maxDocumentBytes) {
			shopup_venipak_shipping_show_error(wrapper, venipakAdmin.documentTooLarge);
			return;
		}
		data.append('doc_type', jQuery('#shopup_venipak_shipping_doc_type').val());
		data.append('value', valueInput.value);
		data.append('customs_document_id', jQuery('#shopup_venipak_shipping_doc_id').val());
		if (file) {
			data.append('customs_document', file);
		}
	}

	shopup_venipak_shipping_dispatches_in_flight[id] = true;
	jQuery(button).addClass('disabled');
	wrapper.html('<span style="visibility: visible;" class="spinner"></span>');
	jQuery.ajax({ url: ajaxurl, type: 'POST', data, processData: false, contentType: false }).done(function(response) {
		wrapper.html(response);
	}).fail(function(xhr) {
		// No answer or a proxy timeout: PHP may still be waiting on Venipak (up to 120 s with a
		// document), so a retry now could register the parcel twice.
		if (xhr.status === 0 || xhr.status === 502 || xhr.status === 504) {
			shopup_venipak_shipping_show_error(wrapper, venipakAdmin.dispatchMayBeRunning);
			return;
		}
		shopup_venipak_shipping_show_error(wrapper, venipakAdmin.requestFailed);
	}).always(function() {
		delete shopup_venipak_shipping_dispatches_in_flight[id];
		jQuery(button).removeClass('disabled');
	});
}

// Customs document block on the order page, rendered for destinations outside Venipak's network. The file
// comes either from the computer (file input) or from the media library (attachment ID in the
// hidden input); picking one clears the other, and the chosen name is shown with a remove link.
jQuery(function ($) {
	const customs = $('#shopup_venipak_shipping_customs');
	if (!customs.length) return;
	const fileInput = $('#shopup_venipak_shipping_doc_file');
	const attachmentInput = $('#shopup_venipak_shipping_doc_id');
	const chosen = $('#shopup_venipak_shipping_doc_chosen');
	let libraryFrame = null;

	function showChosen(name) {
		chosen.find('.name').text(name);
		chosen.toggle(name !== '');
	}

	fileInput.on('change', function () {
		attachmentInput.val('');
		showChosen(this.files.length ? this.files[0].name : '');
	});

	$('#shopup_venipak_shipping_doc_library').on('click', function () {
		if (!libraryFrame) {
			libraryFrame = wp.media({ title: $(this).data('title'), library: { type: 'application/pdf' }, multiple: false });
			libraryFrame.on('select', function () {
				const attachment = libraryFrame.state().get('selection').first().toJSON();
				fileInput.val('');
				attachmentInput.val(attachment.id);
				showChosen(attachment.filename);
			});
		}
		libraryFrame.open();
	});

	chosen.find('a').on('click', function (event) {
		event.preventDefault();
		fileInput.val('');
		attachmentInput.val('');
		showChosen('');
	});
});

// Pickup point editor on the order page (markup: add_venipak_shipping_order_edit). Save sends the choice by AJAX, so
// it needs no Update of the whole order; the select stays inside the order form, so Update also posts what is chosen.
jQuery(function ($) {
	const editor = $('#venipak-pickup-point-editor');
	if (!editor.length) return;
	const orderId = Number(editor.attr('data-order-id'));
	// .attr, not .data: .data would turn a stored id into a number and an old order's JSON point into an object.
	let storedId = editor.attr('data-stored-id');
	const select = $('#venipak_pickup_point');
	const loadedInput = $('input[name="venipak_pickup_point_loaded"]');
	const changeButton = $('#venipak-pickup-point-change');
	const status = $('#venipak-pickup-point-status');
	let editorOpen = false;

	const chosenId = () => String(select.val() || '');

	// Dispatch sends the stored point, so it is refused while another one is only chosen here.
	function updateUnsavedFlag() {
		shopup_venipak_shipping_unsaved_point_changes[orderId] = editorOpen && chosenId() !== storedId;
	}

	function setSelect(id) {
		select.val(id === '' ? null : id).trigger('change');
	}

	function showStatus(message, isError) {
		status.css('color', isError ? 'red' : '#008a20').text(message);
	}

	function closeEditor() {
		editorOpen = false;
		editor.hide();
		changeButton.show();
		updateUnsavedFlag();
	}

	select.on('change', updateUnsavedFlag);

	changeButton.on('click', function () {
		editorOpen = true;
		status.text('');
		editor.show();
		changeButton.hide();
	});

	$('#venipak-pickup-point-remove').on('click', function () {
		setSelect('');
	});

	$('#venipak-pickup-point-cancel').on('click', function () {
		setSelect(storedId);
		closeEditor();
	});

	$('#venipak-pickup-point-save').on('click', function () {
		const id = chosenId();
		// Also covers an old order whose stored point is not an id: nothing changed, nothing to send.
		if (id === storedId) {
			closeEditor();
			return;
		}
		const buttons = editor.find('button').prop('disabled', true);
		status.text('');
		$.post(ajaxurl, { action: 'woocommerce_shopup_venipak_shipping_save_pickup_point', order_id: orderId, point_id: id, nonce: venipakAdmin.nonce }).done(function (response) {
			storedId = String(response.data.point_id);
			// Update compares the select with what the page was loaded with, which is now the saved point.
			loadedInput.val(storedId);
			$('#venipak-pickup-point-title').text(response.data.title || venipakAdmin.notSelected);
			setSelect(storedId);
			closeEditor();
			showStatus(venipakAdmin.pointSaved, false);
		}).fail(function (xhr) {
			const failure = xhr.responseJSON && xhr.responseJSON.data;
			showStatus(failure ? failure.message : venipakAdmin.requestFailed, true);
		}).always(function () {
			buttons.prop('disabled', false);
		});
	});

	$.get(ajaxurl, { action: 'woocommerce_venipak_shipping_pickup_points', order_id: orderId }, function (points) {
		// Adding options makes the browser select the first one, so put the current choice back
		// afterwards (Remove may have been clicked while the list was loading).
		const choice = chosenId();
		select.select2({
			width: '100%',
			data: points.map(point => ({
				id: point.id,
				text: `${point.name}, ${point.address}, ${point.city}, ${point.zip}`,
			})),
		});
		setSelect(choice);
	}, 'json');
});

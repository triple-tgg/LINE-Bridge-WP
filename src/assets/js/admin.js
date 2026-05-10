/* global jQuery, lineBridgeAdmin */
jQuery(function ($) {
	'use strict';

	// Test message
	$('#line-bridge-test-btn').on('click', function () {
		var btn    = $(this);
		var result = $('#line-bridge-test-result');
		btn.prop('disabled', true).text('กำลังส่ง...');
		result.text('').removeAttr('class');

		$.post(lineBridgeAdmin.ajaxUrl, {
			action: 'line_bridge_test_message',
			nonce:  lineBridgeAdmin.nonce,
		}, function (res) {
			if (res.success) {
				result.text('✅ ' + lineBridgeAdmin.i18n.testSuccess).css('color', '#0a6640');
			} else {
				result.text('❌ ' + (res.data || lineBridgeAdmin.i18n.testFailed)).css('color', '#d63638');
			}
		}).always(function () {
			btn.prop('disabled', false).text('📨 ส่งข้อความทดสอบ');
		});
	});

	// Unlink user
	$(document).on('click', '.line-bridge-unlink-btn', function () {
		if (!confirm(lineBridgeAdmin.i18n.unlinkConfirm)) return;
		var btn    = $(this);
		var userId = btn.data('user-id');
		var nonce  = btn.data('nonce') || lineBridgeAdmin.nonce;

		$.post(lineBridgeAdmin.ajaxUrl, {
			action:  'line_bridge_unlink_user',
			nonce:   nonce,
			user_id: userId,
		}, function (res) {
			if (res.success) {
				alert(lineBridgeAdmin.i18n.unlinkSuccess);
				btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
			} else {
				alert(res.data || 'เกิดข้อผิดพลาด');
			}
		});
	});

	// Save template
	$('#line-bridge-save-tpl').on('click', function () {
		var btn    = $(this);
		var result = $('#tpl-save-result');
		btn.prop('disabled', true);

		$.post(lineBridgeAdmin.ajaxUrl, {
			action:        'line_bridge_save_template',
			nonce:         lineBridgeAdmin.nonce,
			template_key:  $('#tpl-key').val(),
			message_type:  $('#tpl-message-type').val(),
			body:          $('#tpl-body').val(),
		}, function (res) {
			if (res.success) {
				result.text('✅ ' + lineBridgeAdmin.i18n.saveSuccess).css('color', '#0a6640');
			} else {
				result.text('❌ ' + (res.data || 'เกิดข้อผิดพลาด')).css('color', '#d63638');
			}
		}).always(function () {
			btn.prop('disabled', false);
		});
	});

	// Copy to clipboard
	$(document).on('click', '.line-bridge-copy-btn', function () {
		var target = $($(this).data('target'));
		if (!target.length) return;
		navigator.clipboard.writeText(target.val()).then(function () {
			alert('คัดลอกแล้ว!');
		});
	});
});

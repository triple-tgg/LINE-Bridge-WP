/* global lineBridgePublic */
(function () {
	'use strict';

	// Unlink LINE account from My Account page
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.line-bridge-unlink-btn');
		if (!btn) return;
		if (!confirm('ยืนยันการยกเลิกเชื่อมต่อบัญชี LINE?')) return;

		var url   = btn.dataset.url;
		var nonce = btn.dataset.nonce;

		fetch(url, {
			method:  'DELETE',
			headers: {
				'X-WP-Nonce': nonce,
				'Content-Type': 'application/json',
			},
		}).then(function (r) { return r.json(); }).then(function (res) {
			if (res.success) {
				alert('ยกเลิกการเชื่อมต่อสำเร็จ');
				window.location.reload();
			} else {
				alert(res.message || 'เกิดข้อผิดพลาด');
			}
		}).catch(function () {
			alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
		});
	});
})();

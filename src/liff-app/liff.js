(function () {
	'use strict';

	const params     = new URLSearchParams(window.location.search);
	const orderId    = params.get('order');
	const apiBase    = params.get('api') || '';

	function show(id) {
		['loading', 'error-screen', 'order-screen', 'no-order-screen'].forEach(function (s) {
			document.getElementById(s).style.display = s === id ? '' : 'none';
		});
	}

	function showError(msg) {
		document.getElementById('error-msg').textContent = msg || 'เกิดข้อผิดพลาด';
		show('error-screen');
	}

	function renderOrder(data) {
		document.getElementById('order-number').textContent = '#' + data.order_number;
		document.getElementById('order-date').textContent   = data.date || '—';
		document.getElementById('order-total').textContent  = data.total || '—';

		const badge = document.getElementById('status-badge');
		badge.textContent = data.status;
		badge.className   = 'status-badge status-' + (data.status_code || 'default');

		const itemsEl = document.getElementById('order-items');
		itemsEl.innerHTML = '';
		if (data.items && data.items.length) {
			data.items.forEach(function (item) {
				const row = document.createElement('div');
				row.className = 'item-row';
				row.innerHTML =
					'<span class="item-name">' + escHtml(item.name) + ' × ' + escHtml(String(item.quantity)) + '</span>' +
					'<span class="item-price">' + escHtml(item.total) + '</span>';
				itemsEl.appendChild(row);
			});
		}

		show('order-screen');
	}

	function escHtml(str) {
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	async function fetchOrder(accessToken) {
		if (!orderId || !apiBase) {
			show('no-order-screen');
			return;
		}
		const url = apiBase + 'line-bridge/v1/order-status?order=' + encodeURIComponent(orderId)
			+ '&line_token=' + encodeURIComponent(accessToken);
		const resp = await fetch(url);
		if (!resp.ok) {
			const err = await resp.json().catch(() => ({}));
			showError(err.message || 'ไม่สามารถโหลดข้อมูลออเดอร์ได้');
			return;
		}
		const data = await resp.json();
		renderOrder(data);
	}

	async function init() {
		try {
			const liffId = document.body.dataset.liffId || params.get('liff_id');
			if (!liffId) {
				showError('ไม่พบ LIFF ID');
				return;
			}
			await liff.init({ liffId: liffId });
			if (!liff.isLoggedIn()) {
				liff.login({ redirectUri: window.location.href });
				return;
			}
			const profile     = await liff.getProfile();
			const accessToken = liff.getAccessToken();
			document.getElementById('site-name').textContent = 'ติดตามออเดอร์';
			await fetchOrder(accessToken);
		} catch (e) {
			showError(e.message || 'ไม่สามารถเริ่มต้น LIFF ได้');
		}
	}

	document.getElementById('retry-btn').addEventListener('click', function () {
		show('loading');
		init();
	});

	init();
})();

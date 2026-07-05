(function () {
	'use strict';

	var cfg       = window.tbcSetup || {};
	var STEPS     = cfg.steps  || [];
	var LABELS    = cfg.labels || {};
	var ajaxurl   = cfg.ajaxurl || '';
	var nonce     = cfg.nonce   || '';

	var startBtn     = document.getElementById('tbc-setup-start');
	var resetBtn     = document.getElementById('tbc-setup-reset');
	var progressWrap = document.getElementById('tbc-setup-progress-wrap');
	var progressFill = document.getElementById('tbc-progress-fill');
	var progressLabel= document.getElementById('tbc-progress-label');
	var progressFrac = document.getElementById('tbc-progress-fraction');
	var consoleWrap  = document.getElementById('tbc-setup-console');
	var consoleInner = document.getElementById('tbc-console-inner');
	var doneEl       = document.getElementById('tbc-setup-done');
	var doneMsgEl    = document.getElementById('tbc-setup-done-msg');
	var pageLinkEl   = document.getElementById('tbc-setup-page-link');
	var errorEl      = document.getElementById('tbc-setup-error');
	var errorMsg     = document.getElementById('tbc-setup-error-msg');

	var running  = false;
	var pageUrl  = '';  // set by front_page step in staging mode

	function getMode() {
		var checked = document.querySelector('input[name="tbc-mode"]:checked');
		return checked ? checked.value : 'production';
	}

	function appendLog(lines, type) {
		(lines || []).forEach(function (line) {
			var el = document.createElement('div');
			el.className = 'tbc-console-line' + (type ? ' tbc-console-line--' + type : '');
			el.textContent = line;
			consoleInner.appendChild(el);
		});
		consoleInner.scrollTop = consoleInner.scrollHeight;
	}

	function setProgress(stepIndex) {
		var pct = Math.round((stepIndex / STEPS.length) * 100);
		progressFill.style.width = pct + '%';
		progressFrac.textContent = stepIndex + ' / ' + STEPS.length;
	}

	function setLabel(step) {
		progressLabel.textContent = LABELS[step] || step;
	}

	function showError(msg) {
		errorMsg.textContent = msg;
		errorEl.style.display = 'block';
	}

	function runStep(stepIndex, mode) {
		if (stepIndex >= STEPS.length) {
			setProgress(STEPS.length);
			progressLabel.textContent = 'Complete';

			// Build done message depending on mode.
			if (mode === 'staging' && pageUrl) {
				doneMsgEl.textContent = 'Staging import complete.';
				pageLinkEl.href = pageUrl;
				pageLinkEl.style.display = 'inline';
			} else {
				doneMsgEl.textContent = 'Setup complete — all steps finished successfully.';
				pageLinkEl.style.display = 'none';
			}

			doneEl.style.display = 'flex';
			resetBtn.style.display = 'inline-block';
			running = false;
			return;
		}

		var step = STEPS[stepIndex];
		setLabel(step);
		setProgress(stepIndex);

		appendLog(['> ' + (LABELS[step] || step) + '…'], 'step');

		var body = 'action=tbc_setup_run_step'
			+ '&step='  + encodeURIComponent(step)
			+ '&mode='  + encodeURIComponent(mode)
			+ '&_ajax_nonce=' + encodeURIComponent(nonce);

		fetch(ajaxurl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body,
		})
		.then(function (r) {
			if (!r.ok) throw new Error('HTTP ' + r.status);
			return r.json();
		})
		.then(function (resp) {
			if (!resp.success) {
				var msg = (resp.data && resp.data.error) ? resp.data.error : 'Unknown AJAX error.';
				appendLog([msg], 'error');
				showError(msg);
				resetBtn.style.display = 'inline-block';
				running = false;
				return;
			}

			var data = resp.data || {};
			appendLog(data.logs || []);

			// Capture staging page URL whenever the server provides it.
			if (data.page_url) {
				pageUrl = data.page_url;
			}

			if (data.error) {
				appendLog(['[error] ' + data.error], 'error');
				showError(data.error);
				resetBtn.style.display = 'inline-block';
				running = false;
				return;
			}

			runStep(stepIndex + 1, mode);
		})
		.catch(function (err) {
			appendLog(['[error] ' + err.message], 'error');
			showError(err.message);
			resetBtn.style.display = 'inline-block';
			running = false;
		});
	}

	function startSetup() {
		if (running) return;
		running  = true;
		pageUrl  = '';

		var mode = getMode();

		// Lock mode selection while running.
		document.querySelectorAll('input[name="tbc-mode"]').forEach(function (r) { r.disabled = true; });

		startBtn.disabled      = true;
		startBtn.style.display = 'none';
		resetBtn.style.display = 'none';
		progressWrap.style.display = 'block';
		consoleWrap.style.display  = 'block';
		doneEl.style.display       = 'none';
		errorEl.style.display      = 'none';
		consoleInner.innerHTML     = '';

		appendLog(['Mode: ' + mode], 'step');
		setProgress(0);
		runStep(0, mode);
	}

	function resetSetup() {
		running  = false;
		pageUrl  = '';

		document.querySelectorAll('input[name="tbc-mode"]').forEach(function (r) { r.disabled = false; });

		startBtn.disabled      = false;
		startBtn.style.display = 'inline-block';
		resetBtn.style.display = 'none';
		progressWrap.style.display = 'none';
		consoleWrap.style.display  = 'none';
		doneEl.style.display       = 'none';
		errorEl.style.display      = 'none';
		pageLinkEl.style.display   = 'none';
		consoleInner.innerHTML     = '';
		progressFill.style.width   = '0%';
	}

	if (startBtn) startBtn.addEventListener('click', startSetup);
	if (resetBtn) resetBtn.addEventListener('click', resetSetup);
})();

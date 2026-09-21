(function() {
	'use strict';

	const root = document.getElementById('workplanner-quick');
	if (!root) {
		return;
	}

	const form = root.querySelector('form');
	const status = root.querySelector('[data-role="status"]');
	const locationSelect = form.elements.locationId;
	const timeFromInput = form.elements.timeFrom;
	const timeToInput = form.elements.timeTo;
	const noteInput = form.elements.note;

	function translate(text, vars) {
		return t('workplanner', text, vars || {});
	}

	function apiUrl(path) {
		return OC.generateUrl('/apps/workplanner' + path);
	}

	function request(path, options) {
		return fetch(apiUrl(path), Object.assign({
			headers: {
				'Content-Type': 'application/json',
				'requesttoken': OC.requestToken,
			},
		}, options || {})).then(async response => {
			const data = await response.json().catch(() => ({}));
			if (!response.ok) {
				throw new Error(data.error ? translate(data.error) : translate('The request failed.'));
			}
			return data;
		});
	}

	function setStatus(message, isError) {
		status.textContent = message || '';
		status.classList.toggle('workplanner__status--error', !!isError);
	}

	function today() {
		const date = new Date();
		const copy = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
		return copy.toISOString().slice(0, 10);
	}

	function loadLocations() {
		setStatus(translate('Loading locations ...'));
		request('/plans?start=' + today() + '&end=' + today())
			.then(data => {
				locationSelect.innerHTML = '';
				data.locations.forEach(location => {
					const option = document.createElement('option');
					option.value = String(location.id);
					option.textContent = location.name;
					locationSelect.appendChild(option);
				});
				form.elements.day.value = today();
				setStatus('');
			})
			.catch(error => setStatus(error.message, true));
	}

	function isCompleteTime(value) {
		return /^([01]\d|2[0-3]):[0-5]\d$/.test(value);
	}

	timeFromInput.addEventListener('input', () => {
		if (isCompleteTime(timeFromInput.value)) {
			(timeToInput.value === '' ? timeToInput : noteInput).focus();
		}
	});
	timeToInput.addEventListener('input', () => {
		if (isCompleteTime(timeToInput.value)) {
			noteInput.focus();
		}
	});

	form.addEventListener('submit', event => {
		event.preventDefault();
		const timeFrom = timeFromInput.value;
		const timeTo = timeToInput.value;
		if (timeFrom && timeTo && timeFrom > timeTo) {
			setStatus(translate('The time range is invalid.'), true);
			return;
		}
		setStatus(translate('Saving ...'));
		request('/plans', {
			method: 'POST',
			body: JSON.stringify({
				day: form.elements.day.value,
				locationId: Number(locationSelect.value),
				timeFrom,
				timeTo,
				note: noteInput.value,
			}),
		}).then(() => {
			form.elements.note.value = '';
			setStatus(translate('Saved.'));
		}).catch(error => setStatus(error.message, true));
	});

	loadLocations();
})();

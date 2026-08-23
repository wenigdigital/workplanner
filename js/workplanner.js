(function() {
	'use strict';

	const root = document.getElementById('workplanner-app');
	if (!root) {
		return;
	}

	const calendar = root.querySelector('[data-role="calendar"]');
	const status = root.querySelector('[data-role="status"]');
	const rangeTitle = root.querySelector('[data-role="range-title"]');
	const modeSelect = root.querySelector('[data-role="view-mode"]');
	const feed = root.querySelector('[data-role="feed"]');
	const feedUrl = root.querySelector('[data-role="feed-url"]');
	const copyFeedButton = root.querySelector('[data-action="copy-feed"]');
	const quickLink = root.querySelector('[data-role="quick-link"]');
	const dialog = document.getElementById('workplanner-dialog');
	const dialogForm = dialog.querySelector('form');
	const dialogTitle = dialog.querySelector('[data-role="dialog-title"]');
	const locationSelect = dialogForm.elements.locationId;
	const timeFromInput = dialogForm.elements.timeFrom;
	const timeToInput = dialogForm.elements.timeTo;
	const noteInput = dialogForm.elements.note;
	const deleteButton = dialog.querySelector('[data-action="delete"]');

	let current = startOfWeek(new Date());
	let viewMode = 'week';
	let payload = { today: isoDate(new Date()), userId: '', locations: [], plans: [] };
	let selectedDay = null;
	let selectedPlanId = 0;

	function translate(text, vars) {
		return t('workplanner', text, vars || {});
	}

	function apiUrl(path) {
		return OC.generateUrl('/apps/workplanner' + path);
	}

	quickLink.href = apiUrl('/quick');

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

	function isoDate(date) {
		const copy = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
		return copy.toISOString().slice(0, 10);
	}

	function parseDay(day) {
		return new Date(day + 'T00:00:00');
	}

	function addDays(date, days) {
		const copy = new Date(date);
		copy.setDate(copy.getDate() + days);
		return copy;
	}

	function startOfWeek(date) {
		const copy = new Date(date.getFullYear(), date.getMonth(), date.getDate());
		const day = (copy.getDay() + 6) % 7;
		copy.setDate(copy.getDate() - day);
		return copy;
	}

	function startOfMonth(date) {
		return new Date(date.getFullYear(), date.getMonth(), 1);
	}

	function endOfMonth(date) {
		return new Date(date.getFullYear(), date.getMonth() + 1, 0);
	}

	function getRange() {
		if (viewMode === 'month') {
			const start = startOfWeek(startOfMonth(current));
			const end = addDays(startOfWeek(endOfMonth(current)), 4);
			return { start, end };
		}

		return { start: startOfWeek(current), end: addDays(startOfWeek(current), 4) };
	}

	function isWeekend(date) {
		const day = date.getDay();
		return day === 0 || day === 6;
	}

	function formatDay(date) {
		return date.toLocaleDateString(OC.getLanguage(), { weekday: 'short', day: '2-digit', month: '2-digit' });
	}

	function formatTitle(start, end) {
		if (viewMode === 'month') {
			return current.toLocaleDateString(OC.getLanguage(), { month: 'long', year: 'numeric' });
		}

		return start.toLocaleDateString(OC.getLanguage(), { day: '2-digit', month: '2-digit', year: 'numeric' })
			+ ' - '
			+ end.toLocaleDateString(OC.getLanguage(), { day: '2-digit', month: '2-digit', year: 'numeric' });
	}

	function plansFor(day) {
		return payload.plans.filter(plan => plan.day === day);
	}

	function render() {
		const range = getRange();
		rangeTitle.textContent = formatTitle(range.start, range.end);
		calendar.classList.toggle('workplanner__grid--month', viewMode === 'month');
		calendar.innerHTML = '';

		for (let day = new Date(range.start); day <= range.end; day = addDays(day, 1)) {
			if (isWeekend(day)) {
				continue;
			}

			const dayIso = isoDate(day);
			const isPast = dayIso < payload.today;
			const isOutsideMonth = viewMode === 'month' && day.getMonth() !== current.getMonth();
			const cell = document.createElement('section');
			cell.className = 'workplanner-day';
			cell.classList.toggle('workplanner-day--past', isPast);
			cell.classList.toggle('workplanner-day--muted', isOutsideMonth);
			cell.innerHTML = '<header><strong></strong><button type="button" class="button"></button></header><div class="workplanner-day__plans"></div>';
			cell.querySelector('strong').textContent = formatDay(day);
			const button = cell.querySelector('button');
			button.textContent = translate('Add');
			button.disabled = isPast || payload.locations.length === 0;
			button.addEventListener('click', () => openDialog(dayIso, null));

			const list = cell.querySelector('.workplanner-day__plans');
			const dayPlans = plansFor(dayIso);
			if (dayPlans.length === 0) {
				const empty = document.createElement('p');
				empty.className = 'workplanner-day__empty';
				empty.textContent = translate('No planning');
				list.appendChild(empty);
			} else {
				dayPlans.forEach(plan => {
					const item = document.createElement('div');
					item.className = 'workplanner-plan';
					item.style.borderColor = plan.color;
					item.innerHTML = '<span class="workplanner-plan__dot"></span><div class="workplanner-plan__content"><strong></strong><small></small><div class="workplanner-plan__actions" hidden><button type="button" class="button edit"></button><button type="button" class="button delete"></button></div></div>';
					item.querySelector('.workplanner-plan__dot').style.background = plan.color;
					item.querySelector('strong').textContent = plan.userId;
					const locationName = plan.locationDeleted
						? (plan.locationName || translate('Deleted location')) + ' (' + translate('deleted') + ')'
						: (plan.locationName || translate('Location'));
					item.querySelector('small').textContent = [locationName, plan.timeValue, plan.note].filter(Boolean).join(' - ');
					if (plan.editable) {
						const actions = item.querySelector('.workplanner-plan__actions');
						actions.hidden = false;
						actions.querySelector('.edit').textContent = translate('Edit');
						actions.querySelector('.delete').textContent = translate('Delete');
						actions.querySelector('.edit').addEventListener('click', () => openDialog(dayIso, plan));
						actions.querySelector('.delete').addEventListener('click', () => deletePlan(plan.id));
					}
					list.appendChild(item);
				});
			}

			calendar.appendChild(cell);
		}
	}

	function load() {
		const range = getRange();
		setStatus(translate('Loading planning ...'));
		return request('/plans?start=' + isoDate(range.start) + '&end=' + isoDate(range.end))
			.then(data => {
				payload = data;
				setStatus('');
				render();
			})
			.catch(error => setStatus(error.message, true));
	}

	function loadFeedInfo() {
		request('/feed-info')
			.then(data => {
				feedUrl.value = data.url;
				feed.hidden = false;
			})
			.catch(() => {
				feed.hidden = true;
			});
	}

	function openDialog(day, plan) {
		selectedDay = day;
		selectedPlanId = plan ? plan.id : 0;
		dialogTitle.textContent = new Date(day + 'T00:00:00').toLocaleDateString(OC.getLanguage(), { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' });
		locationSelect.innerHTML = '';
		payload.locations.forEach(location => {
			const option = document.createElement('option');
			option.value = String(location.id);
			option.textContent = location.name;
			locationSelect.appendChild(option);
		});
		locationSelect.value = plan && plan.locationId ? String(plan.locationId) : (payload.locations[0] ? String(payload.locations[0].id) : '');
		timeFromInput.value = plan ? (plan.timeFrom || '') : '';
		timeToInput.value = plan ? (plan.timeTo || '') : '';
		noteInput.value = plan ? plan.note : '';
		deleteButton.hidden = !plan;
		dialog.hidden = false;
		locationSelect.focus();
	}

	function closeDialog() {
		dialog.hidden = true;
		selectedDay = null;
		selectedPlanId = 0;
	}

	function deletePlan(id) {
		request('/plans/' + encodeURIComponent(id), { method: 'DELETE' })
			.then(() => load())
			.catch(error => setStatus(error.message, true));
	}

	dialogForm.addEventListener('submit', event => {
		event.preventDefault();
		request('/plans', {
			method: 'POST',
			body: JSON.stringify({
				id: selectedPlanId,
				day: selectedDay,
				locationId: Number(locationSelect.value),
				timeFrom: timeFromInput.value,
				timeTo: timeToInput.value,
				note: noteInput.value,
			}),
		}).then(() => {
			closeDialog();
			return load();
		}).catch(error => setStatus(error.message, true));
	});

	dialog.querySelector('[data-action="cancel"]').addEventListener('click', closeDialog);
	deleteButton.addEventListener('click', () => {
		if (selectedPlanId > 0) {
			deletePlan(selectedPlanId);
			closeDialog();
		}
	});

	root.querySelector('[data-action="previous"]').addEventListener('click', () => {
		current = viewMode === 'month' ? new Date(current.getFullYear(), current.getMonth() - 1, 1) : addDays(current, -7);
		load();
	});
	root.querySelector('[data-action="today"]').addEventListener('click', () => {
		current = viewMode === 'month' ? startOfMonth(new Date()) : startOfWeek(new Date());
		load();
	});
	root.querySelector('[data-action="next"]').addEventListener('click', () => {
		current = viewMode === 'month' ? new Date(current.getFullYear(), current.getMonth() + 1, 1) : addDays(current, 7);
		load();
	});
	modeSelect.addEventListener('change', () => {
		viewMode = modeSelect.value;
		current = viewMode === 'month' ? startOfMonth(current) : startOfWeek(current);
		load();
	});

	copyFeedButton.addEventListener('click', () => {
		const url = feedUrl.value;
		if (!url) {
			return;
		}
		if (navigator.clipboard) {
			navigator.clipboard.writeText(url).then(() => setStatus(translate('Calendar feed link copied.')));
		} else {
			feedUrl.select();
			document.execCommand('copy');
			setStatus(translate('Calendar feed link copied.'));
		}
	});

	loadFeedInfo();
	load();
})();

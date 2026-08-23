(function() {
	'use strict';

	const root = document.getElementById('workplanner-admin');
	if (!root) {
		return;
	}

	const form = root.querySelector('[data-role="location-form"]');
	const tbody = root.querySelector('[data-role="locations"]');
	const status = root.querySelector('[data-role="status"]');
	let locations = [];

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

	function resetForm() {
		form.reset();
		form.elements.id.value = '';
		form.elements.color.value = '#2f6fdd';
		form.elements.sortOrder.value = '0';
		form.elements.active.checked = true;
	}

	function fillForm(location) {
		form.elements.id.value = location.id;
		form.elements.name.value = location.name;
		form.elements.color.value = location.color;
		form.elements.description.value = location.description || '';
		form.elements.sortOrder.value = location.sortOrder || 0;
		form.elements.active.checked = !!location.active;
		form.elements.name.focus();
	}

	function render() {
		tbody.innerHTML = '';
		locations.forEach(location => {
			const row = document.createElement('tr');
			row.innerHTML = '<td><div class="workplanner-admin__location"><span class="workplanner-admin__swatch"></span><strong></strong></div></td><td></td><td></td><td><div class="workplanner-admin__actions"><button type="button" class="button edit"></button><button type="button" class="button toggle"></button><button type="button" class="button purge" hidden></button></div></td>';
			row.querySelector('.workplanner-admin__swatch').style.background = location.color;
			row.querySelector('strong').textContent = location.name;
			row.children[1].textContent = location.description || '';
			row.children[2].textContent = location.active ? translate('Active') : translate('Inactive');
			row.querySelector('.edit').textContent = translate('Edit');
			row.querySelector('.toggle').textContent = location.active ? translate('Disable') : translate('Activate');
			row.querySelector('.purge').textContent = translate('Delete permanently');
			row.querySelector('.purge').hidden = !!location.active;
			row.querySelector('.edit').addEventListener('click', () => fillForm(location));
			row.querySelector('.toggle').addEventListener('click', () => {
				request(location.active ? '/locations/' + location.id : '/locations/' + location.id + '/restore', {
					method: location.active ? 'DELETE' : 'POST',
				})
					.then(data => {
						locations = data.locations;
						render();
						resetForm();
					})
					.catch(error => setStatus(error.message, true));
			});
			row.querySelector('.purge').addEventListener('click', () => {
				if (!confirm(translate('Delete this inactive location permanently?'))) {
					return;
				}

				request('/locations/' + location.id + '/purge', { method: 'DELETE' })
					.then(data => {
						locations = data.locations;
						render();
						resetForm();
					})
					.catch(error => setStatus(error.message, true));
			});
			tbody.appendChild(row);
		});
	}

	function load() {
		setStatus(translate('Loading locations ...'));
		request('/locations')
			.then(data => {
				locations = data.locations;
				setStatus('');
				render();
			})
			.catch(error => setStatus(error.message, true));
	}

	form.addEventListener('submit', event => {
		event.preventDefault();
		const id = form.elements.id.value;
		const body = {
			name: form.elements.name.value,
			color: form.elements.color.value,
			description: form.elements.description.value,
			sortOrder: Number(form.elements.sortOrder.value || 0),
			active: form.elements.active.checked,
		};
		request(id ? '/locations/' + id : '/locations', {
			method: id ? 'PUT' : 'POST',
			body: JSON.stringify(body),
		}).then(data => {
			locations = data.locations;
			resetForm();
			render();
			setStatus(translate('Saved.'));
		}).catch(error => setStatus(error.message, true));
	});

	root.querySelector('[data-action="reset"]').addEventListener('click', resetForm);
	load();
})();

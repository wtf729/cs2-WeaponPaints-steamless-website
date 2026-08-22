(function (app) {
	'use strict';
	var appConfig = app.config || {};
	var appText = appConfig.text || {};
	var loadRemoteImage = app.loadRemoteImage;
	var observePickerImages = app.observePickerImages;
	var setStickerUnderlay = app.setModalUnderlay;
	var markStickerBackdrop = app.markPickerBackdrop;
	var showFloatingNotice = app.showFloatingNotice || function () {};
	var fetchJson = app.fetchJson;
	var fetchOptionalJson = app.fetchOptionalJson;
	var scheduleIdleTask = app.scheduleIdleTask;
	var dataLoadFailedMessage = appText.dataLoadFailed;
			var keychainData = null;
			var keychainDataPromise = null;
			var activeKeychainSlot = null;
			var keychainPickerEl = document.getElementById('keychainPickerModal');
			var keychainPicker = keychainPickerEl && window.bootstrap ? new bootstrap.Modal(keychainPickerEl) : null;
			var keychainSearchInput = keychainPickerEl ? keychainPickerEl.querySelector('.keychain-search') : null;
			var keychainResultsEl = keychainPickerEl ? keychainPickerEl.querySelector('[data-keychain-results]') : null;
			var keychainPickerBody = keychainPickerEl ? keychainPickerEl.querySelector('[data-keychain-picker-body]') : null;
			var keychainLoadingState = keychainPickerEl ? keychainPickerEl.querySelector('[data-keychain-loading]') : null;
			var keychainDefaults = { template: 1, x: 0, y: 0, z: 0 };
			var keychainConfig = {
				template: { min: 1, max: 99999, decimals: 0, defaultValue: 1 },
				x: { min: -1, max: 1, decimals: 2, defaultValue: 0 },
				y: { min: -1, max: 1, decimals: 2, defaultValue: 0 }
			};
			var keychainSaveFailedMessage = appText.keychainSaveFailed;

			var clampKeychainParam = function (key, value, fallback) {
				var config = keychainConfig[key];
				var numeric = parseFloat(value);
				if (!config || !isFinite(numeric)) return fallback !== undefined ? fallback : (config ? config.defaultValue : 0);
				return Math.min(config.max, Math.max(config.min, numeric));
			};
			var formatKeychainParam = function (key, value) {
				var config = keychainConfig[key];
				var normalized = clampKeychainParam(key, value, config ? config.defaultValue : 0);
				return config && config.decimals > 0 ? normalized.toFixed(config.decimals) : String(Math.round(normalized));
			};
			var parseKeychainValue = function (value) {
				var parts = String(value || '').split(';');
				while (parts.length < 5) parts.push('');
				return {
					id: parseInt(parts[0], 10) || 0,
					x: clampKeychainParam('x', parts[1], 0),
					y: clampKeychainParam('y', parts[2], 0),
					z: parseFloat(parts[3]) || 0,
					template: clampKeychainParam('template', parts[4], 1)
				};
			};
			var buildKeychainValueForClient = function (id, params) {
				id = parseInt(id, 10) || 0;
				if (!id) return '0;0;0;0;0';
				params = params || keychainDefaults;
				return [
					id,
					formatKeychainParam('x', params.x),
					formatKeychainParam('y', params.y),
					formatKeychainParam('x', params.z || 0),
					formatKeychainParam('template', params.template)
				].join(';');
			};
			var syncCardKeychainPreview = function (slot, id, name, image) {
				var form = slot ? slot.closest('form') : null;
				if (!form) return;
				var row = form.querySelector('.card-stickers');
				var existing = row ? row.querySelector('.card-keychain-preview') : null;
				id = parseInt(id, 10) || 0;
				if (!id) {
					if (existing) existing.remove();
					if (row && !row.querySelector('img')) row.remove();
					return;
				}
				if (!row) {
					row = document.createElement('div');
					row.className = 'card-stickers';
					row.setAttribute('aria-label', appText.keychain);
					var wearMeter = form.querySelector('.wear-meter');
					if (wearMeter) form.insertBefore(row, wearMeter);
				}
				if (!existing) {
					existing = document.createElement('img');
					existing.className = 'card-keychain-preview';
					row.appendChild(existing);
				}
				existing.src = 'img/skins/keychain.png';
				existing.dataset.remoteSrc = image || '';
				existing.alt = name || '';
				existing.title = name || '';
				loadRemoteImage(existing, true);
			};
			var syncKeychainInlineControls = function (slot, params) {
				var input = slot ? slot.querySelector('[data-keychain-input]') : null;
				var id = input ? String(input.value || '0') : '0';
				var enabled = id !== '0';
				var valueInput = slot ? slot.querySelector('[data-keychain-value]') : null;
				var values = params || parseKeychainValue(valueInput ? valueInput.value : '');
				var editor = slot ? slot.closest('.keychain-inline-editor') : null;
				if (!editor) return;
				editor.querySelectorAll('[data-keychain-inline-param]').forEach(function (field) {
					var key = field.dataset.keychainInlineParam;
					var range = editor.querySelector('[data-keychain-inline-range="' + key + '"]');
					var value = formatKeychainParam(key, enabled ? values[key] : keychainDefaults[key]);
					field.disabled = !enabled;
					field.value = value;
					if (range) {
						range.disabled = !enabled;
						range.value = value;
					}
				});
			};
			var updateKeychainValueFromInlineControls = function (slot, normalizeFields) {
				var input = slot ? slot.querySelector('[data-keychain-input]') : null;
				var valueInput = slot ? slot.querySelector('[data-keychain-value]') : null;
				var editor = slot ? slot.closest('.keychain-inline-editor') : null;
				var id = input ? parseInt(input.value, 10) || 0 : 0;
				if (!valueInput || !editor) return;
				if (!id) {
					valueInput.value = buildKeychainValueForClient(0, keychainDefaults);
					return;
				}
				var current = parseKeychainValue(valueInput.value);
				var params = { template: current.template, x: current.x, y: current.y, z: 0 };
				editor.querySelectorAll('[data-keychain-inline-param]').forEach(function (field) {
					var key = field.dataset.keychainInlineParam;
					var numeric = parseFloat(field.value);
					var value = isFinite(numeric)
						? clampKeychainParam(key, numeric, current[key])
						: current[key];
					if (normalizeFields) field.value = formatKeychainParam(key, value);
					var range = editor.querySelector('[data-keychain-inline-range="' + key + '"]');
					if (range && isFinite(numeric)) range.value = String(value);
					params[key] = value;
				});
				valueInput.value = buildKeychainValueForClient(id, params);
			};
			var setKeychainSlot = function (slot, id, name, image) {
				if (!slot) return;
				var input = slot.querySelector('[data-keychain-input]');
				var valueInput = slot.querySelector('[data-keychain-value]');
				var preview = slot.querySelector('[data-keychain-preview]');
				var plus = slot.querySelector('.keychain-plus');
				var label = slot.querySelector('[data-keychain-name-text]') || slot.querySelector('[data-keychain-name]');
				id = String(id || '0');
				image = image || '';
				if (input) input.value = id;
				if (valueInput) valueInput.value = buildKeychainValueForClient(id, keychainDefaults);
				if (preview) {
					preview.src = 'img/skins/keychain.png';
					preview.dataset.remoteSrc = image;
					preview.hidden = id === '0' || !image;
					loadRemoteImage(preview, true);
				}
				if (plus) plus.hidden = id !== '0' && !!image;
				if (label) label.textContent = id === '0' ? (slot.dataset.emptyLabel || appText.noKeychain) : name;
				syncKeychainInlineControls(slot, keychainDefaults);
				syncCardKeychainPreview(slot, id, name, image);
			};
			var loadKeychains = function () {
				if (keychainData) return Promise.resolve(keychainData);
				if (keychainDataPromise) return keychainDataPromise;
				keychainDataPromise = Promise.all([
					fetchJson(window.cs2KeychainDataUrl),
					fetchOptionalJson(window.cs2KeychainAliasDataUrl)
				]).then(function (payloads) {
					var items = payloads[0] || [];
					var aliases = payloads[1] || [];
					var aliasById = {};
					var seen = {};
					aliases.forEach(function (item) {
						aliasById[parseInt(item.id, 10) || 0] = item.name || '';
					});
					keychainData = [{ id: 0, name: appText.noKeychain, image: '' }].concat(items.map(function (item) {
						var id = parseInt(item.id, 10) || 0;
						var name = item.name || '';
						var alias = aliasById[id] || '';
						seen[id] = true;
						return { id: id, name: name, image: item.image || '', searchText: name + ' ' + alias };
					}));
					aliases.forEach(function (item) {
						var id = parseInt(item.id, 10) || 0;
						if (!id || seen[id]) return;
						keychainData.push({ id: id, name: item.name || '', image: item.image || '', searchText: item.name || '' });
					});
					keychainDataPromise = null;
					return keychainData;
				}).catch(function (error) {
					keychainDataPromise = null;
					throw error;
				});
				return keychainDataPromise;
			};
			var renderKeychainResults = function () {
				if (!keychainResultsEl || !keychainData) return;
				var query = (keychainSearchInput ? keychainSearchInput.value : '').trim().toLowerCase();
				var terms = query ? query.split(/\s+/).filter(Boolean) : [];
				var shown = keychainData.filter(function (item) {
					var searchText = (item.searchText || item.name || '').toLowerCase();
					return !query || String(item.id) === query || terms.every(function (term) {
						return searchText.indexOf(term) !== -1;
					});
				}).slice(0, 80);
				keychainResultsEl.innerHTML = '';
				shown.forEach(function (item) {
					var button = document.createElement('button');
					button.type = 'button';
					button.className = 'keychain-result';
					button.dataset.keychainId = String(item.id);
					button.dataset.keychainName = item.name;
					button.dataset.keychainImage = item.image || '';
					if (item.image) {
						var image = document.createElement('img');
						image.src = 'img/skins/keychain.png';
						image.dataset.remoteSrc = item.image;
						image.alt = '';
						button.appendChild(image);
					} else {
						var empty = document.createElement('span');
						empty.className = 'keychain-empty-icon';
						empty.textContent = '+';
						button.appendChild(empty);
					}
					var name = document.createElement('span');
					name.textContent = item.name;
					button.appendChild(name);
					keychainResultsEl.appendChild(button);
				});
				observePickerImages(keychainPickerEl);
			};
			var saveKeychainChoice = function (slot, id) {
				if (!window.fetch || !slot) return Promise.resolve(null);
				var form = slot.closest('form');
				var formData = new FormData();
				formData.append('action', 'save_keychain_choice');
				formData.append('id', form ? (form.querySelector('input[name="id"]') || {}).value || '' : '');
				formData.append('team', form ? (form.querySelector('input[name="team"]') || {}).value || '1' : '1');
				formData.append('weapon_defindex', slot.dataset.weaponDefindex || '0');
				formData.append('keychain_id', String(id || '0'));
				formData.append('ajax', '1');
				return fetch(window.location.href, {
					method: 'POST',
					body: formData,
					headers: { 'X-Requested-With': 'fetch', 'X-CSRF-Token': window.cs2CsrfToken, 'Accept': 'application/json' }
				}).then(function (response) {
					return response.ok ? response.json() : Promise.reject();
				}).then(function (payload) {
					if (!payload || !payload.ok) throw new Error(payload && payload.message ? payload.message : keychainSaveFailedMessage);
					return payload;
				});
			};
			var setKeychainPickerLoading = function (loading) {
				if (keychainPickerBody) {
					keychainPickerBody.classList.toggle('is-loading', loading);
					keychainPickerBody.setAttribute('aria-busy', loading ? 'true' : 'false');
				}
				if (keychainLoadingState) keychainLoadingState.hidden = !loading;
			};
			if (keychainPickerEl) {
				keychainPickerEl.addEventListener('shown.bs.modal', function () {
					markStickerBackdrop();
					observePickerImages(keychainPickerEl);
				});
				keychainPickerEl.addEventListener('hidden.bs.modal', function () {
					setStickerUnderlay(null);
				});
			}
			document.querySelectorAll('[data-keychain-slot]').forEach(function (slot) {
				syncKeychainInlineControls(slot);
				var editor = slot.closest('.keychain-inline-editor');
				if (!editor) return;
				editor.querySelectorAll('[data-keychain-inline-param]').forEach(function (field) {
					var key = field.dataset.keychainInlineParam;
					var range = editor.querySelector('[data-keychain-inline-range="' + key + '"]');
					field.addEventListener('input', function () {
						if (!field.disabled) updateKeychainValueFromInlineControls(slot, false);
					});
					field.addEventListener('change', function () {
						if (field.disabled) return;
						updateKeychainValueFromInlineControls(slot, true);
					});
					if (range) {
						range.addEventListener('input', function () {
							if (range.disabled) return;
							field.value = formatKeychainParam(key, range.value);
							updateKeychainValueFromInlineControls(slot, false);
						});
					}
				});
			});
			if (keychainSearchInput) {
				keychainSearchInput.addEventListener('input', renderKeychainResults);
			}
			document.addEventListener('click', function (event) {
				var keychainOpenButton = event.target.closest('[data-keychain-open]');
				if (keychainOpenButton) {
					activeKeychainSlot = keychainOpenButton.closest('[data-keychain-slot]');
					setStickerUnderlay(keychainOpenButton.closest('.modal'));
					if (keychainSearchInput) keychainSearchInput.value = '';
					var needsLoading = !keychainData;
					setKeychainPickerLoading(needsLoading);
					if (!needsLoading) renderKeychainResults();
					if (keychainPicker) {
						keychainPicker.show();
						setTimeout(markStickerBackdrop, 0);
					}
					if (!needsLoading) {
						setTimeout(function () { if (keychainSearchInput) keychainSearchInput.focus(); }, 150);
						return;
					}
					loadKeychains().then(function () {
						renderKeychainResults();
						setKeychainPickerLoading(false);
						setTimeout(function () { if (keychainSearchInput) keychainSearchInput.focus(); }, 150);
					}).catch(function (error) {
						setKeychainPickerLoading(false);
						if (keychainPicker) keychainPicker.hide();
						activeKeychainSlot = null;
						setStickerUnderlay(null);
						if (window.console && console.error) console.error(error);
						alert(dataLoadFailedMessage);
					});
					return;
				}
				var keychainResultButton = event.target.closest('[data-keychain-id]');
				if (keychainResultButton && activeKeychainSlot) {
					var id = keychainResultButton.dataset.keychainId || '0';
					var name = keychainResultButton.dataset.keychainName || appText.noKeychain;
					var image = keychainResultButton.dataset.keychainImage || '';
					saveKeychainChoice(activeKeychainSlot, id).then(function (payload) {
						setKeychainSlot(activeKeychainSlot, id, name, image);
						var valueInput = activeKeychainSlot.querySelector('[data-keychain-value]');
						if (valueInput && payload && payload.value) valueInput.value = payload.value;
						activeKeychainSlot.dataset.savedKeychainId = String(id || '0');
						syncKeychainInlineControls(activeKeychainSlot, parseKeychainValue(valueInput ? valueInput.value : ''));
						if (keychainPicker) keychainPicker.hide();
						showFloatingNotice(appText.keychainSelectionSaved);
					}).catch(function (error) {
						alert(error && error.message ? error.message : keychainSaveFailedMessage);
					});
				}
			});

			if (keychainPickerEl && document.querySelector('[data-keychain-open]') && typeof scheduleIdleTask === 'function') {
				scheduleIdleTask(loadKeychains);
			}

	app.keychains = { pickerEl: keychainPickerEl, picker: keychainPicker };

})(window.cs2App = window.cs2App || {});

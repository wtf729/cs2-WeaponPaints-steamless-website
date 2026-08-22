(function (app) {
	'use strict';
	var appConfig = app.config || {};
	var appText = appConfig.text || {};
	var loadRemoteImage = app.loadRemoteImage;
	var observePickerImages = app.observePickerImages;
	var createImageCrossfadeStage = app.createImageCrossfadeStage;
	var attachImageSpinnerFallback = app.attachImageSpinnerFallback;
	var setStickerUnderlay = app.setModalUnderlay;
	var markStickerBackdrop = app.markPickerBackdrop;
	var fetchJson = app.fetchJson;
	var fetchOptionalJson = app.fetchOptionalJson;
	var scheduleIdleTask = app.scheduleIdleTask;
	var showFloatingNotice = app.showFloatingNotice || function () {};
	var dataLoadFailedMessage = appText.dataLoadFailed;
			var stickerData = null;
			var stickerDataPromise = null;
			var stickerResultLimit = 90;
			var activeStickerSlot = null;
			var pickerEl = document.getElementById('stickerPickerModal');
			var picker = pickerEl && window.bootstrap ? new bootstrap.Modal(pickerEl) : null;
			var searchInput = pickerEl ? pickerEl.querySelector('.sticker-search') : null;
			var resultsEl = pickerEl ? pickerEl.querySelector('[data-sticker-results]') : null;
			var pickerBody = pickerEl ? pickerEl.querySelector('[data-sticker-picker-body]') : null;
			var loadingState = pickerEl ? pickerEl.querySelector('[data-sticker-loading]') : null;
			var stickerSearchTimer = null;
			var activeAdvancedSlot = null;
			var advancedEl = document.getElementById('stickerAdvancedModal');
			var advancedModal = advancedEl && window.bootstrap ? new bootstrap.Modal(advancedEl) : null;
			var advancedForm = advancedEl ? advancedEl.querySelector('[data-sticker-advanced-form]') : null;
			var advancedTitle = advancedEl ? advancedEl.querySelector('[data-sticker-advanced-title]') : null;
			var advancedName = advancedEl ? advancedEl.querySelector('[data-sticker-advanced-name]') : null;
			var advancedTitleTemplate = appText.stickerSlotSettings;
			var stickerSaveFailedMessage = appText.stickerSaveFailed;
			var stickerDefaults = { wear: 0, x: 0, y: 0, scale: 1, rotation: 0 };
			var stickerParamConfig = {
				wear: { min: 0, max: 1, decimals: 2, defaultValue: 0 },
				x: { min: -1, max: 1, decimals: 2, defaultValue: 0 },
				y: { min: -1, max: 1, decimals: 2, defaultValue: 0 },
				scale: { min: 0.2, max: 5, decimals: 2, defaultValue: 1 },
				rotation: { min: 0, max: 360, decimals: 0, defaultValue: 0 }
			};

			var clampStickerParam = function (key, value, fallback) {
				var config = stickerParamConfig[key];
				var numeric = parseFloat(value);
				if (!config || !isFinite(numeric)) {
					return fallback !== undefined ? fallback : (config ? config.defaultValue : 0);
				}
				if (key === 'scale' && numeric <= 0) numeric = config.defaultValue;
				return Math.min(config.max, Math.max(config.min, numeric));
			};

			var formatStickerParam = function (key, value) {
				var config = stickerParamConfig[key];
				var normalized = clampStickerParam(key, value, config ? config.defaultValue : 0);
				return config && config.decimals > 0 ? normalized.toFixed(config.decimals) : String(Math.round(normalized));
			};

			var parseStickerValue = function (value) {
				var parts = String(value || '').split(';');
				while (parts.length < 7) parts.push('');
				var id = parseInt(parts[0], 10) || 0;
				var schema = parseInt(parts[1], 10) || 0;
				if (id > 0 && schema === 0) schema = id;
				return {
					id: id,
					schema: schema,
					x: clampStickerParam('x', parts[2], 0),
					y: clampStickerParam('y', parts[3], 0),
					wear: clampStickerParam('wear', parts[4], 0),
					scale: clampStickerParam('scale', parts[5], 1),
					rotation: clampStickerParam('rotation', parts[6], 0)
				};
			};

			var buildStickerValueForClient = function (id, schema, params) {
				id = parseInt(id, 10) || 0;
				schema = parseInt(schema, 10) || 0;
				if (!id) return '0;0;0;0;0;0;0';
				if (!schema) schema = id;
				params = params || stickerDefaults;
				return [
					id,
					schema,
					formatStickerParam('x', params.x),
					formatStickerParam('y', params.y),
					formatStickerParam('wear', params.wear),
					formatStickerParam('scale', params.scale),
					formatStickerParam('rotation', params.rotation)
				].join(';');
			};

			var defaultStickerValueForClient = function (id) {
				return buildStickerValueForClient(id, id, stickerDefaults);
			};

			var syncStickerSettingsButton = function (slot) {
				if (!slot) return;
				var input = slot.querySelector('[data-sticker-input]');
				var button = slot.querySelector('[data-sticker-settings]');
				var id = input ? String(input.value || '0') : '0';
				var savedId = String(slot.dataset.savedStickerId || '0');
				var enabled = id !== '0' && id === savedId;
				if (button) {
					button.hidden = !enabled;
					button.disabled = !enabled;
				}
			};

			var setAdvancedControls = function (params) {
				if (!advancedEl) return;
				Object.keys(stickerParamConfig).forEach(function (key) {
					var row = advancedEl.querySelector('[data-sticker-param="' + key + '"]');
					if (!row) return;
					var value = formatStickerParam(key, params[key]);
					var range = row.querySelector('[data-sticker-param-range]');
					var number = row.querySelector('[data-sticker-param-number]');
					if (range) range.value = value;
					if (number) number.value = value;
				});
			};

			var readAdvancedControls = function () {
				var params = {};
				if (!advancedEl) return Object.assign({}, stickerDefaults);
				Object.keys(stickerParamConfig).forEach(function (key) {
					var row = advancedEl.querySelector('[data-sticker-param="' + key + '"]');
					var number = row ? row.querySelector('[data-sticker-param-number]') : null;
					params[key] = clampStickerParam(key, number ? number.value : stickerDefaults[key], stickerDefaults[key]);
				});
				return params;
			};

			var normalizeAdvancedRow = function (row, source) {
				if (!row) return;
				var key = row.dataset.stickerParam;
				var range = row.querySelector('[data-sticker-param-range]');
				var number = row.querySelector('[data-sticker-param-number]');
				var previous = number && number.dataset.validValue !== undefined ? number.dataset.validValue : stickerDefaults[key];
				var raw = source && source.value !== '' ? source.value : previous;
				var value = formatStickerParam(key, raw);
				if (range) range.value = value;
				if (number) {
					number.value = value;
					number.dataset.validValue = value;
				}
			};

			var openStickerAdvanced = function (slot) {
				if (!slot || !advancedEl || !advancedModal) return;
				var info = stickerInfoFromSlot(slot);
				if (info.id === '0') return;
				activeAdvancedSlot = slot;
				var valueInput = slot.querySelector('[data-sticker-value]');
				var parts = parseStickerValue(valueInput ? valueInput.value : defaultStickerValueForClient(info.id));
				var titleSlot = slot.dataset.slotNumber || String((parseInt(slot.dataset.stickerSlotIndex, 10) || 0) + 1);
				if (advancedTitle) advancedTitle.textContent = advancedTitleTemplate.replace('{slot}', titleSlot);
				if (advancedName) advancedName.textContent = info.name || '';
				var defindexInput = advancedEl.querySelector('[data-sticker-advanced-defindex]');
				var slotInput = advancedEl.querySelector('[data-sticker-advanced-slot]');
				if (defindexInput) defindexInput.value = slot.dataset.weaponDefindex || '';
				if (slotInput) slotInput.value = slot.dataset.stickerSlotIndex || '0';
				setAdvancedControls(parts);
				setStickerUnderlay(slot.closest('.modal'));
				advancedModal.show();
				setTimeout(markStickerBackdrop, 0);
			};

			var saveStickerChoice = function (slot, id) {
				if (!window.fetch || !slot) return Promise.resolve(null);
				var form = slot.closest('form');
				var formData = new FormData();
				var idInput = form ? form.querySelector('input[name="id"]') : null;
				var teamInput = form ? form.querySelector('input[name="team"]') : null;
				formData.append('action', 'save_sticker_choice');
				formData.append('id', idInput ? idInput.value : '');
				formData.append('team', teamInput ? teamInput.value : '1');
				formData.append('weapon_defindex', slot.dataset.weaponDefindex || '0');
				formData.append('sticker_slot', slot.dataset.stickerSlotIndex || '0');
				formData.append('sticker_id', String(id || '0'));
				formData.append('ajax', '1');
				return fetch(window.location.href, {
					method: 'POST',
					body: formData,
					headers: { 'X-Requested-With': 'fetch', 'X-CSRF-Token': window.cs2CsrfToken, 'Accept': 'application/json' }
				}).then(function (response) {
					return response.ok ? response.json() : Promise.reject();
				}).then(function (payload) {
					if (!payload || !payload.ok) throw new Error(payload && payload.message ? payload.message : stickerSaveFailedMessage);
					return payload;
				});
			};
			if (pickerEl) {
				pickerEl.addEventListener('shown.bs.modal', function () {
					markStickerBackdrop();
					observePickerImages(pickerEl);
				});
				pickerEl.addEventListener('hidden.bs.modal', function () {
					setStickerUnderlay(null);
				});
			}
			var setStickerPickerLoading = function (loading) {
				if (pickerBody) {
					pickerBody.classList.toggle('is-loading', loading);
					pickerBody.setAttribute('aria-busy', loading ? 'true' : 'false');
				}
				if (loadingState) loadingState.hidden = !loading;
			};

			var loadStickers = function () {
				if (stickerData) return Promise.resolve(stickerData);
				if (stickerDataPromise) return stickerDataPromise;
				stickerDataPromise = Promise.all([
					fetchJson(window.cs2StickerDataUrl),
					fetchOptionalJson(window.cs2StickerAliasDataUrl)
				]).then(function (payloads) {
						var items = payloads[0] || [];
						var aliases = payloads[1] || [];
						var aliasById = {};
						var seen = {};
						aliases.forEach(function (item) {
							aliasById[parseInt(item.id, 10) || 0] = item.name || '';
						});
						stickerData = [{ id: 0, name: appText.noSticker, image: '' }].concat(items.map(function (item) {
							var id = parseInt(item.id, 10) || 0;
							var name = item.name || '';
							var alias = aliasById[id] || '';
							seen[id] = true;
							return { id: id, name: name, image: item.image || '', searchText: name + ' ' + alias };
						}));
						aliases.forEach(function (item) {
							var id = parseInt(item.id, 10) || 0;
							if (!id || seen[id]) return;
							stickerData.push({ id: id, name: item.name || '', image: item.image || '', searchText: item.name || '' });
						});
						stickerDataPromise = null;
						return stickerData;
					}).catch(function (error) {
						stickerDataPromise = null;
						throw error;
					});
				return stickerDataPromise;
			};

			var renderStickerResults = function () {
				if (!resultsEl || !stickerData) return;
				var query = (searchInput ? searchInput.value : '').trim().toLowerCase();
				var terms = query ? query.split(/\s+/).filter(Boolean) : [];
				var matched = stickerData.filter(function (item) {
					var searchText = (item.searchText || item.name || '').toLowerCase();
					return !query || String(item.id) === query || terms.every(function (term) {
						return searchText.indexOf(term) !== -1;
					});
				});
				var shown = matched.slice(0, stickerResultLimit);
				resultsEl.innerHTML = '';
				shown.forEach(function (item) {
					var button = document.createElement('button');
					button.type = 'button';
					button.className = 'sticker-result';
					button.dataset.stickerId = String(item.id);
					button.dataset.stickerName = item.name;
					button.dataset.stickerImage = item.image || '';
					if (item.image) {
						var imageStage = createImageCrossfadeStage('', '', 220);
						attachImageSpinnerFallback(imageStage);
						var image = imageStage.querySelector('.image-crossfade-layer.is-active');
						image.dataset.remoteSrc = item.image;
						button.appendChild(imageStage);
					} else {
						var empty = document.createElement('span');
						empty.className = 'sticker-empty-icon';
						empty.textContent = '+';
						button.appendChild(empty);
					}
					var name = document.createElement('span');
					name.textContent = item.name;
					button.appendChild(name);
					resultsEl.appendChild(button);
				});
				if (matched.length > shown.length) {
					var searchHint = document.createElement('p');
					searchHint.className = 'picker-search-more-hint';
					searchHint.textContent = resultsEl.dataset.searchMoreHint || '';
					resultsEl.appendChild(searchHint);
				}
				observePickerImages(pickerEl);
			};

			var scheduleStickerResultsRender = function () {
				if (stickerSearchTimer !== null) {
					window.clearTimeout(stickerSearchTimer);
				}
				stickerSearchTimer = window.setTimeout(function () {
					stickerSearchTimer = null;
					renderStickerResults();
				}, 100);
			};

			var stickerSlotsIn = function (scope) {
				return Array.prototype.slice.call(scope ? scope.querySelectorAll('.sticker-slot') : []);
			};

			var setStickerSlot = function (slot, id, name, image) {
				if (!slot) return;
				var input = slot.querySelector('[data-sticker-input]');
				var valueInput = slot.querySelector('[data-sticker-value]');
				var preview = slot.querySelector('[data-sticker-preview]');
				var plus = slot.querySelector('.sticker-plus');
				var label = slot.querySelector('[data-sticker-name]');
				var labelText = slot.querySelector('[data-sticker-name-text]');
				id = String(id || '0');
				image = image || '';
				if (input) input.value = id;
				if (valueInput) valueInput.value = id === '0' ? '0;0;0;0;0;0;0' : defaultStickerValueForClient(id);
				if (preview) {
					preview.src = 'img/skins/sticker.png';
					preview.dataset.remoteSrc = image;
					preview.hidden = id === '0' || !image;
					loadRemoteImage(preview, true);
				}
				if (plus) plus.hidden = id !== '0' && !!image;
				if (labelText) {
					labelText.textContent = id === '0' ? (slot.dataset.emptyLabel || appText.stickerSlot) : name;
				} else if (label) {
					label.textContent = id === '0' ? (slot.dataset.emptyLabel || appText.stickerSlot) : name;
				}
				syncStickerSettingsButton(slot);
			};
			var stickerInfoFromSlot = function (slot) {
				var input = slot ? slot.querySelector('[data-sticker-input]') : null;
				var preview = slot ? slot.querySelector('[data-sticker-preview]') : null;
				var label = slot ? (slot.querySelector('[data-sticker-name-text]') || slot.querySelector('[data-sticker-name]')) : null;
				return {
					id: input ? String(input.value || '0') : '0',
					name: label ? label.textContent : '',
					image: preview ? (preview.dataset.remoteSrc || '') : ''
				};
			};

			var syncStickerToolButtons = function (section) {
				if (!section) return;
				var fillButton = section.querySelector('[data-sticker-fill-all]');
				var clearButton = section.querySelector('[data-sticker-clear-all]');
				var unique = {};
				var hasSticker = false;
				stickerSlotsIn(section).forEach(function (slot) {
					var info = stickerInfoFromSlot(slot);
					if (info.id !== '0') {
						unique[info.id] = true;
						hasSticker = true;
					}
				});
				if (fillButton) fillButton.disabled = Object.keys(unique).length !== 1;
				if (clearButton) clearButton.disabled = !hasSticker;
			};

			document.querySelectorAll('.sticker-section').forEach(syncStickerToolButtons);

			document.addEventListener('click', function (event) {
				var fillAllButton = event.target.closest('[data-sticker-fill-all]');
				if (fillAllButton) {
					if (fillAllButton.disabled) return;
					var fillSection = fillAllButton.closest('.sticker-section');
					var slots = stickerSlotsIn(fillSection);
					var source = null;
					slots.some(function (slot) {
						var info = stickerInfoFromSlot(slot);
						if (info.id !== '0') {
							source = info;
							return true;
						}
						return false;
					});
					if (!source) return;
					slots.forEach(function (slot) {
						setStickerSlot(slot, source.id, source.name, source.image);
					});
					syncStickerToolButtons(fillSection);
					return;
				}

				var clearAllButton = event.target.closest('[data-sticker-clear-all]');
				if (clearAllButton) {
					if (clearAllButton.disabled) return;
					var clearSection = clearAllButton.closest('.sticker-section');
					stickerSlotsIn(clearSection).forEach(function (slot) {
						setStickerSlot(slot, '0', appText.noSticker, '');
					});
					syncStickerToolButtons(clearSection);
					return;
				}

				var settingsButton = event.target.closest('[data-sticker-settings]');
				if (settingsButton) {
					if (settingsButton.disabled) return;
					openStickerAdvanced(settingsButton.closest('.sticker-slot'));
					return;
				}
				var openButton = event.target.closest('[data-sticker-open]');
				if (openButton) {
					activeStickerSlot = openButton.closest('.sticker-slot');
					setStickerUnderlay(openButton.closest('.modal'));
					if (stickerSearchTimer !== null) {
						window.clearTimeout(stickerSearchTimer);
						stickerSearchTimer = null;
					}
					if (searchInput) searchInput.value = '';
					var needsLoading = !stickerData;
					setStickerPickerLoading(needsLoading);
					if (!needsLoading) renderStickerResults();
					if (picker) {
						picker.show();
						setTimeout(markStickerBackdrop, 0);
					}
					if (!needsLoading) {
						setTimeout(function () { if (searchInput) searchInput.focus(); }, 150);
						return;
					}
					loadStickers().then(function () {
						renderStickerResults();
						setStickerPickerLoading(false);
						setTimeout(function () { if (searchInput) searchInput.focus(); }, 150);
					}).catch(function (error) {
						setStickerPickerLoading(false);
						if (picker) picker.hide();
						activeStickerSlot = null;
						setStickerUnderlay(null);
						if (window.console && console.error) console.error(error);
						alert(dataLoadFailedMessage);
					});
					return;
				}

				var resultButton = event.target.closest('[data-sticker-id]');
				if (resultButton && activeStickerSlot) {
					var id = resultButton.dataset.stickerId || '0';
					var name = resultButton.dataset.stickerName || appText.noSticker;
					var image = resultButton.dataset.stickerImage || '';
					saveStickerChoice(activeStickerSlot, id).then(function (payload) {
						setStickerSlot(activeStickerSlot, id, name, image);
						var valueInput = activeStickerSlot.querySelector('[data-sticker-value]');
						if (valueInput && payload && payload.value) valueInput.value = payload.value;
						activeStickerSlot.dataset.savedStickerId = String(id || '0');
						syncStickerSettingsButton(activeStickerSlot);
						syncStickerToolButtons(activeStickerSlot.closest('.sticker-section'));
						if (picker) picker.hide();
						showFloatingNotice(appText.stickerSelectionSaved);
					}).catch(function (error) {
						alert(error && error.message ? error.message : stickerSaveFailedMessage);
					});
				}			});

			document.querySelectorAll('[data-sticker-settings]').forEach(function (button) {
				syncStickerSettingsButton(button.closest('.sticker-slot'));
			});

			if (advancedEl) {
				advancedEl.addEventListener('shown.bs.modal', markStickerBackdrop);
				advancedEl.addEventListener('hidden.bs.modal', function () {
					setStickerUnderlay(null);
				});
				advancedEl.querySelectorAll('[data-sticker-param]').forEach(function (row) {
					var range = row.querySelector('[data-sticker-param-range]');
					var number = row.querySelector('[data-sticker-param-number]');
					if (range) {
						range.addEventListener('input', function () {
							normalizeAdvancedRow(row, range);
						});
					}
					if (number) {
						number.addEventListener('input', function () {
							var numeric = parseFloat(number.value);
							if (isFinite(numeric) && range) range.value = numeric;
						});
						number.addEventListener('change', function () {
							normalizeAdvancedRow(row, number);
						});
						normalizeAdvancedRow(row, number);
					}
				});
				var resetButton = advancedEl.querySelector('[data-sticker-advanced-reset]');
				if (resetButton) {
					resetButton.addEventListener('click', function () {
						setAdvancedControls(stickerDefaults);
					});
				}
			}

			if (advancedForm) {
				advancedForm.addEventListener('submit', function (event) {
					if (!window.fetch || !activeAdvancedSlot) return;
					event.preventDefault();
					setAdvancedControls(readAdvancedControls());
					var formData = new FormData(advancedForm);
					formData.append('ajax', '1');
					fetch(window.location.href, {
						method: 'POST',
						body: formData,
						headers: { 'X-Requested-With': 'fetch', 'X-CSRF-Token': window.cs2CsrfToken, 'Accept': 'application/json' }
					}).then(function (response) {
						return response.ok ? response.json() : Promise.reject();
					}).then(function (payload) {
						if (!payload || !payload.ok) throw new Error(payload && payload.message ? payload.message : stickerSaveFailedMessage);
						var valueInput = activeAdvancedSlot.querySelector('[data-sticker-value]');
						if (valueInput) valueInput.value = payload.value || valueInput.value;
						var parts = parseStickerValue(payload.value || '');
						activeAdvancedSlot.dataset.savedStickerId = String(parts.id || '0');
						syncStickerSettingsButton(activeAdvancedSlot);
						if (advancedModal) advancedModal.hide();
						showFloatingNotice(appText.stickerSettingsSaved);
					}).catch(function (error) {
						alert(error && error.message ? error.message : stickerSaveFailedMessage);
					});
				});
			}

			if (searchInput) {
				searchInput.addEventListener('input', scheduleStickerResultsRender);
			}

			if (pickerEl && document.querySelector('[data-sticker-open]') && typeof scheduleIdleTask === 'function') {
				scheduleIdleTask(loadStickers);
			}

	app.stickers = {
		pickerEl: pickerEl, picker: picker,
		advancedEl: advancedEl, advancedModal: advancedModal
	};

})(window.cs2App = window.cs2App || {});

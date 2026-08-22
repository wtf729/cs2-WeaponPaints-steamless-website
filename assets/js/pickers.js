(function (app) {
	'use strict';
	var appConfig = app.config || {};
	var appText = appConfig.text || {};
	var observePickerImages = app.observePickerImages;
			var applyPickerFilter = function (modal) {
				if (!modal) return;
				var input = modal.querySelector('[data-picker-search]');
				var query = input ? input.value.trim().toLowerCase() : '';
				var terms = query ? query.split(/\s+/).filter(Boolean) : [];
				modal.querySelectorAll('[data-picker-result]').forEach(function (result) {
					var searchText = (result.dataset.search || result.textContent || '').toLowerCase();
					var id = result.value || result.dataset.id || '';
					result.hidden = !!query && String(id) !== query && !terms.every(function (term) {
						return searchText.indexOf(term) !== -1;
					});
				});
			};
			var filterPickerResults = function (modal) {
				applyPickerFilter(modal);
				observePickerImages(modal);
			};
			var setDeferredPickerLoading = function (modal, loading) {
				if (!modal) return;
				var body = modal.querySelector('[data-picker-deferred-body]');
				var loadingState = modal.querySelector('[data-picker-deferred-loading]');
				if (body) {
					body.classList.toggle('is-loading', loading);
					body.setAttribute('aria-busy', loading ? 'true' : 'false');
				}
				if (loadingState) loadingState.hidden = !loading;
			};
			document.querySelectorAll('.skin-picker-modal, .agent-picker-modal').forEach(function (modal) {
				var search = modal.querySelector('[data-picker-search]');
				var deferredInitialization = modal.hasAttribute('data-picker-deferred-init');
				var initialized = !deferredInitialization;
				var initializationFrame = null;
				var focusFrame = null;
				if (search) {
					search.addEventListener('input', function () {
						if (!initialized) return;
						filterPickerResults(modal);
					});
				}
				modal.addEventListener('show.bs.modal', function () {
					if (deferredInitialization) {
						if (!initialized) setDeferredPickerLoading(modal, true);
						return;
					}
					if (search) search.value = '';
					filterPickerResults(modal);
				});
				modal.addEventListener('shown.bs.modal', function () {
					if (deferredInitialization && !initialized) {
						initializationFrame = window.requestAnimationFrame(function () {
							initializationFrame = null;
							if (!modal.classList.contains('show')) return;
							if (search) search.value = '';
							applyPickerFilter(modal);
							observePickerImages(modal);
							initialized = true;
							setDeferredPickerLoading(modal, false);
							focusFrame = window.requestAnimationFrame(function () {
								focusFrame = null;
								if (search && modal.classList.contains('show')) search.focus();
							});
						});
						return;
					}
					if (deferredInitialization) {
						if (search) search.focus();
						return;
					}
					observePickerImages(modal);
					if (search) search.focus();
				});
				modal.addEventListener('hidden.bs.modal', function () {
					if (initializationFrame !== null) {
						window.cancelAnimationFrame(initializationFrame);
						initializationFrame = null;
					}
					if (focusFrame !== null) {
						window.cancelAnimationFrame(focusFrame);
						focusFrame = null;
					}
					if (!deferredInitialization || !initialized || !search || search.value === '') return;
					search.value = '';
					applyPickerFilter(modal);
				});
			});


})(window.cs2App = window.cs2App || {});

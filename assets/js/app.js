(function (app) {
	'use strict';
	var appConfig = window.cs2AppConfig || {};
	var appText = appConfig.text || {};
	window.cs2CsrfToken = appConfig.csrfToken || '';
	window.cs2StickerDataUrl = appConfig.stickerDataUrl || '';
	window.cs2StickerAliasDataUrl = appConfig.stickerAliasDataUrl || '';
	window.cs2KeychainDataUrl = appConfig.keychainDataUrl || '';
	window.cs2KeychainAliasDataUrl = appConfig.keychainAliasDataUrl || '';
	window.cs2PaintKitDataUrl = appConfig.paintKitDataUrl || '';
	window.cs2PaintKitAliasDataUrl = appConfig.paintKitAliasDataUrl || '';
	window.cs2PaintKitFinishBadges = appConfig.paintKitFinishBadges || {};
			var floatingNoticeRegion = null;
			var showFloatingNotice = function (message) {
				if (!message) return;
				if (!floatingNoticeRegion) {
					floatingNoticeRegion = document.createElement('div');
					floatingNoticeRegion.className = 'floating-notice-region';
					floatingNoticeRegion.setAttribute('aria-live', 'polite');
					floatingNoticeRegion.setAttribute('aria-atomic', 'true');
					document.body.appendChild(floatingNoticeRegion);
				}
				var notice = document.createElement('div');
				notice.className = 'floating-notice';
				notice.setAttribute('role', 'status');
				notice.setAttribute('data-floating-notice', '');
				notice.textContent = message;
				floatingNoticeRegion.appendChild(notice);
				window.requestAnimationFrame(function () {
					window.requestAnimationFrame(function () {
						notice.classList.add('is-visible');
						window.setTimeout(function () {
							if (!notice.isConnected) return;
							var removalFallback = null;
							var finishRemoval = function () {
								if (removalFallback !== null) window.clearTimeout(removalFallback);
								notice.removeEventListener('transitionend', handleTransitionEnd);
								if (notice.parentNode) notice.parentNode.removeChild(notice);
								if (floatingNoticeRegion && !floatingNoticeRegion.firstChild) {
									floatingNoticeRegion.parentNode.removeChild(floatingNoticeRegion);
									floatingNoticeRegion = null;
								}
							};
							var handleTransitionEnd = function (event) {
								if (event.propertyName === 'opacity') finishRemoval();
							};
							notice.addEventListener('transitionend', handleTransitionEnd);
							notice.classList.remove('is-visible');
							notice.classList.add('is-dismissing');
							removalFallback = window.setTimeout(finishRemoval, 400);
						}, 3000);
					});
				});
			};
			app.showFloatingNotice = showFloatingNotice;
			if (appConfig.floatingNotice) {
				showFloatingNotice(appConfig.floatingNotice);
			}
			if (appConfig.showAdminAuthenticatedNotice === true) {
				showFloatingNotice(appText.adminAuthenticated || '');
			}
			var themeToggle = document.querySelector('[data-theme-toggle]');
			if (themeToggle) {
				var themeTransitionTimer = null;
				var syncThemeToggle = function () {
					var currentTheme = document.documentElement.dataset.bsTheme === 'light' ? 'light' : 'dark';
					var label = currentTheme === 'dark' ? themeToggle.dataset.lightLabel : themeToggle.dataset.darkLabel;
					themeToggle.setAttribute('aria-label', label);
					themeToggle.setAttribute('title', label);
					themeToggle.setAttribute('aria-pressed', currentTheme === 'light' ? 'true' : 'false');
				};
				themeToggle.addEventListener('click', function () {
					var nextTheme = document.documentElement.dataset.bsTheme === 'light' ? 'dark' : 'light';
					var applyTheme = function () {
						document.documentElement.dataset.bsTheme = nextTheme;
						try {
							window.localStorage.setItem('cs2_wp_theme', nextTheme);
						} catch (error) {
							// The current page still switches even when persistence is unavailable.
						}
						syncThemeToggle();
					};
					if (typeof document.startViewTransition === 'function') {
						document.startViewTransition(applyTheme);
						return;
					}
					document.documentElement.classList.add('theme-transitioning');
					window.requestAnimationFrame(applyTheme);
					if (themeTransitionTimer !== null) window.clearTimeout(themeTransitionTimer);
					themeTransitionTimer = window.setTimeout(function () {
						document.documentElement.classList.remove('theme-transitioning');
						themeTransitionTimer = null;
					}, 1000);
				});
				syncThemeToggle();
			}

			var indicatorFitFrame = null;
			var fitStatusIndicatorRows = function () {
				document.querySelectorAll('.card-status-badges, .fusion-result-indicators').forEach(function (row) {
					row.style.transform = '';
					var parent = row.parentElement;
					if (!parent || parent.clientWidth <= 0) return;
					var rightInset = parseFloat(window.getComputedStyle(row).right) || 0;
					var availableWidth = Math.max(0, parent.clientWidth - (rightInset * 2));
					var requiredWidth = row.scrollWidth;
					if (requiredWidth > availableWidth && availableWidth > 0) {
						row.style.transform = 'scale(' + (availableWidth / requiredWidth) + ')';
					}
				});
			};
			var scheduleStatusIndicatorFit = function () {
				if (indicatorFitFrame !== null) window.cancelAnimationFrame(indicatorFitFrame);
				indicatorFitFrame = window.requestAnimationFrame(function () {
					indicatorFitFrame = null;
					fitStatusIndicatorRows();
				});
			};
			scheduleStatusIndicatorFit();
			window.addEventListener('resize', scheduleStatusIndicatorFit);
			if (document.fonts && document.fonts.ready) {
				document.fonts.ready.then(scheduleStatusIndicatorFit);
			}
			document.querySelectorAll('[data-loadout-password-toggle]').forEach(function (toggle) {
				var form = toggle.closest('form');
				var wrap = form ? form.querySelector('[data-loadout-password-input-wrap]') : null;
				var input = form ? form.querySelector('[data-loadout-password-input]') : null;
				var panel = form ? form.closest('.loadout-info-panel') : null;
				var status = panel ? panel.querySelector('[data-loadout-password-status]') : null;
				var sync = function () {
					if (wrap) wrap.classList.toggle('is-inactive', !toggle.checked);
					if (input) {
						input.disabled = !toggle.checked;
						input.required = toggle.checked && input.hasAttribute('data-loadout-password-required-when-enabled');
					}
					if (status) {
						status.classList.toggle('active', toggle.checked);
						status.textContent = toggle.checked ? status.dataset.enabledLabel : status.dataset.disabledLabel;
					}
				};
				toggle.addEventListener('change', sync);
				sync();
			});

			var loadoutPasswordModalEl = document.getElementById('loadoutPasswordModal');
			if (loadoutPasswordModalEl) {
				loadoutPasswordModalEl.addEventListener('show.bs.modal', function (event) {
					var trigger = event.relatedTarget;
					if (!trigger) return;
					loadoutPasswordModalEl.querySelector('[data-loadout-password-id-input]').value = trigger.dataset.loadoutPasswordId || '';
					loadoutPasswordModalEl.querySelector('[data-loadout-password-team-input]').value = trigger.dataset.loadoutPasswordTeam || '1';
					loadoutPasswordModalEl.querySelector('[data-loadout-password-label]').textContent = trigger.dataset.loadoutPasswordLabel || '';
					loadoutPasswordModalEl.querySelector('[data-loadout-password-error]').classList.toggle('d-none', trigger.dataset.loadoutPasswordError !== '1');
				});
				loadoutPasswordModalEl.addEventListener('shown.bs.modal', function () {
					var input = loadoutPasswordModalEl.querySelector('[data-loadout-password-modal-input]');
					if (input) input.focus();
				});
				loadoutPasswordModalEl.addEventListener('hidden.bs.modal', function () {
					var input = loadoutPasswordModalEl.querySelector('[data-loadout-password-modal-input]');
					if (input) input.value = '';
				});
				var requestedLoadoutPasswordId = appConfig.requestedLoadoutPasswordId || '';
				if (requestedLoadoutPasswordId) {
					var trigger = document.querySelector('[data-loadout-password-id="' + CSS.escape(requestedLoadoutPasswordId) + '"]');
					if (trigger) {
						trigger.dataset.loadoutPasswordTeam = String(appConfig.requestedLoadoutPasswordTeam || '1');
						if (appConfig.hasLoadoutPasswordError === true) trigger.dataset.loadoutPasswordError = '1';
						bootstrap.Modal.getOrCreateInstance(loadoutPasswordModalEl).show(trigger);
					}
				}
			}

			var adminModalEl = document.getElementById('adminModal');
			var siteSettingsSuccess = document.querySelector('[data-site-settings-success]');
			if (siteSettingsSuccess) {
				var scheduleSiteSettingsSuccessDismissal = function () {
					window.setTimeout(function () {
						if (!siteSettingsSuccess.isConnected) return;
						var removalFallback = null;
						var finishRemoval = function () {
							if (removalFallback !== null) window.clearTimeout(removalFallback);
							siteSettingsSuccess.removeEventListener('transitionend', handleTransitionEnd);
							if (siteSettingsSuccess.parentNode) siteSettingsSuccess.parentNode.removeChild(siteSettingsSuccess);
						};
						var handleTransitionEnd = function (event) {
							if (event.propertyName === 'max-height') finishRemoval();
						};
						siteSettingsSuccess.addEventListener('transitionend', handleTransitionEnd);
						siteSettingsSuccess.classList.add('is-dismissing');
						removalFallback = window.setTimeout(finishRemoval, 600);
					}, 3000);
				};
				if (adminModalEl) {
					adminModalEl.addEventListener('shown.bs.modal', scheduleSiteSettingsSuccessDismissal, { once: true });
				} else {
					scheduleSiteSettingsSuccessDismissal();
				}
			}

			if ((appConfig.showAdminModal || appConfig.showAdminError) && adminModalEl) {
				bootstrap.Modal.getOrCreateInstance(adminModalEl).show();
			}

			var siteSettingsForm = document.getElementById('siteSettingsForm');
			if (siteSettingsForm) {
				var siteNameInputs = Array.from(siteSettingsForm.querySelectorAll('[data-site-name-input]'));
				var serverAddressInput = siteSettingsForm.querySelector('[data-server-address-input]');
				var serverPasswordInput = siteSettingsForm.querySelector('[data-server-password-input]');
				var isValidServerAddress = function (value) {
					value = value.trim();
					if (value === '') return true;
					var bracketed = value.match(/^\[([^\]]+)\]:(\d{1,5})$/);
					var standard = value.match(/^([^:]+):(\d{1,5})$/);
					var match = bracketed || standard;
					if (!match) return false;
					var port = Number(match[2]);
					if (!Number.isInteger(port) || port < 1 || port > 65535) return false;
					var host = match[1];
					if (bracketed) {
						if (!/^[0-9A-Fa-f:.]+$/.test(host) || host.indexOf(':') === -1) return false;
						try {
							new URL('http://[' + host + ']:' + port + '/');
							return true;
						} catch (error) {
							return false;
						}
					}
					var ipv4Parts = host.split('.');
					var isIpv4 = ipv4Parts.length === 4 && ipv4Parts.every(function (part) {
						return /^\d{1,3}$/.test(part) && Number(part) >= 0 && Number(part) <= 255;
					});
					var isHostname = /^(?=.{1,253}$)(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\.)*[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?$/.test(host);
					return isIpv4 || isHostname;
				};
				var validateSiteSettings = function () {
					siteNameInputs.forEach(function (input) { input.setCustomValidity(''); });
					if (siteNameInputs.length > 0 && !siteNameInputs.some(function (input) { return input.value.trim() !== ''; })) {
						siteNameInputs[0].setCustomValidity(appText.siteSettingsNameRequired || '');
					}
					if (serverAddressInput) {
						serverAddressInput.setCustomValidity(isValidServerAddress(serverAddressInput.value)
							? ''
							: (appText.serverAddressInvalid || ''));
					}
					if (serverPasswordInput) {
						var password = serverPasswordInput.value.trim();
						var passwordIsValid = password === '' || (password.length <= 128 && !/[\x00-\x20\x7F;"\\]/.test(password));
						serverPasswordInput.setCustomValidity(passwordIsValid
							? ''
							: (appText.serverPasswordInvalid || ''));
					}
				};
				var preventInvalidSiteSettingsSubmit = function (event) {
					validateSiteSettings();
					if (!siteSettingsForm.checkValidity()) {
						event.preventDefault();
						siteSettingsForm.reportValidity();
					}
				};
				siteSettingsForm.addEventListener('submit', preventInvalidSiteSettingsSubmit);
				var siteSettingsSubmitButton = document.querySelector('[type="submit"][form="siteSettingsForm"]');
				if (siteSettingsSubmitButton) {
					siteSettingsSubmitButton.addEventListener('click', preventInvalidSiteSettingsSubmit);
				}
				siteNameInputs.concat([serverAddressInput, serverPasswordInput].filter(Boolean)).forEach(function (input) {
					input.addEventListener('input', function () {
						input.setCustomValidity('');
					});
				});
			}

			var serverCommandModalEl = document.getElementById('serverCommandModal');
			var serverCommandStatus = serverCommandModalEl ? serverCommandModalEl.querySelector('[data-server-command-status]') : null;
			var serverCommandDisplay = serverCommandModalEl ? serverCommandModalEl.querySelector('[data-server-command-display]') : null;
			var fallbackCopyText = function (text) {
				var textarea = document.createElement('textarea');
				textarea.value = text;
				textarea.setAttribute('readonly', '');
				textarea.style.position = 'fixed';
				textarea.style.opacity = '0';
				document.body.appendChild(textarea);
				textarea.select();
				var copied = false;
				try {
					copied = document.execCommand('copy');
				} catch (error) {
					copied = false;
				}
				textarea.remove();
				return copied;
			};
			var copyServerCommand = function (command) {
				if (navigator.clipboard && window.isSecureContext) {
					return navigator.clipboard.writeText(command).then(function () {
						return true;
					}).catch(function () {
						return fallbackCopyText(command);
					});
				}
				return Promise.resolve(fallbackCopyText(command));
			};
			document.querySelectorAll('[data-server-command]').forEach(function (button) {
				button.addEventListener('click', function () {
					var command = button.dataset.serverCommand || '';
					if (!command || !serverCommandModalEl) return;
					if (serverCommandDisplay) serverCommandDisplay.value = command;
					copyServerCommand(command).then(function (copied) {
						if (serverCommandStatus) {
							serverCommandStatus.textContent = copied
								? (appText.serverCommandCopied || '')
								: (appText.serverCommandCopyFailed || '');
						}
						bootstrap.Modal.getOrCreateInstance(serverCommandModalEl).show(button);
						if (copied) showFloatingNotice(appText.serverCommandClipboardNotice || '');
					});
				});
			});
			if (serverCommandDisplay) {
				serverCommandDisplay.addEventListener('focus', function () {
					serverCommandDisplay.select();
				});
			}

			var params = new URLSearchParams(location.search);
			var key = 'cs2_wp_scroll_' + location.pathname + '_' + (params.get('action') || '') + '_' + (params.get('id') || '') + '_' + (params.get('team') || '');
			window.rememberScrollPosition = function () {
				sessionStorage.setItem(key, String(window.scrollY || window.pageYOffset || 0));
			};
			var savedY = sessionStorage.getItem(key);
			if (savedY !== null) {
				sessionStorage.removeItem(key);
				if ('scrollRestoration' in history) {
					history.scrollRestoration = 'manual';
				}
				var html = document.documentElement;
				var previousScrollBehavior = html.style.scrollBehavior;
				html.style.scrollBehavior = 'auto';
				window.scrollTo(0, parseInt(savedY, 10) || 0);
				requestAnimationFrame(function () {
					html.style.scrollBehavior = previousScrollBehavior;
				});
			}

			document.addEventListener('submit', function () {
				window.rememberScrollPosition();
			}, true);

	app.scheduleStatusIndicatorFit = scheduleStatusIndicatorFit;

	var idleTaskQueue = [];
	var idleTaskScheduled = false;
	var scheduleNextIdleTask = function () {
		if (idleTaskScheduled || !idleTaskQueue.length) return;
		idleTaskScheduled = true;
		var runTask = function () {
			idleTaskScheduled = false;
			var task = idleTaskQueue.shift();
			var result;
			try {
				result = task();
			} catch (error) {
				if (window.console && console.warn) console.warn(error);
				scheduleNextIdleTask();
				return;
			}
			Promise.resolve(result).then(scheduleNextIdleTask, function (error) {
				if (window.console && console.warn) console.warn(error);
				scheduleNextIdleTask();
			});
		};
		if (typeof window.requestIdleCallback === 'function') {
			window.requestIdleCallback(runTask, { timeout: 2500 });
			return;
		}
		window.setTimeout(runTask, 1200);
	};
	app.scheduleIdleTask = function (task) {
		if (typeof task !== 'function') return;
		idleTaskQueue.push(task);
		scheduleNextIdleTask();
	};


	app.fetchJson = function (url) {
		if (!url) return Promise.resolve([]);
		return fetch(url, { cache: 'no-cache' }).then(function (response) {
			if (!response.ok) throw new Error('HTTP ' + response.status + ': ' + url);
			return response.json();
		}).then(function (payload) {
			if (!Array.isArray(payload)) throw new Error('Invalid JSON payload: ' + url);
			return payload;
		});
	};
	app.fetchOptionalJson = function (url) {
		return app.fetchJson(url).catch(function (error) {
			if (window.console && console.warn) console.warn(error);
			return [];
		});
	};
	app.config = appConfig;

})(window.cs2App = window.cs2App || {});

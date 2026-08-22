(function (app) {
	'use strict';
	var appConfig = app.config || {};
	var appText = appConfig.text || {};
	var loadRemoteImage = app.loadRemoteImage;
	var observePickerImages = app.observePickerImages;
	var createImageCrossfadeStage = app.createImageCrossfadeStage;
	var attachImageSpinnerFallback = app.attachImageSpinnerFallback;
	var resetImageCrossfadeStage = app.resetImageCrossfadeStage;
	var prepareImageCrossfade = app.prepareImageCrossfade;
	var activateImageCrossfade = app.activateImageCrossfade;
	var setStickerUnderlay = app.setModalUnderlay;
	var markStickerBackdrop = app.markPickerBackdrop;
	var fetchJson = app.fetchJson;
	var fetchOptionalJson = app.fetchOptionalJson;
	var scheduleIdleTask = app.scheduleIdleTask;
	var scheduleStatusIndicatorFit = app.scheduleStatusIndicatorFit;
			var dataLoadFailedMessage = appText.dataLoadFailed;

			var fusionData = null;
			var fusionDataPromise = null;
			var fusionTargetDefindex = 0;
			var fusionTargetWeapon = '';
			var fusionOfficialPaints = {};
			var fusionParentModal = null;
			var fusionPickerEl = document.getElementById('fusionPickerModal');
			var fusionPicker = fusionPickerEl && window.bootstrap ? new bootstrap.Modal(fusionPickerEl) : null;
			var fusionPickerTitle = fusionPickerEl ? fusionPickerEl.querySelector('[data-fusion-picker-title]') : null;
			var fusionSearchInput = fusionPickerEl ? fusionPickerEl.querySelector('.fusion-search') : null;
			var fusionResultsEl = fusionPickerEl ? fusionPickerEl.querySelector('[data-fusion-results]') : null;
			var fusionPickerBody = fusionPickerEl ? fusionPickerEl.querySelector('[data-fusion-picker-body]') : null;
			var fusionLoadingState = fusionPickerEl ? fusionPickerEl.querySelector('[data-fusion-loading]') : null;
			var fusionSourceEl = document.getElementById('fusionSourceModal');
			var fusionSourceModal = fusionSourceEl && window.bootstrap ? new bootstrap.Modal(fusionSourceEl) : null;
			var fusionSourcePaintName = fusionSourceEl ? fusionSourceEl.querySelector('[data-fusion-source-paint-name]') : null;
			var fusionSourceResults = fusionSourceEl ? fusionSourceEl.querySelector('[data-fusion-source-results]') : null;
			var fusionSourceCountTemplate = appText.fusionSourceCount;
			var fusionNativeFinishLabel = appText.fusionNativeFinish;
			var fusionPickerTitleTemplate = appText.chooseFusionFinishFor;
			var fusionSearchTimer = null;
			var fusionCarouselTimer = null;
			var fusionCarouselGeneration = 0;
			var fusionCardCache = {};
			var fusionImageRecords = {};
			var fusionImageHighPriorityQueue = [];
			var fusionImageBackgroundQueue = [];
			var fusionImageActiveLoads = 0;
			var fusionImageActiveVisibleLoads = 0;
			var fusionImageActiveBackgroundLoads = 0;
			var fusionImageMaxConcurrentLoads = 20;
			var fusionImageMaxConcurrentBackgroundLoads = 10;
			var fusionVisibilityObserver = null;
			var fusionVisibilityFrame = null;
			var fusionVisibilityScrollBound = false;
			var fusionImageRetryDelay = 5000;
			var fusionImageMaxRetries = 5;
			var fusionTransitionDuration = 220;
			var fusionForm = document.getElementById('fusionSkinForm');
			var fusionFormaInput = fusionForm ? fusionForm.querySelector('[data-fusion-forma]') : null;
			var createPaintKitFinishBadge = function (paint) {
				var badge = window.cs2PaintKitFinishBadges && window.cs2PaintKitFinishBadges[String(paint)];
				if (!badge) return null;
				var element = document.createElement('span');
				element.className = 'paint-variant-badge paint-variant-' + badge.class;
				element.textContent = badge.label;
				return element;
			};
			var sortFusionImageQueues = function () {
				var byDistance = function (left, right) { return left.distance - right.distance; };
				fusionImageHighPriorityQueue.sort(byDistance);
				fusionImageBackgroundQueue.sort(byDistance);
			};
			var queueFusionImageRecord = function (record, highPriority, distance) {
				if (!record || record.status === 'ready' || record.status === 'failed') return;
				distance = Number(distance);
				if (!isFinite(distance)) distance = Number.POSITIVE_INFINITY;
				if (highPriority) record.highPriority = true;
				record.distance = Math.min(record.distance, distance);
				if (record.status === 'queued') {
					if (record.highPriority) {
						var queuedIndex = fusionImageBackgroundQueue.indexOf(record);
						if (queuedIndex !== -1) {
							fusionImageBackgroundQueue.splice(queuedIndex, 1);
							fusionImageHighPriorityQueue.push(record);
						}
					}
					sortFusionImageQueues();
					return;
				}
				if (record.status === 'loading' || record.status === 'retry-wait') return;
				record.status = 'queued';
				(record.highPriority ? fusionImageHighPriorityQueue : fusionImageBackgroundQueue).push(record);
				sortFusionImageQueues();
				runFusionImageQueue();
			};
			var runFusionImageQueue = function () {
				while (fusionImageActiveLoads < fusionImageMaxConcurrentLoads) {
					var record = fusionImageHighPriorityQueue.shift();
					if (!record && fusionImageActiveVisibleLoads === 0 && fusionImageActiveBackgroundLoads < fusionImageMaxConcurrentBackgroundLoads) {
						record = fusionImageBackgroundQueue.shift();
					}
					if (!record) return;
					if (record.status !== 'queued') continue;
					record.status = 'loading';
					record.activePriority = record.highPriority ? 'visible' : 'preload';
					record.attempts++;
					fusionImageActiveLoads++;
					if (record.activePriority === 'visible') fusionImageActiveVisibleLoads++;
					if (record.activePriority === 'preload') fusionImageActiveBackgroundLoads++;
					(function (currentRecord) {
						var image = new Image();
						var settled = false;
						var finish = function (loaded) {
							if (settled) return;
							settled = true;
							if (currentRecord.activePriority === 'visible') fusionImageActiveVisibleLoads--;
							if (currentRecord.activePriority === 'preload') fusionImageActiveBackgroundLoads--;
							currentRecord.activePriority = '';
							fusionImageActiveLoads--;
							if (loaded) {
								currentRecord.status = 'ready';
								currentRecord.resolve(true);
							} else if (currentRecord.retries < fusionImageMaxRetries) {
								currentRecord.retries++;
								currentRecord.status = 'retry-wait';
								currentRecord.retryTimer = setTimeout(function () {
									currentRecord.retryTimer = null;
									if (currentRecord.status !== 'retry-wait') return;
									currentRecord.status = 'idle';
									queueFusionImageRecord(currentRecord, currentRecord.highPriority, currentRecord.distance);
								}, fusionImageRetryDelay);
							} else {
								currentRecord.status = 'failed';
								currentRecord.resolve(false);
							}
							runFusionImageQueue();
						};
						image.onload = function () {
							if (typeof image.decode !== 'function') {
								finish(true);
								return;
							}
							image.decode().then(function () { finish(true); }).catch(function () { finish(true); });
						};
						image.onerror = function () { finish(false); };
						image.src = currentRecord.url;
						setTimeout(function () { finish(false); }, 10000);
					})(record);
				}
			};
			var preloadFusionImage = function (url, highPriority, distance) {
				if (!url) return Promise.resolve(false);
				var existingRecord = fusionImageRecords[url];
				if (existingRecord) {
					queueFusionImageRecord(existingRecord, highPriority, distance);
					return existingRecord.promise;
				}
				var record = {
					url: url,
					status: 'idle',
					attempts: 0,
					retries: 0,
					highPriority: !!highPriority,
					distance: isFinite(Number(distance)) ? Number(distance) : Number.POSITIVE_INFINITY,
					activePriority: '',
					retryTimer: null,
					resolve: null,
					promise: null
				};
				record.promise = new Promise(function (resolve) {
					record.resolve = resolve;
				});
				fusionImageRecords[url] = record;
				queueFusionImageRecord(record, highPriority, distance);
				return record.promise;
			};
			var fusionImageIsReady = function (url) {
				return !!url && !!fusionImageRecords[url] && fusionImageRecords[url].status === 'ready';
			};
			var normalizeFusionSources = function (item) {
				var sources = Array.isArray(item.sources) ? item.sources : [];
				if (!sources.length && (item.source_name || item.image)) {
					sources = [{
						source_name: item.source_name || item.name || '',
						source_weapon: item.source_weapon || '',
						source_defindex: parseInt(item.source_defindex, 10) || 0,
						image: item.image || ''
					}];
				}
				return sources.map(function (source) {
					return {
						sourceName: source.source_name || item.name || '',
						sourceWeapon: source.source_weapon || '',
						sourceDefindex: parseInt(source.source_defindex, 10) || 0,
						image: source.image || ''
					};
				});
			};
			var fusionAliasText = function (item) {
				return [item.name || ''].concat(normalizeFusionSources(item).map(function (source) {
					return source.sourceName + ' ' + source.sourceWeapon;
				})).join(' ').trim();
			};
			var fusionFinishName = function (name) {
				var parts = String(name || '').split('|');
				return (parts.length > 1 ? parts.slice(1).join('|') : parts[0]).trim();
			};
			var setFusionPickerLoading = function (loading) {
				if (fusionPickerBody) {
					fusionPickerBody.classList.toggle('is-loading', loading);
					fusionPickerBody.setAttribute('aria-busy', loading ? 'true' : 'false');
				}
				if (fusionLoadingState) fusionLoadingState.hidden = !loading;
			};
			var loadFusionPaintKits = function () {
				if (fusionData) return Promise.resolve(fusionData);
				if (fusionDataPromise) return fusionDataPromise;
				fusionDataPromise = Promise.all([
					fetchJson(window.cs2PaintKitDataUrl),
					fetchOptionalJson(window.cs2PaintKitAliasDataUrl)
				]).then(function (payloads) {
					var items = payloads[0] || [];
					var aliases = payloads[1] || [];
					var aliasByPaint = {};
					var seen = {};
					aliases.forEach(function (item) {
						aliasByPaint[parseInt(item.paint, 10) || 0] = fusionAliasText(item);
					});
					fusionData = items.map(function (item) {
						var paint = parseInt(item.paint, 10) || 0;
						var sources = normalizeFusionSources(item);
						seen[paint] = true;
						return {
							paint: paint,
							name: item.name || '',
							sources: sources,
							searchText: (fusionAliasText(item) + ' ' + (aliasByPaint[paint] || '')).trim()
						};
					}).filter(function (item) { return item.paint > 0 && item.sources.length; });
					aliases.forEach(function (item) {
						var paint = parseInt(item.paint, 10) || 0;
						if (!paint || seen[paint]) return;
						fusionData.push({ paint: paint, name: item.name || '', sources: normalizeFusionSources(item), searchText: fusionAliasText(item) });
					});
					fusionData.sort(function (a, b) {
						var aHasMultipleSources = a.sources.length > 1;
						var bHasMultipleSources = b.sources.length > 1;
						if (aHasMultipleSources !== bHasMultipleSources) return aHasMultipleSources ? -1 : 1;
						return a.paint - b.paint;
					});
					fusionDataPromise = null;
					return fusionData;
				}).catch(function (error) {
					fusionDataPromise = null;
					throw error;
				});
				return fusionDataPromise;
			};
			var preloadFusionWindow = function (card, sourceIndex) {
				var sources = card._fusionSources || [];
				var seenUrls = {};
				for (var offset = 0; offset < 5 && offset < sources.length; offset++) {
					var candidateIndex = (sourceIndex + offset) % sources.length;
					var source = sources[candidateIndex];
					if (!source.image || seenUrls[source.image]) continue;
					seenUrls[source.image] = true;
					(function (readyIndex, imageUrl) {
						preloadFusionImage(imageUrl, false).then(function (loaded) {
							if (loaded) revealFusionFallback(card, readyIndex);
						});
					})(candidateIndex, source.image);
				}
			};
			var prepareFusionTransition = function (card, sourceIndex) {
				var sources = card._fusionSources || [];
				if (!card.isConnected || !sources.length || card._fusionBusy) return null;
				sourceIndex = sourceIndex % sources.length;
				var source = sources[sourceIndex];
				if (!source || !fusionImageIsReady(source.image)) return null;
				var imageTransition = prepareImageCrossfade(card.querySelector('.image-crossfade-stage'), source.image);
				if (!imageTransition) return null;
				card._fusionBusy = true;
				return {
					card: card,
					sourceIndex: sourceIndex,
					imageTransition: imageTransition
				};
			};
			var commitFusionTransitions = function (transitions, generation) {
				if (!transitions.length) return;
				requestAnimationFrame(function () {
					if (generation !== null && generation !== fusionCarouselGeneration) {
						transitions.forEach(function (transition) { transition.card._fusionBusy = false; });
						return;
					}
					var fading = [];
					transitions.forEach(function (transition) {
						var card = transition.card;
						var source = (card._fusionSources || [])[transition.sourceIndex];
						if (!card.isConnected || !source || !fusionImageIsReady(source.image)) {
							card._fusionBusy = false;
							return;
						}
						transition.imageTransition.outgoing.classList.remove('is-active');
						fading.push(transition);
					});
					setTimeout(function () {
						if (generation !== null && generation !== fusionCarouselGeneration) {
							fading.forEach(function (transition) {
								transition.imageTransition.outgoing.classList.add('is-active');
								transition.card._fusionBusy = false;
							});
							return;
						}
						var committed = [];
						fading.forEach(function (transition) {
							var card = transition.card;
							var source = (card._fusionSources || [])[transition.sourceIndex];
							if (!card.isConnected || !source || !fusionImageIsReady(source.image)) {
								card._fusionBusy = false;
								return;
							}
							activateImageCrossfade(transition.imageTransition);
							card._fusionSourceIndex = transition.sourceIndex;
							preloadFusionWindow(card, transition.sourceIndex + 1);
							committed.push(card);
						});
						setTimeout(function () {
							committed.forEach(function (card) { card._fusionBusy = false; });
						}, fusionTransitionDuration);
					}, fusionTransitionDuration);
				});
			};
			var revealFusionFallback = function (card, sourceIndex) {
				if (!card.isConnected || card._fusionSourceIndex !== -1 || card._fusionBusy) return;
				var transition = prepareFusionTransition(card, sourceIndex);
				if (transition) commitFusionTransitions([transition], null);
			};
			var initializeFusionCard = function (card, highPriority, distance) {
				var sources = card._fusionSources || [];
				card._fusionInitialized = true;
				card._fusionSourceIndex = -1;
				card._fusionBusy = false;
				resetImageCrossfadeStage(card.querySelector('.image-crossfade-stage'), '');
				if (!sources.length) return;
				if (fusionImageIsReady(sources[0].image)) {
					resetImageCrossfadeStage(card.querySelector('.image-crossfade-stage'), sources[0].image);
					card._fusionSourceIndex = 0;
					preloadFusionWindow(card, 1);
					return;
				}
				preloadFusionImage(sources[0].image, !!highPriority, distance).then(function (loaded) {
					if (loaded) revealFusionFallback(card, 0);
				});
				preloadFusionWindow(card, 1);
			};
			var refreshFusionCardPriorities = function () {
				if (!fusionResultsEl) return;
				var rootRect = fusionResultsEl.getBoundingClientRect();
				var viewportHeight = Math.max(fusionResultsEl.clientHeight, rootRect.height);
				if (viewportHeight <= 0) return;
				var viewportCenter = (rootRect.top + rootRect.bottom) / 2;
				fusionResultsEl.querySelectorAll('.fusion-finish-result').forEach(function (card) {
					var rect = card.getBoundingClientRect();
					var distance = Math.abs(((rect.top + rect.bottom) / 2) - viewportCenter);
					var isVisible = rect.bottom >= rootRect.top && rect.top <= rootRect.bottom;
					var isNearby = rect.bottom >= rootRect.top - viewportHeight && rect.top <= rootRect.bottom + viewportHeight;
					card._fusionInPreloadRange = isNearby;
					if (!isNearby) return;
					if (!card._fusionInitialized) {
						initializeFusionCard(card, isVisible, distance);
						return;
					}
					if (isVisible && card._fusionSourceIndex === -1 && card._fusionSources && card._fusionSources[0]) {
						preloadFusionImage(card._fusionSources[0].image, true, distance).then(function (loaded) {
							if (loaded) revealFusionFallback(card, 0);
						});
					}
				});
			};
			var scheduleFusionCardPriorities = function () {
				if (fusionVisibilityFrame !== null) return;
				fusionVisibilityFrame = requestAnimationFrame(function () {
					fusionVisibilityFrame = null;
					refreshFusionCardPriorities();
				});
			};
			var observeFusionCards = function () {
				if (!fusionResultsEl) return;
				if (fusionVisibilityObserver) fusionVisibilityObserver.disconnect();
				if (!('IntersectionObserver' in window)) {
					refreshFusionCardPriorities();
					return;
				}
				fusionVisibilityObserver = new IntersectionObserver(scheduleFusionCardPriorities, { root: fusionResultsEl, rootMargin: '100% 0px', threshold: 0.01 });
				fusionResultsEl.querySelectorAll('.fusion-finish-result').forEach(function (card) {
					fusionVisibilityObserver.observe(card);
				});
				if (!fusionVisibilityScrollBound) {
					fusionResultsEl.addEventListener('scroll', scheduleFusionCardPriorities, { passive: true });
					fusionVisibilityScrollBound = true;
				}
				scheduleFusionCardPriorities();
			};
			var nextReadyFusionSourceIndex = function (card) {
				var sources = card._fusionSources || [];
				var currentIndex = parseInt(card._fusionSourceIndex, 10);
				if (sources.length < 2 || currentIndex < 0 || currentIndex >= sources.length) return -1;
				var currentUrl = sources[currentIndex] ? sources[currentIndex].image : '';
				for (var offset = 1; offset < sources.length; offset++) {
					var candidateIndex = (currentIndex + offset) % sources.length;
					var candidate = sources[candidateIndex];
					if (!candidate || !candidate.image || candidate.image === currentUrl) continue;
					if (fusionImageIsReady(candidate.image)) return candidateIndex;
				}
				return -1;
			};
			var advanceFusionCarousel = function () {
				if (!fusionResultsEl || !fusionPickerEl || !fusionPickerEl.classList.contains('show')) return;
				var generation = fusionCarouselGeneration;
				var transitions = [];
				fusionResultsEl.querySelectorAll('.fusion-finish-result.has-multiple-sources').forEach(function (card) {
					if (!card.isConnected || !card._fusionInPreloadRange || card._fusionBusy) return;
					var nextIndex = nextReadyFusionSourceIndex(card);
					if (nextIndex < 0) return;
					var transition = prepareFusionTransition(card, nextIndex);
					if (transition) transitions.push(transition);
				});
				commitFusionTransitions(transitions, generation);
			};
			var startFusionCarousel = function () {
				var canRun = fusionResultsEl
					&& fusionPickerEl
					&& fusionPickerEl.classList.contains('show')
					&& fusionResultsEl.querySelector('.fusion-finish-result.has-multiple-sources');
				if (!canRun) {
					if (fusionCarouselTimer) clearInterval(fusionCarouselTimer);
					fusionCarouselTimer = null;
					fusionCarouselGeneration++;
					return;
				}
				if (fusionCarouselTimer) return;
				fusionCarouselGeneration++;
				fusionCarouselTimer = setInterval(advanceFusionCarousel, 3000);
			};
			var renderFusionResults = function () {
				if (!fusionResultsEl || !fusionData) return;
				var query = (fusionSearchInput ? fusionSearchInput.value : '').trim().toLowerCase();
				var terms = query ? query.split(/\s+/).filter(Boolean) : [];
				var matched = fusionData.filter(function (item) {
					var searchText = (item.searchText || '').toLowerCase();
					return !query || String(item.paint) === query || terms.every(function (term) { return searchText.indexOf(term) !== -1; });
				});
				var shown = matched.slice(0, 80);
				var resultFragment = document.createDocumentFragment();
				shown.forEach(function (item) {
					var cacheKey = String(item.paint);
					var card = fusionCardCache[cacheKey] || null;
					if (!card) {
					card = document.createElement('div');
					card.className = 'skin-result fusion-finish-result' + (item.sources.length > 1 ? ' has-multiple-sources' : '');
					card.dataset.fusionPaint = String(item.paint);
					card.tabIndex = 0;
					card.setAttribute('role', 'button');
					card._fusionItem = item;
					card._fusionSources = item.sources;
					card._fusionSourceIndex = -1;
					if (fusionOfficialPaints[item.paint]) card.classList.add('is-native-finish');
					var imageStage = createImageCrossfadeStage('', '', fusionTransitionDuration);
					attachImageSpinnerFallback(imageStage);
					card.appendChild(imageStage);
					var indicators = document.createElement('div');
					indicators.className = 'fusion-result-indicators';
					if (item.sources.length > 1) {
						var sourceCount = document.createElement('button');
						sourceCount.type = 'button';
						sourceCount.className = 'fusion-source-count';
						sourceCount.dataset.fusionSourceOpen = '1';
						sourceCount.textContent = fusionSourceCountTemplate.replace('%d', String(item.sources.length));
						indicators.appendChild(sourceCount);
					}
					var finishBadge = createPaintKitFinishBadge(item.paint);
					if (finishBadge) indicators.appendChild(finishBadge);
					var paintId = document.createElement('span');
					paintId.className = 'fusion-paint-id';
					paintId.textContent = 'ID ' + String(item.paint);
					indicators.appendChild(paintId);
					card.appendChild(indicators);
					if (fusionOfficialPaints[item.paint]) {
						var nativeFinish = document.createElement('span');
						nativeFinish.className = 'fusion-native-finish';
						nativeFinish.textContent = fusionNativeFinishLabel;
						card.appendChild(nativeFinish);
					}
					var name = document.createElement('span');
					name.textContent = fusionFinishName(item.name);
					card.appendChild(name);
					fusionCardCache[cacheKey] = card;
					}
					resultFragment.appendChild(card);
				});
				if (matched.length > shown.length) {
					var searchHint = document.createElement('p');
					searchHint.className = 'picker-search-more-hint';
					searchHint.textContent = fusionResultsEl.dataset.searchMoreHint || '';
					resultFragment.appendChild(searchHint);
				}
				fusionResultsEl.replaceChildren(resultFragment);
				observeFusionCards();
				scheduleStatusIndicatorFit();
				startFusionCarousel();
			};
			var renderFusionSources = function (item) {
				if (!fusionSourceResults || !item) return;
				if (fusionSourcePaintName) fusionSourcePaintName.textContent = item.name || '';
				fusionSourceResults.innerHTML = '';
				item.sources.forEach(function (source) {
					var card = document.createElement('div');
					card.className = 'fusion-source-result';
					var finishBadge = createPaintKitFinishBadge(item.paint);
					if (finishBadge) card.appendChild(finishBadge);
					var imageStage = createImageCrossfadeStage('', '', fusionTransitionDuration);
					attachImageSpinnerFallback(imageStage);
					var image = imageStage.querySelector('.image-crossfade-layer.is-active');
					image.dataset.remoteSrc = source.image || '';
					card.appendChild(imageStage);
					var name = document.createElement('span');
					name.className = 'fusion-source-name';
					name.textContent = source.sourceName;
					card.appendChild(name);
					fusionSourceResults.appendChild(card);
				});
				observePickerImages(fusionSourceEl);
			};
			var scheduleFusionResultsRender = function () {
				if (fusionSearchTimer) clearTimeout(fusionSearchTimer);
				fusionSearchTimer = setTimeout(function () {
					fusionSearchTimer = null;
					renderFusionResults();
				}, 100);
			};
			if (fusionPickerEl) {
				fusionPickerEl.addEventListener('shown.bs.modal', function () {
					markStickerBackdrop();
					observeFusionCards();
					scheduleStatusIndicatorFit();
					startFusionCarousel();
					if (fusionSearchInput) fusionSearchInput.focus();
				});
				fusionPickerEl.addEventListener('hidden.bs.modal', function () {
					if (fusionCarouselTimer) clearInterval(fusionCarouselTimer);
					fusionCarouselTimer = null;
					fusionCarouselGeneration++;
					setStickerUnderlay(null);
				});
			}
			if (fusionSourceEl) {
				fusionSourceEl.addEventListener('shown.bs.modal', function () {
					if (fusionCarouselTimer) clearInterval(fusionCarouselTimer);
					fusionCarouselTimer = null;
					fusionCarouselGeneration++;
					markStickerBackdrop();
					observePickerImages(fusionSourceEl);
				});
				fusionSourceEl.addEventListener('hidden.bs.modal', function () {
					setStickerUnderlay(fusionParentModal);
					startFusionCarousel();
				});
			}
			if (fusionSearchInput) fusionSearchInput.addEventListener('input', scheduleFusionResultsRender);
			var submitFusionPaint = function (card) {
				if (!card || !fusionTargetDefindex || !fusionForm || !fusionFormaInput) return;
				var paint = parseInt(card.dataset.fusionPaint, 10) || 0;
				if (!paint) return;
				fusionFormaInput.value = String(fusionTargetDefindex) + '-' + String(paint);
				window.rememberScrollPosition();
				fusionForm.submit();
			};
			document.addEventListener('click', function (event) {
				var fusionOpenButton = event.target.closest('[data-fusion-open]');
				if (fusionOpenButton) {
					fusionTargetDefindex = parseInt(fusionOpenButton.dataset.fusionDefindex, 10) || 0;
					fusionTargetWeapon = fusionOpenButton.dataset.fusionWeapon || '';
					if (fusionPickerTitle) fusionPickerTitle.textContent = fusionPickerTitleTemplate.replace('%s', fusionOpenButton.dataset.fusionTargetName || fusionTargetWeapon);
					fusionOfficialPaints = {};
					fusionCardCache = {};
					String(fusionOpenButton.dataset.fusionOfficialPaints || '').split(',').forEach(function (paint) {
						paint = parseInt(paint, 10) || 0;
						if (paint > 0) fusionOfficialPaints[paint] = true;
					});
					fusionParentModal = fusionOpenButton.closest('.modal');
					setStickerUnderlay(fusionParentModal);
					if (fusionSearchTimer !== null) {
						window.clearTimeout(fusionSearchTimer);
						fusionSearchTimer = null;
					}
					if (fusionSearchInput) fusionSearchInput.value = '';
					var needsLoading = !fusionData;
					setFusionPickerLoading(needsLoading);
					if (!needsLoading) renderFusionResults();
					if (fusionPicker) fusionPicker.show();
					if (!needsLoading) return;
					loadFusionPaintKits().then(function () {
						renderFusionResults();
						setFusionPickerLoading(false);
					}).catch(function (error) {
						setFusionPickerLoading(false);
						if (fusionPicker) fusionPicker.hide();
						setStickerUnderlay(null);
						if (window.console && console.error) console.error(error);
						alert(dataLoadFailedMessage);
					});
					return;
				}
				var fusionSourceOpen = event.target.closest('[data-fusion-source-open]');
				if (fusionSourceOpen) {
					var sourceCard = fusionSourceOpen.closest('[data-fusion-paint]');
					if (sourceCard && sourceCard._fusionItem) {
						renderFusionSources(sourceCard._fusionItem);
						setStickerUnderlay(fusionPickerEl);
						if (fusionSourceModal) fusionSourceModal.show();
					}
					return;
				}
				submitFusionPaint(event.target.closest('[data-fusion-paint]'));
			});
			document.addEventListener('keydown', function (event) {
				if (event.target.closest('[data-fusion-source-open]')) return;
				var card = event.target.closest('[data-fusion-paint]');
				if (!card || (event.key !== 'Enter' && event.key !== ' ')) return;
				event.preventDefault();
				submitFusionPaint(card);
			});

			if (fusionPickerEl && document.querySelector('[data-fusion-open]') && typeof scheduleIdleTask === 'function') {
				scheduleIdleTask(loadFusionPaintKits);
			}

	app.fusion = {
		pickerEl: fusionPickerEl, picker: fusionPicker,
		sourceEl: fusionSourceEl, sourceModal: fusionSourceModal
	};

})(window.cs2App = window.cs2App || {});

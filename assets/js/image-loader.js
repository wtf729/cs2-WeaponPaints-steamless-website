(function (app) {
	'use strict';
	var appConfig = app.config || {};
	var appText = appConfig.text || {};
			var remoteImagePriorityQueue = [];
			var remoteImagePreloadQueue = [];
			var remoteImageRecords = {};
			var remoteImageSubscriptions = new WeakMap();
			var remoteImageActiveLoads = 0;
			var remoteImageActiveVisibleLoads = 0;
			var remoteImageActivePreloads = 0;
			var remoteImageMaxConcurrentLoads = 20;
			var remoteImageMaxConcurrentPreloads = 10;
			var remoteImageRetryDelay = 5000;
			var remoteImageMaxRetries = 5;
			var remoteImageTimeout = 15000;
			var pickerImageObservers = [];
			var remoteImagePendingRecords = [];
			var remoteImageQueueFrame = null;
			var removeQueuedRecord = function (queue, record) {
				var index = queue.indexOf(record);
				if (index !== -1) queue.splice(index, 1);
			};
			var removeRecordFromQueues = function (record) {
				removeQueuedRecord(remoteImagePriorityQueue, record);
				removeQueuedRecord(remoteImagePreloadQueue, record);
			};
			var remoteImagePriorityRank = function (priority) {
				if (priority === 'visible') return 2;
				if (priority === 'preload') return 1;
				return 0;
			};
			var normalizeRemoteImagePriority = function (priority) {
				if (priority === 'visible' || priority === 'preload' || priority === 'deferred') return priority;
				return 'visible';
			};
			var refreshRemoteImageRecordPriority = function (record) {
				var bestPriority = 'deferred';
				var bestDistance = Number.POSITIVE_INFINITY;
				record.subscribers = record.subscribers.filter(function (subscriber) {
					return subscriber.image.isConnected && subscriber.image.dataset.remoteLoadToken === subscriber.token;
				});
				record.subscribers.forEach(function (subscriber) {
					var rank = remoteImagePriorityRank(subscriber.priority);
					var bestRank = remoteImagePriorityRank(bestPriority);
					if (rank > bestRank || (rank === bestRank && subscriber.distance < bestDistance)) {
						bestPriority = subscriber.priority;
						bestDistance = subscriber.distance;
					}
				});
				record.priority = bestPriority;
				record.distance = bestDistance;
			};
			var sortRemoteImageQueues = function () {
				var byDistance = function (left, right) { return left.distance - right.distance; };
				remoteImagePriorityQueue.sort(byDistance);
				remoteImagePreloadQueue.sort(byDistance);
			};
			var applyRemoteImageRecord = function (record) {
				record.subscribers.forEach(function (subscriber) {
					if (!subscriber.image.isConnected || subscriber.image.dataset.remoteLoadToken !== subscriber.token) return;
					if (subscriber.applied) return;
					subscriber.apply();
					subscriber.applied = true;
					subscriber.priority = 'deferred';
				});
				refreshRemoteImageRecordPriority(record);
			};
			var queueRemoteImageRecord = function (record) {
				if (!record || record.status === 'ready') return;
				refreshRemoteImageRecordPriority(record);
				removeRecordFromQueues(record);
				if (record.priority === 'deferred') {
					if (record.status === 'queued') record.status = 'idle';
					return;
				}
				if (record.status === 'loading') return;
				if (record.status === 'failed') {
					if (record.retries >= remoteImageMaxRetries || Date.now() < record.retryAt) return;
				}
				record.status = 'queued';
				if (record.priority === 'visible') {
					remoteImagePriorityQueue.push(record);
				} else if (record.priority === 'preload') {
					remoteImagePreloadQueue.push(record);
				}
			};
			var requeueRemoteImageRecords = function (records) {
				records.forEach(function (record) {
					if (record && remoteImagePendingRecords.indexOf(record) === -1) remoteImagePendingRecords.push(record);
				});
				if (remoteImageQueueFrame !== null) return;
				remoteImageQueueFrame = window.requestAnimationFrame(function () {
					remoteImageQueueFrame = null;
					var pending = remoteImagePendingRecords.splice(0);
					pending.forEach(queueRemoteImageRecord);
					sortRemoteImageQueues();
					runRemoteImageQueue();
				});
			};
			var scheduleRemoteImageRetry = function (record) {
				if (!record || record.retryTimer || record.retries >= remoteImageMaxRetries) return;
				var delay = Math.max(0, record.retryAt - Date.now());
				record.retryTimer = window.setTimeout(function () {
					record.retryTimer = null;
					var hasNearbySubscriber = record.subscribers.some(function (subscriber) {
						return subscriber.image.isConnected && (subscriber.priority === 'visible' || subscriber.priority === 'preload');
					});
					if (hasNearbySubscriber) requeueRemoteImageRecords([record]);
				}, delay);
			};
			var nextRemoteImageRecord = function () {
				var record = remoteImagePriorityQueue.shift();
				if (record) return record;
				if (remoteImageActiveVisibleLoads === 0 && remoteImageActivePreloads < remoteImageMaxConcurrentPreloads) {
					record = remoteImagePreloadQueue.shift();
					if (record) return record;
				}
				return null;
			};
			var runRemoteImageQueue = function () {
				while (remoteImageActiveLoads < remoteImageMaxConcurrentLoads) {
					var record = nextRemoteImageRecord();
					if (!record) return;
					if (record.status !== 'queued') continue;
					record.status = 'loading';
					record.activePriority = record.priority;
					remoteImageActiveLoads++;
					if (record.activePriority === 'visible') remoteImageActiveVisibleLoads++;
					if (record.activePriority === 'preload') remoteImageActivePreloads++;
					(function (currentRecord) {
						var probe = new Image();
						var settled = false;
						var timeout = window.setTimeout(function () { finish(false); }, remoteImageTimeout);
						var finish = function (loaded) {
							if (settled) return;
							settled = true;
							window.clearTimeout(timeout);
							if (loaded) {
								currentRecord.status = 'ready';
								applyRemoteImageRecord(currentRecord);
							} else {
								currentRecord.retries++;
								currentRecord.status = 'failed';
								currentRecord.retryAt = Date.now() + remoteImageRetryDelay;
								scheduleRemoteImageRetry(currentRecord);
							}
							if (currentRecord.activePriority === 'visible') remoteImageActiveVisibleLoads--;
							if (currentRecord.activePriority === 'preload') remoteImageActivePreloads--;
							currentRecord.activePriority = '';
							remoteImageActiveLoads--;
							runRemoteImageQueue();
						};
						probe.onload = function () {
							if (typeof probe.decode !== 'function') {
								finish(true);
								return;
							}
							probe.decode().then(function () { finish(true); }).catch(function () { finish(true); });
						};
						probe.onerror = function () { finish(false); };
						probe.src = currentRecord.url;
					})(record);
				}
			};
			var loadRemoteImage = function (image, priority, distance) {
				if (!image) return;
				var remoteSrc = image.dataset.remoteSrc || '';
				if (!remoteSrc || remoteSrc === image.src) return;
				priority = normalizeRemoteImagePriority(priority);
				distance = Number(distance);
				if (!isFinite(distance)) distance = Number.POSITIVE_INFINITY;
				var existingSubscription = remoteImageSubscriptions.get(image);
				if (existingSubscription && existingSubscription.remoteSrc === remoteSrc) {
					existingSubscription.priority = existingSubscription.applied ? 'deferred' : priority;
					existingSubscription.distance = distance;
					if (existingSubscription.record.status === 'ready') {
						if (!existingSubscription.applied) applyRemoteImageRecord(existingSubscription.record);
					} else {
						requeueRemoteImageRecords([existingSubscription.record]);
						if (existingSubscription.record.status === 'failed') scheduleRemoteImageRetry(existingSubscription.record);
					}
					return;
				}
				if (priority === 'deferred') return;
				if (existingSubscription) {
					existingSubscription.record.subscribers = existingSubscription.record.subscribers.filter(function (subscriber) {
						return subscriber !== existingSubscription;
					});
					requeueRemoteImageRecords([existingSubscription.record]);
				}
				var stage = image.closest('.image-crossfade-stage');
				if (!stage && shouldUseImageCrossfade(image)) stage = upgradeImageToCrossfadeStage(image);
				var imageState = stage || image;
				if (imageState.dataset.remoteAppliedSrc === remoteSrc) return;
				if (stage) stage.dataset.remoteSrc = remoteSrc;
				var token = String((parseInt(image.dataset.remoteLoadToken, 10) || 0) + 1);
				image.dataset.remoteLoadToken = token;
				var applyRemoteImage = function () {
					if (stage) {
						if (stage.dataset.remoteSrc !== remoteSrc) return;
						var transition = prepareImageCrossfade(stage, remoteSrc);
						if (!transition) return;
						var activate = function () {
							requestAnimationFrame(function () {
								if (stage.dataset.remoteSrc === remoteSrc) {
									activateImageCrossfade(transition);
									stage.dataset.remoteAppliedSrc = remoteSrc;
								}
							});
						};
						if (transition.incoming.complete && transition.incoming.naturalWidth > 0) {
							activate();
						} else {
							transition.incoming.addEventListener('load', activate, { once: true });
						}
						return;
					}
					if (image.dataset.remoteSrc === remoteSrc) {
						image.src = remoteSrc;
						image.dataset.remoteAppliedSrc = remoteSrc;
					}
				};
				var subscriber = {
					image: image,
					remoteSrc: remoteSrc,
					token: token,
					apply: applyRemoteImage,
					priority: priority,
					distance: distance,
					applied: false,
					record: null
				};
				var record = remoteImageRecords[remoteSrc];
				if (!record) {
					record = {
						url: remoteSrc,
						status: 'idle',
						priority: priority,
						distance: distance,
						activePriority: '',
						retries: 0,
						retryAt: 0,
						retryTimer: null,
						subscribers: []
					};
					remoteImageRecords[remoteSrc] = record;
				}
				record.subscribers = record.subscribers.filter(function (existing) {
					return existing.image !== image && existing.image.isConnected;
				});
				record.subscribers.push(subscriber);
				subscriber.record = record;
				remoteImageSubscriptions.set(image, subscriber);
				if (record.status === 'ready') {
					applyRemoteImageRecord(record);
					return;
				}
				requeueRemoteImageRecords([record]);
				if (record.status === 'failed') scheduleRemoteImageRetry(record);
			};
			var imageCanLoadInPicker = function (image) {
				if (!image || !image.isConnected) return false;
				var result = image.closest('[data-picker-result], .agent-result, .skin-result, .sticker-result, .keychain-result, .fusion-source-result');
				return !result || !result.hidden;
			};
			var promotePickerImageSource = function (image) {
				if (!image) return;
				if (image.dataset.pickerRemoteSrc !== undefined) {
					image.dataset.remoteSrc = image.dataset.pickerRemoteSrc || '';
					image.removeAttribute('data-picker-remote-src');
				}
			};
			var pickerObserverFor = function (scope) {
				if (!scope) return null;
				var root = scope.matches && scope.matches('.picker-results-scroll')
					? scope
					: scope.querySelector('.picker-results-scroll') || scope.querySelector('.modal-body');
				if (!root) return null;
				var existing = pickerImageObservers.find(function (entry) { return entry.scope === scope && entry.root === root; });
				if (existing) return existing;
				var entry = {
					scope: scope,
					root: root,
					observer: null,
					images: [],
					refreshFrame: null
				};
				var scheduleRefresh = function () {
					if (entry.refreshFrame !== null) return;
					entry.refreshFrame = window.requestAnimationFrame(function () {
						entry.refreshFrame = null;
						refreshPickerImagePriorities(entry);
					});
				};
				if ('IntersectionObserver' in window) {
					entry.observer = new IntersectionObserver(scheduleRefresh, { root: root, rootMargin: '100% 0px', threshold: 0.01 });
				}
				root.addEventListener('scroll', scheduleRefresh, { passive: true });
				pickerImageObservers.push(entry);
				return entry;
			};
			var refreshPickerImagePriorities = function (entry) {
				if (!entry || !entry.root) return;
				var rootRect = entry.root.getBoundingClientRect();
				var viewportHeight = Math.max(entry.root.clientHeight, rootRect.height);
				if (viewportHeight <= 0) return;
				var viewportCenter = (rootRect.top + rootRect.bottom) / 2;
				var preloadDistance = viewportHeight;
				entry.images.forEach(function (image) {
					if (!image.isConnected || !image.dataset.remoteSrc || !imageCanLoadInPicker(image)) {
						loadRemoteImage(image, 'deferred', Number.POSITIVE_INFINITY);
						return;
					}
					var rect = image.getBoundingClientRect();
					var distance = Math.abs(((rect.top + rect.bottom) / 2) - viewportCenter);
					var isVisible = rect.bottom >= rootRect.top && rect.top <= rootRect.bottom;
					var isNearby = rect.bottom >= rootRect.top - preloadDistance && rect.top <= rootRect.bottom + preloadDistance;
					var priority = isVisible
						? 'visible'
						: (isNearby ? 'preload' : 'deferred');
					image.dataset.remoteInViewport = isVisible ? '1' : '0';
					loadRemoteImage(image, priority, distance);
				});
			};
			var observePickerImages = function (scope) {
				var entry = pickerObserverFor(scope);
				if (!entry) return;
				var images = scope.querySelectorAll('img[data-picker-remote-src], img[data-remote-src]');
				entry.images.forEach(function (image) {
					if (!scope.contains(image)) loadRemoteImage(image, 'deferred', Number.POSITIVE_INFINITY);
				});
				if (entry.observer) entry.observer.disconnect();
				entry.images = Array.prototype.slice.call(images);
				images.forEach(function (image) {
					promotePickerImageSource(image);
					if (!image.dataset.remoteSrc) return;
					image.dataset.remoteInViewport = '0';
					if (entry.observer) entry.observer.observe(image);
				});
				if (entry.refreshFrame !== null) window.cancelAnimationFrame(entry.refreshFrame);
				entry.refreshFrame = window.requestAnimationFrame(function () {
					entry.refreshFrame = null;
					refreshPickerImagePriorities(entry);
				});
			};
			var imageCrossfadeDuration = 450;
			var createImageCrossfadeStage = function (fallbackSrc, altText, durationMs) {
				var stage = document.createElement('div');
				stage.className = 'image-crossfade-stage';
				var transitionDuration = Number(durationMs);
				if (!isFinite(transitionDuration) || transitionDuration < 0) transitionDuration = imageCrossfadeDuration;
				stage.style.setProperty('--image-crossfade-duration', transitionDuration + 'ms');
				for (var layerIndex = 0; layerIndex < 2; layerIndex++) {
					var image = document.createElement('img');
					image.className = 'image-crossfade-layer' + (layerIndex === 0 ? ' is-active' : '');
					if (fallbackSrc) image.src = fallbackSrc;
					image.alt = altText || '';
					stage.appendChild(image);
				}
				stage._imageCrossfadeActiveLayer = 0;
				return stage;
			};
			var attachImageSpinnerFallback = function (stage, sizeClass) {
				if (!stage) return stage;
				stage.classList.add('has-spinner-fallback', 'is-loading');
				if (stage.querySelector('.image-crossfade-spinner')) return stage;
				var spinner = document.createElement('span');
				spinner.className = 'cs2-spinner image-crossfade-spinner' + (sizeClass ? ' ' + sizeClass : '');
				spinner.setAttribute('aria-hidden', 'true');
				stage.insertBefore(spinner, stage.firstChild);
				return stage;
			};
			var resetImageCrossfadeStage = function (stage, fallbackSrc) {
				if (!stage) return;
				stage._imageCrossfadeActiveLayer = 0;
				stage.dataset.remoteAppliedSrc = '';
				stage.querySelectorAll('.image-crossfade-layer').forEach(function (image, index) {
					if (fallbackSrc) {
						image.src = fallbackSrc;
					} else {
						image.removeAttribute('src');
					}
					image.dataset.remoteSrc = '';
					image.dataset.remoteAppliedSrc = '';
					image.classList.toggle('is-active', index === 0);
				});
				if (stage.classList.contains('has-spinner-fallback')) {
					stage.classList.toggle('is-loading', !fallbackSrc);
				}
			};
			var prepareImageCrossfade = function (stage, imageSrc) {
				if (!stage || !imageSrc) return null;
				var layers = stage.querySelectorAll('.image-crossfade-layer');
				if (layers.length < 2) return null;
				var outgoingIndex = stage._imageCrossfadeActiveLayer === 1 ? 1 : 0;
				var incomingIndex = outgoingIndex === 0 ? 1 : 0;
				var incoming = layers[incomingIndex];
				incoming.dataset.remoteSrc = imageSrc;
				incoming.src = imageSrc;
				return {
					stage: stage,
					outgoing: layers[outgoingIndex],
					incoming: incoming,
					incomingIndex: incomingIndex
				};
			};
			var activateImageCrossfade = function (transition) {
				if (!transition) return;
				transition.outgoing.classList.remove('is-active');
				transition.incoming.classList.add('is-active');
				transition.stage._imageCrossfadeActiveLayer = transition.incomingIndex;
				transition.stage.classList.remove('is-loading');
			};
			var shouldUseImageCrossfade = function (image) {
				if (!image) return false;
				if (image.classList.contains('skin-image') && image.closest('.skin-card')) return true;
				return !!image.closest('.agent-result, .skin-result, .sticker-result, .keychain-result, .fusion-source-result');
			};
			var upgradeImageToCrossfadeStage = function (image) {
				if (!image || !image.parentNode) return null;
				var existingStage = image.closest('.image-crossfade-stage');
				if (existingStage) return existingStage;
				var stage = document.createElement('div');
				stage.className = 'image-crossfade-stage';
				stage.style.setProperty('--image-crossfade-duration', imageCrossfadeDuration + 'ms');
				image.parentNode.insertBefore(stage, image);
				stage.appendChild(image);
				image.classList.add('image-crossfade-layer', 'is-active');
				var incoming = image.cloneNode(false);
				incoming.removeAttribute('id');
				incoming.removeAttribute('data-remote-src');
				incoming.classList.remove('is-active');
				stage.appendChild(incoming);
				stage._imageCrossfadeActiveLayer = 0;
				return stage;
			};

			document.querySelectorAll('img[data-remote-src]').forEach(function (image) {
				loadRemoteImage(image, 'visible', 0);
			});

	app.loadRemoteImage = loadRemoteImage;
	app.observePickerImages = observePickerImages;
	app.createImageCrossfadeStage = createImageCrossfadeStage;
	app.attachImageSpinnerFallback = attachImageSpinnerFallback;
	app.resetImageCrossfadeStage = resetImageCrossfadeStage;
	app.prepareImageCrossfade = prepareImageCrossfade;
	app.activateImageCrossfade = activateImageCrossfade;

})(window.cs2App = window.cs2App || {});

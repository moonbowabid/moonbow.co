/**
 * GoDaddy Media Tab for wp.media
 *
 * Adds a "GoDaddy Media Library" tab to the WordPress media modal's top
 * tab bar (router) alongside "Upload files" and "Media Library". Delegates
 * asset browsing to the parent MFE's MediaManager via postMessage. Selected
 * assets are sideloaded into WordPress as real attachments via REST API.
 *
 * @version 1.1.0
 */

(function(window) {
	'use strict';

	if (!window.wp || !window.wp.media) {
		return;
	}

	var config = window.siteDesignerGodaddyMedia || {};
	if (!config.sideloadUrl || !config.parentOrigin) {
		return;
	}

	var NO_RESPONSE_TIMEOUT_MS = 10000;
	var ROUTER_ID = 'godaddy-media';

	/**
	 * GoDaddy Media Controller
	 *
	 * Standalone controller (not a wp.media state) that manages the postMessage
	 * protocol and sideload flow. Instantiated per-frame and activated/deactivated
	 * when the router tab is selected/deselected.
	 */
	function GoDaddyMediaController(frame) {
		this.frame = frame;
		this._state = 'idle';
		this._messageHandler = null;
		this._timeoutId = null;
		this._frameCloseHandler = null;
		this._listeners = {};
	}

	GoDaddyMediaController.prototype = {
		on: function(event, fn) {
			if (!this._listeners[event]) {
				this._listeners[event] = [];
			}
			this._listeners[event].push(fn);
		},

		off: function(event, fn) {
			if (!this._listeners[event]) {
				return;
			}
			if (fn) {
				this._listeners[event] = this._listeners[event].filter(function(f) { return f !== fn; });
			} else {
				this._listeners[event] = [];
			}
		},

		trigger: function(event, data) {
			var fns = this._listeners[event] || [];
			for (var i = 0; i < fns.length; i++) {
				fns[i](data);
			}
		},

		activate: function() {
			this._setupMessageListener();
			this._setupFrameCloseListener();
			this._sendOpenMessage();
			this._setState('waiting');
			this._startTimeout();
		},

		deactivate: function() {
			if (this._state === 'waiting') {
				this._sendCancelMessage();
			}
			this._cleanup();
			this._setState('idle');
		},

		_reset: function() {
			this._cleanup();
			this._state = 'idle';
		},

		_setState: function(state) {
			this._state = state;
			this.trigger('stateChange', state);
		},

		_sendOpenMessage: function() {
			if (!window.parent || window.parent === window) {
				return;
			}

			window.parent.postMessage({
				type: 'site-designer-open-media-manager',
				context: 'godaddy-media-tab',
				multiple: false,
				timestamp: Date.now(),
				source: 'site-designer-media-upload'
			}, config.parentOrigin);
		},

		_sendCancelMessage: function() {
			if (!window.parent || window.parent === window) {
				return;
			}

			window.parent.postMessage({
				type: 'site-designer-media-manager-cancel',
				timestamp: Date.now(),
				source: 'site-designer-media-upload'
			}, config.parentOrigin);
		},

		_setupMessageListener: function() {
			if (this._messageHandler) {
				return;
			}

			var self = this;
			this._messageHandler = function(event) {
				if (event.origin !== config.parentOrigin) {
					return;
				}

				var data = event.data;
				if (!data || typeof data !== 'object' || !data.type) {
					return;
				}

				switch (data.type) {
					case 'site-designer-media-manager-selected':
						self._clearTimeout();
						self._handleAssetSelected(data);
						break;
					case 'site-designer-media-manager-closed':
						self._clearTimeout();
						self._handleManagerClosed();
						break;
				}
			};

			window.addEventListener('message', this._messageHandler);
		},

		_setupFrameCloseListener: function() {
			if (this._frameCloseHandler) {
				return;
			}

			var self = this;
			this._frameCloseHandler = function() {
				if (self._state === 'waiting') {
					self._sendCancelMessage();
				}
				self._cleanup();
			};

			this.frame.on('close', this._frameCloseHandler);
		},

		_startTimeout: function() {
			this._clearTimeout();

			var self = this;
			this._timeoutId = setTimeout(function() {
				if (self._state === 'waiting') {
					self._sendCancelMessage();
					self._cleanup();
					self._setState('error');
					self.trigger('sideloadError', 'Could not open GoDaddy Media Library. Please try again.');
				}
			}, NO_RESPONSE_TIMEOUT_MS);
		},

		_clearTimeout: function() {
			if (this._timeoutId) {
				clearTimeout(this._timeoutId);
				this._timeoutId = null;
			}
		},

		_handleAssetSelected: function(data) {
			if (this._state !== 'waiting') {
				return;
			}

			if (!data.assets || !data.assets.length) {
				this._sendCancelMessage();
				this._cleanup();
				this._setState('idle');
				return;
			}

			var asset = data.assets[0];
			this._setState('sideloading');
			this._sideloadAsset(asset);
		},

		_handleManagerClosed: function() {
			if (this._state !== 'waiting') {
				return;
			}
			this._cleanup();
			this._setState('idle');
		},

		_sideloadAsset: function(asset) {
			var self = this;

			wp.apiRequest({
				url: config.sideloadUrl,
				method: 'POST',
				data: {
					url: asset.imageUrl,
					filename: asset.fileName || '',
					title: asset.title || '',
					alt_text: asset.altText || '',
					caption: ''
				},
				headers: {
					'X-WP-Nonce': config.nonce
				}
			}).done(function(response) {
				self._handleSideloadSuccess(response);
			}).fail(function(jqXHR) {
				var message = 'Failed to import image.';
				if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
					message = jqXHR.responseJSON.message;
				}
				self._cleanup();
				self._setState('error');
				self.trigger('sideloadError', message);
			});
		},

		_handleSideloadSuccess: function(response) {
			var attachment = new wp.media.model.Attachment(response);

			this._cleanup();
			this._setState('done');

			if (!this.frame || !this.frame.$el || !this.frame.$el.length) {
				return;
			}

			var state = this.frame.state();
			if (state && state.get('selection')) {
				state.get('selection').reset([attachment]);
			}

			this.frame.trigger('select');
		},

		retry: function() {
			this.activate();
		},

		_cleanup: function() {
			this._clearTimeout();

			if (this._messageHandler) {
				window.removeEventListener('message', this._messageHandler);
				this._messageHandler = null;
			}

			if (this._frameCloseHandler && this.frame) {
				this.frame.off('close', this._frameCloseHandler);
				this._frameCloseHandler = null;
			}
		}
	};

	/**
	 * View: GoDaddy Media Content
	 *
	 * Renders status messages for the GoDaddy Media tab content area.
	 */
	// @ts-expect-error -- Backbone .extend() returns a constructor; TS lacks wp.media type defs.
	wp.media.view.GoDaddyMediaContent = wp.media.View.extend({
		className: 'gdmu-godaddy-media-content',

		initialize: function() {
			this._currentState = 'idle';

			if (this.options.gdController) {
				this.gdController = this.options.gdController;
				this._onStateChangeBound = _.bind(this._onStateChange, this);
				this._onErrorBound = _.bind(this._onError, this);
				this.gdController.on('stateChange', this._onStateChangeBound);
				this.gdController.on('sideloadError', this._onErrorBound);
			}
		},

		render: function() {
			this.$el.empty();

			switch (this._currentState) {
				case 'idle':
					this._renderIdle();
					break;
				case 'waiting':
					this._renderWaiting();
					break;
				case 'sideloading':
					this._renderSideloading();
					break;
				case 'error':
					this._renderError();
					break;
				case 'done':
					this._renderDone();
					break;
			}

			return this;
		},

		_onStateChange: function(state) {
			this._currentState = state;
			this.render();
		},

		_onError: function(message) {
			this._errorMessage = message;
			this._currentState = 'error';
			this.render();
		},

		_renderIdle: function() {
			this.$el.html(
				'<div class="gdmu-godaddy-media-status">' +
					'<p class="gdmu-godaddy-media-message">Browse your GoDaddy Media Library</p>' +
				'</div>'
			);
		},

		_renderWaiting: function() {
			this.$el.html(
				'<div class="gdmu-godaddy-media-status">' +
					'<span class="spinner is-active"></span>' +
					'<p class="gdmu-godaddy-media-message">Browsing your GoDaddy Media Library&hellip;</p>' +
				'</div>'
			);
		},

		_renderSideloading: function() {
			this.$el.html(
				'<div class="gdmu-godaddy-media-status">' +
					'<span class="spinner is-active"></span>' +
					'<p class="gdmu-godaddy-media-message">Importing image into Site&hellip;</p>' +
				'</div>'
			);
		},

		_renderError: function() {
			var message = this._errorMessage || 'Something went wrong. Please try again.';
			var self = this;

			this.$el.html(
				'<div class="gdmu-godaddy-media-status gdmu-godaddy-media-error">' +
					'<p class="gdmu-godaddy-media-message">' + _.escape(message) + '</p>' +
					'<button type="button" class="button gdmu-godaddy-media-retry">Try Again</button>' +
				'</div>'
			);

			this.$el.find('.gdmu-godaddy-media-retry').on('click', function() {
				if (self.gdController) {
					self.gdController.retry();
				}
			});
		},

		_renderDone: function() {
			this.$el.html(
				'<div class="gdmu-godaddy-media-status">' +
					'<p class="gdmu-godaddy-media-message">Image imported successfully.</p>' +
				'</div>'
			);
		},

		remove: function() {
			if (this.gdController) {
				this.gdController.off('stateChange', this._onStateChangeBound);
				this.gdController.off('sideloadError', this._onErrorBound);
			}
			wp.media.View.prototype.remove.apply(this, arguments);
		}
	});

	/**
	 * Tab Registration
	 *
	 * Hooks into the browseRouter to add "GoDaddy Media Library" as a top tab
	 * next to "Upload files" and "Media Library". Each time the tab is selected,
	 * a fresh content view is created and the controller re-activates.
	 */
	function extendFrame(FrameClass) {
		if (!FrameClass) {
			return;
		}

		var originalInitialize = FrameClass.prototype.initialize;

		FrameClass.prototype.initialize = function() {
			originalInitialize.apply(this, arguments);

			this._gdMediaController = new GoDaddyMediaController(this);
			this._gdMediaActive = false;

			this.on('content:render:' + ROUTER_ID, this._gdMediaActivateTab, this);
			this.on('content:deactivate:' + ROUTER_ID, this._gdMediaDeactivateTab, this);
			this.on('router:render:browse', this._gdMediaAddRouterTab, this);
		};

		FrameClass.prototype._gdMediaAddRouterTab = function(routerView) {
			routerView.set(ROUTER_ID, {
				text: 'GoDaddy Media Library',
				priority: 70
			});

			var frame = this;
			var eventNs = 'click.gdmedia';
			var selector = '#menu-item-' + ROUTER_ID;
			routerView.$el.off(eventNs, selector).on(eventNs, selector, function() {
				var controllerState = frame._gdMediaController._state;
				if (frame._gdMediaActive && (controllerState === 'idle' || controllerState === 'done' || controllerState === 'error')) {
					frame._gdMediaController.activate();
				}
			});
		};

		FrameClass.prototype._gdMediaActivateTab = function() {
			this._gdMediaActive = true;
			this._gdMediaController._reset();

			var view = new wp.media.view.GoDaddyMediaContent({
				controller: this.state(),
				gdController: this._gdMediaController
			});

			this.content.set(view);
			this._gdMediaController.activate();
		};

		FrameClass.prototype._gdMediaDeactivateTab = function() {
			if (this._gdMediaActive) {
				this._gdMediaActive = false;
				this._gdMediaController.deactivate();
			}
		};
	}

	extendFrame(wp.media.view.MediaFrame.Post);
	extendFrame(wp.media.view.MediaFrame.Select);

})(window);

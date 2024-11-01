const __ = wp.i18n.__;
function notionWpSyncSettingsHandler() {
    const $ = jQuery;
    return {
        config: notionWpSyncGetConfig(),
        databases: [],
        pages: [],
        object_index: {},
        current_object: undefined,
        fields: [],
		synchronized: false,

        originalConfigJson: JSON.stringify(notionWpSyncGetConfig()),
        originalPostTypeSlug: notionWpSyncGetConfig().post_type_slug || '',
        loadingDatabasesAndPages: false,
        loadingTables: false,
        validation: {},
        nonce: document.getElementById('notion-wp-sync-ajax-nonce').value,
		mappingMetabox: $('#notionwpsync-mapping'),
		mappingOptions: {},

		hideNoticeTemp: {},
        init() {
            const self = this;

            const selects = $('#notionwpsync-global-settings #database_objects_id, #notionwpsync-global-settings #page_objects_id');

            // pre-populate object_index
            selects.find('option').each(function () {
                if ($(this).data('full-object')) {
                    self.object_index[$(this).val()] = $(this).data('full-object');
                }
            });

            self.setCurrentDatabaseOrPage();

            selects.each(function () {
                self.setupSelect2($(this), selects);
            });

			self.synchronized = self.config.synchronized;
			$(document).on('notionwpsync/synchronized', function () {
				self.synchronized = true;
			});
			// Update mapping from React
			$(document).on('notionwpsync/mapping-updated', function (e) {
				self.config = {
					...self.config,
					mapping: e.detail
				};
			});
        },
        setupSelect2($select, $selects) {
            const self = this;

            let lastResult = { success: false };
            $select.select2({
                multiple: true,
                minimumInputLength: 3,
                maximumSelectionLength: $select.is('#database_objects_id') ? 1 : 0,
                ajax: {
                    url: window.ajaxurl,
                    dataType: 'json',
                    type: "POST",
					delay: 250,
                    data: function (params) {
                        return Object.assign({}, params, {
                            action: 'notion_wp_sync_get_notion_objects',
                            nonce: self.nonce,
                            apiKey: self.config.api_key,
                            objectType: $select.is('#database_objects_id') ? 'database' : 'page',
                        });
                    },
                    processResults: function (response) {

                        const result = {
                            results: []
                        };
                        if (!response.success) {
                            return result
                        }

                        lastResult = response;

						response.data.forEach(function (item) {
							self.object_index[item.id] = item;
						})
						result.results = self.toSelect2Format(response.data);

                        return result;
                    }
                },

            }).on('change.select2', function () {
                if ($(this).val().length > 0) {
                    // Don't mix-up object types.
                    $selects.not($select).each(function () {
                      if ($(this).val().length > 0) {
                          $(this).val(null).trigger('change');
                      }
                    });
                }

                self.config.objects_id = $(this).val();
                self.setCurrentDatabaseOrPage();

            });
        },
        toSelect2Format(data) {
            return data.map(function (item) {
                return {
                    id: item.id,
                    text: item.name,
                };
            });
        },
		showNoticeHandler(noticeKey) {
			const self = this;
			return function () {
				self.config.notices[noticeKey] = true;
			}
		},
		hideNoticeHandler(noticeKey) {
			const self = this;
			return function () {
				self.config.notices[noticeKey] = false;
			}
		},
		tempHideNoticeHandler(noticeKey) {
			const self = this;
			return function () {
				self.hideNoticeTemp[noticeKey] = true;
			}
		},
		updateWordPressOptions() {
			const self = this;

			if (self.config.object_type !== 'page' && self.config.post_type === 'nwpsync-content') {
				self.config.post_type = 'post';
			}

			if (self.config.post_type === 'nwpsync-content') {
				self.mappingMetabox.hide();
			} else {
				self.mappingMetabox.show();
			}

			if (self.config.object_type === 'page' && self.config.post_type === 'nwpsync-content') {
				self.config.mapping = [
					{ notion: "title", wordpress: "post::post_title", options: {} },
					{ notion: "__notionwpsync_blocks", wordpress: "post::post_content", options: {} }
				];
			} else if (self.config.mapping.length === 0 && self.config.object_type === 'page' && (self.config.post_type === 'post' || self.config.post_type === 'page')) {
				self.config.mapping = [
					{ notion: "title", wordpress: "post::post_title", options: {} },
					{ notion: "__notionwpsync_blocks", wordpress: "post::post_content", options: {} }
				];
			}

			self.refreshMetaboxMapping();
		},
        getValidationCssClass(key) {
            if (key === 'post_type_slug') {
                var reservedSlugs = window.notionWpSync.reservedCptSlugs || Array();
                if (this.config.hasOwnProperty('post_type_slug') && !this.config.post_type_slug.match(/^[a-z0-9-_]+$/)) {
                    return 'form-invalid form-invalid-character';
                }
                if (this.config.hasOwnProperty('post_type_slug') && this.config.post_type_slug !== this.originalPostTypeSlug && reservedSlugs.indexOf(this.config.post_type_slug) > -1) {
                    return 'form-invalid';
                }
            }
            if (this.validation[key]) {
                if (this.validation[key].valid === true) {
                    return 'dashicons-before dashicons-yes-alt';
                }
                if (this.validation[key].valid === false) {
                    return 'dashicons-before dashicons-dismiss';
                }
            }
            return '';
        },
        configHasChanged() {
            return JSON.stringify(this.config) !== this.originalConfigJson;
        },
        setCurrentDatabaseOrPage() {
			const self = this;
            if (self.config.objects_id && self.config.objects_id.length > 0 && self.object_index[self.config.objects_id[0]]) {
                self.current_object = self.object_index[self.config.objects_id[0]];
                self.config.object_type = self.current_object.type;
            } else  {
                self.current_object = undefined;
                self.config.object_type = '';
            }

			self.updateWordPressOptions();
        },

		refreshMetaboxMapping() {
			const self = this;
			if (!self.current_object) {
				$('#notionwpsync-metabox-mapping').empty();
				return;
			}
			window.notionWPSyncRenderMetaboxMapping({
				id: 'notionwpsync-metabox-mapping',
				i18n: wp.i18n,
				mappingInit: [ ...self.config.mapping ],
				defaultMappingOptions: window.notionWpSync.mappingOptions,
				featuresByPostType: window.notionWpSync.featuresByPostType,
				fields: self.current_object ? self.current_object.fields : [],
				config: {
					post_type: self.config.post_type,
					post_type_slug: self.config.post_type_slug,
				}
			});
		},

		copyToClipboard(button) {
				// Get the text field
			var copyText = button.previousSibling;

			// Select the text field
			copyText.select();
			copyText.setSelectionRange(0, 99999); // For mobile devices

			// Copy the text inside the text field
			navigator.clipboard.writeText(copyText.value);

			jQuery(button).addClass('is-copied');
			setTimeout(function () {
				jQuery(button).removeClass('is-copied');
			}, 2000)
		},

		today() {
			const d = new Date();
			d.setUTCHours(0,0,0,0);
			return d;
		},

    }
}


function notionWpSyncGetConfig() {
    var config = window.notionwpsyncImporterData || {};
    if (!config.hasOwnProperty('mapping')) {
        config.mapping = [];
    }

    if (!config.hasOwnProperty('validation')) {
        config.validation = {};
    }

    if (!config.hasOwnProperty('scheduled_sync')) {
        config.scheduled_sync = {
            type: 'manual',
            recurrence: '',
        };
    }

    for (var i=0;i<config.mapping.length;i++) {
        if (!config.mapping[i].hasOwnProperty('options')) {
            config.mapping[i].options = {};
        }
    }

    if (!config.hasOwnProperty('objects_id')) {
        config.objects_id = [];
    }


	if (!config.hasOwnProperty('page_scope')) {
		config.page_scope = 'no';
	}

	if (!config.hasOwnProperty('notices')) {
		config.notices = {};
	}

	// Pro > Free compatibility.
	if (config.post_type !== 'post' && config.post_type !== 'page') {
		config.post_type = 'post';
		config.filters = [];
	}

    return config;
}

(function($) {
    var $nonceField;
	var $importButton;
	var $cancelButton;
	var $feedback;
	var $infos;
	var originalConfigJson;
	var timeout;

	function init() {
        $nonceField = $('#notion-wp-sync-trigger-update-nonce');
		$importButton = $('#notionwpsync-import-button');
		$cancelButton = $('#notionwpsync-cancel-button');
		$feedback = $('#notionwpsync-import-feedback');
		$infos = $('#notionwpsync-import-stats');

        originalConfigJson = JSON.stringify(notionWpSyncGetConfig());

		$importButton.on('click', function() {
			var importerId = $(this).data('importer');
			triggerUpdate(importerId);
		});

		$cancelButton.on('click', function() {
			var importerId = $importButton.data('importer');
			cancelImport(importerId);
		});

		if ($importButton.hasClass('loading')) {
			$importButton.attr('disabled', 'disabled');
			var importerId = $importButton.data('importer');
			getProgress(importerId);
		}


		$(window).on('beforeunload', beforeUnload);

        $('#delete-action').on('click', function() {
            $(window).off('beforeunload', beforeUnload);

            var postType = notionWpSyncGetConfig().post_type || '';
            if (postType === 'custom') {
                if (confirm(window.notionWpSyncI18n.deleteActionConfirmation || 'You have a Custom Post Type declared using this connection. Are you sure to delete it?')) {
                    return true;
                }
                else {
                    return false;
                }
            }
        })
        $('#post').on('submit', function() {
            $(window).off('beforeunload', beforeUnload);
        })
    }

	function triggerUpdate(importerId) {
		clearTimeout(timeout);
		$importButton.addClass('loading').attr('disabled', 'disabled');
		$feedback.html(window.notionWpSyncI18n.startingUpdate || 'In progress...').show();
		var data = {
			'action': 'notion_wp_sync_trigger_update',
			'nonce': $nonceField.val(),
			'importer': importerId,
		};
		$.post(window.ajaxurl, data, function(response) {
			$feedback.html(response.data.feedback);
			if (response.success) {
				getProgress(importerId);
			}
			else {
				$importButton.removeClass('loading').removeAttr('disabled');
				$infos.html(response.data.infosHtml);
				timeout = setTimeout(function() {
					$feedback.fadeOut();
				}, 6000);
			}
		}).fail(function() {
			$importButton.removeClass('loading').removeAttr('disabled');
		});
	}

	function cancelImport(importerId) {
		clearTimeout(timeout);
		$importButton.removeClass('loading').removeAttr('disabled');
		$cancelButton.addClass('loading').attr('disabled', 'disabled');
		$feedback.html(window.notionWpSyncI18n.canceling || 'Canceling...').show();
		var data = {
			'action': 'notion_wp_sync_cancel_import',
			'nonce': $nonceField.val(),
			'importer': importerId,
		};
		$.post(window.ajaxurl, data, function(response) {
			$feedback.html(response.data.feedback);
			$cancelButton.removeClass('loading').removeAttr('disabled').hide();
			$infos.html(response.data.infosHtml);
			timeout = setTimeout(function() {
				$feedback.fadeOut();
			}, 6000);
		}).fail(function() {
			$cancelButton.removeClass('loading').removeAttr('disabled');
		});
	}

	function getProgress(importerId) {
		if (!$importButton.hasClass('loading')) {
			return;
		}
		$cancelButton.show();
		var data = {
			'action': 'notion_wp_sync_get_progress',
			'nonce': $nonceField.val(),
			'importer': importerId,
		};
		$.post(window.ajaxurl, data, function(response) {
			$feedback.html(response.data.feedback);
			if (response.data.infosHtml || !response.success) {
				$importButton.removeClass('loading').removeAttr('disabled');
				$cancelButton.removeClass('loading').removeAttr('disabled').hide();
				$infos.html(response.data.infosHtml);
				timeout = setTimeout(function() {
					$feedback.fadeOut();
				}, 6000);
			}
			else {
				setTimeout(function() {
					getProgress(importerId);
				}, 3000);
			}

		}).fail(function() {
			$importButton.removeClass('loading').removeAttr('disabled');
		});
	}


	function beforeUnload() {
        if ( originalConfigJson !== $('[name="content"]').val() ) {
            return "You have unsaved changes.";
        }
    }

    $(init);
})(jQuery);

(function($) {
    function init() {
        $('#titlewrap').addClass('form-required');

        var $notice = $('#notionwpsync-validation-notice');

        $(document).tooltip({
            items: '.notionwpsync-tooltip',
            tooltipClass: 'arrow-bottom',
            content: function() {
                return $(this).attr('aria-label');
            },
            position: {
                my: 'center bottom',
                at: 'center-3 top-11',
            },
            open: function (event, ui) {
                self = this;
                if (typeof (event.originalEvent) === 'undefined') {
                    return false;
                }

                var $id = ui.tooltip.attr('id');
                $('div.ui-tooltip').not('#' + $id).remove();
            },
            close: function (event, ui) {
                ui.tooltip.hover(function () {
                    $(this).stop(true).fadeTo(400, 1);
                },
                function () {
                    $(this).fadeOut('500', function() {
                        $(this).remove();
                    });
                });
            }
        });

        $('#notionwpsync-alpine-container').on('focus', '.form-required input, .form-required select', function() {
            $(this).parents('.form-required').removeClass('form-invalid');
        });

        $('#publish').on('click', function(e) {
            var formValid = true;
            $notice.hide();
            var $fields = $('#notionwpsync-alpine-container .form-required').find('input, select').filter('[name]');
            $fields.parents('.form-required').removeClass('form-invalid');
            $fields.each(function() {
                let value = $(this).val();
                let inputvalid = true;

                if (typeof value === 'string') {
                    inputvalid = value.trim() !== '';
                } else if ( Array.isArray(value) ) {
                    inputvalid = value.length > 0;
                }

                if (!inputvalid) {
                    formValid = false;
                    $notice.show();
                    $(this).parents('.form-required').addClass('form-invalid');
                }
            });
            if (!formValid) {
                e.preventDefault();
            }
        });
    }

    $(init);
})(jQuery);

/**
 * Settings page: live title preview and copy-status warning.
 *
 * @package Oli_Duplicate_Post
 */
/* global oliDpSettings */
( function ( config ) {
	'use strict';

	function ready( callback ) {
		if ( document.readyState !== 'loading' ) {
			callback();
		} else {
			document.addEventListener( 'DOMContentLoaded', callback );
		}
	}

	ready( function () {
		var prefixInput = document.getElementById( 'oli_dp_title_prefix' );
		var suffixInput = document.getElementById( 'oli_dp_title_suffix' );
		var previewText = document.getElementById( 'oli-dp-title-preview-text' );
		var warning = document.getElementById( 'oli-dp-status-warning' );
		var statusRadios = document.querySelectorAll( 'input[name="oli_dp_settings[copy_status]"]' );
		var i18n = config || {};
		var sampleTitle = i18n.sampleTitle || 'Sample title';
		var defaultSuffix = i18n.defaultSuffix || '(Copy)';

		function updatePreview() {
			var prefix;
			var suffix;

			if ( ! previewText || ! prefixInput || ! suffixInput ) {
				return;
			}

			prefix = prefixInput.value;
			suffix = suffixInput.value;
			if ( suffix === '' ) {
				suffix = ' ' + defaultSuffix;
			}

			previewText.textContent = prefix + sampleTitle + suffix;
		}

		function updateWarning() {
			var selected;

			if ( ! warning ) {
				return;
			}

			selected = document.querySelector( 'input[name="oli_dp_settings[copy_status]"]:checked' );
			warning.hidden = ! ( selected && selected.value === '1' );
		}

		if ( prefixInput ) {
			prefixInput.addEventListener( 'input', updatePreview );
		}
		if ( suffixInput ) {
			suffixInput.addEventListener( 'input', updatePreview );
		}

		statusRadios.forEach( function ( radio ) {
			radio.addEventListener( 'change', updateWarning );
		} );

		updatePreview();
		updateWarning();
	} );
}( window.oliDpSettings ) );

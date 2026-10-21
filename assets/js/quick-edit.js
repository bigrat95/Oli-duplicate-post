/**
 * Quick Edit "Copy to New Draft" button.
 *
 * @package Oli_Duplicate_Post
 */
/* global jQuery, oliDpQuickEdit */
( function ( $, config ) {
	'use strict';

	if ( ! $ || ! config ) {
		return;
	}

	$( function () {
		$( '.editinline' ).on( 'click', function () {
			var $row = $( this ).closest( 'tr' );
			var rowId = $row.attr( 'id' ) || '';
			var postId = rowId.replace( 'post-', '' );
			var $postRow = $( '#post-' + postId );
			var cloneLink = $postRow.find( '.oli_dp_new_draft a, .oli_dp_clone a' ).first().attr( 'href' );

			if ( ! cloneLink ) {
				return;
			}

			window.setTimeout( function () {
				var $quickEditRow = $( '#edit-' + postId );
				var $copyButton;
				var $link;

				if ( ! $quickEditRow.length || $quickEditRow.find( '.oli-dp-quick-copy' ).length ) {
					return;
				}

				$copyButton = $( '<div class="oli-dp-quick-copy"></div>' );
				$link = $( '<a></a>', {
					href: cloneLink,
					'class': 'button button-secondary',
					text: config.copyToNewDraft || '',
				} );

				$copyButton.append( $link );
				$quickEditRow.find( '.inline-edit-col-left' ).append( $copyButton );
			}, 50 );
		} );
	} );
}( window.jQuery, window.oliDpQuickEdit ) );

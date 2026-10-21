/**
 * Block editor sidebar panel and Options menu items.
 *
 * @package Oli_Duplicate_Post
 */
/* global wp, oliDpBlockEditor */
( function ( wp, config ) {
	'use strict';

	if ( ! wp || ! wp.plugins || ! wp.editPost || ! wp.element || ! wp.components || ! config ) {
		return;
	}

	var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
	var PluginMoreMenuItem = wp.editPost.PluginMoreMenuItem;
	var createElement = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var Button = wp.components.Button;
	var registerPlugin = wp.plugins.registerPlugin;

	var cloneUrl = config.cloneUrl || '';
	var newDraftUrl = config.newDraftUrl || '';
	var rewriteUrl = config.rewriteUrl || '';
	var i18n = config.i18n || {};

	var OliDPPanel = function () {
		return createElement(
			PluginDocumentSettingPanel,
			{
				name: 'oli-duplicate-post-panel',
				title: i18n.duplicatePost || '',
				className: 'oli-duplicate-post-panel',
			},
			createElement(
				'div',
				{ className: 'oli-dp-block-panel' },
				createElement(
					Button,
					{
						href: cloneUrl,
						variant: 'secondary',
						className: 'oli-dp-block-button',
						'aria-label': i18n.cloneLabel || i18n.clone || '',
					},
					i18n.clone || ''
				),
				createElement(
					Button,
					{
						href: newDraftUrl,
						variant: 'secondary',
						className: 'oli-dp-block-button',
						'aria-label': i18n.newDraftLabel || i18n.newDraft || '',
					},
					i18n.newDraft || ''
				),
				rewriteUrl
					? createElement(
							Button,
							{
								href: rewriteUrl,
								variant: 'secondary',
								className: 'oli-dp-block-button',
								'aria-label': i18n.rewriteLabel || i18n.rewriteRepublish || '',
							},
							i18n.rewriteRepublish || ''
					  )
					: null
			)
		);
	};

	var OliDPToolbarItems = function () {
		return createElement(
			Fragment,
			null,
			createElement(
				PluginMoreMenuItem,
				{
					icon: 'admin-page',
					onClick: function () {
						window.location.href = cloneUrl;
					},
					'aria-label': i18n.cloneLabel || i18n.clone || '',
				},
				i18n.clone || ''
			),
			createElement(
				PluginMoreMenuItem,
				{
					icon: 'edit',
					onClick: function () {
						window.location.href = newDraftUrl;
					},
					'aria-label': i18n.newDraftLabel || i18n.copyToNewDraft || '',
				},
				i18n.copyToNewDraft || ''
			),
			rewriteUrl
				? createElement(
						PluginMoreMenuItem,
						{
							icon: 'update',
							onClick: function () {
								window.location.href = rewriteUrl;
							},
							'aria-label': i18n.rewriteLabel || i18n.rewriteRepublish || '',
						},
						i18n.rewriteRepublish || ''
				  )
				: null
		);
	};

	registerPlugin( 'oli-duplicate-post', {
		render: function () {
			return createElement(
				Fragment,
				null,
				createElement( OliDPPanel ),
				createElement( OliDPToolbarItems )
			);
		},
		icon: null,
	} );
}( window.wp, window.oliDpBlockEditor ) );

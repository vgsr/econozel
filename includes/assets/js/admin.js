/**
 * Econozel Admin scripts
 *
 * @package Econozel
 * @subpackage Administration
 */

/* global inlineEditTax, inlineEditPost, econozelAdmin */
( function( $ ) {

	var settings = econozelAdmin.settings, l10n = econozelAdmin.l10n, wp_inline_edit;

	// Extend inlineEditTax to contain custom edit fields
	if ( typeof inlineEditTax !== 'undefined' ) {

		// Create a copy of the inline edit method
		wp_inline_edit = inlineEditTax.edit;

		/**
		 * Extend the inline edit method by redefining it
		 *
		 * @see wp-admin/js/inline-edit-tax.js
		 */
		inlineEditTax.edit = function( id ) {

			// Apply original logic before adding custom logic
			wp_inline_edit.apply( this, arguments );

			/*
			 * From here on the custom logic kicks in.
			 */
			var editRow, rowData, val, t = this;

			if ( typeof( id ) === 'object' ) {
				id = t.getId( id );
			}

			editRow = $( '#edit-' + id ), rowData = $( '#tag-' + id );

			// Handle Volume
			val = $( '.column-taxonomy-' + settings.volumeTaxId + ' .hidden', rowData ).text();
			$( ':input[name="taxonomy-' + settings.volumeTaxId + '"]', editRow ).val( val );

			// Handle issue. @todo Run through generic taxonomy meta logic.
			val = $( '.column-issue .hidden', rowData ).text();
			$( ':input[name="' + settings.editionTaxId + '-issue"]', editRow ).val( val );
		};
	}

	// Extend inlineEditPost to contain custom edit fields
	if ( typeof inlineEditPost !== 'undefined' ) {

		// Create a copy of the inline edit method
		wp_inline_edit = inlineEditPost.edit;

		/**
		 * Extend the inline edit method by redefining it
		 *
		 * @see wp-admin/js/inline-edit-post.js
		 */
		inlineEditPost.edit = function( id ) {

			// Apply original logic before adding custom logic
			wp_inline_edit.apply( this, arguments );

			/*
			 * From here on the custom logic kicks in.
			 */
			var editRow, rowData, val, t = this, edition;

			if ( typeof( id ) === 'object' ) {
				id = t.getId( id );
			}

			editRow = $( '#edit-' + id ), rowData = $( '#post-' + id );

			// Hacky: replace menu_order label for Articles
			editRow.filter( '.inline-edit-' + settings.articlePostType ).find( '.inline-edit-menu-order-input' ).parents( 'label' ).first().find( '.title' ).text( l10n.articleMenuOrderLabel );

			// Handle Edition
			edition = $( '#' + settings.editionTaxId + '_' + id, rowData ).text();
			// Remove default flat-taxonomy Edition input field (textarea)
			$( '.tax_input_' + settings.editionTaxId, editRow ).eq(0).parents( 'label' ).first().remove();
			// Position Edition field before Page Number and set its value
			$( '.article-edition label', editRow )
				.prependTo( $( '.inline-edit-col-right .inline-edit-col', editRow ).first() )
				.find( 'select option[value="' + edition + '"]' ).prop( 'selected', true );
		};
	}

})( jQuery );

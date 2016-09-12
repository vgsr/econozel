/**
 * Econozel Admin scripts
 *
 * @package Econozel
 * @subpackage Administration
 */

/* global inlineEditTax, econozelAdmin */
( function( $ ) {

	var settings = econozelAdmin.settings;
	
	// Extend inlineEditTax to contain custom edit fields
	if ( typeof inlineEditTax !== 'undefined' ) {

		// Create a copy of the inline edit method
		var wp_inline_edit = inlineEditTax.edit;

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

			// Assign data to edit fields
			// Volume
			val = $( '.column-taxonomy-' + settings.volumeTaxId + ' .hidden', rowData ).text();
			$( ':input[name="taxonomy-' + settings.volumeTaxId + '"]', editRow ).val( val );

			// Issue. Should be run through main Taxonomy Meta logic.
			val = $( '.column-issue .hidden', rowData ).text();
			$( ':input[name="' + settings.editionTaxId + '-issue"]', editRow ).val( val );
		};
	}

})( jQuery );

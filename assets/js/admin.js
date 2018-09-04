/**
 * Econozel Admin scripts
 *
 * @package Econozel
 * @subpackage Administration
 */

/* global inlineEditTax, inlineEditPost, econozelAdmin */
( function( $ ) {

	var settings = econozelAdmin.settings, l10n = econozelAdmin.l10n,
	    wp_inline_edit, wp_bulk_edit;

	/* Taxonomy Terms */

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

	/* Posts */

	// Extend inlineEditPost to contain custom edit fields
	if ( typeof inlineEditPost !== 'undefined' ) {

		// Create a copy of the edit methods
		wp_inline_edit = inlineEditPost.edit;
		wp_bulk_edit   = inlineEditPost.setBulk;

		/**
		 * Extend the inline edit method by redefining it.
		 *
		 * @see wp-admin/js/inline-edit-post.js
		 */
		inlineEditPost.edit = function( id ) {

			// Apply original logic before adding custom logic
			wp_inline_edit.apply( this, arguments );

			/*
			 * From here on the custom logic kicks in.
			 */
			var editRow, rowData, t = this, $order, edition;

			if ( typeof( id ) === 'object' ) {
				id = t.getId( id );
			}

			editRow = $( '#edit-' + id ), rowData = $( '#post-' + id );

			// Get menu_order parent element
			$order = editRow.filter( '.inline-edit-' + settings.articlePostType ).find( '.inline-edit-menu-order-input' ).parents( 'label' ).first();

			// User is capable to assign Editions
			if ( settings.userCanAssignEditions ) {

				// Hacky: replace menu_order label for Articles
				$order.find( '.title' ).text( l10n.articleMenuOrderLabel );

				// Get the current Edition
				edition = $( '#' + settings.editionTaxId + '_' + id, rowData ).text();

				// Remove default flat-taxonomy Edition input field (textarea)
				$( '.tax_input_' + settings.editionTaxId, editRow ).eq(0).parents( 'label' ).first().remove();

				// Edition field...
				$( '.article-edition label', editRow )
					// ... move before Page Number
					.prependTo( $( '.inline-edit-col-right .inline-edit-col', editRow ).first() )
					// ... and set its value
					.find( 'select option[value="' + edition + '"]' ).prop( 'selected', true );

			// User cannot assign Editions
			} else {

				// Remove Page Number input
				$order.remove();
			}

			// When post is featured
			if ( rowData.is( '.status-' + settings.featuredStatusId ) ) {

				// Prepend 'featured' post status dropdown option
				$( '<option />', {
					value:    settings.featuredStatusId,
					selected: ( settings.featuredStatusId == rowData.find( '#inline_' + id ).find( '._status' ).text() ),
					text:     settings.featuredLabel
				}).prependTo( editRow.find( ':input[name="_status"]' ) );
			}
		};

		/**
		 * Extend the bulk edit method by redefining it.
		 *
		 * @see wp-admin/js/inline-edit-post.js
		 */
		inlineEditPost.setBulk = function() {

			// Apply original logic before adding custom logic
			wp_bulk_edit.apply( this, arguments );

			/*
			 * From here on the custom logic kicks in.
			 */
			var bulkRow = $( '#bulk-edit' ), $taxInput;

			// Bail when no posts were selected
			if ( ! $( '#bulk-titles', bulkRow ).length ) {
				return;
			}

			// Get Edition input field(s)
			$taxInput = $( '.tax_input_' + settings.editionTaxId, bulkRow );

			// Initial setup
			if ( 1 !== $taxInput.length ) {

				// Remove default flat-taxonomy Edition input field (textarea)
				$taxInput.eq(0).parents( 'label' ).first().remove();

				// Edition field...
				$( '.article-edition', bulkRow )
					// ... below the other selectors
					.appendTo( $( '.inline-edit-col-right .inline-edit-col', bulkRow ).first() )
					// ... and add no-change option, select on init
					.find( 'select' ).prepend( $( '<option/>' ).html( l10n.noChangeLabel ).attr({ 'value': '-1', 'selected': 'selected' }) );
			}
		};
	}

	/* Single Post */

	// Multiple authors
	var $metabox = $( '#econozel-author' );

	$metabox
		.on( 'click', '.add-extra-author', function() {
			$metabox.find( '.post-author.hidden' ).clone()
				.insertBefore( $metabox.find( '.post-author.hidden' ) )
				.removeClass( 'hidden' ).find( 'select' ).removeAttr( 'disabled' );
		})
		.on( 'click', '.remove-extra-author', function() {
			$(this).parent().remove();
		});

	// When post is featured
	if ( settings.isFeatured ) {

		// Prepend 'featured' post status dropdown option
		$( '<option />', {
			value:    settings.featuredStatusId,
			selected: settings.isFeatured,
			text:     settings.featuredLabel
		}).prependTo( '#post_status' );

		// Correct displayed status
		$( '#post-status-display' ).text( settings.featuredLabel );

		// Restore 'Published' dropdown option
		$( '<option />', {
			value:    settings.publishStatusId,
			text:     settings.publishLabel
		}).insertAfter( '#post_status option:first' );
	}

})( jQuery );

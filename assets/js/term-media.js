/* global wp */
( function( $, _ ) {

	// Bail when method already exists
	if ( typeof wp.media.wpTermMedia !== 'undefined' )
		return;

	/**
	 * The Term Media constructor which creates instances for the given media
	 *
	 * @since 1.0.0
	 *
	 * @param {object} media Media details
	 */
	wp.media.wpTermMedia = function( media ) {
		var Attachment = wp.media.model.Attachment,
		    FeaturedImage = wp.media.controller.FeaturedImage;

		// Define collection of termMedias controller and media instances
		if ( typeof wp.media.controller.termMedias === 'undefined' ) {
			wp.media.controller.termMedias = {};
			wp.media.termMedias = {};
		}

		/**
		 * Construct implementation of the FeaturedImage modal controller
		 * for the Term Media
		 */
		wp.media.controller.termMedias[ media.name ] = FeaturedImage.extend({
			defaults: _.defaults({
				id:      media.key,
				title:   media.l10n.termMediaTitle,
				toolbar: media.key
			}, FeaturedImage.prototype.defaults ),

			/**
			 * Overload the controller's native initializer method to modify
			 * the collection's mime type.
			 */
			initialize: function() {

				// If we haven't been provided a `library`, create a `Selection`.
				if ( ! this.get('library') ) {
					this.set( 'library', wp.media.query({ type: media.mimeType }) );
				}

				FeaturedImage.prototype.initialize.apply( this, arguments );
			},

			/**
			 * Overload the controller's native selection updater method
			 *
			 * @this wp.media.controller.FeaturedImage (Library)
			 */
			updateSelection: function() {
				var selection = this.get('selection'),
				    term = wp.media.termMedias[ media.name ].term(),
					id = wp.media.view.settings.termMedias[ media.metaKey ][ term ].media,
					attachment;

				if ( '' !== id && -1 !== id ) {
					attachment = Attachment.get( id );
					attachment.fetch();
				}

				selection.reset( attachment ? [ attachment ] : [] );
			}
		});

		/**
		 * wp.media.termMedias
		 * @namespace
		 *
		 * @see wp.media.featuredMedia wp-includes/js/media-editor.js
		 */
		wp.media.termMedias[ media.name ] = {
			/**
			 * Set the term media id, save the term media data and
			 * set the HTML in the term meta box to the new term media.
			 *
			 * @global wp.media.view.settings
			 * @global wp.media.post
			 *
			 * @param {number} id The post ID of the term media, or -1 to unset it.
			 */
			set: function( id ) {
				var settings = wp.media.view.settings.termMedias[ media.metaKey ], term = this.term();

				settings[ term ].media = id;

				wp.media.post( media.ajaxAction, {
					json:          true,
					term_id:       term,
					term_media_id: settings[ term ].media,
					_wpnonce:      settings[ term ].nonce,
				}).done( function( resp ) {
					if ( resp == '0' ) {
						window.alert( media.l10n.error );
						return;
					}
					$( '.wp-term-media', media.parentEl ).filter( media.wrapEl + ' [data-term="' + term + '"]' )
						.toggleClass( 'has-image', resp.setImageClass )
						.html( resp.html );
					settings[ term ].nonce = resp.nonce;
				});
			},
			/**
			 * Remove the term media id, save the term media data and
			 * set the HTML in the term meta box to no term media.
			 */
			remove: function() {
				wp.media.termMedias[ media.name ].set( -1 );
			},
			/**
			 * The Term Media workflow
			 *
			 * @global wp.media.controller.FeaturedImage
			 * @global wp.media.view.l10n
			 *
			 * @this wp.media.termMedias
			 *
			 * @returns {wp.media.view.MediaFrame.Select} A media workflow.
			 */
			frame: function() {
				if ( this._frame ) {
					wp.media.frame = this._frame;
					return this._frame;
				}

				this._frame = wp.media({
					state:  media.key,
					states: [ new wp.media.controller.termMedias[ media.name ](), new wp.media.controller.EditImage() ]
				});

				this._frame.on( 'toolbar:create:' + media.key, function( toolbar ) {
					/**
					 * @this wp.media.view.MediaFrame.Select
					 */
					this.createSelectToolbar( toolbar, {
						text: media.l10n.setTermMedia
					});
				}, this._frame );

				this._frame.on( 'content:render:edit-media', function() {
					var selection = this.state( media.key ).get('selection'),
						view = new wp.media.view.EditImage( { model: selection.single(), controller: this } ).render();

					this.content.set( view );

					// after bringing in the frame, load the actual editor via an ajax call
					view.loadEditor();

				}, this._frame );

				this._frame.state( media.key ).on( 'select', this.select );
				return this._frame;
			},
			/**
			 * 'select' callback for Term Media workflow, triggered when
			 *  the 'Set Term Media' button is clicked in the media modal.
			 *
			 * @global wp.media.view.settings
			 *
			 * @this wp.media.controller.FeaturedImage
			 */
			select: function() {
				var selection = this.get('selection').single();

				if ( ! wp.media.view.settings.termMedias[ media.metaKey ] ) {
					return;
				}

				wp.media.termMedias[ media.name ].set( selection ? selection.id : -1 );
			},
			/**
			 * Open the content media manager to the 'term media' tab when
			 * the term media is clicked.
			 *
			 * Update the term media id when the 'remove' link is clicked.
			 *
			 * @global wp.media.view.settings
			 */
			init: function() {
				$( media.parentEl ).on( 'click', media.wrapEl + ' .wp-term-media-set', function( event ) {
					event.preventDefault();
					// Stop propagation to prevent thickbox from activating.
					event.stopPropagation();

					wp.media.termMedias[ media.name ].context( event ).frame().open();
				}).on( 'click', media.wrapEl + ' .wp-term-media-remove', function( event ) {
					wp.media.termMedias[ media.name ].context( event ).remove();
					return false;
				});
			},
			/**
			 * Return the id from the contexted term
			 *
			 * @returns {number} Term id
			 */
			term: function() {
				return this._term;
			},
			/**
			 * Set the term id from the provided event context
			 *
			 * @param {object} event The context's event object
			 * @returns {wp.media.termMedias} Returns itself to allow chaining
			 */
			context: function( event ) {
				var $term  = $( event.target ).parents( '.wp-term-media' );
				this._term = $term.data( 'term' );

				// Add newly created terms to the global settings
				if ( typeof wp.media.view.settings.termMedias[ media.metaKey ][ this._term ] === 'undefined' ) {
					wp.media.view.settings.termMedias[ media.metaKey ][ this._term ] = {
						'media': -1,
						'nonce': $term.data( 'nonce' )
					};
				}

				return this;
			}
		};

		$( wp.media.termMedias[ media.name ].init );
	};

}( jQuery, _ ) );

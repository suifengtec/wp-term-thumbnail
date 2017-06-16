/* globals jQuery: false, ajaxurl: false, wp: true, wpActiveEditor: true, wpTermThumbnail: false */
(function($, d, w, undefined) {

	var file_frame = {};
	// !Add new/Change thumbnail.
	$( "body" ).on( "click", ".add-term-thumbnail", function wpTermThumbnailOpenLibrary( e ) {
		var editor = $( this ).parent( ".thumbnail-field-wrapper" ).attr( "id" ).slice( 0, -14 );
		wpActiveEditor = editor;
		// If the media frame already exists, reopen it.
		if ( typeof( file_frame[ editor ] ) !== "undefined" ) {
			file_frame[ editor ].open();
			return;
		}
		// Create the media frame.
		file_frame[ editor ] = wp.media.frames.file_frame = wp.media( {
			title: wpTermThumbnail.chooseImage,
			button: {
				text: wpTermThumbnail.selectImage
			},
			library: {
				type: "image"
			},
			multiple: false
		} );

		// If the input has some value, preselect the image.
		file_frame[ editor ].on( "open", function wpTermThumbnailPreselectCurrentThumbnail() {
			var preselect = Number( d.getElementById( editor ).value ),
				attachment, selection;

			if ( ! preselect ) {
				return;
			}

			attachment = wp.media.attachment( preselect );

			if ( ! attachment ) {
				return;
			}

			attachment.fetch();
			selection = file_frame[ editor ].state().get( "selection" );

			selection.add( [ attachment ] );
		} );

		// When an image is selected, fill the input and create the image preview.
		file_frame[ editor ].on( "select", function wpTermThumbnailSelectCurrentThumbnail() {
			var attachment   = file_frame[ editor ].state().get( "selection" ).first().toJSON(),
				ActiveEditor = d.getElementById( editor ),
				$output_wrap = $( "#" + editor + "-field-wrapper" ),
				tt_ID        = $output_wrap.data( "tt-id" ),
				$image       = $( "<img />" ),
				orientation;

			// Input value
			ActiveEditor.value = attachment.id;

			// The image
			if ( attachment.sizes && typeof( attachment.sizes.medium ) === "object" ) {
				$image.attr( { "src": attachment.sizes.medium.url, "height": attachment.sizes.medium.height, "width": attachment.sizes.medium.width, "class": "attachment-thumbnail" } );
				orientation = attachment.sizes.medium.orientation;
			}
			else if ( attachment.sizes && typeof( attachment.sizes.thumbnail ) === "object" ) {
				$image.attr( { "src": attachment.sizes.thumbnail.url, "height": attachment.sizes.thumbnail.height, "width": attachment.sizes.thumbnail.width, "class": "attachment-thumbnail" } );
				orientation = attachment.sizes.thumbnail.orientation;
			}
			else {
				$image.attr( { "src": attachment.url, "height": attachment.height, "width": attachment.width, "class": "attachment-full" } );
				orientation = attachment.orientation;
			}

			$image.attr( { "alt": attachment.alt, "title": attachment.title } );

			// Button wrapping the image
			$image = $image.wrap( "<button type=\"button\" class=\"change-term-thumbnail add-term-thumbnail attachment\" id=\"thumbnail-button\" title=\"" + wpTermThumbnail.changeImage + "\"><span class=\"attachment-preview type-image\"><span class=\"thumbnail\"><span class=\"centered\"></span></span></span></button>" ).parents( ".attachment-preview" ).addClass( orientation ).parents( ".change-term-thumbnail" );

			// Insert all the things
			$output_wrap.text( "" ).append( $image ).append( "<div class=\"clear\"><div/>" ).append( "<button type=\"button\" class=\"remove-term-thumbnail button button-secondary button-large delete\">" + wpTermThumbnail.removeImage + "</button>" );
			// Set the thumbnail via ajax.
			if ( typeof tt_ID !== "undefined" && tt_ID ) {
				$output_wrap
					.find( ".add-term-thumbnail" ).attr( { "disabled": "disabled", "aria-disabled": "true", "title": wpTermThumbnail.loading } ).focus()
					.siblings( ".remove-term-thumbnail" ).after( "<span class=\"spinner is-active\"></span>" );

				wp.media.ajax( "wpTermThumbnail_set", {
					data: {
						id: attachment.id,
						tt_ID: Number( tt_ID ),
						term_ID: $( "[name=\"tag_ID\"]" ).val(),
						taxonomy: $( "[name=\"taxonomy\"]" ).val(),
						_wpnonce: d.getElementById( "_wpnonce" ).value
					}
				} )
				.done( function() {
					// Prevent updating the term thumbnail on form submit (it's useless).
					$output_wrap
						.find( ".add-term-thumbnail" ).removeAttr( "disabled aria-disabled" ).attr( "title", wpTermThumbnail.changeImage )
						.siblings( ".spinner" ).replaceWith( "<span class=\"dashicons dashicons-yes\"></span><input type=\"hidden\" name=\"term-thumbnail-updated\" value=\"1\" />" );

					if ( wp.a11y && wp.a11y.speak ) {
						wp.a11y.speak( wpTermThumbnail.successSet );
					}
				} )
				.fail( function() {
					$output_wrap
						.find( ".add-term-thumbnail" ).removeAttr( "disabled aria-disabled" ).attr( "title", wpTermThumbnail.changeImage )
						.siblings( ".spinner" ).replaceWith( "<div class=\"error-message\">" + wpTermThumbnail.errorSet + "</div>" );

					if ( wp.a11y && wp.a11y.speak ) {
						wp.a11y.speak( wpTermThumbnail.errorSet );
					}
				} );
			}
		} );

		// Finally, open the modal
		file_frame[ editor ].open();
	} );

	// !Remove thumbnail
	$("body").on( "click", ".remove-term-thumbnail", function wpTermThumbnailOpenLibrary( e ) {
		var editor = $(this).parent( ".thumbnail-field-wrapper" ).attr( "id" ).slice( 0, -14 ),
			$output_wrap = $( "#" + editor + "-field-wrapper" ),
			tt_ID        = $output_wrap.data( "tt-id" );

		// Input value
		d.getElementById( editor ).value = '';

		// Remove the wrapper content and insert the button.
		$output_wrap.text( "" ).append( "<button type=\"button\" class=\"add-term-thumbnail button button-secondary button-large\" id=\"thumbnail-button\">" + wpTermThumbnail.setImage + "</button><div class=\"clear\"></div>" );

		// Unset the thumbnail via ajax.
		if ( typeof tt_ID !== "undefined" && tt_ID ) {
			$output_wrap
				.find( ".add-term-thumbnail" ).attr( { "disabled": "disabled", "aria-disabled": "true", "title": wpTermThumbnail.loading } ).focus()
				.after( "<span class=\"spinner is-active\"></span>" );

			wp.media.ajax( "wpTermThumbnail_delete", {
				data: {
					tt_ID: Number( tt_ID ),
					term_ID: $( "[name=\"tag_ID\"]" ).val(),
					taxonomy: $( "[name=\"taxonomy\"]" ).val(),
					_wpnonce: d.getElementById( "_wpnonce" ).value
				}
			} )
			.done( function() {
				// Prevent updating the term thumbnail on form submit (it's useless).
				$output_wrap
					.find( ".add-term-thumbnail" ).removeAttr( "disabled aria-disabled" ).attr( "title", wpTermThumbnail.changeImage )
					.siblings( ".spinner" ).replaceWith( "<span class=\"dashicons dashicons-yes\"></span><input type=\"hidden\" name=\"term-thumbnail-updated\" value=\"1\" />" );

				if ( wp.a11y && wp.a11y.speak ) {
					wp.a11y.speak( wpTermThumbnail.successRemoved );
				}
			} )
			.fail( function() {
				$output_wrap
					.find( ".add-term-thumbnail" ).removeAttr( "disabled aria-disabled title" )
					.siblings( ".spinner" ).replaceWith( "<div class=\"error-message\">" + wpTermThumbnail.errorRemoved + "</div>" );

				if ( wp.a11y && wp.a11y.speak ) {
					wp.a11y.speak( wpTermThumbnail.errorRemoved );
				}
			} );
		}
	} );

	// !Change the attribute "for".
	$( "[for=\"thumbnail\"]" ).attr( "for", "thumbnail-button" );

	// !Deal with aria-hidden.
	$( ".wp-thumbnail-wrap" ).attr( "aria-hidden", "true" );
	$( ".thumbnail-field-wrapper" ).removeAttr( "aria-hidden" );

	// !If the thumbnail is not changed, these inputs will stay in place and prevent a useless thumbnail update.
	$( ".add-term-thumbnail, .remove-term-thumbnail" ).after( "<input type=\"hidden\" name=\"term-thumbnail-updated\" value=\"1\" />" );

	// !The "New tag" form is submitted via ajax: let's empty the field value after submition.
	if ( $( "#addtag" ).length ) {
		$.ajaxPrefilter( function( options ) {
			var data = "&" + options.data + "&";
			if ( data.indexOf( "&action=add-tag&" ) !== -1 ) {
				$( ".remove-term-thumbnail" ).click();
			}
		} );
	}

} )(jQuery, document, window);

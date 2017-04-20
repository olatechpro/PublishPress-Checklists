/**
 * @package PublishPress
 * @author PressShack
 *
 * Copyright (c) 2017 PressShack
 *
 * ------------------------------------------------------------------------------
 * Based on Edit Flow
 * Author: Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and
 * others
 * Copyright (c) 2009-2016 Mohammad Jangda, Daniel Bachhuber, et al.
 * ------------------------------------------------------------------------------
 *
 * This file is part of PublishPress
 *
 * PublishPress is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PublishPress is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PublishPress.  If not, see <http://www.gnu.org/licenses/>.
 */

/*====================================
=            Min Word Count          =
=====================================*/
// Based on the TinyMCE word count display found at /wp-admin/js/post.js
( function( $, counter, tinymce, _ ) {
	"use strict";

	if ( typeof objectL10n_checklist_req_min_words.requirements.min_word_count === 'undefined' ) {
		return;
	}

	var editor = tinyMCE.editors['content'];

	editor.onInit.add( function() {
		var $content      = $( '#content' ),
			$status       = $( '#pp-checklist-req-min_word_count' ).find( '.dashicons' ),
			$status_label = $( '#pp-checklist-req-min_word_count' ).find( '.status-label' ),
			prev_count    = 0,
			content_editor;

		/**
		 * Get the word count from TinyMCE and update the status of the requirement
		 */
		function update() {
			var text, count;

			if ( ! content_editor || content_editor.isHidden() ) {
				text = $content.val();
			} else {
				text = content_editor.getContent( { format: 'raw' } );
			}

			count = counter.count( text );

			if ( count !== prev_count ) {
				// Compare the count with the configured value
				if ( count >= objectL10n_checklist_req_min_words.requirements.min_word_count.value ) {
					// Ok
					$status.removeClass('dashicons-no');
					$status.addClass('dashicons-yes');
					$status_label.removeClass('status-no');
					$status_label.addClass('status-yes');
				} else {
					// Not ok
					$status.removeClass('dashicons-yes');
					$status.addClass('dashicons-no');
					$status_label.removeClass('status-yes');
					$status_label.addClass('status-no');
				}
			}

			prev_count = count;
		}

		/**
		 * Bind the word count update triggers.
		 *
		 * When a node change in the main TinyMCE editor has been triggered.
		 * When a key has been released in the plain text content editor.
		 */

		if ( editor.id !== 'content' ) {
			return;
		}

		content_editor = editor;

		editor.on( 'nodechange keyup', _.debounce( update, 500 ) );
		$content.on( 'input keyup', _.debounce( update, 500 ) );

		update();
	} );
} )( jQuery, new wp.utils.WordCounter(), tinymce, _ );
/*====  End of Min Word Count  ====*/
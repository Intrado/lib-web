/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 * SMK converted from standard BlockQuote to RCI editableBlock handling
 * SMK added support to toggle nonEditableBlock <-> editableBlock to minimize DOM insertion/removal
 */

(function() {
	function noBlockLeft( bqBlock ) {
		for ( var i = 0, length = bqBlock.getChildCount(), child; i < length && ( child = bqBlock.getChild( i ) ); i++ ) {
			if ( child.type == CKEDITOR.NODE_ELEMENT && child.isBlockBoundary() )
				return false;
		}
		return true;
	}

	var commandObject = {
		exec: function( editor ) {

			var state = editor.getCommand( 'mkblock' ).state;
			var selection = editor.getSelection();
			var range = selection && selection.getRanges( true )[ 0 ];

			if ( !range ) return;

			var bookmarks = selection.createBookmarks();

			// Kludge for #1592: if the bookmark nodes are in the beginning of
			// blockquote, then move them to the nearest block element in the
			// blockquote.
			if ( CKEDITOR.env.ie ) {
				var bookmarkStart = bookmarks[ 0 ].startNode,
					bookmarkEnd = bookmarks[ 0 ].endNode,
					cursor;

				if ( bookmarkStart && bookmarkStart.getParent().getName() == 'div' ) {
					cursor = bookmarkStart;
					while ( ( cursor = cursor.getNext() ) ) {
						if ( cursor.type == CKEDITOR.NODE_ELEMENT && cursor.isBlockBoundary() ) {
							bookmarkStart.move( cursor, true );
							break;
						}
					}
				}

				if ( bookmarkEnd && bookmarkEnd.getParent().getName() == 'div' ) {
					cursor = bookmarkEnd;
					while ( ( cursor = cursor.getPrevious() ) ) {
						if ( cursor.type == CKEDITOR.NODE_ELEMENT && cursor.isBlockBoundary() ) {
							bookmarkEnd.move( cursor );
							break;
						}
					}
				}
			}

			var iterator = range.createIterator(),
				block;
			iterator.enlargeBr = editor.config.enterMode != CKEDITOR.ENTER_BR;

			if ( state == CKEDITOR.TRISTATE_OFF ) {
				var paragraphs = [];

				var gotit = false;
				while ( ( block = iterator.getNextParagraph() ) ) {
					paragraphs.push( block );

					// If the selection is inside a nonEditableBlock the we can reactivate...
					var thisblock = block;
					do {
						if (thisblock.hasClass('nonEditableBlock')) {
							thisblock.removeClass('nonEditableBlock');
							thisblock.addClass('editableBlock');
							gotit = true;
							break;
						}
					} while (thisblock = thisblock.getParent());
					if (gotit) break;
				}

				if (! gotit) {

					// If no paragraphs, create one from the current selection position.
					if ( paragraphs.length < 1 ) {
						var para = editor.document.createElement( editor.config.enterMode == CKEDITOR.ENTER_P ? 'p' : 'div' ),
							firstBookmark = bookmarks.shift();
						range.insertNode( para );
						para.append( new CKEDITOR.dom.text( '\ufeff', editor.document ) );
						range.moveToBookmark( firstBookmark );
						range.selectNodeContents( para );
						range.collapse( true );
						firstBookmark = range.createBookmark();
						paragraphs.push( para );
						bookmarks.unshift( firstBookmark );
					}

					// Make sure all paragraphs have the same parent.
					var commonParent = paragraphs[ 0 ].getParent(),
						tmp = [];
					for ( var i = 0; i < paragraphs.length; i++ ) {
						block = paragraphs[ i ];
						commonParent = commonParent.getCommonAncestor( block.getParent() );
					}

					// The common parent must not be the following tags: table, tbody, tr, ol, ul.
					var denyTags = { table:1,tbody:1,tr:1,ol:1,ul:1 };
					while ( denyTags[ commonParent.getName() ] )
						commonParent = commonParent.getParent();

					// Reconstruct the block list to be processed such that all resulting blocks
					// satisfy parentNode.equals( commonParent ).
					var lastBlock = null;
					while ( paragraphs.length > 0 ) {
						block = paragraphs.shift();
						while ( !block.getParent().equals( commonParent ) )
							block = block.getParent();
						if ( !block.equals( lastBlock ) )
							tmp.push( block );
						lastBlock = block;
					}

					// If any of the selected blocks is an editableBlock, remove it to prevent nesting
					while ( tmp.length > 0 ) {
						block = tmp.shift();
						if ( block.hasClass('editableBlock') ) {
							var docFrag = new CKEDITOR.dom.documentFragment( editor.document );
							while ( block.getFirst() ) {
								docFrag.append( block.getFirst().remove() );
								paragraphs.push( docFrag.getLast() );
							}

							docFrag.replace( block );
						} else
							paragraphs.push( block );
					}

					// Now we have all the blocks to be included...
					var bqBlock = editor.document.createElement( 'div' );
					bqBlock.addClass('editableBlock');
					bqBlock.addClass('dynamicBlock');
					bqBlock.insertBefore( paragraphs[ 0 ] );
					while ( paragraphs.length > 0 ) {
						block = paragraphs.shift();
						bqBlock.append( block );
					}
				}

			} else if ( state == CKEDITOR.TRISTATE_ON ) {
				var moveOutNodes = [];
				var database = {};

				var gotit = false;
				while ( ( block = iterator.getNextParagraph() ) ) {
					var bqParent = null, bqChild = null;

					// If the selection is inside the editableBlock being removed...
					var thisblock = block;
					do {
						if (thisblock.hasClass('editableBlock')) {

							// If this block was added by us dynamically...
							if (thisblock.hasClass('dynamicBlock')) {

								// Then remove it altogether; the 'true' makes it remove
								// the container but preserve the inner content
								thisblock.remove(true);
							}
							else {

								// Otherwise, it was preexisting and just made
								// editable, so just remove the editable class
								thisblock.removeClass('editableBlock');
								thisblock.addClass('nonEditableBlock');
							}
							gotit = true;
							break;
						}
					} while (thisblock = thisblock.getParent());
					if (gotit) break;

					// Otherwise scan back down through the hierarchy until we find the first container that needs to be grabbed
					while ( block.getParent() ) {

						if ( block.getParent().hasClass('editableBlock') ) {
							bqParent = block.getParent();
							bqChild = block;
							break;
						}
						block = block.getParent();
					}

					// Remember the blocks that were recorded down in the moveOutNodes array to prevent duplicates.
					if ( bqParent && bqChild && !bqChild.getCustomData( 'div' + '_moveout' ) ) {
						moveOutNodes.push( bqChild );
						CKEDITOR.dom.element.setMarker( database, bqChild, 'div' + '_moveout', true );
					}
				}

				if (! gotit) {
					CKEDITOR.dom.element.clearAllMarkers( database );

					var movedNodes = [], processedBlockquoteBlocks = [];

					database = {};
					while ( moveOutNodes.length > 0 ) {
						var node = moveOutNodes.shift();
						bqBlock = node.getParent();

						// If the node is located at the beginning or the end, just take it out
						// without splitting. Otherwise, split the blockquote node and move the
						// paragraph in between the two blockquote nodes.
						if ( !node.getPrevious() )
							node.remove().insertBefore( bqBlock );
						else if ( !node.getNext() )
							node.remove().insertAfter( bqBlock );
						else {
							node.breakParent( node.getParent() );
							processedBlockquoteBlocks.push( node.getNext() );
						}

						// Remember the blockquote node so we can clear it later (if it becomes empty).
						if ( !bqBlock.getCustomData( 'div' + '_processed' ) ) {
							processedBlockquoteBlocks.push( bqBlock );
							CKEDITOR.dom.element.setMarker( database, bqBlock, 'div' + '_processed', true );
						}

						movedNodes.push( node );
					}

					CKEDITOR.dom.element.clearAllMarkers( database );

					// Clear blockquote nodes that have become empty.
					for ( i = processedBlockquoteBlocks.length - 1; i >= 0; i-- ) {
						bqBlock = processedBlockquoteBlocks[ i ];
						if ( noBlockLeft( bqBlock ) )
							bqBlock.remove();
					}
				}
			}

			selection.selectBookmarks( bookmarks );
			editor.focus();
		},

		refresh: function( editor, path ) {
			// Check if inside of editableBlock
			var firstBlock = path.block || path.blockLimit;
			var el = editor.elementPath(firstBlock);
			var insideEditableBlock = false;
			if (el) {
				el.elements.each(function (e) {
					if (e.hasClass('editableBlock')) {
						insideEditableBlock = true;
					}
				});
			}
			this.setState( insideEditableBlock ? CKEDITOR.TRISTATE_ON : CKEDITOR.TRISTATE_OFF );
		},

		context: 'mkblock'
	};

	CKEDITOR.plugins.add( 'mkblock', {
		icons: 'mkblock',
		init: function( editor ) {
			if ( editor.blockless )
				return;

			editor.addCommand( 'mkblock', commandObject );

			editor.ui.addButton && editor.ui.addButton( 'mkBlock', {
				label: 'Make Editable Block',
				command: 'mkblock',
				toolbar: 'blocks,10'
			});
		}
	});
})();

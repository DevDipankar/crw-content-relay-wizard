(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	jQuery(document).ready(function($) {
		//console.log(OBJ);
		if('source' === OBJ.crw_env_type){
			// Find the Publish button by its ID
			var publishButton = $('#publish');
			var original_publish = $('#original_publish').val();
		
			// Change the text of the Publish button
			original_publish == 'Update' ? publishButton.val('Update') : publishButton.val('Submit'); // Change 'Your Custom Text' to whatever you want the button text to be
		}
		
	});
	

})( jQuery );

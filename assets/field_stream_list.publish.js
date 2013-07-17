(function($) {

	$(document).on('ready.stream_list', function() {
  			
 		$(event.target).find('input').on('focus.stream_list click.stream_list', function(event) {
//  			event.preventDefault();
//  			event.stopPropagation();
  		});

		$('.stream_list-duplicator').symphonyDuplicator({
				orderable: true,
				collapsible: true
	  		});

	});

})(window.jQuery);

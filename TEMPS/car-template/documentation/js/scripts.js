(function ($) {
	"use strict";

	// Page Loaded...
	$(document).ready(function () {

		/*==========  Tooltip  ==========*/
		$('.tool-tip').tooltip();

		/*==========  Alerts  ==========*/
		$('.alert').bind('inview', function (event, isInView) {
			if (isInView) {
				$(this).addClass('in');
			}
		});
		$(function() {
			$('[data-hide]').on('click', function() {
				$(this).closest('.' + $(this).attr('data-hide')).fadeOut();
			});
		});

		$('.menu nav a').on('click', function(event){
			event.preventDefault();
			var target= $(this.hash);
			$('body,html').animate({
				'scrollTop': target.offset().top
				}, 400
			);
		});

	});

})(jQuery);
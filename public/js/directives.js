'use strict';

/* Directives */


angular.module('myApp.directives', []).
directive('fileUpload', function() {
	return function(scope, element, attrs) {
		$(element).wrap('<div></div>');
		var $progressDiv = $('<div></div>').addClass('progress_report');
		$('<div></div>').addClass('progress_report_name').appendTo($progressDiv);
		$('<div></div>').addClass('progress_report_status').appendTo($progressDiv).css('style','italic');
		var $progressBarDiv = $('<div></div>').addClass('progress_report_bar_container').css('width','90%').css('height','5px').appendTo($progressDiv);
		$('<div></div>').addClass('progress_report_bar').css('background-color','blue').css('width','0').css('height','100%').appendTo($progressBarDiv);

		$(element).parent().append($progressDiv);
		$(element).html5_upload({
			url: 'document.php',
			sendBoundary: window.FormData || $.browser.mozilla,
			onStart: function(event, total) {
				return true;
				return confirm("You are trying to upload " + total + " files. Are you sure?");
			},
			onProgress: function(event, progress, name, number, total) {
				console.log(progress, number);
			},
			setName: function(text) {
				$(".progress_report_name").text(text);
			},
			setStatus: function(text) {
				$(".progress_report_status").text(text);
			},
			setProgress: function(val) {
				$(".progress_report_bar").css('width', Math.ceil(val*100)+"%");
			},
			onFinishOne: function(event, response, name, number, total) {
				//alert(response);
			},
			onError: function(event, name, error) {
				alert('error while uploading file ' + name);
			}
		});
	};
});

'use strict';

/* Directives */


angular.module('myApp.directives', [])

	.directive('ngFocus', ['$parse', function($parse) {
		return function(scope, element, attr) {
			var fn = $parse(attr['ngFocus']);
			element.bind('focus', function(event) {
				scope.$apply(function() {
					fn(scope, {$event:event});
				});
			});
		}
	}])

	.directive('ngBlur', ['$parse', function($parse) {
		return function(scope, element, attr) {
			var fn = $parse(attr['ngBlur']);
			element.bind('blur', function(event) {
				scope.$apply(function() {
					fn(scope, {$event:event});
				});
			});
		}
	}])

	.directive('ngEnter', ['$parse', function($parse) {
		return function(scope, element, attr) {
			var fn = $parse(attr['ngEnter']);
			element.bind("keydown keypress", function(event) {
				if(event.which === 13) {
					scope.$apply(function(){
						fn(scope,{event:event});
					});
					event.preventDefault();
				}
			});
		};
	}])

	.directive('ngHelp', function(){
		return function(scope, element, attrs){
			$(element).button({
				icons: {
					primary: "ui-icon-note"
				},
				text: false
			});
		}
	})

	.directive('ngButton',function(){
		return function(scope, element, attrs){
			$(element).button({
				icons: {
					primary: attrs.ngButton
				},
				text: false
			})
		}
	})

	.directive('ngDetailDialog',function(){
		return function(scope, element, attrs){
			$(element).dialog();
			scope.$watch('selectedDocument',function(newValue,oldValue){
				if (newValue == null || typeof newValue == 'undefined')
					$(element).dialog('close');
				else
					$(element).dialog('open');
			});
			$(element).dialog({
				autoOpen: false
			});
		}
	})

	.directive('fileUpload', function() {
	return function(scope, element, attrs) {
		$(element).wrap('<div></div>');
		var $progressDiv = $('<div></div>').addClass('progress_report');
		$('<div></div>').addClass('progress_report_name').appendTo($progressDiv);
		$('<div></div>').addClass('progress_report_status').appendTo($progressDiv).css('style','italic');
		$('<div></div>').addClass('progress_report_errors').appendTo($progressDiv).css('color','red');
		var $progressBarDiv = $('<div></div>').addClass('progress_report_bar_container').css('width','90%').css('height','5px').appendTo($progressDiv);
		$('<div></div>').addClass('progress_report_bar').css('background-color','blue').css('width','0').css('height','100%').appendTo($progressBarDiv);

		var onFinish = function(event,total){
			$(".progress_report_bar").css('width','0');
			$(".progress_report_name").html('');
			scope.$emit('uploadComplete',{event:event});
		}

		var onError = function(event, name, error){
			var response = $.parseJSON(error.currentTarget.response);
//			var response = JSON.parse(error.currentTarget.response);
			for (var i=0; i<response.length; i++){
				$('<div></div>').addClass('error').html('Error uploading '+name+': '+response[i].message).appendTo('.progress_report_errors');
//				console.log(response[i].message);
			}
			scope.$emit('uploadError',{event:event,name:name,error:error});
		}

		var totalFileCount = 1;
		var currentFileCount = 1;
		var onStart = function(event, total){
			$('.progress_report_errors').html('');
			totalFileCount = total;
			return true;
		}
		var onProgress = function(event, progress, name, number, total) {
			currentFileCount = number;
		}
		var setProgress = function(val){
			$(".progress_report_bar").css('width', Math.ceil(val*(currentFileCount+1)/(totalFileCount+1)*100)+"%");
		}


		$(element).parent().append($progressDiv);
		$(element).html5_upload({
			url: 'document.php',
			sendBoundary: window.FormData || $.browser.mozilla,
			onStart: onStart,
			onProgress: onProgress,
			setName: function(text) {
				$(".progress_report_name").text(text);
			},
			setStatus: function(text) {
				$(".progress_report_status").text(text);
			},
			setProgress: setProgress,
			onFinish: onFinish,
			onError: function(event, name, error) {
				onError(event,name,error);
//				alert('error while uploading file ' + name);
			},
			genName: function(file, number, total) {
				return file + " (" + (number+1) + " of " + total + ")";
			},
			STATUSES: {
				'STARTED'   : 'Started',
				'PROGRESS'  : 'Progress',
				'LOADED'    : 'Processing',
				'FINISHED'  : 'Finished'
			}
		});
	};
});

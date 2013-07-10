'use strict';

/* Directives */


angular.module('myApp.directives', [])

	.directive('nistFocus', ['$parse', function($parse) {
		return function(scope, element, attr) {
			var fn = $parse(attr['ngFocus']);
			element.bind('focus', function(event) {
				scope.$apply(function() {
					fn(scope, {$event:event});
				});
			});
		}
	}])

	.directive('nistBlur', ['$parse', function($parse) {
		return function(scope, element, attr) {
			var fn = $parse(attr['ngBlur']);
			element.bind('blur', function(event) {
				scope.$apply(function() {
					fn(scope, {$event:event});
				});
			});
		}
	}])

	.directive('nistEnter', ['$parse', function($parse) {
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

//	.directive('ngHelp', function(){
//		return function(scope, element, attrs){
//			$(element).button({
//				icons: {
//					primary: "ui-icon-note"
//				},
//				text: false
//			});
//		}
//	})

//	.directive('ngClose', function(){
//		return function(scope,element,attrs){
//			$(element).button({
//				icons: {
//					primary: "ui-icon-close"
//				},
//				text: false
//			});
//		}
//	})
//
	.directive('nistButton',function(){
		return function(scope, element, attrs){
			$(element).button({
				icons: {
					primary: attrs.nistButton
				},
				text: false
			})
		}
	})

	.directive('nistDialog',function(){
		return {
			link: function(scope,element,attrs){
				scope.$watch(attrs.nistDialog,function(newValue,oldValue){
					if (newValue == null || typeof newValue == 'undefined')
						$(element).dialog('close');
					else
						$(element).dialog('open');
				});
				if (typeof attrs.nistDialogWidth !== 'undefined'){
					var p, width=200,height=200;
					if (typeof attrs.nistDialogHeight !== 'undefined'){
						if (attrs.nistDialogHeight.indexOf('%')>=0){
							p = attrs.nistDialogHeight.replace('%','');
							height = p/100*window.innerHeight;
						} else {
							height = attrs.nistDialogHeight;
						}
					}
					if (typeof attrs.nistDialogWidth !== 'undefined'){
						if (attrs.nistDialogWidth.indexOf('%')>=0){
							p = attrs.nistDialogWidth.replace('%','');
							width = p/100*window.innerWidth;
						} else {
							width = attrs.nistDialogWidth;
						}
					}
				}
				$(element).dialog({
					autoOpen: false,
					width:width,
					height:height
				});
			}
		};

		return function(scope, element, attrs){

			scope.$watch(attrs.nistDialog,function(newValue,oldValue){
				if (newValue == null || typeof newValue == 'undefined')
					$(element).dialog('close');
				else
					$(element).dialog('open');
			});
			if (typeof attrs.nistDialogWidth !== 'undefined'){
				var p, width=200,height=200;
				if (typeof attrs.nistDialogHeight !== 'undefined'){
					if (attrs.nistDialogHeight.indexOf('%')>=0){
						p = attrs.nistDialogHeight.replace('%','');
						height = p/100*window.innerHeight;
					} else {
						height = attrs.nistDialogHeight;
					}
				}
				if (typeof attrs.nistDialogWidth !== 'undefined'){
					if (attrs.nistDialogWidth.indexOf('%')>=0){
						p = attrs.nistDialogWidth.replace('%','');
						width = p/100*window.innerWidth;
					} else {
						width = attrs.nistDialogWidth;
					}
				}
			}
			$(element).dialog({
				autoOpen: false,
				width:width,
				height:height
			});
		}
	})

//	.directive('nistSelectable',function(){
//		return function(scope,element,attrs){
//			console.log(element);
//
//			$.each(element,function(){
//				$.each(this.children,function(){
//
//				});
//			});
//			$(element).selectable({
//				stop:function(event,ui){
//					var selected = element.find('.ui-selected').map(function(){
//						var i = $(this).index();
//						return {name:scope.items[i].name,index:i}
//					}).get();
//					$scope.selectedPages = selected;
//					scope.$apply();
//				}
//			});
//		}
//	})

	.directive('nistFileUpload', function() {
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

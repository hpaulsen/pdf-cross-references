'use strict';

/* Services */


// Demonstrate how to register services
// In this case it is a simple value service.
angular.module('myApp.services', ['ngResource'])
	.factory('Pattern',function($resource){
		return $resource('pattern.php');
	})
	.factory('Document',function($resource){
		return $resource('document.php');
	})
;

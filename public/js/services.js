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
	.factory('Name',function($resource){
		return $resource('name.php');
	})
	.factory('DocumentDetail',function($resource){
		return $resource('document_detail.php');
	})
	.factory('CrossReference',function($resource){
		return $resource('cross_reference.php')
	})
;

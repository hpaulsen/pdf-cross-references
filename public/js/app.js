'use strict';


// Declare app level module which depends on filters, and services
angular.module('myApp', ['myApp.filters', 'myApp.services', 'myApp.directives', 'myApp.controllers', 'ui']).
	config(['$routeProvider', function($routeProvider) {
		$routeProvider
//		    .when('/patterns', {templateUrl: 'partials/patterns.html', controller: 'PatternController'})
		    .when('/documents', {templateUrl: 'partials/documents.html', controller: 'DocumentController'})
			.when('/search', {templateUrl: 'partials/search.html', controller: 'SearchController'})
		    .otherwise({redirectTo: '/documents'});
	}]);

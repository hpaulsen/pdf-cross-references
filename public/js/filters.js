'use strict';

/* Filters */

angular.module('myApp.filters', [])
	.filter('pattern', ['Pattern', function(Pattern) {
	    return function(text) {
		    return text;
		    return Pattern.query({id:id});
	    }
	}])
;

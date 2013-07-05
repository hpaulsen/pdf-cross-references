'use strict';

/* Filters */

angular.module('myApp.filters', [])
	.filter('pattern', ['Pattern', function(Pattern) {
	    return function(text) {
		    return text;
//		    return Pattern.query({id:id});
	    }
	}])
	.filter('document', function(){
		return function(text, documents) {
//			return text;
			for (var i=0; i<documents.length; i++){
				if (documents[i].id == text){
					return documents[i].doc_id;
				}
			}
			/* else */
			return text;
		}
	})
;

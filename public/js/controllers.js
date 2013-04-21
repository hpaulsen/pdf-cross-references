'use strict';

/* Controllers */

angular.module('myApp.controllers', []).
	controller('PatternController', ['$scope','Pattern',function($scope,Pattern) {
		$scope.refreshList = function(){
			$scope.patterns = Pattern.query();
			$scope.newPattern = new Pattern();
		}

		$scope.saveNewPattern = function(){
			$scope.newPattern.$save(
				function(){
					$scope.errors = false;
					$scope.refreshList();
				},
				function(response){
					$scope.errors = response.data.errors;
					console.log($scope.errors);
				}
			)
		}

		$scope.deletePattern = function(item){
			item.$delete({id:item.id},function(){$scope.refreshList()});
		}

		$scope.refreshList();
	}])
	.controller('DocumentController', ['$scope','Document',function($scope,Document) {
		$scope.refreshList = function(){
			$scope.documents = Document.query();
		}

		$scope.setFile = function(element){
			$scope.$apply(function($scope){
				$scope.files = [];
				for (var $i=0; $i<element.files.length; $i++){

					console.log(element.files[$i]);
				}
			})
		}

		$scope.deleteDocument = function(item){
			item.$delete({id:item.id},function(){$scope.refreshList()});
		}

		$scope.refreshList();
	}])
;
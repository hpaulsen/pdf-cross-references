'use strict';

/* Controllers */

angular.module('myApp.controllers', [])

	.controller('PatternController', ['$scope','Pattern',function($scope,Pattern) {
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

	.controller('DocumentController', ['$scope','$rootScope','Document','Name',function($scope,$rootScope,Document,Name) {

		// Event listener for file upload
		$scope.$on('uploadComplete',function(event){
			$scope.refreshList();
		});

		$scope.refreshList = function(){
			$scope.documents = Document.query(function(){
				for (var i=0; i<$scope.documents.length; i++){
					$scope.refreshNames($scope.documents[i]);
//					$scope.documents[i].names = Name.query({file_id:$scope.documents[i].id});
//					$scope.documents[i].newName = new Name();
//					$scope.documents[i].newName.file_id = $scope.documents[i].id;
				}
			});
			$scope.selectDocument();
		}

		$scope.refreshNames = function(document){
			document.names = Name.query({file_id:document.id});
			document.newName = new Name();
			document.newName.file_id = document.id;
		}

		$scope.saveName = function(name){
			var document;
			for (var i=0; i<$scope.documents.length; i++){
				if ($scope.documents[i].id == name.file_id) {
					document = $scope.documents[i];
					break;
				}
			}
			name.$save(function(){$scope.refreshNames(document)});
		}

		$scope.selectedDocument = null;

		$scope.selectDocument = function(document){
			if (document != null && typeof document !== 'undefined'){
				if ($scope.selectedDocument == document.id)
					$scope.selectedDocument = null; // toggle
				else
					$scope.selectedDocument = document.id;
			} else {
				$scope.selectedDocument = null;
			}
			console.log('sending selectDocument ('+$scope.selectedDocument+')');
			$rootScope.$broadcast('selectDocument',{id:$scope.selectedDocument});
		}

		$scope.deleteName = function(name){
			name.$delete({id:name.id},function(){$scope.refreshList()});
		}

		$scope.setFile = function(element){
			$scope.$apply(function($scope){
				$scope.files = [];
//				for (var $i=0; $i<element.files.length; $i++){
//					console.log(element.files[$i]);
//				}
			})
		}

		$scope.deleteDocument = function(item){
			item.$delete({id:item.id},function(){$scope.refreshList()});
		}

		$scope.refreshList();
	}])

	.controller('DocumentDetailController',['$scope','$rootScope','DocumentDetail',function($scope,$rootScope,DocumentDetail){
		$scope.$on('selectDocument',function(event,args){
			console.log('received selectDocument ('+args.id+')');
			console.log(args);
			console.log(typeof args.id);
			if (args.id != null){
				$scope.documentDetails = DocumentDetail.query({file_id:args.id});
			} else {
				$scope.documentDetails = null;
			}
		});
	}])
;
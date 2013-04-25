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
			if (args.id != null){
				$scope.documentDetails = DocumentDetail.query({file_id:args.id});
			} else {
				$scope.documentDetails = null;
			}
		});
	}])

	.controller('SearchController',['$scope','$q','Document','Pattern','CrossReference',function($scope,$q,Document,Pattern,CrossReference){

		$scope.patterns = Pattern.query();
		$scope.documents = Document.query();
//		$scope.crossReferences = CrossReference.query();

		$scope.deleteReference = function(reference){
			console.log(reference);
			reference.$delete({id:reference.id});
		}

		$scope.$watch('selectedPattern',function(id){
			$scope.crossReferences = CrossReference.query({pattern_id:id});
		});

		$scope.beginSearch = function(docs,pattern){
			var currentDocIndex = -1;
			var doNext = function(){
				currentDocIndex++;
				var currentDocId = docs[currentDocIndex];
				if (currentDocIndex < docs.length){
					var cr = new CrossReference();
					cr.$save({file_id:currentDocId,pattern_id:pattern},doNext);
				}
				else
					$scope.crossReferences = CrossReference.query({pattern_id:pattern});
			}
			doNext();
		}
	}])
;
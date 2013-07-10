'use strict';

/* Controllers */

angular.module('myApp.controllers', [])

	.controller('DocumentController', ['$scope','$rootScope','Document','Page','CrossReference',function($scope,$rootScope,Document,Page,CrossReference) {

		// Event listener for file upload
		$scope.$on('uploadComplete',function(event){
			$scope.refreshList();
		});

		$scope.refreshList = function(){
			$scope.documents = Document.query(function(){
				for (var i=0; i<$scope.documents.length; i++){
					$scope.documents[i].pages = Page.query({file_id:$scope.documents[i].id});
					$scope.documents[i].detailsVisible = false;
				}
			});
			$scope.selectDocument();
		}

		$scope.selectedDocument = null;

		$scope.selectDocument = function(document){
			if (document != null && typeof document !== 'undefined'){
				if ($scope.selectedDocument == document.id){
					$scope.selectedDocument = null; // toggle
					$scope.documentDetails = null;
				}
				else{
					$scope.selectedDocument = document.id;
					$scope.documentDetails = Document.query({id:document.id,metadata:true});
				}
			} else {
				$scope.selectedDocument = null;
			}
		}

		$scope.deleteName = function(name){
			name.$delete({id:name.id},function(){$scope.refreshList()});
		}

		$scope.setFile = function(element){
			$scope.$apply(function($scope){
				$scope.files = [];
			})
		}

		$scope.deleteDocument = function(item){
			item.$delete({id:item.id},function(){$scope.refreshList()});
		}

		$scope.togglePage = function(document,page){
			console.log(page);
			page.include = !page.include;
			page.$save();
			if (page.include)
				document.num_references -= -page.num_references;
			else
				document.num_references -= page.num_references;
		}

		function selectPage(document,pageNumber){
			var pageDetail = Page.query({file_id:document.id,page:pageNumber},function(){
				$scope.selectedPage = pageDetail[0];
				$scope.selectedPage.numReferences = document.pages[pageNumber-1].num_references;
				$scope.selectedPage.document = document;
			});
		}

		$scope.viewPage = function(document,page,$event){
			$event.stopPropagation();
			if (document != null && typeof document !== 'undefined' && page != null && typeof page !== 'undefined'){
				selectPage(document,page.page);
			} else {
				$scope.selectedPage = null;
			}
		}

		$scope.firstPage = function(){
			selectPage($scope.selectedPage.document,1);
		}

		$scope.prevPage = function(){
			if ($scope.selectedPage.page > 1){
				selectPage($scope.selectedPage.document,$scope.selectedPage.page-1);
			}
		}

		$scope.nextPage = function(){
			if ($scope.selectedPage.page < $scope.documents[$scope.selectedPage.file_id].num_pages-0){
				var newPageNumber = $scope.selectedPage.page-(-1);
				selectPage($scope.selectedPage.document,newPageNumber);
			}
		}

		$scope.lastPage = function(){
			var newPageNumber = $scope.documents[$scope.selectedPage.file_id].num_pages;
			selectPage($scope.selectedPage.document,newPageNumber);
		}

		$scope.refreshList();
	}])

	.controller('SearchController',['$scope','$q','Document','CrossReference',function($scope,$q,Document,CrossReference){
		$scope.documents = Document.query();
		$scope.deleteReference = function(reference){
			reference.$delete({id:reference.id},function(){$scope.crossReferences = CrossReference.query({pattern_id:$scope.selectedPattern.id})});
		}
		$scope.crossReferences = CrossReference.query();
	}])
	.controller('ChartController',['$scope','$q','CrossReference',function($scope,$q,CrossReference){
		$scope.crossReferences = CrossReference.query({summary:true});
	}])
;

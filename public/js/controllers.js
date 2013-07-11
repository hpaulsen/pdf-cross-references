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

		$scope.start = 0; $scope.count = 15;
		var loadReferences = function(start){
			$scope.start = start;
			$scope.crossReferences = CrossReference.query({start:$scope.start,count:$scope.count});
		}

		loadReferences(0,15);

		var tmp = CrossReference.count(function(){
			$scope.crossReferenceCount = tmp[0].count;
		});
		$scope.deleteReference = function(reference){
			reference.$delete({id:reference.id},function(){$scope.crossReferences = CrossReference.query({pattern_id:$scope.selectedPattern.id})});
		}

		var lastPageStart = function(){
			var numPages = Math.floor($scope.crossReferenceCount/$scope.count)+1;
			var lastStart = numPages*$scope.count;
			if (lastStart >= $scope.crossReferenceCount) lastStart -= $scope.count;
			return lastStart;
		}

		$scope.first = function(){
			loadReferences(0);
		}
		$scope.next = function(){
			var start = Math.min($scope.start+$scope.count,lastPageStart());
			loadReferences(start);
		}
		$scope.prev = function(){
			var start = Math.max($scope.start-$scope.count,0);
			loadReferences(start);
		}
		$scope.last = function(){
			loadReferences(lastPageStart());
		}
	}])
	.controller('ChartController',['$scope','$q','CrossReference',function($scope,$q,CrossReference){
		$scope.crossReferences = CrossReference.query({summary:true});

		$scope.gexf = CrossReference.gexf();

		$scope.filtersVisible = false;

		$scope.toggleFilters = function(){
			$scope.filtersVisible = !$scope.filtersVisible;
		}

		var filters = [];

		$scope.toggleFilterItem = function(docType,$event){
			if ($event.toElement.nodeName == 'LABEL') return; // ignore
			if (filters.indexOf(docType)>=0)
				filters.splice(filters.indexOf(docType),1);
			else
				filters.push(docType);
		}

		var getNodeDocType = function(n){
			var type='';
			for (var i=0; i< n.attr.attributes.length; i++){
				if (n.attr.attributes[i].attr=='doc_type'){
					type = n.attr.attributes[i].val;
				}
			}
			return type;
		}
		var nodeShouldBeVisible = function(n){
			if (filters.length==0) return true;
			var type = getNodeDocType(n);
			return filters.indexOf(type)>=0;
		}

		$scope.refreshFilter = function(){
			sigInst.iterNodes(function(n){
				if (nodeShouldBeVisible(n)){
					n.hidden=0;
				} else {
					n.hidden=1;
				}
			}).draw();
		}
		$scope.types = CrossReference.query({types:true});

		// Instanciate sigma.js and customize rendering :
		var sigInst = sigma.init(document.getElementById('sigma')).drawingProperties({
			defaultLabelColor: '#fff',
			defaultLabelSize: 14,
			defaultLabelBGColor: '#fff',
			defaultLabelHoverColor: '#000',
			labelThreshold: 6,
			defaultEdgeType: 'curve'
		}).graphProperties({
				minNodeSize: 0.5,
				maxNodeSize: 5,
				minEdgeSize: 1,
				maxEdgeSize: 1
			}).mouseProperties({
				maxRatio: 4
			});

		// Parse a GEXF encoded file to fill the graph
		// (requires "sigma.parseGexf.js" to be included)
		sigInst.parseGexf('/cross_reference.php?gexf=true');

		// Bind events :
		sigInst.bind('overnodes',function(event){
			var nodes = event.content;
			var neighbors = {};
			sigInst.iterEdges(function(e){
				if(nodes.indexOf(e.source)>=0 || nodes.indexOf(e.target)>=0){
					neighbors[e.source] = 1;
					neighbors[e.target] = 1;
				}
			}).iterNodes(function(n){
					if(!neighbors[n.id]){
						n.hidden = 1;
					}else{
						if (nodeShouldBeVisible(n))
							n.hidden = 0;
						else
							n.hidden = 1;
					}
				}).draw(2,2,2);
			}).bind('outnodes',function(){
				sigInst.iterEdges(function(e){
					e.hidden = 0;
				}).iterNodes(function(n){
					if (nodeShouldBeVisible(n))
						n.hidden = 0;
				}).draw(2,2,2);
			});

		// Draw the graph :
		sigInst.draw();
	}])
;

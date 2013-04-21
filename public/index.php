<?php require '../app/bootstrap.php'; ?>
<!doctype html>
<html lang="en" ng-app="myApp">
<head>
	<meta charset="utf-8">
	<title>NIST PDF Cross-Reference Analyzer</title>
	<link rel="stylesheet" href="css/app.css"/>
	<link rel="stylesheet" href="lib/angular-ui/angular-ui.min.css"/>
	<script src="lib/angular-ui/angular-ui-ieshiv.min.js"></script>
</head>
<body>
<ul class="menu">
	<li><a href="#/patterns">Patterns</a></li>
	<li><a href="#/documents">Documents</a></li>
</ul>

<div ng-view></div>

<!-- In production use:
  <script src="//ajax.googleapis.com/ajax/libs/angularjs/1.0.6/angular.min.js"></script>
  -->
<script src="lib/angular/angular.js"></script>
<script src="lib/angular/angular-resource.js"></script>
<script src="js/app.js"></script>
<script src="js/services.js"></script>
<script src="js/controllers.js"></script>
<script src="js/filters.js"></script>
<script src="js/directives.js"></script>
<script src="lib/angular-ui/angular-ui.min.js"></script>
<script src="lib/jquery-1.9.1.min.js"></script>
<script src="lib/jquery-html5-upload-master/jquery.html5_upload.js"></script>
</body>
</html>

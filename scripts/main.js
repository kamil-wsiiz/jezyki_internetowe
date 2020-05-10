var App = angular.module('IdeoTree', []);

App.provider('Configuration', function() {

	this.config = {

		baseUrl: 'api/'

	};

	this.$get = function() {
		var obj = this;
		return {
			getConfig: function(conf) {
				return (typeof obj.config[conf] === "undefined") ? null : obj.config[conf];
			}
		}
	}

});

App.service('CSRF', function() {

	var token = null;

	this.Set = function(csrfToken) {

		token = csrfToken;

	};

	this.Get = function() {

		return token;

	};

});

App.service('UserService', function(CSRF) {

	var adminPriv = false;

	this.Set = function(status, csrfToken) {

		adminPriv = status;
		CSRF.Set(csrfToken);

	};

	/**
	 * @return {boolean}
	 */
	this.isAdmin = function() {

		return adminPriv;

	};

});

App.factory('API', function() {

	/**
	 * @return {boolean}
	 */
	var Parse = function(response, params) {

		try {

			/** @namespace Response.values */
			var Response = angular.fromJson(response);

		} catch(e) {

			console.log('Bad response from server.');
			return false;

		}

		var Return = false;

		switch (Response.status) {

			case 0:
				console.log('Server responsed unknown error.');
				break;

			case 1:

				if (angular.isObject(Response) && Response.hasOwnProperty('values')) {

					angular.forEach(params, function(key) {

						if (!Response.values.hasOwnProperty(key)) {
							console.log('Server responsed without required params: ' + key);
							return false;
						}

					});

					Return = Response.values;

				} else console.log('Server responsed without required properties.');

				break;

			case 2:
				console.log('Server responsed error:\n' + Response.error);
				break;

			default:
				console.log('Bad response from server.');

		}

		return Return;

	};

	return {

		Parse: Parse

	};

});

App.controller('Home', function($scope, $http, API, UserService, CSRF, Configuration, $timeout) {

	$scope.loading = false;
	$scope.error = null;
	$scope.isAdmin = UserService.isAdmin;

	$http.get(Configuration.getConfig('baseUrl') + 'dir'

	).then(function Success(response) {

		var ParsedAPI = API.Parse(response.data, ['status', 'tree']);

		$scope.loading = false;

		if (ParsedAPI === false) {
			$scope.error = 'Could not load data.';
			return;
		}

		if (ParsedAPI.status) {
			$scope.tree = ParsedAPI.tree;

			if (ParsedAPI.hasOwnProperty('auth'))
				UserService.Set(ParsedAPI.auth, ParsedAPI.csrf)
		}

	}, function Error(response) {

		$scope.loading = false;
		$scope.error = 'Error occured';
		console.log(response);

	});

	$scope.admin = function() {

		var pass = prompt("Password", "");

		if (pass != null) {

			$scope.loading = true;

			$http.post(Configuration.getConfig('baseUrl') + 'auth', {pass: pass}

			).then(function Success(response) {

				var ParsedAPI = API.Parse(response.data, ['status', 'data']);

				$scope.loading = false;

				if (ParsedAPI === false) {
					$scope.error = 'Error occured';
					return;
				}

				if (!ParsedAPI.status)
					$scope.error = ParsedAPI.data;
				else
					UserService.Set(ParsedAPI.status, ParsedAPI.data);

			}, function Error(response) {

				$scope.loading = false;
				$scope.error = 'Error occured';
				console.log(response);

			});

		}

	};

	var fullLoad = false;

	$scope.expandAll = function() {

		if (fullLoad) {
			expandAllClient($scope.tree);
			return;
		}

		$scope.loading = true;

		$http.get(Configuration.getConfig('baseUrl') + 'fullDir'

		).then(function Success(response) {

			var ParsedAPI = API.Parse(response.data, ['status', 'tree']);

			$scope.loading = false;

			if (ParsedAPI === false) {
				$scope.error = 'Could not load data.';
				return;
			}

			if (ParsedAPI.status) {
				$scope.tree = [ParsedAPI.tree.ROOT];
				fullLoad = true;
			}

		}, function Error(response) {

			$scope.loading = false;
			$scope.error = 'Error occured';
			console.log(response);

		});

	};

	var expandAllClient = function(tree) {

		angular.forEach(tree, function(obj) {
			obj.childVisible = true;
			expandAllClient(obj.childs);
		});

	};

	$scope.collapseAll = function(tree) {
		if (!tree)
			tree = $scope.tree;

		angular.forEach(tree, function(obj) {
			obj.childVisible = false;
			$scope.collapseAll(obj.childs);
		});
	};

	$scope.subDirectory = function(obj, reset) {

		if (reset) {
			obj.childs = [];
			obj.childVisible = true;
		}
		else
			obj.childVisible = !obj.childVisible;

		if (obj.childs.length === 0) {

			$scope.loading = true;

			$http.get(Configuration.getConfig('baseUrl') + 'subdir/' + obj.id

			).then(function Success(response) {

				var ParsedAPI = API.Parse(response.data, ['status', 'tree']);

				$scope.loading = false;

				if (ParsedAPI === false) {
					$scope.error = 'Could not load data.';
					return;
				}

				if (ParsedAPI.status)
					obj.childs = ParsedAPI.tree[obj.id].childs;

			}, function Error(response) {

				$scope.loading = false;
				$scope.error = 'Error occured';
				console.log(response);

			});

		}

	};

	$scope.addDirectory = function(obj) {

		$scope.loading = true;

		$http.post(Configuration.getConfig('baseUrl') + 'dir', {parent_id: obj.id, csrf: CSRF.Get()}

	   ).then(function Success(response) {

		   var ParsedAPI = API.Parse(response.data, ['status', 'id']);

		   $scope.loading = false;

		   if (ParsedAPI === false) {
			   $scope.error = 'Error occured';
			   return;
		   }

		   if (ParsedAPI.status) {
				obj.hasChilds = true;
				$scope.subDirectory(obj, true);
		   }

	   }, function Error(response) {

		   $scope.loading = false;
		   $scope.error = 'Error occured';
		   console.log(response);

	   });


	};

	$scope.removeDirectory = function(parent, id) {

		$scope.loading = true;

		$http.delete(Configuration.getConfig('baseUrl') + 'dir', {params: {id: id, csrf: CSRF.Get()}}

		).then(function Success(response) {

			var ParsedAPI = API.Parse(response.data, ['status']);

			$scope.loading = false;

			if (ParsedAPI === false) {
				$scope.error = 'Error occured';
				return;
			}

			if (ParsedAPI.status) {
				delete parent.childs[id];

				if (Object.keys(parent.childs).length === 0)
					parent.hasChilds = false;
			}

		}, function Error(response) {

			$scope.loading = false;
			$scope.error = 'Error occured';
			console.log(response);

		});

	};

	$scope.editStart = function(object, event) {

		object.edit = true;
		$timeout(function() { angular.element(event.target).children('.nameInput').prevObject.focus() });

	};

	$scope.editSave = function(obj) {

		obj.edit = false;
		$scope.loading = true;
		$scope.error = null;

		$http.put(Configuration.getConfig('baseUrl') + 'dir', {id: obj.id, newName: obj.name, csrf: CSRF.Get()}

		).then(function Success(response) {

			var ParsedAPI = API.Parse(response.data, ['status']);

			$scope.loading = false;

			if (ParsedAPI === false)
				$scope.error = 'Error occured';

		}, function Error(response) {

			$scope.loading = false;
			$scope.error = 'Correct name format: 3-50 chars without special chars.';
			obj.name = null;
			console.log(response);

		});

	};

	$scope.sortDirectory = function(object) {
		object.sort = !object.sort;
		object.childs.sort((a, b) => (a.name > b.name) ? (object.sort ? -1 : 1)  : (object.sort ? 1 : -1));
	};

});

 App.directive('directory', function() { //Directory directive template
	 return {
		 restrict: 'E',
		 scope: {
			 obj: '=dirObj',
			 add: '&dirAdd',
			 remove: '&dirRemove',
			 sub: '&dirSub',
			 admin: '=dirPriv',
			 parent: '=dirParent',
			 editStart: '&dirEditStart',
			 editSave: '&dirEditSave',
			 sort: '&dirSort'
		 },
		 template: `
			<li ng-hide="obj.removed">
				 <button ng-click="sub({object: obj})" ng-class="{expanded: obj.childVisible}" ng-hide="!obj.hasChilds" class="dirBtn"></button>
				 <div class="dirName" ng-dblclick="editStart({object: obj, event: $event})">
					<input type="text" class="nameInput" ng-model="obj.name" ng-keypress="$event.keyCode == 13 && editSave({object: obj})" ng-disabled="!obj.edit" ng-blur="obj.edit && editSave({object: obj})">
				 </div>
				 <div class="options" ng-hide="!admin">
					<button ng-click="sort({object: obj})" ng-hide="!obj.hasChilds || !obj.childVisible" class="dirSort" ng-class="obj.sort ? 'sortDown' : 'sortUp'"></button>
					<button ng-click="add({object: obj})" class="dirAdd"></button>
					<button ng-click="remove({parent: parent, id: obj.id})" class="dirRemove" ng-hide="obj.flags.indexOf(\'root\')!==-1"></button>
				 </div>
			 </li>
			 <ul ng-hide="!obj.childVisible">
				<directory ng-repeat="newObj in obj.childs" dir-obj="newObj" dir-parent="obj" dir-sub="sub({object})" dir-priv="admin" dir-add="add({ object})" dir-remove="remove({parent, id})" dir-edit-start="editStart({object, event})" dir-edit-save="editSave({object})"  dir-sort="sort({object})"></directory>
			 </ul>
		`
	 }
 });
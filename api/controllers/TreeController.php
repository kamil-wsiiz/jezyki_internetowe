<?php

namespace IdeoTree\Controllers;

use IdeoTree\Managers\Database, IdeoTree\Models\Callback, IdeoTree\Api\Api, IdeoTree\Models, IdeoTree\Configuration\Config, PDO;

require_once 'controllers/__class/Database.php';
require_once 'controllers/__class/Config.php';
require_once 'controllers/__class/Api.php';
require_once 'controllers/__model/Dir.php';
require_once 'controllers/AuthController.php';


class TreeController
{


	/* @var $Db PDO */
	private $Db;
	private $Config;

	public function __construct()
	{

		$this->Db = Database::getInstance();
		$this->Config = Config::getInstance();

	}

	/**
	 * @param $data
	 * @param bool $status
	 * @return Models\Dir|null
	 * Get root directory from database
	 */
	private function getRoot(&$data, $status=true)
	{

		foreach ($data as $key => $dir)
		{
			if ($dir['id'] == $this->Config->ROOT_DIRECTORY) {
				array_splice($data, $key, 1);

				$model = new Models\Dir();
				$model->id = $dir['id'];
				$model->name = $dir['name'];
				$model->hasChilds = ($dir['childCount'] > 0);
				$model->flags[] = 'root';
				$model->childVisible = $status;

				return $model;
			}
		}

		return null;

	}

	/**
	 * @param $data
	 * @param $tree
	 * @param $id
	 * @param bool $status
	 * Get childrens of dir
	 */
	private function addChildrens($data, &$tree, $id, $status=true)
	{

		foreach ($data as $key => $dir)
		{

			if ($dir['parent_id'] === $id) {

				$model = new Models\Dir();
				$model->id = $dir['id'];
				$model->name = $dir['name'];
				$model->hasChilds = ($dir['childCount'] > 0);
				$model->childVisible = $status;

				$tree[$dir['id']] = $model;

				array_splice($data, $key, 1);
				$this->addChildrens($data,$tree[$dir['id']]->childs, $dir['id'], $status);
			}

		}

	}

	/**
	 * @param $data
	 * @param $type
	 * @return array
	 * Build api directory tree
	 * default: only root
	 * full: full tree
	 * childs: only childs for specific id
	 */
	private function buildTree($data, $type)
	{

		$tree = [];

		switch ($type) {

			case 'default':
				$tree['ROOT'] = $this->getRoot($data, false);
				break;

			case 'full':
				$tree['ROOT'] = $this->getRoot($data, true);
				$this->addChildrens($data, $tree['ROOT']->childs, $tree['ROOT']->id, true);
				break;

			case 'childs':
				foreach ($data as $dir) {
					$model = new Models\Dir;
					$model->id = $dir['id'];
					$model->name = $dir['name'];
					$model->hasChilds = ($dir['childCount'] > 0);

					$tree[$dir['parent_id']] = new Models\Dir;
					$tree[$dir['parent_id']]->childs[$dir['id']] = $model;
				}
				break;

		}

		return $tree;

	}

	/**
	 * Get and return root directory
	 */
	public function Default()
	{

		$Query = $this->Db->query("SELECT id, name, parent_id, (SELECT COUNT(*) FROM ideotree.directories WHERE parent_id=".$this->Config->ROOT_DIRECTORY.") AS childCount FROM ideotree.directories WHERE id=".$this->Config->ROOT_DIRECTORY." ORDER BY id DESC;");

		if (!$Data = $Query->fetchAll(PDO::FETCH_ASSOC)) {

			$Query->closeCursor();
			Api::Callback(Callback::API_STATUS_FAILED, [], 'Could not get dir data.');

		} else {

			$Query->closeCursor();

			$Auth = new AuthController();
			$authStatus = $Auth->isAdmin();

			if ($authStatus)
				Api::Callback(Callback::API_STATUS_OK, ['status' => true, 'tree' => $this->buildTree($Data, 'default'), 'auth' => $authStatus, 'csrf' => $Auth->getCSRFToken()]);
			else
				Api::Callback(Callback::API_STATUS_OK, ['status' => true, 'tree' => $this->buildTree($Data, 'default')]);

		}

	}

	/**
	 * @param $id
	 * @param $sort
	 * Get and return childs of specific id with sort
	 */
	public function childsByParent($id, $sort)
	{

		$sortDir = ($sort == 'true') ? 'DESC' : 'ASC';

		$Query = $this->Db->prepare("SELECT act.id, act.name, act.parent_id, (SELECT COUNT(*) FROM ideotree.directories WHERE parent_id=act.id) AS childCount FROM ideotree.directories AS act WHERE parent_id=:id ORDER BY act.name ".$sortDir.";");
		$Query->bindValue(':id', $id, PDO::PARAM_INT);
		$Query->execute();

		if (!$Data = $Query->fetchAll(PDO::FETCH_ASSOC)) {

			$Query->closeCursor();
			Api::Callback(Callback::API_STATUS_FAILED, [], 'Could not get dir data.');

		} else {

			$Query->closeCursor();
			Api::Callback(Callback::API_STATUS_OK, ['status' => true, 'tree' => $this->buildTree($Data, 'childs')]);

		}

	}

	/**
	 * Return full tree
	 */
	public function allDir()
	{

		$Query = $this->Db->query("SELECT act.id, act.name, act.parent_id, (SELECT COUNT(*) FROM ideotree.directories WHERE parent_id=act.id) AS childCount FROM ideotree.directories AS act ORDER BY act.id DESC;");

		if (!$Data = $Query->fetchAll(PDO::FETCH_ASSOC)) {

			$Query->closeCursor();
			Api::Callback(Callback::API_STATUS_FAILED, [], 'Could not get dir data.');

		} else {

			$Query->closeCursor();
			Api::Callback(Callback::API_STATUS_OK, ['status' => true, 'tree' => $this->buildTree($Data, 'full')]);

		}

	}

	/**
	 * @param $parentId
	 * Add directory
	 */
	public function addDir($parentId)
	{

		$Query = $this->Db->prepare("INSERT INTO ideotree.directories (name, parent_id) VALUES (:name, :parent_id);");
		$Query->bindValue(':parent_id', $parentId, PDO::PARAM_INT);
		$Query->bindValue(':name', $this->Config->DEFAULT_NAME, PDO::PARAM_STR);

		Api::Callback(Callback::API_STATUS_OK, ['status' => ($Query->execute() && $Query->rowCount() != 0), 'id' => $this->Db->lastInsertId()]);

	}

	/**
	 * @param $id
	 * Delete directory
	 */
	public function deleteDir($id)
	{

		$Query = $this->Db->prepare("DELETE FROM ideotree.directories WHERE id=:id OR parent_id=:parent_id;");
		$Query->bindValue(':id', $id, PDO::PARAM_INT);
		$Query->bindValue(':parent_id', $id, PDO::PARAM_INT);

		Api::Callback(Callback::API_STATUS_OK, ['status' => ($Query->execute() && $Query->rowCount() != 0)]);

	}

	/**
	 * @param $id
	 * @param $name
	 * Edit directory
	 */
	public function editDir($id, $name)
	{

		$Query = $this->Db->prepare("UPDATE ideotree.directories SET name=:name WHERE id=:id;");
		$Query->bindValue(':id', $id, PDO::PARAM_INT);
		$Query->bindValue(':name', $name, PDO::PARAM_STR);

		Api::Callback(Callback::API_STATUS_OK, ['status' => ($Query->execute() && $Query->rowCount() != 0)]);

	}

	/**
	 * @param $id
	 * @param $newParent
	 * Move directory to other parent
	 */
	public function moveDir($id, $newParent)
	{

		$Query = $this->Db->prepare("UPDATE ideotree.directories SET parent_id=:new_parent WHERE id=:id;");
		$Query->bindValue(':id', $id, PDO::PARAM_INT);
		$Query->bindValue(':new_parent', $newParent, PDO::PARAM_STR);

		Api::Callback(Callback::API_STATUS_OK, ['status' => ($Query->execute() && $Query->rowCount() != 0)]);

	}

}
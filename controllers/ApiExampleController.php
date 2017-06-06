<?php

/* API example */

/* Example of api controller. */
class ApiExampleController extends Core\Controller
{
	public function testAction()
	{
		$selectors = array();

		$types = array(
			array('name' => 'First Type', 'value' => 1),
			array('name' => 'Second Type', 'value' => 2),
			array('name' => 'Third Type', 'value' => 3),
			array('name' => 'Fourth Type', 'value' => 4),
			array('name' => 'Fifth Type', 'value' => 5),
		);

		$selectors['class']['type']['values'] = $types;
		$selectors['array']['type']['values'] = $types;

		$api    = new API\API($this);
		$params = array('api' => $api, 'selectors' => $selectors);
		return $this->renderRoute('api:test', $params);
	}

	public function classAction($id = null)
	{
		$o          = null;
		$new        = false;
		$repository = new ApiExampleItemRepository();

		/* when delete is called */
		if ($this->kernel->method == 'delete')
		{
			if ($id === null)
			{
				throw new Exception400('Id required for deletion.');
			}
			$repository->delete($id);
			/* return deleted object data? */
			return $this->display(null, null);
		}

		/*
		 * when get/put/post
		 */

		if ($id !== null)
		{
			/* get single item with given id */
			$o = $repository->getObject($id);
			if (!$o)
			{
				throw new Exception404('Item not found with id: ' . $id);
			}
		}
		else if ($this->kernel->method == 'get')
		{
			/* get all items */
			$o = $repository->getAllObjects();
		}
		else if ($this->kernel->method == 'put' || $this->kernel->method == 'post')
		{
			/* new item */
			$o   = new ApiExampleItem();
			$new = true;
		}

		/* check input data */
		$api  = new API\API($this);
		$data = $api->parse('class', $o, $new);
		if ($data === false)
		{
			throw new Exception400('Request failed, reason: ' . $api->getError());
		}

		/* create/modify */
		if ($this->kernel->method == 'put' || $this->kernel->method == 'post')
		{
			$repository->putObject($o);
			$data = $api->parseGet('class', $o);
		}

		return $this->display(null, $data);
	}

	public function arrayAction($id = null)
	{
		$o          = null;
		$new        = false;
		$single     = false;
		$repository = new ApiExampleItemRepository();

		/* when delete is called */
		if ($this->kernel->method == 'delete')
		{
			if ($id === null)
			{
				throw new Exception400('Id required for deletion.');
			}
			$repository->delete($id);
			/* return deleted item data? */
			return $this->display(null, null);
		}

		/*
		 * when get/put/post
		 */

		if ($id !== null)
		{
			/* get single item with given id */
			$single = true;
			$o      = $repository->get($id);
			if (!$o)
			{
				throw new Exception404('Item not found with id: ' . $id);
			}
		}
		else if ($this->kernel->method == 'get')
		{
			/* get all items */
			$o = $repository->getAll();
		}
		else if ($this->kernel->method == 'put' || $this->kernel->method == 'post')
		{
			/* new item */
			$o      = array();
			$single = true;
			$new    = true;
		}

		/* check input data */
		$api  = new API\API($this);
		$data = $api->parse('array', $o, $new, $single);
		if ($data === false)
		{
			throw new Exception400('Request failed, reason: ' . $api->getError());
		}

		/* create/modify */
		if ($this->kernel->method == 'put' || $this->kernel->method == 'post')
		{
			$o    = $repository->put($id, $o);
			$data = $api->parseGet('array', $o);
		}

		return $this->display(null, $data);
	}

}

/* Example item repository class, you would normally put this under modules. */
class ApiExampleItemRepository extends Core\Module
{
	private $path = null;

	public function __construct()
	{
		parent::__construct();
		$this->path = $this->kernel->expand('{path:tmp}/__api_example');
		@mkdir($this->path, 0777, true);
	}

	/**
	 * Get all item ids. This means listing all files under tmp/__api_example-directory.
	 */
	public function getAllIds()
	{
		$files = scandir($this->path);
		$items = array();

		foreach ($files as $key => $file)
		{
			if (!is_file($this->path . '/' . $file))
			{
				continue;
			}

			$items[] = $file;
		}

		return $items;
	}

	/**
	 * Get all items as array.
	 */
	public function getAll()
	{
		$ids   = $this->getAllIds();
		$items = array();
		foreach ($ids as $id)
		{
			$items[] = $this->get($id);
		}
		return $items;
	}

	/**
	 * Get all items as objects.
	 */
	public function getAllObjects()
	{
		$ids   = $this->getAllIds();
		$items = array();
		foreach ($ids as $id)
		{
			$items[] = $this->getObject($id);
		}
		return $items;
	}

	/**
	 * Get single item as array.
	 */
	public function get($id, $add_id = true)
	{
		$data = @file_get_contents($this->path . '/' . $id);
		if (!$data)
		{
			return null;
		}
		$data = @json_decode($data, true);
		if ($data === null)
		{
			return null;
		}
		if ($add_id)
		{
			$data['id'] = $id;
		}
		return $data;
	}

	/**
	 * Get item as object.
	 */
	public function getObject($id)
	{
		$data = $this->get($id, false);
		if ($data === null)
		{
			return null;
		}
		$o = new ApiExampleItem($id, $data);
		return $o;
	}

	/**
	 * Save single item from array.
	 */
	public function put($id, $data)
	{
		if ($id === null)
		{
			$id = uniqid();
		}
		$data['id'] = $id;
		$json       = json_encode($data);
		@file_put_contents($this->path . '/' . $id, $json);
		return $data;
	}

	/**
	 * Save single object.
	 */
	public function putObject($o)
	{
		if ($o->id === null)
		{
			$o->id = uniqid();
		}
		$this->put($o->id, $o->getData());
	}

	/**
	 * Delete single item.
	 */
	public function delete($id)
	{
		@unlink($this->path . '/' . $id);
	}
}

/* Example item class, you would normally put this under modules. */
class ApiExampleItem extends Core\Module
{
	public $id = null;

	private $data = array();

	public function __construct($id = null, $data = array())
	{
		parent::__construct();
		$this->id   = $id;
		$this->data = $data;

		$keys = array('name', 'type', 'width', 'height', 'secret');
		foreach ($keys as $key)
		{
			if (!array_key_exists($key, $this->data))
			{
				$this->data[$key] = null;
			}
		}
	}

	public function getData()
	{
		return $this->data;
	}

	public function getName()
	{
		return $this->data['name'];
	}

	public function setName($name)
	{
		$this->data['name'] = $name;
	}

	public function getType()
	{
		return $this->data['type'];
	}

	public function setType($value)
	{
		$this->data['type'] = $value;
	}

	public function getWidth()
	{
		return $this->data['width'];
	}

	public function setWidth($value)
	{
		$this->data['width'] = $value;
	}

	public function getHeight()
	{
		return $this->data['height'];
	}

	public function setHeight($value)
	{
		$this->data['height'] = $value;
	}

	public function getSecret()
	{
		return $this->data['secret'];
	}

	public function setSecret($value)
	{
		$this->data['secret'] = $value;
	}

}

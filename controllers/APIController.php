<?php

class APIController extends Core\Controller
{
	public function testAction($api, $selectors = array())
	{
		$params = array(
			'api'       => $api,
			'selectors' => $selectors,
		);
		return $this->render('test.html', $params);
	}

	public function testToolAction($api, $selectors = array())
	{
		$params     = array();
		$controller = $api->getController();
		$config     = $api->getConfig();
		$calls      = array();

		foreach ($config as $name => $data)
		{
			if (!isset($data['route']))
			{
				continue;
			}
			list($base, $baseconfig, $route, $config) = $controller->routePath($data['route']);
			if ($config === false)
			{
				continue;
			}
			if (!isset($config[ROUTE_KEY_PATTERN]))
			{
				continue;
			}

			$call = array(
				'name'      => $name,
				'delete'    => true,
				'url'       => null,
				'selectors' => null,
			);

			$parts   = $this->kernel->routePartsGet($config[ROUTE_KEY_PATTERN]);
			$slugs   = array();
			$pattern = '';
			$jsSlugs = array();

			$basepath = trim($baseconfig['pattern'], '/');
			if (strlen($basepath) > 0)
			{
				$jsSlugs[] = array(
					'type'     => 'static',
					'name'     => $base,
					'default'  => $basepath,
					'optional' => false,
				);
			}

			foreach ($parts as $part)
			{
				$slug = $this->kernel->routeSlugParse($part);
				if (!$slug)
				{
					$pattern .= '/' . $part;
					$jsSlugs[] = array(
						'type'     => 'static',
						'name'     => $part,
						'default'  => $part,
						'optional' => false,
					);
					continue;
				}
				$slugs[] = $slug;
				$pattern .= '/<' . $slug['slug'] . '>';
				$jsSlugs[] = array(
					'type'     => 'slug',
					'name'     => $slug['slug'],
					'default'  => $slug['default'],
					'optional' => $slug['optional'],
				);
			}
			$call['pattern'] = $pattern;
			$call['slugs']   = $slugs;
			$call['jsSlugs'] = $jsSlugs;

			$call['selectors'] = array();
			if (isset($selectors[$name]))
			{
				$call['selectors'] = $selectors[$name];
			}

			if (isset($data['get']['values']))
			{
				$call['get'] = true;
			}
			if (isset($data['put']['values']))
			{
				$values = array();
				$this->parsePut($data['put']['values'], '', $values, $call['selectors']);
				$call['put'] = $values;
			}

			$calls[] = $call;
		}

		// echo "<pre>";
		// var_dump($calls);

		$params['controller'] = $controller ? get_class($controller) : 'unknown';
		$params['calls']      = $calls;

		// echo "<pre>";
		// var_dump($calls);
		// die;
		return $this->render('test-tool.html', $params);
	}

	private function parsePut($data, $key_prepend, &$values, &$selectors)
	{
		if (!is_array($data))
		{
			return;
		}
		foreach ($data as $key => $value)
		{
			if (isset($value['type']) && is_string($value['type']))
			{
				$required                    = isset($value['required']) ? $value['required'] : false;
				$values[$key_prepend . $key] = array('type' => $value['type'], 'required' => $required);
				if (isset($value['accept']))
				{
					$accepted_values = array();
					foreach ($value['accept'] as $accept)
					{
						$accepted_values[] = array('name' => $accept, 'value' => $accept);
					}
					$selectors[$key_prepend . $key]['values'] = $accepted_values;
				}
			}
			else
			{
				$this->parsePut($value, $key_prepend . $key . ':', $values, $selectors);
			}
		}
	}
}

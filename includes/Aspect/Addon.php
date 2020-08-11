<?php

namespace Zhours\Aspect;

defined('ABSPATH') or die('No script kiddies please!');

class Addon extends \Zhours\Aspect\Input
{
		protected $namespace;

		public function setNamespace($namespace) {
				if (is_string($namespace)) {
						$this->namespace = $namespace;
				}
				return $this;
		}

		public function getNamespace() {
				return $this->namespace;
		}

}

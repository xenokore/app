<?php

namespace Xenokore\App\Doctrine;

use Xenokore\Utility\Helper\FileHelper;

class DoctrineConfig extends \ArrayObject {

    private $config;

    public function __construct(string $config_file_path = '')
    {
        if(!FileHelper::isAccessible($config_file_path)){
            throw new \Exception('doctrine config filepath is not accessible');
        }

        $this->config = include $config_file_path;
    }

    public function &offsetGet($index)
    {
        return $this->config[$index];
    }

    public function offsetSet($index, $value): void
    {
        $this->config[$index] = $value;
    }

    public function offsetExists($index): bool
    {
        return isset($this->config[$index]);
    }

    public function offsetUnset($index): void
    {
        unset($this->config[$index]);
    }
}

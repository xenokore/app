<?php

namespace Xenokore\App\Tests\Data\Test;

class TestClass
{
    private $test_var;

    public function __construct()
    {
        $this->test_var = 'success';
    }

    public function getTestVar(): string
    {
        return $this->test_var;
    }
}

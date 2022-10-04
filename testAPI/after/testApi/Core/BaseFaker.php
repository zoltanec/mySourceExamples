<?php
declare(strict_types=1);

namespace Test\testApi\Core {

    use Common\Core\Traits\DebugTrait;
    use Test\Traits\FakerTrait;
    use PHPUnit\Framework\TestCase as PhpUnitTestCase;

    abstract class BaseFaker extends PhpUnitTestCase
    {
        use FakerTrait;
        use FileTrait;
        use DebugTrait;
    }
}
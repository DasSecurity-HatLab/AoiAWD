<?php

namespace Amp\Http;

use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    public function testEachDefinedStatusHasDefaultReason()
    {
        $class = new \ReflectionClass(Status::class);

        foreach ($class->getConstants() as $statusCode) {
            $this->assertNotEmpty(Status::getReason($statusCode), "{$statusCode} doesn't have a default reason.");
        }
    }

    public function testEachDefaultReasonHasCorrespondingConstant()
    {
        $class = new \ReflectionClass(Status::class);
        $constants = $class->getConstants();

        for ($i = 0; $i < 600; $i++) {
            $reason = Status::getReason($i);

            if ($reason !== "") {
                $this->assertContains($i, $constants);
            }
        }
    }

    public function testNoDuplicateDefinition()
    {
        $class = new \ReflectionClass(Status::class);
        $constants = $class->getConstants();

        // Double array_flip removes any duplicates.
        $this->assertSame($constants, \array_flip(\array_flip($constants)));
    }
}

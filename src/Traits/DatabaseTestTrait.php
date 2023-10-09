<?php

namespace Selective\TestTrait\Traits;

trait DatabaseTestTrait
{
    use DatabaseConnectionTestTrait;
    use DatabaseSchemaTestTrait;
    use DatabaseTableTestTrait;
}

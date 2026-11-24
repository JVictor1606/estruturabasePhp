<?php

declare(strict_types=1);

use Modular\Core\Container;
use Modular\Core\Database;

Container::set(Database::class, static function () {
    return new Database();
});

<?php

spl_autoload_register(function($className) {
    $classNameParts = explode('\\', $className);

    $className = implode('/', $classNameParts);
    if (is_readable("{$className}.php")) {
        require_once "{$className}.php";
    }
});

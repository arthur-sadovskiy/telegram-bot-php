<?php

namespace WeatherBot;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

class Config
{
    private const CONFIG_FILENAME_PATH = __DIR__ . '/config/config.php';

    /**
     * @var array
     */
    private $config;

    /**
     * Config constructor.
     *
     * @throws FileNotFoundException
     */
    public function __construct()
    {
        if (is_file(self::CONFIG_FILENAME_PATH) && is_readable(self::CONFIG_FILENAME_PATH)) {
            $this->config = require self::CONFIG_FILENAME_PATH;
        } else {
            throw new FileNotFoundException('Config file not found!');
        }
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function get(string $key): string
    {
        return $this->config[$key];
    }
}

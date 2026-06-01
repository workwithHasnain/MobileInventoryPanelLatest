<?php

/**
 * Filter Configuration Helper
 * Provides easy access to filter constraints from filter_config.json
 */

class FilterConfig
{
    private static $config = null;

    /**
     * Load and return the filter configuration
     */
    public static function get()
    {
        if (self::$config === null) {
            $configPath = __DIR__ . '/filter_config.json';
            if (!file_exists($configPath)) {
                throw new Exception('Filter configuration file not found: ' . $configPath);
            }

            self::$config = json_decode(file_get_contents($configPath), true);
            if (self::$config === null) {
                throw new Exception('Invalid JSON in filter configuration file');
            }
        }
        return self::$config;
    }

    /**
     * Get a specific filter configuration
     * Example: FilterConfig::getValue('price.max')
     */
    public static function getValue($path, $default = null)
    {
        $config = self::get();
        $keys = explode('.', $path);
        $value = $config;

        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Get all options for a specific filter field
     * Example: FilterConfig::getOptions('colors')
     */
    public static function getOptions($fieldName)
    {
        $config = self::get();
        if (isset($config[$fieldName]) && is_array($config[$fieldName])) {
            $options = $config[$fieldName];
            // If it's a numeric array, it's a list of options
            if (isset($options[0]) && !isset($options['min'])) {
                return $options;
            }
        }
        return [];
    }

    /**
     * Get min/max constraints for a filter
     * Example: FilterConfig::getConstraints('price')
     */
    public static function getConstraints($fieldName)
    {
        $config = self::get();
        if (isset($config[$fieldName])) {
            return $config[$fieldName];
        }
        return [];
    }

    /**
     * Reload the configuration (useful for testing)
     */
    public static function reload()
    {
        self::$config = null;
    }
}

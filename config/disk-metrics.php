<?php

return [
    /**
     * Track disks defined in filesystems.php config file.
     * Support only 'local' or 's3' driver.
     *
     * You can pass an array ['directories', 'files'] ONLY to a local disk
     * which will determine what to be counted. To count both,
     * you can pass an empty array.
     */
    'disks' => [
        env('FILESYSTEM_DISK', 's3') => [],
        // 'public' => [],
        // 's3' => []
    ],

    /**
     * How often (in minutes) should the Laravel Pulse capture data?
     * The value should be greated than 1!
     * Default: 10
     */
    'record_interval' => 15
];

<?php
// Central timezone config for the app.
// Include this file and use the returned timezone identifier or set PHP default timezone.
$TZ = 'Asia/Dubai';
// Set PHP default timezone (affects date/time functions that don't pass a TZ explicitly)
date_default_timezone_set($TZ);

// Also return the TZ string for use in DateTimeZone(...)
return $TZ;

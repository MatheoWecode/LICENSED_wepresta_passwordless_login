<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Security index file
 *
 * @author      WePresta
 * @copyright   2024 Votre Société
 * @license     MIT
 */

header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

header('Location: ../');
exit;


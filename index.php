<?php
/*
 *---------------------------------------------------------------
 * local-printer-smlabs — CodeIgniter 3 front controller
 *---------------------------------------------------------------
 * NOTE: This file assumes the CodeIgniter 3 framework core has
 * been placed in a sibling "system/" folder (download it from
 * https://codeigniter.com/download or `composer create-project
 * codeigniter/framework`). Only application/, assets/, uploads/
 * and print-agent/ are specific to this project.
 */

// If your kit didn't come with a system folder, define its path
$system_path = 'system';

// The application folder is the one this project ships with.
$application_folder = 'application';

/*
 *---------------------------------------------------------------
 * DEFAULT CONTROLLER
 *---------------------------------------------------------------
 */
$default_controller = 'dashboard';

/*
 * --------------------------------------------------------------------
 * ENVIRONMENT
 * --------------------------------------------------------------------
 */
define('ENVIRONMENT', isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'development');

if (defined('ENVIRONMENT')) {
    switch (ENVIRONMENT) {
        case 'development':
            error_reporting(-1);
            ini_set('display_errors', 1);
            break;
        case 'testing':
        case 'production':
            ini_set('display_errors', 0);
            if (version_compare(PHP_VERSION, '5.3', '>=')) {
                error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
            } else {
                error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
            }
            break;
        default:
            header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
            echo 'The application environment is not set correctly.';
            exit(1);
    }
}

/*
 * --------------------------------------------------------------------
 * Resolve system/application paths and hand off to CodeIgniter
 * --------------------------------------------------------------------
 */
if (!defined('BASEPATH')) {
    $system_path = rtrim($system_path, '/') . '/';

    if (!is_dir($system_path)) {
        header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
        echo 'Your system folder path does not appear to be set correctly. Please open the following file and correct this: ' . pathinfo(__FILE__, PATHINFO_BASENAME);
        exit(3);
    }

    define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
    define('BASEPATH', str_replace('\\', '/', $system_path));
    define('FCPATH', str_replace(SELF, '', __FILE__));
    define('SYSDIR', trim(strrchr(trim(BASEPATH, '/'), '/'), '/'));

    if (is_dir($application_folder)) {
        define('APPPATH', $application_folder . '/');
    } else {
        if (!is_dir(BASEPATH . $application_folder . '/')) {
            header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
            echo 'Your application folder path does not appear to be set correctly.';
            exit(3);
        }
        define('APPPATH', BASEPATH . $application_folder . '/');
    }
}

define('VIEWPATH', APPPATH . 'views/');

require_once BASEPATH . 'core/CodeIgniter.php';

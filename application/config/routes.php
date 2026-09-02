<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'dashboard';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// Print jobs
$route['print-jobs']              = 'print_jobs/index';
$route['print-jobs/create']       = 'print_jobs/create';
$route['print-jobs/store']        = 'print_jobs/store';
$route['print-jobs/(:num)']       = 'print_jobs/view/$1';
$route['print-jobs/(:num)/cancel'] = 'print_jobs/cancel/$1';

// Printers
$route['printers']              = 'printers/index';
$route['printers/create']       = 'printers/create';
$route['printers/store']        = 'printers/store';
$route['printers/(:num)/edit']  = 'printers/edit/$1';
$route['printers/(:num)/update'] = 'printers/update/$1';
$route['printers/(:num)/delete'] = 'printers/delete/$1';

// API for the local print-agent (print-agent/agent.php)
$route['api/printers/(:num)/heartbeat'] = 'api/heartbeat/$1';
$route['api/printers/(:num)/jobs']      = 'api/pending_jobs/$1';
$route['api/jobs/(:num)/status']        = 'api/update_status/$1';

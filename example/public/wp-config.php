<?php
/**
 * Minimal WordPress configuration to get the example working.
 */

// We'll serve WordPress core out of a subdirectory.
define('WP_CONTENT_DIR', __DIR__ . '/wp-content');

// Create with:
// `mysql -u root -e "CREATE DATABASE awful_example;"`
define('DB_NAME', 'awful_example');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');
$table_prefix = 'wp_';

define('WP_DEBUG', true);

// You should always regenerate these instead of copy-pasting from this file.
// SEE: https://api.wordpress.org/secret-key/1.1/salt/
define('AUTH_KEY', 'dXYBM%{11A359&/2t#zDBNy|?df3^v__!?@6$)K-^ x3wEc^Vei7W7,1b.Duo|nb');
define('SECURE_AUTH_KEY', 'ad_f|y8aM&8P7.g]`yoyaoyd;a+1AoQ7UsqF#T&w]P|wMf`5G^F51@}D@bPC(!)!');
define('LOGGED_IN_KEY', 'TCV}BBGYsBiQS5[QXu_Is?cEJ2nOjn6,qzb9zxj23|N)wC&`BwvHol^QzGX59+N,');
define('NONCE_KEY', 'V0cyoKyL] dR$Or-y&zmYhb)~W?jL @,e8GG8h* w2RY&s.b Txk~|l$KS31h7@D');
define('AUTH_SALT', '|uMNJ?eL! Qp.<H5WOWg=s*./zbUJM|aQu_Ot6~dz?_jw|P&ggtEUKG<A<U:5ruB');
define('SECURE_AUTH_SALT', 'h-/9}R7M-L*KQb<BuS,e|>N+N5>T3svYOIiix=0q5ltQ[x#/ug AR4;5pgIgFZ7J');
define('LOGGED_IN_SALT', ' pS%3-6+-e?BO%H7g(dEhxwL3P+9bB=~TJqr-.T/aJ(8/+t=!Z6{^o%a:n+5JFPH');
define('NONCE_SALT', '7YNWyg`W+[pD6g+j^yaS`]T$,A6u_IRKGn|ay(%|l:eh+L`<(t4(Ub[2&saV~>i3');

// That's all, stop editing! Happy blogging.

/** Absolute path to the WordPress directory. */
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/wordpress/');
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

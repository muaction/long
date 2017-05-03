<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'cp559386_longcar');

/** MySQL database username */
define('DB_USER', 'cp559386_longcar');

/** MySQL database password */
define('DB_PASSWORD', 'Long1234%');

/** MySQL hostname */
define('DB_HOST', 'cpanel01wh-han1');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'Q4K Np>SB*JHn-N#U#&Y%X33mhSO^3e./DySe]F]v&mmw^|4:cO~9X#-S|sU]v&v');
define('SECURE_AUTH_KEY',  'C X2O.I:aV5Gl%S9LGh3V&duiW[jsAJTTI0$V+^;Az~_)N{z53wKi{o0q.hh%q13');
define('LOGGED_IN_KEY',    'iNAh189a,fruh/OJ cVLoyagh/B@rSU=zSwFYo*A.W)Y1UqpC5wUVw`e2WTy(5-E');
define('NONCE_KEY',        'Uh/ZY&9tA;24H2$J)*`VMIwlN[F)^QM_K;ylJCh^+ &/~O)`g]OXMp88oH9d!v^O');
define('AUTH_SALT',        '>UNwykZd[Fp+Y9J$$ITSrij*{v^!X}T4F{Cf&|WpZ4P&q*#Y;cHvpQF<&5NBWWcQ');
define('SECURE_AUTH_SALT', '%@Q;Oodp?b!3{C3L/z5G)U>VjTB-vyBKv=Kkw6?}HZU{yMzAX5u`aQi;*)Cr(S{(');
define('LOGGED_IN_SALT',   'W_M/U_[:XH3O>%2G*AFvS^Xn8}pC5.wm6<.P2Q1Qv1&p{g5BG54/JtAFD}#];A@A');
define('NONCE_SALT',       'Q-z^[u{O[/ZG(Q5mC;v[/u<NeJ,jaq2ZQ1^|G15%+#pPF_o.3!.~4[?+cs/pDrT8');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

<?php
/*
 * config/security.php - registration/security switches. (Database settings stay in config/database.php.)
 * Keep this file private: do not commit it or share it if you set an invite code.
 */

// Team invite code. If you set one, new users must type it to register.
//   ''            = anyone who can open the website may register (OK on a local laptop only)
//   'MyTeamCode1' = only people you gave the code to can register  <-- recommended on real hosting
define('REGISTRATION_INVITE_CODE', '');

// true = show PHP errors on screen (local development only). Keep false on hosting.
define('APP_DEBUG', false);

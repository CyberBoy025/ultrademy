<?php
/**
 * Careers portal entry point.
 *
 * CAREERS_URL points at this directory, so hitting the bare base URL has to land
 * somewhere — without this it 403s, because directory listing is off (see the project
 * .htaccess) and there is no index to serve.
 *
 * A forward rather than a redirect: when the vhost cutover happens and careers gets its
 * own domain with DocumentRoot here, this file becomes the natural front controller and
 * `app.php` can fold into it without any URL changing.
 */
require __DIR__ . '/app.php';

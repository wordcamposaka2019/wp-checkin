<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);

use WCTokyo\WpCheckin\FireBase;
FireBase::get_instance()->setCredentials( dirname( __DIR__ ) . '/wposaka2019test-d6168d434d72.json', 'https://wposaka2019test.firebaseio.com' );

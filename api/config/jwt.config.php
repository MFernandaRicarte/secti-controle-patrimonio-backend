<?php
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'secti_user');
define('JWT_EXPIRES_IN', (int)(getenv('JWT_EXPIRES_IN') ?: 3600));

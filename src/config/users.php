<?php

return array(

	/*
	|--------------------------------------------------------------------------
	| Default Authentication Driver
	|--------------------------------------------------------------------------
	|
	| This option controls the authentication driver that will be utilized.
	| This drivers manages the retrieval and authentication of the users
	| attempting to get access to protected areas of your application.
	|
	| Supported: "database", "eloquent"
	|
	*/

	'driver' => 'eloquent',

    /*
	|--------------------------------------------------------------------------
	| Eloquent Configuration
	|--------------------------------------------------------------------------
	|
	| Configuration specific to eloquent.
	|
	*/

    'eloquent' => array(

        /*
        |--------------------------------------------------------------------------
        | Model
        |--------------------------------------------------------------------------
        |
        | The eloquent model to use for authentication.
        |
        */

        'model' => 'User',

        /*
        |--------------------------------------------------------------------------
        | Username Field
        |--------------------------------------------------------------------------
        |
        | The field from the model to use for the username.
        |
        */

        'username' => 'username',

        /*
        |--------------------------------------------------------------------------
        | Password Field
        |--------------------------------------------------------------------------
        |
        | The field from the model to use for the password.
        |
        */

        'password' => 'password',

        /*
        |--------------------------------------------------------------------------
        | UID Field
        |--------------------------------------------------------------------------
        |
        | The field from the model to use for the system users UID.
        |
        */

        'uid' => 'uid',

        /*
        |--------------------------------------------------------------------------
        | GID Field
        |--------------------------------------------------------------------------
        |
        | The field from the model to use for the system users GID.
        |
        */

        'gid' => 'gid',

        /*
        |--------------------------------------------------------------------------
        | Home Directory Field
        |--------------------------------------------------------------------------
        |
        | The field from the model to use for the home path.
        |
        */

        'home_path' => 'home_path'

    ),

    /*
	|--------------------------------------------------------------------------
	| Eloquent Configuration
	|--------------------------------------------------------------------------
	|
	| Configuration specific to eloquent.
	|
	*/

    'database' => array(

        /*
        |--------------------------------------------------------------------------
        | Model
        |--------------------------------------------------------------------------
        |
        | The database table to use for authentication.
        |
        */

        'table' => 'ftp_users',

        /*
        |--------------------------------------------------------------------------
        | Username Field
        |--------------------------------------------------------------------------
        |
        | The field from the table to use for the username.
        |
        */

        'username' => 'username',

        /*
        |--------------------------------------------------------------------------
        | Password Field
        |--------------------------------------------------------------------------
        |
        | The field from the table to use for the password.
        |
        */

        'password' => 'password',

        /*
        |--------------------------------------------------------------------------
        | UID Field
        |--------------------------------------------------------------------------
        |
        | The field from the table to use for the system users UID.
        |
        */

        'uid' => 'uid',

        /*
        |--------------------------------------------------------------------------
        | GID Field
        |--------------------------------------------------------------------------
        |
        | The field from the table to use for the system users GID.
        |
        */

        'gid' => 'gid',

        /*
        |--------------------------------------------------------------------------
        | Home Directory Field
        |--------------------------------------------------------------------------
        |
        | The field from the table to use for the home path.
        |
        */

        'home_path' => 'home_path'

    )

);

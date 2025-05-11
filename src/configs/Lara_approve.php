<?php

use App\Models\User;

return [

    /**
     * The model that will be used for approval. This should be set to the model class
     * that will perform approvals in your application, such as App\Models\User
     * or App\Models\Employee. Make sure this model exists in your application.
     */
    'user' => User::class,

    'models' => [
        //App\Models\YourModel::class,
    ],
];

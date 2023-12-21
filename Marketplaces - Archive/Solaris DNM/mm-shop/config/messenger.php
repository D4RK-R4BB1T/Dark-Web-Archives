<?php

return [

    'user_model' => App\User::class,

    'message_model' => App\MessengerModels\Message::class,

    'participant_model' => \App\MessengerModels\Participant::class,

    'thread_model' => \App\MessengerModels\Thread::class,

    /**
     * Define custom database table names.
     */
    'messages_table' => null,

    'participants_table' => null,

    'threads_table' => null,
];

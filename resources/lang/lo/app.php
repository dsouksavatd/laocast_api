<?php

return [

    /**
     * General
     */
    'general' => [
        'hello' => 'Hello',
    ],

    /**
     * Categories
     */
    'categories' => [
        'art_and_education' => 'ສິນລະປະ ແລະ ການສືກສາ',
        'business' => 'Business',
        'history' => 'History',
        'fiction_and_stories' => 'Fiction & Stories',
        'technology' => 'Technology',
        'science' => 'Science',
        'health_and_fitness' => 'Health & Fitness',
        'society_and_culture' => 'Society & Culture',
        'kids_and_family' => 'Kids & Familty'
    ],

    /** 
     * Mail Template
     */
    'email' => [
        'email_verification' => [
            'subject' => 'Email Verification Code',
            'body' => 'Your email verification code is: :code'
        ],
        'password_reset' => [
            'subject' => 'Password Reset',
            'body' => 'Your password reset code is: :code'
        ],
        'password_changed' => [
            'subject' => 'Password Changed',
            'body' => 'Your password for Laocast account :email was just changed'
        ]
    ],

    /**
     * Messages
     */
    'errors' => [
        'signin' => 'Email and password are not correct',
        'signup' => 'Sorry, Unable to create new account',
        'invalid_verification_code' => 'Invalid Verification Code',
        'password_reset_code' => 'Your password reset code is not correct',
        'current_password' => 'Your current password is not correct'
    ],
    'success' => [
        'verification_success' => 'Thank you for verifying your email',
        'verification_code_sent' => 'A verication code has been sent to your email :email',
        'password_reset_code_sent' => 'A password reset code has been sent to your email :email',
        'password_changed' => 'The password for the Laocast account :email was just changed',
        'ticket_sent' => 'Ticket :name has been sent to :account successfully.',
        'support_message' => 'Your message has been sent'
    ],

    /**
     * 
     */
    'push_notification' => [
        'subscribe' => [
            'title' => 'You have new subscriber!',
            'body' => '":name" is now subscribe to your channel ":channel"'
        ],
        'favorite' => [
            'title' => 'You have new favorite',
            'body' => '":name" is put ":track" to favorites'
        ],
        'comment' => [
            'title' => 'You have new comment',
            'body' => '":name" has comment on ":track"'
        ]
    ]
];
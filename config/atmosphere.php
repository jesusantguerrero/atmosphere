<?php

return [
  "superadmin" => [
    "email" => env('APP_SUPER_ADMIN', null)
  ],
  "backup" => [
    "email" => env('APP_BACKUP_EMAIL', null)
  ],
  "sso" => [
    "url" => env('SSO_URL'),
    "key" => env("SSO_APP_KEY"),
    "secret" => env("SSO_APP_SECRET")
  ]
];

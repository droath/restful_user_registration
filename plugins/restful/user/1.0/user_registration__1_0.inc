<?php

/**
 * @file
 * The user registration plugin for the RESTful user registration resource.
 */

$plugin = [
  'label'                   => t('User Registration'),
  'resource'                => 'user_registration',
  'name'                    => 'user_registration__1_0',
  'description'             => t('User registration actions.'),
  'class'                   => 'RestfulUserRegistrationResource',
  'authentication_types'    => TRUE,
  'authentication_optional' => FALSE,
];

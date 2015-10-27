<?php

/**
 * @file
 * The user registration RESTful resource.
 */

class RestfulUserRegistrationResource extends RestfulBase implements \RestfulDataProviderInterface {

  /**
   * {@inheritdoc}
   */
  public static function controllersInfo() {
    return [
      '' => [
        \RestfulInterface::POST => 'addAccount',
      ],
      '^.*$' => [
        \RestfulInterface::PUT => 'updateAccount',
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function publicFieldsInfo() {
    return [];
  }

  /**
   * Add user account into Drupal.
   */
  public function addAccount() {
    $account = $this->getAccount();

    // Check if the account has access to register a user.
    if (!user_access('allowed to register user account', $account)) {
      throw new \RestfulBadRequestException(
        "Account doesn't have access to register user."
      );
    }
    $account = new stdClass();
    $account->is_new = TRUE;

    $user = $this->saveUserAccount($account);

    // Check to make sure we didn't fail when saving the user.
    if (FALSE === $user) {
      throw new \RestfulBadRequestException('Adding user failed.');
    }

    return [$user];
  }

  /**
   * Update user account in Drupal.
   */
  public function updateAccount($uid) {
    if (!isset($uid)) {
      throw new \RestfulBadRequestException('User identifier is missing.');
    }
    $account = $this->getAccount();

    // Check if the account has access to update a user.
    if (!user_access('allowed to update user account', $account)) {
      throw new \RestfulBadRequestException(
        "Account doesn't have access to update user."
      );
    }
    $account = user_load($uid);

    if (FALSE === $account) {
      throw new \RestfulBadRequestException("Account doesn't exist.");
    }

    $user = $this->saveUserAccount($account);

    // Check to make sure we didn't fail when saving the user.
    if (FALSE === $user) {
      throw new \RestfulBadRequestException('Updating user failed.');
    }

    return [$user];
  }

  /**
   * Save the user account based on request.
   *
   * @return array
   *   An array of the saved user object; otherwise FALSE if failed.
   */
  protected function saveUserAccount($account) {
    if (!is_object($account)) {
      return FALSE;
    }
    $request = $this->getRequest();
    static::cleanRequest($request);

    // Check if the request has the valid parameters defined.
    if (!$this->isValidateRequest($request)) {
      throw new \RestfulBadRequestException('Missing required parameters.');
    }
    $name = $request['name'];
    $pass = $request['pass'];
    $mail = $request['mail'];

    // Load the user object by account name.
    $object = user_load_by_name($name);

    if ((isset($account->is_new) && $account->is_new) ||
      ($object->uid !== $account->uid)) {

      if (FALSE !== $object) {
        throw new \RestfulBadRequestException('Account name already exists.');
      }
    }

    $edit = [
      'name'   => $name,
      'pass'   => $pass,
      'mail'   => $mail,
      'init'   => NULL,
      'status' => TRUE,
    ];
    $roles = user_roles(TRUE);

    // Attach the valid roles to the user account based on the id.
    if (isset($request['roles']) && !empty($request['roles'])) {
      foreach ($request['roles'] as $id) {
        if (!isset($roles[$id])) {
          continue;
        }
        $edit['roles'][$id] = $roles[$id];
      }
    }

    // Save the account in Drupal.
    return user_save($account, $edit);
  }

  /**
   * Determine if the request has the valid parameters defined.
   */
  protected function isValidateRequest($request) {
    return $request['name'] && $request['pass'] ?: FALSE;
  }

}

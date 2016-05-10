<?php

/**
 * @file
 * Contains \Drupal\restful_user_registration\Plugin\resource\user_registration\v1\UserRegistration__1_0.
 */

namespace Drupal\restful_user_registration\Plugin\resource\user_registration\v1;

use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Http\Request;
use Drupal\restful\Plugin\resource\Resource;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Exception\BadRequestException;

/**
 * Class UserRegistration
 * @package \Drupal\restful_resources\Plugin\resource
 *
 * @Resource(
 *   name = "user-registration:1.0",
 *   resource = "user-registration",
 *   label = "User Registration",
 *   description = "An endpoint for registering users.",
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "user",
 *     "bundles": {
 *       "user"
 *     },
 *   },
 *   menuItem = "user-registration",
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */

class UserRegistration__1_0 extends Resource implements ResourceInterface {

  /**
   * {@inheritdoc}
   */
  public function controllersInfo() {
    return array(
      '' => array(
        RequestInterface::METHOD_POST => 'addAccount',
      ),
      '^.*$' => array(
        RequestInterface::PUT => 'updateAccount',
      )
    );
  }
  
  /**
   * {@inheritdoc}
   */
  protected function publicFields() {
    return array();
  }

  /**
   * Add user account into Drupal.
   */
  public function addAccount() {
    $account = $this->getAccount();

    // Check if the account has access to register a user.
    if (!user_access('allowed to register user account', $account)) {
      throw new BadRequestException(
        "Account doesn't have access to register user."
      );
    }
    $account = new \stdClass();
    $account->is_new = TRUE;

    $user = $this->saveUserAccount($account);

    // Check to make sure we didn't fail when saving the user.
    if (FALSE === $user) {
      throw new BadRequestException('Adding user failed.');
    }

    return array($user);
  }

  /**
   * Update user account in Drupal.
   */
 public function updateAccount($uid) {
    if (!isset($uid)) {
      throw new BadRequestException('User identifier is missing.');
    }
    $account = $this->getAccount();

    // Check if the account has access to update a user.
    if (!user_access('allowed to update user account', $account)) {
      throw new BadRequestException(
        "Account doesn't have access to update user."
      );
    }
    $account = user_load($uid);

    if (FALSE === $account) {
      throw new BadRequestException("Account doesn't exist.");
    }

    $user = $this->saveUserAccount($account);

    // Check to make sure we didn't fail when saving the user.
    if (FALSE === $user) {
      throw new BadRequestException('Updating user failed.');
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
    $request_body = $this->getRequest()->getParsedBody();

    // Check if the request has the valid parameters defined.
    if (!$this->isValidateRequest($request_body)) {
      throw new BadRequestException('Missing required parameters.');
    }
    $name = $request_body['name'];
    $pass = $request_body['pass'];
    $mail = $request_body['mail'];

    // Load the user object by account name.
    $object = user_load_by_name($name);

    if ((isset($account->is_new) && $account->is_new) ||
      ($object->uid !== $account->uid)) {

      if (FALSE !== $object) {
        throw new BadRequestException('Account name already exists.');
      }
    }

    $edit = array(
      'name'   => $name,
      'pass'   => $pass,
      'mail'   => $mail,
      'init'   => $mail,
      'status' => TRUE,
    );
    $roles = user_roles(TRUE);

    // Attach the valid roles to the user account based on the id.
    if (isset($request_body['roles']) && !empty($request_body['roles'])) {
      foreach ($request_body['roles'] as $id) {
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
  protected function isValidateRequest($request_body) {
    return $request_body['name'] && $request_body['pass'] && $request_body['mail'] ?: FALSE;
  }

}

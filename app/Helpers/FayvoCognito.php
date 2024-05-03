<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Helpers;

use pmill\AwsCognito\CognitoClient;
use pmill\AwsCognito\Exception\CognitoResponseException;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;

/**
 * Description of FayvoCognito
 *
 * @author rizwan
 */
class FayvoCognito extends CognitoClient {

    public function __construct(CognitoIdentityProviderClient $client) {
        parent::__construct($client);
    }

    public function createAndVerifyUser($username, $password, array $attributes = []) {
        try {
            if ($resp = $this->adminAdminGetUser($username)) {
                $this->adminAdminDeleteUser($username);
            }
            return $this->createUser($username, $password, $attributes);
        } catch (\Exception $ex) {
            return ['success' => false, 'message' => $ex->getMessage()];
        }
    }

    public function createUser($username, $password, array $attributes = []) {
        try {

            $resp = $this->registerUser($username, $password, $attributes);

            if ($resp) {
                $this->adminEnableUser($username);
                return ['success' => true, 'ref' => $resp];
            }
        } catch (\Exception $ex) {
            return ['success' => false, 'message' => $ex->getMessage()];
        }
    }

    /**
     * Send Resend Confirmation Code
     * @param string $username
     * 
     */
    public function adminResendRegistrationConfirmationCode($username) {
        try {
            $res = $this->client->resendConfirmationCode([
                'ClientId' => $this->appClientId,
                'SecretHash' => $this->cognitoSecretHash($username),
                'Username' => $username,
            ]);
            return !empty($res['CodeDeliveryDetails']) ? true : false;
        } catch (CognitoIdentityProviderException $e) {

            return false;
        }
    }

    public function adminEnableUser($username) {

        try {
            return $this->client->AdminConfirmSignUp([
                        'Username' => $username,
                        'UserPoolId' => $this->userPoolId,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            throw CognitoResponseException::createFromCognitoException($e);
        }
    }

    /**
     * get Admin User
     * @param type $username
     * @return type
     */
    public function adminAdminGetUser($username) {
        try {
            $data = $this->client->AdminGetUser([
                'Username' => $username,
                'UserPoolId' => $this->userPoolId,
            ]);
            return ['UserAttributes' => $data['UserAttributes'], 'UserStatus' => $data['UserStatus']];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check User is Un Confirmed
     * @param type $username
     * @return boolean
     */
    public function adminAdminGetUserIsUnConfirmred($username) {
        if ($user = $this->adminAdminGetUser($username)) {
            return $user['UserStatus'] != "CONFIRMED" ? true : false;
        }
        return false;
    }

    /**
     * Check User is Confirmed
     * @param type $username
     * @return boolean
     */
    public function adminAdminGetUserIsConfirmred($username) {
        if ($user = $this->adminAdminGetUser($username)) {
            return $user['UserStatus'] == "CONFIRMED" ? true : false;
        }
        return false;
    }

    /**
     * @param string $confirmationCode
     * @param string $username
     * @throws Exception
     */
    public function adminConfirmUserRegistration($confirmationCode, $username) {

        try {
            if ($this->adminAdminGetUserIsConfirmred($username)) {
                return false;
            } else {
                $data = $this->client->confirmSignUp([
                    'ClientId' => $this->appClientId,
                    'ConfirmationCode' => $confirmationCode,
                    'SecretHash' => $this->cognitoSecretHash($username),
                    'Username' => $username,
                ]);

                return $data;
            }
        } catch (\Exception $e) {
            throw CognitoResponseException::createFromCognitoException($e);
        }
    }

    /**
     * 
     * @param type $username
     * @return type
     * @throws type
     */
    public function adminAdminDeleteUser($username) {

        try {
            return $this->client->AdminDeleteUser([
                        'Username' => $username,
                        'UserPoolId' => $this->userPoolId,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            throw CognitoResponseException::createFromCognitoException($e);
        }
    }

    /**
     * login user from cognito
     * @param type $username
     * @param type $password
     * @return type
     */
    public function Cognitologin($username, $password) {
        try {
            return $this->authenticate($username, $password);
        } catch (\pmill\AwsCognito\Exception\UserNotConfirmedException $ex) {
            return ['success' => false, 'message' => $ex->getMessage()];
        } catch (\pmill\AwsCognito\Exception\UserNotConfirmedException $ex) {
            return ['success' => false, 'message' => $ex->getMessage()];
        } catch (\Exception $ex) {
            return ['success' => false, 'message' => $ex->getMessage()];
        }
    }

    public function updateAttributs($username, $attributs = []) {
        $response = $this->updateUserAttributes($username, $attributs);
    }

    /**
     * 
     * @param type $accessToken
     * @param type $previousPassword
     * @param type $proposedPassword
     * @return type
     * @throws type
     */
    public function changePassword($accessToken, $previousPassword, $proposedPassword) {
        $this->verifyAccessToken($accessToken);

        try {
            return $this->client->changePassword([
                        'AccessToken' => $accessToken,
                        'PreviousPassword' => $previousPassword,
                        'ProposedPassword' => $proposedPassword,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            throw CognitoResponseException::createFromCognitoException($e);
        }
    }

    /**
     * 
     * @param type $token
     * @param type $inputs
     * @return boolean
     */
    public function updatePassword($token, $username, $inputs) {

        try {
            $resp = $this->changePassword($token, $inputs['password'], $inputs['confirm_password']);
            $auth_resp = $this->Cognitologin($username, $inputs['confirm_password']);
            return $auth_resp;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function decodeToken($token) {
        try {
            return $this->decodeAccessToken($token);
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function delete($username, $password) {
        $authenticationResponse = $this->authenticate($username, $password);
        $accessToken = $authenticationResponse['AccessToken'];
        $resp = $this->deleteUser($accessToken);
    }

}

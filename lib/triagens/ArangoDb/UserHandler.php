<?php

/**
 * ArangoDB PHP client: user document handler
 *
 * @package   triagens\ArangoDb
 * @author    Frank Mayer
 * @since     1.2
 */

namespace triagens\ArangoDb;

/**
 * A handler that manages users
 * .
 * A user-document handler that fetches vertices from the server and
 * persists them on the server. It does so by issuing the
 * appropriate HTTP requests to the server.
 *
 * <br />
 *
 * @package   triagens\ArangoDb
 * @since     1.2
 */
class UserHandler extends
    Handler
{

    /**
     * save an user to the user-collection
     *
     * This will save the user to the user-collection
     *
     * This will throw if the user cannot be saved
     *
     * @throws Exception
     *
     * @param mixed $username - The name of the user as a string. This is mandatory.
     * @param mixed $passwd   - The user password as a string. If no password is specified, the empty string will be used.
     * @param mixed $active   - an optional flag that specifies whether the user is active. If not specified, this will default to true.
     * @param array $extra    - an optional array with arbitrary extra data about the user.
     * @param array $options  - an array of options
     *                        <p>Options are :<br>
     *                        </p>
     *
     * @return boolean - true, if user could be saved
     * @since 1.2
     */
    public function addUser($username, $passwd = null, $active = null, $extra = null, $options = array())
    {
        $userDocument           = new User();
        $userDocument->username = $username;
        $userDocument->passwd   = $passwd;
        $userDocument->active   = $active;
        $userDocument->extra    = $extra;
        $data                   = $userDocument->getAll();

        $this->getConnection()->post(Urls::URL_USER, $this->json_encode_wrapper($data));

        return true;
    }


    /**
     * Replace an existing User, identified by its username
     *
     * This will replace the user-document on the server
     *
     * This will throw if the document cannot be Replaced
     *
     * @throws Exception
     *
     * @param mixed $username - The name of the user as a string, who's user-data is going to be replaced. This is mandatory.
     * @param mixed $passwd   - The user password as a string. If no password is specified, the empty string will be used.
     * @param mixed $active   - an optional flag that specifies whether the user is active. If not specified, this will default to true.
     * @param array $extra    - an optional array with arbitrary extra data about the user.
     * @param array $options  - an array of options
     *                        <p>Options are :
     *                        </p>
     *
     * @return bool - always true, will throw if there is an error
     */
    public function replaceUser($username, $passwd = null, $active = null, $extra = null, $options = array())
    {
        $userDocument         = new User();
        $userDocument->passwd = $passwd;
        $userDocument->active = $active;
        $userDocument->extra  = $extra;
        $data                 = $userDocument->getAll();
        $url                  = UrlHelper::buildUrl(Urls::URL_USER, array($username));
        $this->getConnection()->put($url, $this->json_encode_wrapper($data));

        return true;
    }


    /**
     * Update an existing user, identified by the username
     *
     * This will update the user-document on the server
     *
     * This will throw if the document cannot be updated
     *
     * @throws Exception
     *
     * @param mixed $username - The name of the user as a string, who's user-data is going to be updated. This is mandatory.
     * @param mixed $passwd   - The user password as a string. If no password is specified, the empty string will be used.
     * @param mixed $active   - an optional flag that specifies whether the user is active. If not specified, this will default to true.
     * @param array $extra    - an optional array with arbitrary extra data about the user.
     * @param array $options  - an array of options
     *                        <p>Options are :
     *                        </p>
     *
     * @return bool - always true, will throw if there is an error
     */
    public function updateUser($username, $passwd = null, $active = null, $extra = null, $options = array())
    {
        $userDocument         = new User();
        $userDocument->active = $active;
        if (!is_null($passwd)) {
            $userDocument->passwd = $passwd;
        }
        if (!is_null($active)) {
            $userDocument->active = $active;
        }
        if (!is_null($extra)) {
            $userDocument->extra = $extra;
        }

        $url = UrlHelper::buildUrl(Urls::URL_USER, array($username));
        $this->getConnection()->patch($url, $this->json_encode_wrapper($userDocument->getAll()));

        return true;
    }


    /**
     * Get a single user-document, identified by the username
     *
     * This will throw if the document cannot be fetched from the server
     *
     * @throws Exception
     *
     * @param mixed $username - username as a string
     *
     * @return User - the user-document fetched from the server
     */
    public function get($username)
    {
        $url      = UrlHelper::buildUrl(Urls::URL_USER, array($username));
        $response = $this->getConnection()->get($url);

        $data = $response->getJson();

        $options = array('_isNew' => false);

        return User::createFromArray($data, $options);
    }


    /**
     * Remove a user, identified by the username
     *
     * @throws Exception
     *
     * @param mixed $username - username as a string, of the user that is to be deleted
     *
     * @return bool - always true, will throw if there is an error
     */
    public function removeUser($username)
    {
        // This preserves compatibility for the old policy parameter.
        $params = array();

        $url = UrlHelper::buildUrl(Urls::URL_USER, array($username));
        $url = UrlHelper::appendParamsUrl($url, $params);
        $this->getConnection()->delete($url);

        return true;
    }
}

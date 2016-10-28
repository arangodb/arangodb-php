<?php

/**
 * ArangoDB PHP client: single user document
 *
 * @package   triagens\ArangoDb
 * @author    Frank Mayer
 * @since     1.2
 */

namespace triagens\ArangoDb;

/**
 * Value object representing a single User document
 *
 * @property string     user
 * @property mixed|null passwd
 * @property mixed|null active
 * @property array|null extra
 * @package   triagens\ArangoDb
 * @since     1.2
 */
class User extends
    Document
{

}

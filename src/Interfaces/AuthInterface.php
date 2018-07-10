<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/1/24 0024
 * Time: 19:42
 */

namespace Tiny\Interfaces;


use Tiny\Exception\AuthError;

interface AuthInterface
{

    /**
     * Determine if the current user is authenticated.
     *
     * @return mixed
     *
     * @throws AuthError
     */
    public function authenticate();

    /**
     * Determine if the current user is authenticated.
     * @return bool
     */
    public function check();

    /**
     * Determine if the current user is a guest.
     * @return bool
     */
    public function guest();

    /**
     * 判断当前用户 角色 指定权限 值
     * @param string $key
     * @return mixed
     */
    public function role($key);

    /**
     * Get the currently authenticated user.
     * @return mixed
     */
    public function user();

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|null
     */
    public function id();

    /**
     * Log a user into the application without sessions or cookies.
     *
     * @param  array $credentials
     * @return bool
     */
    public function once(array $credentials = []);

    /**
     * Log the given user ID into the application.
     *
     * @param  mixed $id
     * @param  bool $remember
     * @return mixed
     */
    public function loginUsingId($id, $remember = false);

    /**
     * Get the last user we attempted to authenticate.
     *
     * @return mixed
     */
    public function getLastAttempted();

    /**
     * Log the given user ID into the application without sessions or cookies.
     *
     * @param  mixed $id
     * @return bool
     */
    public function onceUsingId($id);

    /**
     * Log the user out of the application.
     *
     * @return void
     */
    public function logout();

    /**
     * Validate a user's credentials.
     *
     * @param  array $credentials
     * @return bool
     */
    public function validate(array $credentials = []);

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param  array $credentials
     * @param  bool $remember
     * @param  bool $login
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false, $login = true);
}
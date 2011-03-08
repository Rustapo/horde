<?php
/**
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Oauth
 */

/**
 * OAuth abstract signature method base class
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Oauth
 */
abstract class Horde_Oauth_SignatureMethod
{
    abstract public function getName();

    abstract public function sign($request, $consumer, $token);

    public function verify($signature, $request, $consumer, $token)
    {
        return $signature == $this->sign($request, $consumer, $token);
    }
}

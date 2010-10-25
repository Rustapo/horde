<?php
/**
 * Mnemo_Driver:: defines an API for implementing storage backends for Mnemo.
 *
 * $Horde: mnemo/lib/Driver.php,v 1.47 2009/07/14 00:25:34 mrubinsk Exp $
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Jon Parise <jon@horde.org>
 * @since   Mnemo 1.0
 * @package Mnemo
 */
class Mnemo_Driver {

    /**
     * Array holding the current memo list.  Each array entry is a hash
     * describing a memo.  The array is indexed numerically by memo ID.
     *
     * @var array
     */
    var $_memos = array();

    /**
     * String containing the current notepad name.
     *
     * @var string
     */
    var $_notepad = '';

    /**
     * Crypting processor.
     *
     * @var Horde_Crypt_pgp
     */
    var $_pgp;

    /**
     * An error message to throw when something is wrong.
     *
     * @var string
     */
    var $_errormsg;

    /**
     * Constructor - All real work is done by initialize().
     */
    function Mnemo_Driver($errormsg = null)
    {
        if (is_null($errormsg)) {
            $this->_errormsg = _("The Notes backend is not currently available.");
        } else {
            $this->_errormsg = $errormsg;
        }
    }

    /**
     * Lists memos based on the given criteria. All memos will be
     * returned by default.
     *
     * @return array    Returns a list of the requested memos.
     */
    function listMemos()
    {
        return $this->_memos;
    }

    /**
     * Update the description (short summary) of a memo.
     *
     * @param integer $memo_id  The memo to update.
     */
    function getMemoDescription($body)
    {
        if (!strstr($body, "\n") && Horde_String::length($body) <= 64) {
            return trim($body);
        }

        $lines = explode("\n", $body);
        if (!is_array($lines)) {
            return trim(Horde_String::substr($body, 0, 64));
        }

        // Move to a line with more than spaces.
        $i = 0;
        while (isset($lines[$i]) && !preg_match('|[^\s]|', $lines[$i])) {
            $i++;
        }
        if (Horde_String::length($lines[$i]) <= 64) {
            return trim($lines[$i]);
        } else {
            return trim(Horde_String::substr($lines[$i], 0, 64));
        }
    }

    /**
     * Loads the PGP encryption driver.
     */
    function loadPGP()
    {
        if (empty($GLOBALS['conf']['utils']['gnupg'])) {
            $this->_pgp = PEAR::raiseError(_("Encryption support has not been configured, please contact your administrator."));
            return;
        }

        $this->_pgp = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Crypt')->create('pgp', array(
            'program' => $GLOBALS['conf']['utils']['gnupg']
        ));
    }

    /**
     * Encrypts a note.
     *
     * @param string $note        The note text.
     * @param string $passphrase  The passphrase to encrypt the note with.
     *
     * @return string|PEAR_Error  The encrypted text or PEAR_Error on failure.
     */
    function encrypt($note, $passphrase)
    {
        $this->loadPGP();
        if (is_a($this->_pgp, 'PEAR_Error')) {
            return $this->_pgp;
        }
        return $this->_pgp->encrypt($note, array('type' => 'message', 'symmetric' => true, 'passphrase' => $passphrase));
    }

    /**
     * Decrypts a note.
     *
     * @param string $note        The encrypted note text.
     * @param string $passphrase  The passphrase to decrypt the note with.
     *
     * @return string|PEAR_Error  The decrypted text or PEAR_Error on failure.
     */
    function decrypt($note, $passphrase)
    {
        $this->loadPGP();
        if (is_a($this->_pgp, 'PEAR_Error')) {
            return $this->_pgp;
        }
        return $this->_pgp->decrypt($note, array('type' => 'message', 'passphrase' => $passphrase));
    }

    /**
     * Returns whether note encryption is supported.
     *
     * Checks if PGP support could be loaded, if it supports symmetric
     * encryption, and if we have a secure connection.
     *
     * @return boolean  Whether encryption is suppoted.
     */
    function encryptionSupported()
    {
        $this->loadPGP();
        return (is_callable(array($this->_pgp, 'encryptedSymmetrically')) &&
                Horde::isConnectionSecure());
    }

    /**
     * Attempts to return a concrete Mnemo_Driver instance based on $driver.
     *
     * @param string    $notepad    The name of the current notepad.
     *
     * @param string    $driver     The type of concrete Mnemo_Driver subclass
     *                              to return.  The is based on the storage
     *                              driver ($driver).  The code is dynamically
     *                              included.
     *
     * @param array     $params     (optional) A hash containing any additional
     *                              configuration or connection parameters a
     *                              subclass might need.
     *
     * @return mixed    The newly created concrete Mnemo_Driver instance, or
     *                  dummy instance containing an error message.
     */
    function &factory($notepad = '', $driver = null, $params = null)
    {
        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }

        $driver = basename($driver);

        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        require_once dirname(__FILE__) . '/Driver/' . $driver . '.php';
        $class = 'Mnemo_Driver_' . $driver;
        if (class_exists($class)) {
            $mnemo = new $class($notepad, $params);
            $result = $mnemo->initialize();
            if (is_a($result, 'PEAR_Error')) {
                $mnemo = new Mnemo_Driver(sprintf(_("The Notes backend is not currently available: %s"), $result->getMessage()));
            }
        } else {
            $mnemo = new Mnemo_Driver(sprintf(_("Unable to load the definition of %s."), $class));
        }

        return $mnemo;
    }

    /**
     * Attempts to return a reference to a concrete Mnemo_Driver instance based
     * on $driver.
     *
     * It will only create a new instance if no Mnemo_Driver instance with the
     * same parameters currently exists.
     *
     * This should be used if multiple storage sources are required.
     *
     * This method must be invoked as: $var = &Mnemo_Driver::singleton()
     *
     * @param string    $notepad    The name of the current notepad.
     *
     * @param string    $driver     The type of concrete Mnemo_Driver subclass
     *                              to return.  The is based on the storage
     *                              driver ($driver).  The code is dynamically
     *                              included.
     *
     * @param array     $params     (optional) A hash containing any additional
     *                              configuration or connection parameters a
     *                              subclass might need.
     *
     * @return mixed    The created concrete Mnemo_Driver instance, or false
     *                  on error.
     */
    function &singleton($notepad = '', $driver = null, $params = null)
    {
        static $instances = array();

        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }

        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        $signature = serialize(array($notepad, $driver, $params));
        if (!isset($instances[$signature])) {
            $instances[$signature] = &Mnemo_Driver::factory($notepad, $driver, $params);
        }

        return $instances[$signature];
    }

    /**
     * Export this memo in iCalendar format.
     *
     * @param array  memo      the memo (hash array) to export
     * @param object vcal      a Horde_Icalendar object that acts as container.
     *
     * @return object  Horde_Icalendar_Vnote object for this event.
     */
    function toiCalendar($memo, &$calendar)
    {
        global $prefs;

        $vnote = Horde_Icalendar::newComponent('vnote', $calendar);

        $vnote->setAttribute('UID', $memo['uid']);
        $vnote->setAttribute('BODY', $memo['body']);
        $vnote->setAttribute('SUMMARY', $this->getMemoDescription($memo['body']));

        if (!empty($memo['category'])) {
            $vnote->setAttribute('CATEGORIES', $memo['category']);
        }

        /* Get the note's history. */
        $history = $GLOBALS['injector']->getInstance('Horde_History');
        $log = $history->getHistory('mnemo:' . $memo['memolist_id'] . ':' . $memo['uid']);
        if ($log && !is_a($log, 'PEAR_Error')) {
            foreach ($log->getData() as $entry) {
                switch ($entry['action']) {
                case 'add':
                    $created = $entry['ts'];
                    break;

                case 'modify':
                    $modified = $entry['ts'];
                    break;
                }
            }
        }

        if (!empty($created)) {
            $vnote->setAttribute('DCREATED', $created);
        }
        if (!empty($modified)) {
            $vnote->setAttribute('LAST-MODIFIED', $modified);
        }

        return $vnote;
    }

    /**
     * Create a memo (hash array) from a Horde_Icalendar_Vnote object.
     *
     * @param Horde_Icalendar_Vnote $vnote  The iCalendar data to update from.
     *
     * @return array  Memo (hash array) created from the vNote.
     */
    function fromiCalendar($vNote)
    {
        $memo = array();

        $body = $vNote->getAttribute('BODY');
        if (!is_array($body) && !is_a($body, 'PEAR_Error')) {
            $memo['body'] = $body;
        } else {
            $memo['body'] = '';
        }

        $memo['desc'] = $this->getMemoDescription($memo['body']);

        $cat = $vNote->getAttribute('CATEGORIES');
        if (!is_array($cat) && !is_a($cat, 'PEAR_Error')) {
            $memo['category'] = $cat;
        }

        return $memo;
    }

    /**
     * Retrieves notes from the database.
     *
     * @return mixed  True on success, PEAR_Error on failure.
     */
    function retrieve()
    {
        return PEAR::raiseError($this->_errormsg);
    }

}

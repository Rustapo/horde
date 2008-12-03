<?php

$block_name = _("Last most read news");

/**
 * $Id: most_read.php 201 2008-01-09 08:38:05Z duck $
 *
 * @package Horde_Block
*/

class Horde_Block_News_most_read extends Horde_Block {

    var $_app = 'news';

    function _title()
    {
        return _("Last most read news");
    }

    function _params()
    {
        return array('limit' => array('type' => 'int',
                                      'name' => _("How many news to display?"),
                                      'default' => 10),
                     'days' => array('type' => 'int',
                                      'name' => _("How many days back to check?"),
                                      'default' => 30));
    }

    function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';

        $query = 'SELECT n.id, n.publish, n.comments, n.picture, nl.title, nl.abbreviation ' .
                 'FROM ' . $GLOBALS['news']->prefix . ' AS n, ' . $GLOBALS['news']->prefix . '_body AS nl WHERE ' .
                 'n.status = ? AND n.publish <= NOW() AND n.publish > ?' .
                 'AND nl.lang = ? AND n.id = nl.id ' .
                 'ORDER BY n.view_count DESC ' .
                 'LIMIT 0, ' . $this->_params['limit'];

        $younger = $_SERVER['REQUEST_TIME'] - $this->_params['days'] * 86400;
        $params = array(News::CONFIRMED, date('Y-m-d', $younger), NLS::select());
        $rows = $GLOBALS['news']->db->getAll($query, $params, DB_FETCHMODE_ASSOC);
        if ($rows instanceof PEAR_Error) {
            return $rows->getDebugInfo();
        }

        $view = new News_View();
        $view->news = $rows;
        $view->news_url = Horde::applicationUrl('news.php');

        return $view->render('/block/titles.php');
    }

}

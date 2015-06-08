<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Select;
use Icinga\Data\Filter\Filter;

/**
 * Query for host and service notifications
 */
class NotificationQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'notifications' => array(
            'notification_state'            => 'n.notification_state',
            'notification_start_time'       => 'n.notification_start_time',
            'notification_contact_name'     => 'n.notification_contact_name',
            'notification_output'           => 'n.notification_output',
            'notification_object_id'        => 'n.notification_object_id',
            'contact_object_id'             => 'n.contact_object_id',
            'acknowledgement_entry_time'    => 'n.acknowledgement_entry_time',
            'acknowledgement_author_name'   => 'n.acknowledgement_author_name',
            'acknowledgement_comment_data'  => 'n.acknowledgement_comment_data',
            'object_type'                   => 'n.object_type'
        ),
        'hosts' => array(
            'host_display_name' => 'n.host_display_name',
            'host_name'         => 'n.host_name'
        ),
        'services' => array(
            'service_description'   => 'n.service_description',
            'service_display_name'  => 'n.service_display_name',
            'service_host_name'     => 'n.service_host_name'
        )
    );

    /**
     * The union
     *
     * @var Zend_Db_Select
     */
    protected $notificationQuery;

    /**
     * Subqueries used for the notification query
     *
     * @var IdoQuery[]
     */
    protected $subQueries = array();

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $this->notificationQuery = $this->db->select();
        $this->select->from(
            array('n' => $this->notificationQuery),
            array()
        );
        $this->joinedVirtualTables['notifications'] = true;
    }

    /**
     * Join hosts
     */
    protected function joinHosts()
    {
        $columns = array_keys(
            $this->columnMap['notifications'] + $this->columnMap['hosts'] + $this->columnMap['services']
        );
        $hosts = $this->createSubQuery('hostnotification', $columns);
        $this->subQueries[] = $hosts;
        $this->notificationQuery->union(array($hosts), Zend_Db_Select::SQL_UNION_ALL);
    }

    /**
     * Join services
     */
    protected function joinServices()
    {
        $columns = array_keys(
            $this->columnMap['notifications'] + $this->columnMap['hosts'] + $this->columnMap['services']
        );
        $services = $this->createSubQuery('servicenotification', $columns);
        $this->subQueries[] = $services;
        $this->notificationQuery->union(array($services), Zend_Db_Select::SQL_UNION_ALL);
    }

    /**
     * {@inheritdoc}
     */
    public function order($columnOrAlias, $dir = null)
    {
        foreach ($this->subQueries as $sub) {
            $sub->requireColumn($columnOrAlias);
        }
        return parent::order($columnOrAlias, $dir);
    }

    /**
     * {@inheritdoc}
     */
    public function where($condition, $value = null)
    {
        $this->requireColumn($condition);
        foreach ($this->subQueries as $sub) {
            $sub->where($condition, $value);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addFilter(Filter $filter)
    {
        foreach ($this->subQueries as $sub) {
            $sub->applyFilter(clone $filter);
        }
        return $this;
    }
}

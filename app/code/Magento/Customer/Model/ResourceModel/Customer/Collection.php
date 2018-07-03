<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Customer\Model\ResourceModel\Customer;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\PersonName\SqlFormat;
use Magento\Framework\PersonName\FormatInterface;

/**
 * Customers collection
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Collection extends \Magento\Eav\Model\Entity\Collection\VersionControl\AbstractCollection
{
    /**
     * Name of collection model
     */
    const CUSTOMER_MODEL_NAME = \Magento\Customer\Model\Customer::class;

    /**
     * @var \Magento\Framework\DataObject\Copy\Config
     */
    protected $_fieldsetConfig;

    /**
     * @var string
     */
    protected $_modelName;

    /**
     * SQL expression for full name formatting
     *
     * @var SqlFormat
     */
    private $nameSqlFormat;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Eav\Model\EntityFactory $eavEntityFactory
     * @param \Magento\Eav\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\Validator\UniversalFactory $universalFactory
     * @param \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot
     * @param \Magento\Framework\DataObject\Copy\Config $fieldsetConfig
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param string $modelName
     * @param SqlFormat $nameSqlFormat
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Eav\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot,
        \Magento\Framework\DataObject\Copy\Config $fieldsetConfig,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        $modelName = self::CUSTOMER_MODEL_NAME,
        SqlFormat $nameSqlFormat = null
    ) {
        $this->_fieldsetConfig = $fieldsetConfig;
        $this->_modelName = $modelName;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $universalFactory,
            $entitySnapshot,
            $connection
        );
        $this->nameSqlFormat = $nameSqlFormat ?: ObjectManager::getInstance()->get(SqlFormat::class);
    }

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init($this->_modelName, \Magento\Customer\Model\ResourceModel\Customer::class);
    }

    /**
     * Group result by customer email
     *
     * @return $this
     */
    public function groupByEmail()
    {
        $this->getSelect()->from(
            ['email' => $this->getEntity()->getEntityTable()],
            ['email_count' => new \Zend_Db_Expr('COUNT(email.entity_id)')]
        )->where(
            'email.entity_id = e.entity_id'
        )->group(
            'email.email'
        );

        return $this;
    }

    /**
     * Add Name to select
     *
     * @return $this
     */
    public function addNameToSelect()
    {
        $fields = [];
        $customerAccount = $this->_fieldsetConfig->getFieldset('customer_account');
        foreach ($customerAccount as $code => $field) {
            if (isset($field['name'])) {
                $fields[$code] = $code;
            }
        }

        $mappedFields = [
            FormatInterface::PART_FIRST_NAME => '{{firstname}}',
            FormatInterface::PART_GIVEN_NAME => '{{firstname}}',
            FormatInterface::PART_LAST_NAME => '{{lastname}}',
            FormatInterface::PART_FAMILY_NAME => '{{lastname}}',
        ];
        if (isset($fields['prefix'])) {
            $mappedFields[FormatInterface::PART_NAME_PREFIX] = '{{prefix}}';
        }
        if (isset($fields['middlename'])) {
            $mappedFields[FormatInterface::PART_MIDDLE_NAME] = '{{middleName}}';
        }
        if (isset($fields['suffix'])) {
            $mappedFields[FormatInterface::PART_NAME_SUFFIX] = '{{suffix}}';
        }

        $nameExpr = $this->getConnection()->getConcatSql($this->nameSqlFormat->getSqlParts($mappedFields));
        $this->addExpressionAttributeToSelect('name', $nameExpr, $fields);

        return $this;
    }

    /**
     * Get SQL for get record count
     *
     * @return \Magento\Framework\DB\Select
     */
    public function getSelectCountSql()
    {
        $select = parent::getSelectCountSql();
        $select->resetJoinLeft();

        return $select;
    }

    /**
     * Reset left join
     *
     * @param int $limit
     * @param int $offset
     * @return \Magento\Eav\Model\Entity\Collection\AbstractCollection
     */
    protected function _getAllIdsSelect($limit = null, $offset = null)
    {
        $idsSelect = parent::_getAllIdsSelect($limit, $offset);
        $idsSelect->resetJoinLeft();
        return $idsSelect;
    }
}

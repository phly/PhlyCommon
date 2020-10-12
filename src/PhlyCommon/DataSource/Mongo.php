<?php

namespace PhlyCommon\DataSource;

use DomainException;
use InvalidArgumentException;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerInterface;
use MongoCollection;
use PhlyCommon\DataSource;
use PhlyCommon\Query as QueryDefinition;
use Traversable;

use function array_key_exists;
use function compact;
use function get_class;
use function gettype;
use function is_array;
use function is_object;
use function sprintf;
use function strtolower;

class Mongo implements DataSource
{
    protected $events;
    protected $mongo;
    protected $insertOptions
        = [
            'safe'  => true,
            'fsync' => true,
        ];
    protected $updateOptions
        = [
            'safe'     => true,
            'fsync'    => true,
            'upsert'   => false,
            'multiple' => false,
        ];
    protected $removeOptions
        = [
            'safe'    => true,
            'fsync'   => true,
            'justOne' => true,
        ];

    public function __construct($options = null)
    {
        if ($options instanceof MongoCollection) {
            $this->setConnection($options);
        } elseif (is_array($options) || $options instanceof Traversable) {
            $this->setOptions($options);
        }
    }

    public function events(?EventManagerInterface $events = null)
    {
        if (null !== $events) {
            $this->events = $events;
        } elseif (null === $this->events) {
            $this->events = new EventManager(
                null,
                [
                    self::class,
                    static::class,
                ]
            );
        }
        return $this->events;
    }

    public function setOptions($options)
    {
        if (! is_array($options) && ! $options instanceof Traversable) {
            throw new InvalidArgumentException(
                sprintf(
                    'Expected an array or Traversable; received "%s"',
                    is_object($options)
                        ? get_class($options)
                        : gettype(
                            $options
                        )
                )
            );
        }

        foreach ($options as $key => $value) {
            switch (strtolower($key)) {
                case 'connection':
                case 'mongo':
                    $this->setConnection($value);
                    break;
                case 'insert_options':
                    $this->setInsertOptions($value);
                    break;
                case 'update_options':
                    $this->setUpdateOptions($value);
                    break;
                case 'remove_options':
                    $this->setRemoveOptions($value);
                    break;
                default:
                    break;
            }
        }
        return $this;
    }

    /**
     * Set mongo connection
     *
     * @return $this
     */
    public function setConnection(MongoCollection $connection)
    {
        $this->mongo = $connection;
        return $this;
    }

    /**
     * Get mongo connection
     *
     * @return MongoConnection
     */
    public function getConnection()
    {
        if (null === $this->mongo) {
            throw new DomainException(
                'No MongoCollection on which to operate!'
            );
        }
        return $this->mongo;
    }

    /**
     * Set Mongo insert options to use on create()
     *
     * @return $this
     */
    public function setInsertOptions(array $options)
    {
        $this->insertOptions = $options;
        return $this;
    }

    /**
     * Get Mongo insert options
     *
     * @return array
     */
    public function getInsertOptions()
    {
        return $this->insertOptions;
    }

    /**
     * Set Mongo update options to use on update()
     *
     * @return $this
     */
    public function setUpdateOptions(array $options)
    {
        $this->updateOptions = $options;
        return $this;
    }

    /**
     * Get Mongo update options
     *
     * @return array
     */
    public function getUpdateOptions()
    {
        return $this->updateOptions;
    }

    /**
     * Set Mongo remove options to use on delete()
     *
     * @return $this
     */
    public function setRemoveOptions(array $options)
    {
        $this->removeOptions = $options;
        return $this;
    }

    /**
     * Get Mongo remove options
     *
     * @return array
     */
    public function getRemoveOptions()
    {
        return $this->removeOptions;
    }

    /**
     * Query for records
     *
     * @return MongoCursor
     */
    public function query(QueryDefinition $query)
    {
        $params = compact('query');
        $events = $this->events();
        $events->trigger(__FUNCTION__ . '.pre', $this, $params);

        $parser   = new Mongo\QueryParser($query);
        $criteria = $parser->getCriteria();

        $params['criteria'] = $criteria;
        $events->trigger(__FUNCTION__ . '.criteria', $this, $params);
        $cursor = $this->getConnection()->find($criteria);
        if (false !== ($sort = $parser->getSort())) {
            $cursor->sort($sort);
        }
        if (false !== ($offset = $parser->getSkip())) {
            $cursor->skip($offset);
        }
        if (false !== ($limit = $parser->getLimit())) {
            $cursor->limit($limit);
        }
        $params['cursor'] = $cursor;
        $events->trigger(__FUNCTION__ . '.post', $this, $params);
        return $cursor;
    }

    /**
     * Fetch a single record
     *
     * @param string|int $id
     * @return null|array
     */
    public function get($id)
    {
        $item = $this->getConnection()->findOne(
            [
                '_id' => $id,
            ]
        );
        if (null === $item) {
            return null;
        }
        $item['id'] = $item['_id'];
        unset($item['_id']);
        return $item;
    }

    /**
     * Create a new record
     *
     * If the item has an "id" field, it will be changed to "_id" so that it
     * may be used as the document identifier.
     *
     * Returns the saved document, with "_id" changed to "id".
     *
     * @return array
     */
    public function create(array $definition)
    {
        if (array_key_exists('id', $definition)) {
            $definition['_id'] = $definition['id'];
            unset($definition['id']);
        }

        $connection = $this->getConnection();
        $connection->insert($definition, $this->getInsertOptions());

        if (array_key_exists('_id', $definition)) {
            $definition['id'] = (string) $definition['_id'];
            unset($definition['_id']);
        }

        return $definition;
    }

    /**
     * Update a record
     *
     * @param string|int $id
     * @return array
     */
    public function update($id, array $fields)
    {
        $criteria = ['_id' => $id];
        $update   = ['$set' => $fields];
        $this->getConnection()->update(
            $criteria,
            $update,
            $this->getUpdateOptions()
        );
        if (null === $record = $this->get($id)) {
            throw new DomainException('Cannot update; record does not exist');
        }
        return $record;
    }

    /**
     * Delete a record
     *
     * @param string|int $id
     * @return bool
     */
    public function delete($id)
    {
        $criteria = ['_id' => $id];
        $this->getConnection()->remove($criteria, $this->getRemoveOptions());
        return true;
    }
}

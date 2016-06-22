<?php
/**
 * OData client library
 *
 * @author  Михаил Красильников <m.krasilnikov@yandex.ru>
 * @license MIT
 */
namespace Mekras\OData\Client\EDM;

/**
 * OData Entry
 *
 * @since 1.0
 *
 * @link  http://www.odata.org/documentation/odata-version-2-0/overview/#EntityDataModel
 */
class EntityType extends ComplexType
{
    /**
     * Create value of a ComplexType.
     *
     * @param ComplexType|null $properties Property set.
     *
     * @since 1.0
     */
    public function __construct(ComplexType $properties = null)
    {
        parent::__construct([]);
        foreach ($properties as $key => $value) {
            $this[$key] = $value;
        }
    }
}

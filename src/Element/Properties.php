<?php
/**
 * OData client library
 *
 * @author  Михаил Красильников <m.krasilnikov@yandex.ru>
 * @license MIT
 */
namespace Mekras\OData\Client\Element;

use Mekras\Atom\Node;
use Mekras\OData\Client\EDM\Primitive;
use Mekras\OData\Client\Exception\LogicException;
use Mekras\OData\Client\OData;

/**
 * OData Entry properties.
 *
 * @since 1.0
 */
class Properties extends Element
{
    /**
     * Property cache.
     *
     * @var Primitive[]
     */
    private $properties;

    /**
     * Create node.
     *
     * @param  Node            $parent  Parent node.
     * @param \DOMElement|null $element DOM element.
     *
     * @since 1.0
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Node $parent, $element = null)
    {
        parent::__construct($parent, $element);

        if (null === $element) {
            $parent->getDomElement()->setAttribute('type', 'application/xml');
        }

        /** @var \DOMNodeList $nodes */
        $nodes = $this->query('d:*');
        foreach ($nodes as $node) {
            $primitive = new Primitive($this, $node);
            $this->properties[$primitive->getName()] = $primitive;
        }
    }

    /**
     * Add property.
     *
     * @param string $name  Property name.
     * @param mixed  $value Property value.
     * @param string $type  Primitive type (default is Primitive::STRING).
     *
     * @return Primitive
     * @throws \InvalidArgumentException
     *
     * @throws \Mekras\OData\Client\Exception\LogicException If property already exist.
     *
     * @since 1.0
     */
    public function add($name, $value, $type = Primitive::STRING)
    {
        if ($this->has($name)) {
            throw new LogicException(sprintf('Property "%s" already exist', $name));
        }

        $primitive = new Primitive($this, $name, $type);
        $primitive->setValue($value);
        $this->properties[$name] = $primitive;
    }

    /**
     * Return property.
     *
     * @param string $name
     *
     * @return Primitive
     *
     * @since 1.0
     */
    public function get($name)
    {
        return $this->properties[$name];
    }

    /**
     * Return true if property exists.
     *
     * @param string $name
     *
     * @return bool
     *
     * @since 1.0
     */
    public function has($name)
    {
        return array_key_exists($name, $this->properties);
    }

    /**
     * Return node main namespace.
     *
     * @return string
     *
     * @since 1.0
     */
    public function ns()
    {
        return OData::META;
    }

    /**
     * Return node name here.
     *
     * @return string
     *
     * @since 1.0
     */
    protected function getNodeName()
    {
        return 'properties';
    }
}
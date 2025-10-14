<?php
namespace WPvividProAws\Api;

/**
 * Base class representing a modeled shape.
 */
class Shape extends AbstractModel
{
    /**
     * Get a concrete shape for the given definition.
     *
     * @param array    $definition
     * @param ShapeMap $shapeMap
     *
     * @return mixed
     * @throws \RuntimeException if the type is invalid
     */
    public static function create(array $definition, ShapeMap $shapeMap)
    {
        static $map = [
            'structure' => 'WPvividProAws\Api\StructureShape',
            'map'       => 'WPvividProAws\Api\MapShape',
            'list'      => 'WPvividProAws\Api\ListShape',
            'timestamp' => 'WPvividProAws\Api\TimestampShape',
            'integer'   => 'WPvividProAws\Api\Shape',
            'double'    => 'WPvividProAws\Api\Shape',
            'float'     => 'WPvividProAws\Api\Shape',
            'long'      => 'WPvividProAws\Api\Shape',
            'string'    => 'WPvividProAws\Api\Shape',
            'byte'      => 'WPvividProAws\Api\Shape',
            'character' => 'WPvividProAws\Api\Shape',
            'blob'      => 'WPvividProAws\Api\Shape',
            'boolean'   => 'WPvividProAws\Api\Shape'
        ];

        if (isset($definition['shape'])) {
            return $shapeMap->resolve($definition);
        }

        if (!isset($map[$definition['type']])) {
            throw new \RuntimeException('Invalid type: '
                . print_r($definition, true));
        }

        $type = $map[$definition['type']];

        return new $type($definition, $shapeMap);
    }

    /**
     * Get the type of the shape
     *
     * @return string
     */
    public function getType()
    {
        return $this->definition['type'];
    }

    /**
     * Get the name of the shape
     *
     * @return string
     */
    public function getName()
    {
        return $this->definition['name'];
    }
}

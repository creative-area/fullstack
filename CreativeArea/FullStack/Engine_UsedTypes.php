<?php namespace CreativeArea\FullStack;

/**
 * Trait Engine_UsedTypes.
 */
trait Engine_UsedTypes
{
    /**
     * Trait constructor.
     */
    private function __construct_used_types()
    {
        $this->nameDependencies = new \CreativeArea\Cache(function &($currentName) {
            return $this->generateNameDependency($currentName);
        });
    }

    /**
     * @var \CreativeArea\Cache
     */
    private $nameDependencies;

    /**
     * @param string $currentName
     *
     * @return string[]
     */
    private function &generateNameDependency($currentName)
    {
        $map = [];
        $reflectionClass = & $this->classForName->get($currentName);
        $parentClassName = & $reflectionClass->getParentClass()->name;
        if ($parentClassName !== "CreativeArea\\FullStack\\Object") {
            foreach ($this->nameDependencies->get($this->nameForClass->get($parentClassName)) as $name) {
                $map[$name] = true;
            }
        }
        $dependencies = $reflectionClass->getAnnotation("DependsOn");
        if ($dependencies) {
            foreach ($dependencies as $dependencyName) {
                foreach ($this->nameDependencies->get($dependencyName) as $name) {
                    $map[$name] = true;
                }
            }
        }
        $map[$currentName] = true;
        $map = array_keys($map);

        return $map;
    }

    /**
     * @var bool[]
     */
    private $usedTypes = [];

    /**
     * @var bool[]
     */
    private $clientTypes = [];

    /**
     * @param string $name
     * @param bool   $fromClient
     */
    private function addUsedType($name, $fromClient = false)
    {
        foreach ($this->nameDependencies->get($name) as $type) {
            $this->usedTypes[$type] = true;
            if ($fromClient) {
                $this->clientTypes[$type] = true;
            }
        }
    }

    /**
     * @return string[]
     */
    public function getUsedTypes()
    {
        $types = [];
        foreach ($this->usedTypes as $key => $_) {
            if (!isset($this->clientTypes[$key])) {
                $types[] = $key;
            }
        }

        return $types;
    }
}

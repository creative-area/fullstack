<?php namespace CreativeArea\Annotate;

trait Reflector
{
    /**
     * @var \CreativeArea\Annotate
     */
    private $annotate;

    /**
     * @var null|Annotations[]
     */
    private $annotations = null;

    /**
     * @param string $type
     *
     * @return mixed
     */
    public function getAnnotation($type)
    {
        if ($this->annotations === null) {
            $this->annotations = $this->annotate->getAnnotations($this);
        }

        return $this->annotations->get($type);
    }
}

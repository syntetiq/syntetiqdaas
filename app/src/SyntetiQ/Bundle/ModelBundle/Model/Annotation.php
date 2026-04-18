<?php

namespace SyntetiQ\Bundle\ModelBundle\Model;

class Annotation
{
    private string $folder = '';
    private string $filename = '';
    private string $path = '';
    private array $source = [];
    private array $size = [];
    private string $segmented = '';
    private array $object = [];

    /**
     * Get the value of folder
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * Set the value of folder
     *
     * @return  self
     */
    public function setFolder($folder)
    {
        $this->folder = $folder;

        return $this;
    }

    /**
     * Get the value of object
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Set the value of object
     *
     * @return  self
     */
    public function setObject($object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * Get the value of segmented
     */
    public function getSegmented()
    {
        return $this->segmented;
    }

    /**
     * Set the value of segmented
     *
     * @return  self
     */
    public function setSegmented($segmented)
    {
        $this->segmented = $segmented;

        return $this;
    }

    /**
     * Get the value of filename
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set the value of filename
     *
     * @return  self
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get the value of path
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set the value of path
     *
     * @return  self
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get the value of source
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set the value of source
     *
     * @return  self
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get the value of size
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set the value of size
     *
     * @return  self
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }
}

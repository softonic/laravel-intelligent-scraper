<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Application;

class VariantGenerator
{
    protected $type = null;

    protected $configPerField = [];

    protected $allFieldsFound = true;

    public function setType($type): void
    {
        $this->type = $type;
    }

    public function addConfig($field, $xpath)
    {
        $this->configPerField[] = $field . $xpath;
    }

    public function fieldNotFound()
    {
        $this->allFieldsFound = false;
    }

    public function getId($type = null)
    {
        $type = $type ?? $this->type;
        if (empty($type)) {
            throw new \InvalidArgumentException('Type should be provided in the getVariantId call or setType');
        }

        if (empty($this->configPerField) || !$this->allFieldsFound) {
            return '';
        }

        sort($this->configPerField);

        $id = sha1($type . implode('', $this->configPerField));
        $this->reset();

        return $id;
    }

    public function reset()
    {
        $this->setType(null);
        $this->configPerField = [];
        $this->allFieldsFound = true;
    }
}

<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Application;

class XpathBuilder
{
    /**
     * @var string
     */
    private $idsToIgnore;

    public function __construct(string $idsToIgnore)
    {
        $this->idsToIgnore = $idsToIgnore;
    }

    /**
     * Indicates if the comparision should be done using a regexp.
     */
    const REGEXP_COMPARISION = 'regexp';

    public function find($documentElement, $values)
    {
        $values = (!is_array($values) || array_key_exists(self::REGEXP_COMPARISION, $values))
            ? [$values]
            : $values;

        $nodes = [];
        foreach ($values as $value) {
            $nodes[] = $this->findNode($documentElement, $value);
        }

        return $this->getXPath($nodes);
    }

    public function findNode($documentElement, $value)
    {
        list($value, $isFoundCallback) = $this->getComparisionCallbackWithBasicValue($value);

        $node = $this->getNodeWithValue([$documentElement], $isFoundCallback);
        if (empty($node)) {
            throw new \UnexpectedValueException("'$value' not found");
        }

        return $node;
    }

    /**
     * Get the comparision callback with the value used internally.
     *
     * The callback will be different depending on the value. If the value
     * contains a regexp the callback will evaluate it, if not, the value is a simple value
     * and it is checked as a normal equal.
     *
     * @param mixed $value
     *
     * @return array
     */
    private function getComparisionCallbackWithBasicValue($value): array
    {
        if (is_array($value) && array_key_exists(self::REGEXP_COMPARISION, $value)) {
            $value           = $value[self::REGEXP_COMPARISION];
            $isFoundCallback = $this->regexpComparision($value);

            return [$value, $isFoundCallback];
        }

        $isFoundCallback = $this->normalComparision($value);

        return [$value, $isFoundCallback];
    }

    private function regexpComparision($regexp)
    {
        return function ($string) use ($regexp): bool {
            return !!preg_match($regexp, $string);
        };
    }

    private function normalComparision($value)
    {
        return function ($string) use ($value): bool {
            return $string === $value;
        };
    }

    private function getNodeWithValue($nodes, $isFoundCallback)
    {
        foreach ($nodes as $item) {
            if ($isFoundCallback($item->textContent)) {
                return $item;
            }

            foreach ($item->attributes ?? [] as $attribute) {
                if ($isFoundCallback($attribute->value)) {
                    return $attribute;
                }
            }

            if ($item->hasChildNodes()) {
                $result = $this->getNodeWithValue($item->childNodes, $isFoundCallback);
                if (!empty($result)) {
                    return $result;
                }
            }
        }
    }

    private function getXPath(array $nodes)
    {
        $elements = [];
        foreach ($nodes as $node) {
            $elements[] = $this->optimizeElements($node, $this->getPathElements($node));
        }

        $finalElements = (count($elements) > 1) ? $this->getCommonElements($elements) : $elements[0];

        return implode('/', array_reverse($finalElements));
    }

    private function optimizeElements($node, $elements, $childNode = null, $index = 0)
    {
        if ('meta' === $node->nodeName) {
            foreach ($node->attributes as $attribute) {
                if ('name' === $attribute->name) {
                    return ["//meta[@name=\"$attribute->value\"]/@{$childNode->nodeName}"];
                }
            }
        }

        foreach ($node->attributes ?? [] as $attribute) {
            if ($attribute->name === 'id' && !preg_match($this->idsToIgnore, $attribute->value)) {
                $elements[$index] = "//*[@id=\"{$attribute->value}\"]";

                return array_slice($elements, 0, $index + 1);
            }
        }

        if (empty($node->parentNode)) {
            return $elements;
        }

        return $this->optimizeElements($node->parentNode, $elements, $node, $index + 1);
    }

    private function getPathElements($node)
    {
        $nodePath = $node->getNodePath();
        $parts    = explode('/', $nodePath);

        return array_reverse($parts);
    }

    private function getCommonElements($elements): array
    {
        $fixedElements = array_intersect_assoc(...$elements);
        $finalElements = [];
        $totalElements = count($elements[0]);

        for ($i = 0; $i < $totalElements; ++$i) {
            $finalElements[$i] = (!isset($fixedElements[$i])) ? '*' : $fixedElements[$i];
        }

        return $finalElements;
    }
}

<?php
/**
 * This file is part of the FastFeed package.
 *
 * (c) Daniel González <daniel@desarrolla2.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace FastFeed\Processor;

use DOMDocument;
use FastFeed\Item;

/**
 * ImageProcessor
 */
class ImageProcessor implements ProcessorInterface
{
    /**
     * @var array
     */
    protected $ignoredPatterns = array();

    /**
     * @var bool
     */
    protected $overrideImage = false;

    /**
     * @param array $ignoredPatterns
     */
    public function setIgnoredPatterns(array $ignoredPatterns)
    {
        $this->ignoredPatterns = array();
        foreach ($ignoredPatterns as $ignoredPattern) {
            $this->addIgnoredPattern($ignoredPattern);
        }
    }

    /**
     * @param $ignoredPattern
     */
    public function addIgnoredPattern($ignoredPattern)
    {
        $this->ignoredPatterns[] = (string) $ignoredPattern;
    }

    /**
     * @return array
     */
    public function getIgnoredPatterns()
    {
        return $this->ignoredPatterns;
    }

    /**
     * @param boolean $overrideImage
     */
    public function setOverrideImage($overrideImage)
    {
        $this->overrideImage = (bool) $overrideImage;
    }

    /**
     * Execute processor
     *
     * @param array $items
     *
     * @return array
     */
    public function process(array $items)
    {
        foreach ($items as $key => $item) {
            $items[$key] = $this->setImage($item);
        }

        return $items;
    }

    /**
     * @param Item $item
     *
     * @return Item
     */
    protected function setImage(Item $item)
    {
        if ($item->hasImage() && !$this->overrideImage) {
            return $item;
        }

        $this->setImageFromContent($item);

        return $item;
    }

    /**
     * @param Item $item
     *
     * @return bool|string
     */
    protected function setImageFromContent(Item $item)
    {
        $images = $this->getImages($item->getContent());

        foreach ($images as $image) {
            if ($this->isOnIgnoredPatterns($image)) {
                continue;
            }
            $item->setImage($image);

            return;
        }
    }

    /**
     * @param $imageSrc
     *
     * @return bool
     */
    protected function isOnIgnoredPatterns($imageSrc)
    {
        foreach ($this->ignoredPatterns as $ignoredPattern) {
            if (preg_match($ignoredPattern, $imageSrc)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $content
     *
     * @return bool|DOMDocument
     */
    protected function createDOM($content)
    {
        if (!$content) {
            return false;
        }
        $dom = new DOMDocument();
        $dom->loadHTML($content);
        $dom->preserveWhiteSpace = false;

        return $dom;
    }

    /**
     * @param $content
     *
     * @return bool|array
     */
    protected function getImages($content)
    {
        $images = array();

        $dom = $this->createDOM($content);
        if (!$dom) {
            return $images;
        }

        $domList = $dom->getElementsByTagName('img');

        foreach ($domList as $image) {
            $imageSrc = $image->getAttribute('src');

            if (!$imageSrc) {
                continue;
            }
            $images[] = $imageSrc;
        }

        return $images;
    }
}
